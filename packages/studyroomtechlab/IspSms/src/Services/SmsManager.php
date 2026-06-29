<?php

namespace StudyRoomTechLab\IspSms\Services;

use Illuminate\Support\Facades\Http;
use StudyRoomTechLab\IspSms\Models\IspSmsMessage;
use StudyRoomTechLab\IspSms\Models\IspSmsSetting;

class SmsManager
{
    public function send(IspSmsMessage $message): IspSmsMessage
    {
        if (! in_array($message->status, ['queued', 'pending', 'failed'], true)) {
            return $message;
        }

        $setting = $this->resolveSetting((int) $message->isp_id, (string) $message->sending_mode);
        $provider = $setting?->provider ?: $message->provider ?: config('isp-sms.default_provider', 'platform');

        $message->forceFill([
            'sending_mode' => $setting?->mode ?: $message->sending_mode ?: 'platform',
            'provider' => $provider,
        ])->save();

        if (! $setting || ! $setting->is_active) {
            return $this->markFailed($message, 'No active SMS gateway setting was found.');
        }

        if ($setting->mode === 'platform' && ! $this->consumeSystemSmsUnit((int) $message->isp_id)) {
            return $this->markFailed($message, 'System SMS balance is empty. Use the 5 starter SMS first, then top up your SMS account from SMS settings.');
        }

        if (config('isp-sms.dry_run', true)) {
            return $this->markSent($message, 'DRY' . now()->format('YmdHis') . random_int(100, 999), [
                'dry_run' => true,
                'provider' => $provider,
                'message' => 'SMS dry-run mode. No provider request was sent.',
            ]);
        }

        if ($provider === 'custom_http') {
            return $this->sendCustomHttp($message, $setting);
        }

        return $this->markSent($message, strtoupper((string) $provider) . now()->format('YmdHis') . random_int(100, 999), [
            'provider' => $provider,
            'message' => 'SMS recorded successfully for provider [' . $provider . '].',
        ]);
    }

    public function sendQueued(int $limit = 25): int
    {
        $sent = 0;

        IspSmsMessage::query()
            ->whereIn('status', ['queued', 'pending'])
            ->oldest()
            ->limit($limit)
            ->get()
            ->each(function (IspSmsMessage $message) use (&$sent) {
                $this->send($message);
                $sent++;
            });

        return $sent;
    }

    private function resolveSetting(int $ispId, string $mode): ?IspSmsSetting
    {
        $ispSetting = IspSmsSetting::query()
            ->where('scope', 'isp')
            ->where('isp_id', $ispId)
            ->where('is_active', true)
            ->first();

        if ($ispSetting && $ispSetting->mode === 'own') {
            return $ispSetting;
        }

        $platform = IspSmsSetting::query()
            ->where('scope', 'platform')
            ->whereNull('isp_id')
            ->where('is_active', true)
            ->first();

        if ($platform) {
            $platform->mode = $mode === 'own' ? 'own' : 'platform';
            return $platform;
        }

        return $ispSetting;
    }

    private function consumeSystemSmsUnit(int $ispId): bool
    {
        $setting = IspSmsSetting::firstOrCreate(
            ['scope' => 'isp', 'isp_id' => $ispId],
            [
                'mode' => 'platform',
                'provider' => 'platform',
                'is_active' => true,
                'allow_system_sms' => true,
                'allow_own_sms' => true,
                'free_sms_remaining' => 5,
                'sms_balance' => 0,
                'estimated_cost_per_sms' => 1,
            ]
        );

        if (! $setting->allow_system_sms) {
            return false;
        }

        if ((int) $setting->free_sms_remaining > 0) {
            $setting->decrement('free_sms_remaining');
            $this->maybeCreateLowBalanceAlert($setting->refresh(), $ispId);
            return true;
        }

        $cost = (float) ($setting->estimated_cost_per_sms ?? 1);
        $balance = (float) ($setting->sms_balance ?? 0);

        if ($balance < $cost || $cost <= 0) {
            return false;
        }

        $setting->sms_balance = max(0, round($balance - $cost, 2));
        $setting->save();
        $this->maybeCreateLowBalanceAlert($setting->refresh(), $ispId);

        return true;
    }


    private function maybeCreateLowBalanceAlert(IspSmsSetting $setting, int $ispId): void
    {
        if (! ($setting->low_balance_alert_enabled ?? true)) {
            return;
        }

        $phone = $this->normalizePhone((string) ($setting->low_balance_alert_phone ?: ''));
        if (! $phone) {
            return;
        }

        $threshold = (float) ($setting->low_balance_alert_threshold ?? 10);
        $balance = (float) ($setting->sms_balance ?? 0);

        if ($balance > $threshold && (int) ($setting->free_sms_remaining ?? 0) > 0) {
            return;
        }

        if ($setting->low_balance_alerted_at && $setting->low_balance_alerted_at->gt(now()->subHours(24))) {
            return;
        }

        IspSmsMessage::create([
            'isp_id' => $ispId,
            'customer_id' => null,
            'recipient_user_id' => null,
            'phone' => $phone,
            'message' => 'System alert: your SMS balance is low. Please top up your SMS account to avoid failed customer messages.',
            'channel' => 'sms',
            'direction' => 'outbound',
            'sending_mode' => 'platform',
            'provider' => 'system_alert',
            'status' => 'queued',
            'result_message' => 'Low balance alert generated. Send through your queue or SMS gateway worker.',
        ]);

        $setting->forceFill(['low_balance_alerted_at' => now()])->save();
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (! $digits) {
            return null;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '254' . substr($digits, 1);
        }

        if (strlen($digits) === 9 && preg_match('/^[17]\d{8}$/', $digits)) {
            return '254' . $digits;
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            return $digits;
        }

        return null;
    }

    private function sendCustomHttp(IspSmsMessage $message, IspSmsSetting $setting): IspSmsMessage
    {
        if (! $setting->callback_url) {
            return $this->markFailed($message, 'Custom HTTP SMS gateway URL is missing.');
        }

        try {
            $response = Http::timeout((int) config('isp-sms.custom_http.timeout', 20))
                ->acceptJson()
                ->post($setting->callback_url, [
                    'to' => $message->phone,
                    'message' => $message->message,
                    'sender_id' => $setting->sender_id,
                    'username' => $setting->username,
                    'api_key' => $setting->api_key,
                    'api_secret' => $setting->api_secret,
                    'reference' => 'SMS-' . $message->id,
                ]);

            $payload = $response->json() ?: ['body' => $response->body()];

            if (! $response->successful()) {
                return $this->markFailed($message, 'Gateway HTTP ' . $response->status(), $payload);
            }

            $providerId = data_get($payload, 'message_id')
                ?: data_get($payload, 'id')
                ?: data_get($payload, 'reference')
                ?: 'HTTP' . now()->format('YmdHis') . $message->id;

            return $this->markSent($message, (string) $providerId, $payload);
        } catch (\Throwable $e) {
            return $this->markFailed($message, $e->getMessage());
        }
    }

    private function markSent(IspSmsMessage $message, string $providerMessageId, array $response = []): IspSmsMessage
    {
        $message->forceFill([
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
            'provider_response' => $response,
            'result_message' => $response['message'] ?? 'SMS sent successfully.',
            'sent_at' => now(),
            'failed_at' => null,
        ])->save();

        return $message->refresh();
    }

    private function markFailed(IspSmsMessage $message, string $reason, array $response = []): IspSmsMessage
    {
        $message->forceFill([
            'status' => 'failed',
            'provider_response' => $response ?: null,
            'result_message' => $reason,
            'failed_at' => now(),
        ])->save();

        return $message->refresh();
    }
}
