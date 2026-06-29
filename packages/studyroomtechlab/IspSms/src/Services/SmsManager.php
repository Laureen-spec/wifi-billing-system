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

        if (config('isp-sms.dry_run', true)) {
            return $this->markSent($message, 'DRY' . now()->format('YmdHis') . random_int(100, 999), [
                'dry_run' => true,
                'provider' => $provider,
                'message' => 'SMS dry-run mode. No provider request was sent.',
            ]);
        }

        if (! $setting || ! $setting->is_active) {
            return $this->markFailed($message, 'No active SMS gateway setting was found.');
        }

        if ($provider === 'custom_http') {
            return $this->sendCustomHttp($message, $setting);
        }

        return $this->markFailed($message, 'SMS provider [' . $provider . '] is not configured for live sending yet.');
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
        if ($mode === 'own') {
            $own = IspSmsSetting::query()
                ->where('scope', 'isp')
                ->where('isp_id', $ispId)
                ->where('mode', 'own')
                ->where('is_active', true)
                ->first();

            if ($own) {
                return $own;
            }
        }

        $platform = IspSmsSetting::query()
            ->where('scope', 'platform')
            ->whereNull('isp_id')
            ->where('is_active', true)
            ->first();

        if ($platform) {
            return $platform;
        }

        return IspSmsSetting::query()
            ->where('scope', 'isp')
            ->where('isp_id', $ispId)
            ->where('is_active', true)
            ->first();
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
