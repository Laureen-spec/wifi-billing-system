<?php

namespace StudyRoomTechLab\IspWhatsapp\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappConversation;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappMessage;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappSetting;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappTemplate;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappUsageLog;

class WhatsappManager
{
    public const TEMPLATE_VARIABLES = [
        'customer_name',
        'phone',
        'plan_name',
        'amount',
        'currency',
        'expiry_time',
        'receipt_code',
        'ticket_number',
        'payment_link',
        'support_phone',
    ];

    public function receiveInbound(int $ispId, string $phone, string $body, array $payload = []): IspWhatsappConversation
    {
        $normalizedPhone = $this->normalizePhone($phone) ?: $phone;
        $customer = $this->findCustomerByPhone($ispId, $normalizedPhone);
        $setting = $this->workspaceSetting($ispId);
        $expiresAt = now()->addMinutes(max(1, (int) ($setting->reply_window_minutes ?: 120)));

        $conversation = IspWhatsappConversation::query()
            ->where('isp_id', $ispId)
            ->where('phone', $normalizedPhone)
            ->latest('id')
            ->first();

        if (! $conversation) {
            $conversation = new IspWhatsappConversation([
                'isp_id' => $ispId,
                'phone' => $normalizedPhone,
                'source' => 'whatsapp',
                'status' => 'open',
            ]);
        }

        $conversation->forceFill([
            'customer_id' => $customer?->id,
            'customer_name' => $customer?->name ?: $conversation->customer_name,
            'last_customer_message_at' => now(),
            'reply_window_expires_at' => $expiresAt,
            'last_message_preview' => str($body)->limit(160)->toString(),
            'last_message_at' => now(),
            'status' => 'open',
        ])->save();

        IspWhatsappMessage::create([
            'isp_id' => $ispId,
            'conversation_id' => $conversation->id,
            'customer_id' => $customer?->id,
            'phone' => $normalizedPhone,
            'direction' => 'inbound',
            'message_type' => 'text',
            'provider_mode' => 'customer',
            'provider' => 'whatsapp',
            'body' => $body,
            'payload' => $payload,
            'cost' => 0,
            'status' => 'received',
            'sent_at' => now(),
        ]);

        return $conversation->refresh();
    }

    public function systemHandover(IspWhatsappConversation $conversation, string $note, ?int $userId = null): IspWhatsappMessage
    {
        $setting = $this->workspaceSetting((int) $conversation->isp_id);
        $conversation->forceFill([
            'handover_at' => now(),
            'reply_window_expires_at' => now()->addMinutes(max(1, (int) ($setting->reply_window_minutes ?: 120))),
            'last_message_preview' => str($note)->limit(160)->toString(),
            'last_message_at' => now(),
            'status' => 'open',
        ])->save();

        return $this->internalNote($conversation->refresh(), $note, $userId, 'notification');
    }

    public function internalNote(IspWhatsappConversation $conversation, string $body, ?int $userId = null, string $type = 'internal_note'): IspWhatsappMessage
    {
        $message = IspWhatsappMessage::create([
            'isp_id' => $conversation->isp_id,
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'phone' => $conversation->phone,
            'direction' => 'internal',
            'message_type' => $type,
            'provider_mode' => 'internal',
            'provider' => 'desk',
            'body' => $body,
            'cost' => 0,
            'status' => 'internal',
            'sent_by' => $userId,
            'sent_at' => now(),
        ]);

        $conversation->forceFill([
            'last_message_preview' => str($body)->limit(160)->toString(),
            'last_message_at' => now(),
        ])->save();

        return $message;
    }

    public function sendText(IspWhatsappConversation $conversation, string $body, ?int $userId = null): IspWhatsappMessage
    {
        return $this->sendOutbound($conversation, $body, [
            'message_type' => 'text',
            'sent_by' => $userId,
        ]);
    }

    public function sendTemplate(IspWhatsappConversation $conversation, IspWhatsappTemplate $template, array $variables = [], ?int $userId = null): IspWhatsappMessage
    {
        $body = $this->renderTemplate($template, $conversation, $variables);

        return $this->sendOutbound($conversation, $body, [
            'message_type' => 'template',
            'template_id' => $template->id,
            'sent_by' => $userId,
            'payload' => [
                'variables' => $variables,
                'template_key' => $template->key,
                'provider_template_name' => $template->provider_template_name,
                'language' => $template->language,
            ],
        ]);
    }

    public function sendSystemMessage(IspWhatsappConversation $conversation, string $body, string $type = 'system', array $payload = []): IspWhatsappMessage
    {
        return $this->sendOutbound($conversation, $body, [
            'message_type' => $type,
            'payload' => $payload,
            'bypass_reply_window' => true,
        ]);
    }

    public function sendOutbound(IspWhatsappConversation $conversation, string $body, array $options = []): IspWhatsappMessage
    {
        $conversation->loadMissing(['customer', 'isp']);

        $ispId = (int) $conversation->isp_id;
        $setting = $this->workspaceSetting($ispId);
        $providerMode = (string) ($setting->provider_mode ?: 'platform');
        $platformProvider = $this->platformSetting()?->provider ?: 'platform';
        $provider = $providerMode === 'own_api' ? (string) ($setting->provider ?: 'custom_http') : (string) $platformProvider;
        $messageType = (string) ($options['message_type'] ?? 'text');
        $templateId = $options['template_id'] ?? null;
        $cost = $providerMode === 'platform' ? $setting->messageCost() : 0.0;

        $base = [
            'isp_id' => $conversation->isp_id,
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'phone' => $conversation->phone,
            'direction' => 'outbound',
            'message_type' => $messageType,
            'template_id' => $templateId,
            'provider_mode' => $providerMode,
            'provider' => $provider,
            'body' => $body,
            'payload' => $options['payload'] ?? null,
            'cost' => $cost,
            'sent_by' => $options['sent_by'] ?? null,
        ];

        if ($conversation->blocked || $conversation->opted_out) {
            return $this->createFailedMessage($setting, $base, 'This phone is blocked or opted out.');
        }

        if (! ($options['bypass_reply_window'] ?? false) && $messageType === 'text' && $conversation->requiresTemplate()) {
            return $this->createFailedMessage($setting, $base, 'Reply window expired. Use an approved WhatsApp template.');
        }

        if ($templateId) {
            $template = IspWhatsappTemplate::find($templateId);
            if (! $template || ! $template->isSendable()) {
                return $this->createFailedMessage($setting, $base, 'Selected WhatsApp template is not approved or enabled.');
            }
        }

        $preflightError = $this->preflightError($setting, $providerMode, $provider, $cost);
        if ($preflightError) {
            return $this->createFailedMessage($setting, $base, $preflightError);
        }

        $message = IspWhatsappMessage::create(array_merge($base, [
            'status' => 'queued',
        ]));

        if ((bool) config('isp-whatsapp.dry_run', true)) {
            return $this->markSent($message, $setting, 'WA-DRY-' . now()->format('YmdHis') . '-' . $message->id, [
                'dry_run' => true,
                'provider' => $provider,
                'message' => 'WhatsApp dry-run mode. No provider request was sent.',
            ]);
        }

        if ($provider === 'custom_http' || $provider === 'other') {
            return $this->sendCustomHttp($message, $setting);
        }

        return $this->markSent($message, $setting, strtoupper($provider) . '-' . now()->format('YmdHis') . '-' . $message->id, [
            'provider' => $provider,
            'message' => 'WhatsApp message recorded for provider dispatch.',
        ]);
    }

    public function renderTemplate(IspWhatsappTemplate $template, IspWhatsappConversation $conversation, array $variables = []): string
    {
        $customer = $conversation->customer;
        $package = $customer?->internetPackage;
        $defaults = [
            'customer_name' => $conversation->customer_name ?: $customer?->name ?: 'Customer',
            'phone' => $conversation->phone,
            'plan_name' => $package?->name ?: 'internet package',
            'amount' => $variables['amount'] ?? $customer?->monthly_amount ?? '',
            'currency' => $variables['currency'] ?? config('isp-whatsapp.default_currency', 'KES'),
            'expiry_time' => $customer?->next_due_date ? $customer->next_due_date->format('Y-m-d') : '',
            'receipt_code' => '',
            'ticket_number' => '',
            'payment_link' => '',
            'support_phone' => $conversation->isp?->phone ?: config('app.name'),
        ];

        $replacements = array_merge($defaults, array_intersect_key($variables, array_flip(self::TEMPLATE_VARIABLES)));

        return (string) preg_replace_callback('/{{\s*([a-zA-Z0-9_]+)\s*}}/', function (array $matches) use ($replacements) {
            return array_key_exists($matches[1], $replacements) ? (string) $replacements[$matches[1]] : $matches[0];
        }, $template->body);
    }

    public function testSetting(IspWhatsappSetting $setting): IspWhatsappSetting
    {
        $message = null;
        $status = 'ok';

        if ($setting->provider_mode === 'platform') {
            $message = 'Platform WhatsApp API selected. Workspace admins do not configure provider keys.';
        } elseif (! $setting->provider || $setting->provider === 'platform') {
            $status = 'failed';
            $message = 'Select a supported own API provider.';
        } elseif (in_array($setting->provider, ['custom_http', 'other'], true) && ! $setting->api_base_url) {
            $status = 'failed';
            $message = 'Custom HTTP API URL is required.';
        } elseif (! $this->hasAnyCredential($setting)) {
            $status = 'failed';
            $message = 'Save provider credentials before testing the connection.';
        } else {
            $message = 'Credentials are stored and the provider configuration is ready for dispatch.';
        }

        $setting->forceFill([
            'last_tested_at' => now(),
            'last_test_status' => $status,
            'last_test_message' => $message,
        ])->save();

        return $setting->refresh();
    }

    public function workspaceSetting(int $ispId): IspWhatsappSetting
    {
        return IspWhatsappSetting::firstOrCreate(
            ['scope' => 'isp', 'isp_id' => $ispId],
            [
                'provider_mode' => 'platform',
                'provider' => 'platform',
                'is_active' => true,
                'allow_platform_api' => true,
                'allow_own_api' => true,
                'reply_window_minutes' => 120,
                'whatsapp_balance' => 0,
                'estimated_cost_per_message' => config('isp-whatsapp.default_message_cost', 1),
                'billing_enabled' => true,
                'billing_status' => 'active',
            ]
        );
    }

    public function platformSetting(): ?IspWhatsappSetting
    {
        return IspWhatsappSetting::query()
            ->where('scope', 'platform')
            ->whereNull('isp_id')
            ->where('is_active', true)
            ->first();
    }

    public function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '254') && strlen($digits) === 12) {
            return $digits;
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

    public function assertTemplateSendAllowed(IspWhatsappConversation $conversation, ?int $templateId): IspWhatsappTemplate
    {
        if (! $templateId) {
            throw ValidationException::withMessages([
                'template_id' => 'Select an approved template.',
            ]);
        }

        $template = IspWhatsappTemplate::query()
            ->where('id', $templateId)
            ->where(function ($query) use ($conversation) {
                $query->whereNull('isp_id')->orWhere('isp_id', $conversation->isp_id);
            })
            ->first();

        if (! $template || ! $template->isSendable()) {
            throw ValidationException::withMessages([
                'template_id' => 'Select an approved and enabled WhatsApp template.',
            ]);
        }

        return $template;
    }

    private function preflightError(IspWhatsappSetting $setting, string $providerMode, string $provider, float $cost): ?string
    {
        if (! $setting->is_active) {
            return 'WhatsApp sending is disabled in API settings.';
        }

        if ($providerMode === 'platform') {
            if (! $setting->allow_platform_api) {
                return 'Platform WhatsApp API is disabled for this workspace.';
            }

            if (($setting->billing_enabled ?? true) && $setting->billing_status !== 'active') {
                return 'WhatsApp billing is not active for this workspace.';
            }

            if ((float) $setting->whatsapp_balance < $cost || $cost <= 0) {
                return 'WhatsApp balance is low or exhausted. Top up before sending.';
            }

            return null;
        }

        if (! $setting->allow_own_api) {
            return 'Own WhatsApp API is disabled for this workspace.';
        }

        if (! in_array($provider, ['meta_cloud', 'africastalking', 'twilio', 'custom_http', 'other'], true)) {
            return 'Select a supported WhatsApp provider.';
        }

        if (in_array($provider, ['custom_http', 'other'], true) && ! $setting->api_base_url) {
            return 'Custom HTTP WhatsApp API URL is missing.';
        }

        if (! $this->hasAnyCredential($setting) && ! in_array($provider, ['custom_http', 'other'], true)) {
            return 'Own WhatsApp API credentials are missing.';
        }

        return null;
    }

    private function sendCustomHttp(IspWhatsappMessage $message, IspWhatsappSetting $setting): IspWhatsappMessage
    {
        try {
            $response = Http::timeout((int) config('isp-whatsapp.custom_http.timeout', 20))
                ->acceptJson()
                ->post($setting->api_base_url, [
                    'to' => $message->phone,
                    'message' => $message->body,
                    'message_type' => $message->message_type,
                    'template_id' => $message->template_id,
                    'reference' => 'WA-' . $message->id,
                    'credentials' => $setting->credentials ?: [],
                ]);

            $payload = $response->json() ?: ['body' => $response->body()];

            if (! $response->successful()) {
                return $this->markFailed($message, $setting, 'Gateway HTTP ' . $response->status(), $payload);
            }

            $providerId = data_get($payload, 'message_id')
                ?: data_get($payload, 'id')
                ?: data_get($payload, 'reference')
                ?: 'WA-HTTP-' . now()->format('YmdHis') . '-' . $message->id;

            return $this->markSent($message, $setting, (string) $providerId, $payload);
        } catch (\Throwable $e) {
            return $this->markFailed($message, $setting, $e->getMessage());
        }
    }

    private function markSent(IspWhatsappMessage $message, IspWhatsappSetting $setting, string $providerMessageId, array $response = []): IspWhatsappMessage
    {
        $message->forceFill([
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
            'payload' => array_merge($message->payload ?: [], ['provider_response' => $response]),
            'sent_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ])->save();

        if ($message->provider_mode === 'platform') {
            $setting->forceFill([
                'whatsapp_balance' => max(0, round((float) $setting->whatsapp_balance - (float) $message->cost, 2)),
                'messages_sent' => (int) $setting->messages_sent + 1,
                'last_billed_at' => now(),
            ])->save();
        } else {
            $setting->increment('messages_sent');
        }

        $this->writeUsageLog($message->refresh());
        $this->syncConversationPreview($message);

        return $message->refresh();
    }

    private function markFailed(IspWhatsappMessage $message, IspWhatsappSetting $setting, string $reason, array $response = []): IspWhatsappMessage
    {
        $message->forceFill([
            'status' => 'failed',
            'payload' => $response ? array_merge($message->payload ?: [], ['provider_response' => $response]) : $message->payload,
            'error_message' => $reason,
            'failed_at' => now(),
        ])->save();

        $setting->increment('messages_failed');
        $this->writeUsageLog($message->refresh());
        $this->syncConversationPreview($message);

        return $message->refresh();
    }

    private function createFailedMessage(IspWhatsappSetting $setting, array $base, string $reason): IspWhatsappMessage
    {
        $message = IspWhatsappMessage::create(array_merge($base, [
            'status' => 'failed',
            'error_message' => $reason,
            'failed_at' => now(),
        ]));

        $setting->increment('messages_failed');
        $this->writeUsageLog($message);
        $this->syncConversationPreview($message);

        return $message->refresh();
    }

    private function writeUsageLog(IspWhatsappMessage $message): void
    {
        if ($message->direction !== 'outbound') {
            return;
        }

        IspWhatsappUsageLog::create([
            'isp_id' => $message->isp_id,
            'message_id' => $message->id,
            'phone' => $message->phone,
            'message_type' => $message->message_type,
            'template_id' => $message->template_id,
            'provider_mode' => $message->provider_mode,
            'provider' => $message->provider,
            'cost' => $message->cost,
            'status' => $message->status,
            'error_message' => $message->error_message,
            'sent_at' => $message->sent_at,
        ]);
    }

    private function syncConversationPreview(IspWhatsappMessage $message): void
    {
        if (! $message->conversation_id) {
            return;
        }

        IspWhatsappConversation::whereKey($message->conversation_id)->update([
            'last_message_preview' => str((string) $message->body)->limit(160)->toString(),
            'last_message_at' => now(),
        ]);
    }

    private function findCustomerByPhone(int $ispId, string $phone): ?Customer
    {
        $normalized = $this->normalizePhone($phone);

        return Customer::query()
            ->with(['internetPackage', 'isp'])
            ->where('isp_id', $ispId)
            ->whereNotNull('phone')
            ->get()
            ->first(function (Customer $customer) use ($normalized, $phone) {
                return $this->normalizePhone($customer->phone) === $normalized || $customer->phone === $phone;
            });
    }

    private function hasAnyCredential(IspWhatsappSetting $setting): bool
    {
        $credentials = $setting->credentials ?: [];

        return collect($credentials)->filter(fn ($value) => filled($value))->isNotEmpty();
    }
}
