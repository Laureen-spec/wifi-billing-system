<?php

namespace StudyRoomTechLab\IspWhatsapp\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Isp;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappBroadcast;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappConversation;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappMessage;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappPaymentRequest;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappReceipt;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappSetting;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappSupportTicket;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappTemplate;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappTopup;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappUsageLog;
use StudyRoomTechLab\IspWhatsapp\Services\WhatsappBotService;
use StudyRoomTechLab\IspWhatsapp\Services\WhatsappManager;

class IspWhatsappController extends Controller
{
    public function __construct(
        private readonly WhatsappManager $whatsapp,
        private readonly WhatsappBotService $bot
    ) {
    }

    public function overview(Request $request)
    {
        return $this->renderDesk($request, 'overview');
    }

    public function inbox(Request $request)
    {
        return $this->renderDesk($request, 'inbox');
    }

    public function botFlows(Request $request)
    {
        return $this->renderDesk($request, 'bot-flows');
    }

    public function paymentRequests(Request $request)
    {
        return $this->renderDesk($request, 'payment-requests');
    }

    public function receipts(Request $request)
    {
        return $this->renderDesk($request, 'receipts');
    }

    public function supportTickets(Request $request)
    {
        return $this->renderDesk($request, 'support-tickets');
    }

    public function broadcasts(Request $request)
    {
        return $this->renderDesk($request, 'broadcasts');
    }

    public function templates(Request $request)
    {
        return $this->renderDesk($request, 'templates');
    }

    public function usage(Request $request)
    {
        return $this->renderDesk($request, 'usage');
    }

    public function apiSettings(Request $request)
    {
        return $this->renderDesk($request, 'api-settings');
    }

    public function logs(Request $request)
    {
        return $this->renderDesk($request, 'logs');
    }

    public function settings(Request $request)
    {
        return $this->renderDesk($request, 'settings');
    }

    public function sendReply(Request $request, IspWhatsappConversation $conversation)
    {
        $this->authorizeManage($request);
        $this->authorizeConversation($request, $conversation);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $message = $this->whatsapp->sendText($conversation, $data['body'], $request->user()->id);

        return back()->with(
            $message->status === 'sent' ? 'success' : 'error',
            $message->status === 'sent' ? 'WhatsApp reply sent.' : ($message->error_message ?: 'WhatsApp reply was blocked.')
        );
    }

    public function sendTemplate(Request $request, IspWhatsappConversation $conversation)
    {
        $this->authorizeManage($request);
        $this->authorizeConversation($request, $conversation);

        $data = $request->validate([
            'template_id' => ['required', 'integer'],
            'variables' => ['nullable', 'array'],
        ]);

        $template = $this->whatsapp->assertTemplateSendAllowed($conversation, (int) $data['template_id']);
        $message = $this->whatsapp->sendTemplate($conversation, $template, $data['variables'] ?? [], $request->user()->id);

        return back()->with(
            $message->status === 'sent' ? 'success' : 'error',
            $message->status === 'sent' ? 'Template message sent.' : ($message->error_message ?: 'Template message was blocked.')
        );
    }

    public function internalNote(Request $request, IspWhatsappConversation $conversation)
    {
        $this->authorizeManage($request);
        $this->authorizeConversation($request, $conversation);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $this->whatsapp->internalNote($conversation, $data['body'], $request->user()->id);

        return back()->with('success', 'Internal note added.');
    }

    public function handover(Request $request, IspWhatsappConversation $conversation)
    {
        $this->authorizeManage($request);
        $this->authorizeConversation($request, $conversation);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->whatsapp->systemHandover($conversation, $data['note'] ?: 'Conversation handed over to admin support.', $request->user()->id);

        return back()->with('success', 'Reply window refreshed from system handover.');
    }

    public function updateConversation(Request $request, IspWhatsappConversation $conversation)
    {
        $this->authorizeManage($request);
        $this->authorizeConversation($request, $conversation);

        $data = $request->validate([
            'blocked' => ['nullable', 'boolean'],
            'opted_out' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['open', 'pending', 'resolved', 'closed'])],
        ]);

        $conversation->forceFill([
            'blocked' => $request->boolean('blocked', (bool) $conversation->blocked),
            'opted_out' => $request->boolean('opted_out', (bool) $conversation->opted_out),
            'status' => $data['status'] ?? $conversation->status,
        ])->save();

        return back()->with('success', 'Conversation safety settings updated.');
    }

    public function saveSettings(Request $request)
    {
        $this->authorizeManage($request);
        $this->assertTablesReady();

        $isPlatform = $this->isPlatform($request);

        $data = $request->validate([
            'scope' => ['nullable', Rule::in(['isp', 'platform'])],
            'provider_mode' => ['required', Rule::in(['platform', 'own_api'])],
            'provider' => ['nullable', Rule::in(['platform', 'meta_cloud', 'africastalking', 'twilio', 'custom_http', 'other'])],
            'business_phone' => ['nullable', 'string', 'max:100'],
            'phone_number_id' => ['nullable', 'string', 'max:255'],
            'waba_id' => ['nullable', 'string', 'max:255'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'api_base_url' => ['nullable', 'url', 'max:500'],
            'webhook_verify_token' => ['nullable', 'string', 'max:255'],
            'reply_window_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'whatsapp_balance' => ['nullable', 'numeric', 'min:0'],
            'estimated_cost_per_message' => ['nullable', 'numeric', 'min:0'],
            'low_balance_threshold' => ['nullable', 'numeric', 'min:0'],
            'billing_enabled' => ['nullable', 'boolean'],
            'billing_status' => ['nullable', Rule::in(['active', 'paused', 'past_due', 'disabled'])],
            'topup_payment_status' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'allow_platform_api' => ['nullable', 'boolean'],
            'allow_own_api' => ['nullable', 'boolean'],
            'credentials' => ['nullable', 'array'],
        ]);

        $scope = $isPlatform ? ($data['scope'] ?? 'platform') : 'isp';
        $ispId = $scope === 'platform' ? null : $this->resolveIsp($request)->id;

        if ($scope === 'platform') {
            abort_unless($isPlatform, 403, 'Only platform users can update platform WhatsApp credentials.');
        }

        $setting = IspWhatsappSetting::firstOrNew([
            'scope' => $scope,
            'isp_id' => $ispId,
        ]);

        if (! $setting->exists) {
            $setting->created_by = $request->user()->id;
        }

        $providerMode = $data['provider_mode'];
        $provider = $providerMode === 'platform' ? 'platform' : ($data['provider'] ?: 'meta_cloud');

        $setting->fill([
            'scope' => $scope,
            'isp_id' => $ispId,
            'provider_mode' => $providerMode,
            'provider' => $provider,
            'business_phone' => $data['business_phone'] ?? null,
            'phone_number_id' => $providerMode === 'platform' && $scope === 'isp' ? null : ($data['phone_number_id'] ?? null),
            'waba_id' => $providerMode === 'platform' && $scope === 'isp' ? null : ($data['waba_id'] ?? null),
            'sender_name' => $data['sender_name'] ?? null,
            'api_base_url' => $providerMode === 'platform' && $scope === 'isp' ? null : ($data['api_base_url'] ?? null),
            'webhook_verify_token' => $providerMode === 'platform' && $scope === 'isp' ? null : ($data['webhook_verify_token'] ?? null),
            'is_active' => $request->boolean('is_active', true),
            'allow_platform_api' => $request->boolean('allow_platform_api', true),
            'allow_own_api' => $request->boolean('allow_own_api', true),
            'reply_window_minutes' => $data['reply_window_minutes'],
            'billing_enabled' => $request->boolean('billing_enabled', true),
            'billing_status' => $data['billing_status'] ?? ($setting->billing_status ?: 'active'),
            'topup_payment_status' => $data['topup_payment_status'] ?? $setting->topup_payment_status,
            'updated_by' => $request->user()->id,
        ]);

        if ($isPlatform || $scope === 'platform') {
            $setting->whatsapp_balance = $data['whatsapp_balance'] ?? ($setting->whatsapp_balance ?? 0);
            $setting->estimated_cost_per_message = $data['estimated_cost_per_message'] ?? ($setting->estimated_cost_per_message ?? config('isp-whatsapp.default_message_cost', 1));
            $setting->low_balance_threshold = $data['low_balance_threshold'] ?? ($setting->low_balance_threshold ?? 10);
        } else {
            $setting->estimated_cost_per_message = $setting->estimated_cost_per_message ?? config('isp-whatsapp.default_message_cost', 1);
            $setting->low_balance_threshold = $data['low_balance_threshold'] ?? ($setting->low_balance_threshold ?? 10);
        }

        $incomingCredentials = collect($data['credentials'] ?? [])
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => filled($value))
            ->all();

        if ($incomingCredentials !== [] && ! ($providerMode === 'platform' && $scope === 'isp')) {
            $setting->credentials = array_merge($setting->credentials ?: [], $incomingCredentials);
        }

        $setting->save();

        return back()->with('success', 'WhatsApp API settings saved.');
    }

    public function testConnection(Request $request)
    {
        $this->authorizeManage($request);
        $this->assertTablesReady();

        $scope = $this->isPlatform($request) ? $request->input('scope', 'platform') : 'isp';
        $ispId = $scope === 'platform' ? null : $this->resolveIsp($request)->id;

        $setting = IspWhatsappSetting::where('scope', $scope)->where('isp_id', $ispId)->first();

        if (! $setting) {
            throw ValidationException::withMessages([
                'settings' => 'Save WhatsApp API settings before testing.',
            ]);
        }

        $setting = $this->whatsapp->testSetting($setting);

        return back()->with($setting->last_test_status === 'ok' ? 'success' : 'error', $setting->last_test_message);
    }

    public function saveTemplate(Request $request)
    {
        $this->authorizeManage($request);
        $this->assertTablesReady();

        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'scope' => ['nullable', Rule::in(['isp', 'platform'])],
            'name' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::in(['payment', 'renewal', 'support', 'receipt', 'system', 'broadcast'])],
            'provider_template_name' => ['nullable', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:20'],
            'body' => ['required', 'string', 'max:4000'],
            'variables' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(['approved', 'pending', 'rejected'])],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $isPlatform = $this->isPlatform($request);
        $scope = $isPlatform ? ($data['scope'] ?? 'platform') : 'isp';
        $ispId = $scope === 'platform' ? null : $this->resolveIsp($request)->id;

        if ($scope === 'platform') {
            abort_unless($isPlatform, 403);
        }

        $variables = collect($data['variables'] ?? [])
            ->filter(fn ($variable) => in_array($variable, WhatsappManager::TEMPLATE_VARIABLES, true))
            ->values()
            ->all();

        $template = ! empty($data['id'])
            ? IspWhatsappTemplate::where('id', $data['id'])->where('isp_id', $ispId)->firstOrFail()
            : new IspWhatsappTemplate(['created_by' => $request->user()->id]);

        $template->fill([
            'isp_id' => $ispId,
            'name' => $data['name'],
            'key' => $data['key'] ? Str::slug($data['key'], '_') : Str::slug($data['name'], '_'),
            'category' => $data['category'],
            'provider_template_name' => $data['provider_template_name'] ?? null,
            'language' => $data['language'],
            'body' => $data['body'],
            'variables' => $variables,
            'status' => $data['status'] ?? 'approved',
            'enabled' => $request->boolean('enabled', true),
            'updated_by' => $request->user()->id,
        ])->save();

        return back()->with('success', 'WhatsApp template saved.');
    }

    public function createPaymentRequest(Request $request)
    {
        $this->authorizeManage($request);
        $this->assertTablesReady();

        $data = $request->validate([
            'conversation_id' => ['required', 'integer', 'exists:isp_whatsapp_conversations,id'],
            'internet_package_id' => ['nullable', 'integer', 'exists:internet_packages,id'],
            'method' => ['required', Rule::in(['mpesa', 'instructions'])],
        ]);

        $conversation = IspWhatsappConversation::findOrFail($data['conversation_id']);
        $this->authorizeConversation($request, $conversation);

        $paymentRequest = $this->bot->createPaymentRequest(
            $conversation,
            $data['internet_package_id'] ?? null,
            $data['method'],
            $request->user()->id
        );

        return back()->with(
            $paymentRequest->status === 'failed' ? 'error' : 'success',
            $paymentRequest->status === 'failed' ? ($paymentRequest->notes ?: 'Payment request failed.') : 'WhatsApp payment request created.'
        );
    }

    public function confirmPaymentRequest(Request $request, IspWhatsappPaymentRequest $paymentRequest)
    {
        $this->authorizeManage($request);
        $this->authorizeIspRecord($request, (int) $paymentRequest->isp_id);

        $data = $request->validate([
            'receipt_code' => ['nullable', 'string', 'max:120'],
        ]);

        $this->bot->confirmPayment($paymentRequest, $data['receipt_code'] ?? null);

        return back()->with('success', 'Payment marked paid and receipt queued for WhatsApp.');
    }

    public function saveBroadcast(Request $request)
    {
        $this->authorizeManage($request);
        $this->assertTablesReady();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_id' => ['required', 'integer', 'exists:isp_whatsapp_templates,id'],
            'audience' => ['required', Rule::in(['specific', 'active_customers', 'expired_customers', 'all_customers'])],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer', 'exists:isp_customers,id'],
        ]);

        $isp = $this->isPlatform($request) ? null : $this->resolveIsp($request);
        $recipientCount = $this->broadcastRecipientCount($data, $isp?->id);

        $broadcast = IspWhatsappBroadcast::create([
            'isp_id' => $isp?->id,
            'template_id' => $data['template_id'],
            'name' => $data['name'],
            'audience' => $data['audience'],
            'filters' => [
                'customer_ids' => $data['customer_ids'] ?? [],
            ],
            'recipient_count' => $recipientCount,
            'status' => 'pending_confirmation',
            'requires_confirmation' => true,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Broadcast prepared. It will not send until explicitly confirmed. Broadcast #' . $broadcast->id . ' has ' . $recipientCount . ' recipient(s).');
    }

    public function confirmBroadcast(Request $request, IspWhatsappBroadcast $broadcast)
    {
        $this->authorizeManage($request);
        if ($broadcast->isp_id) {
            $this->authorizeIspRecord($request, (int) $broadcast->isp_id);
        }

        $data = $request->validate([
            'confirm_broadcast' => ['accepted'],
        ]);

        $broadcast->forceFill([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => $request->user()->id,
        ])->save();

        return back()->with('success', 'Broadcast confirmed. It remains unsent until a sender worker or manual dispatch is added.');
    }

    public function webhook(Request $request, int $ispId)
    {
        $setting = IspWhatsappSetting::where('scope', 'isp')->where('isp_id', $ispId)->first();
        $token = (string) ($setting?->webhook_verify_token ?: '');

        if ($request->isMethod('get')) {
            if ($token && $request->query('hub_verify_token') !== $token) {
                abort(403);
            }

            return response($request->query('hub_challenge', 'OK'));
        }

        if ($token && $request->header('X-Whatsapp-Token') !== $token) {
            abort(403);
        }

        $phone = (string) ($request->input('phone') ?: data_get($request->all(), 'entry.0.changes.0.value.messages.0.from'));
        $body = (string) ($request->input('body') ?: data_get($request->all(), 'entry.0.changes.0.value.messages.0.text.body'));

        if (! $phone || ! $body) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $conversation = $this->bot->handleIncoming($ispId, $phone, $body, $request->all());

        return response()->json([
            'ok' => true,
            'conversation_id' => $conversation->id,
        ]);
    }

    private function renderDesk(Request $request, string $activeTab)
    {
        $this->authorizeView($request);

        $tablesReady = Schema::hasTable('isp_whatsapp_messages');
        $isPlatform = $this->isPlatform($request);
        $isp = $isPlatform ? null : $this->resolveIsp($request);
        $selectedConversation = $tablesReady ? $this->selectedConversation($request, $isp?->id) : null;

        return Inertia::render('whatsapp-desk/index', [
            'pageTitle' => 'WhatsApp Desk',
            'subtitle' => 'Inbox, bot flows, payment self-service, approved templates, usage billing, API settings, and logs.',
            'activeTab' => $activeTab,
            'tabs' => $this->tabs(),
            'tablesReady' => $tablesReady,
            'isPlatform' => $isPlatform,
            'stats' => $tablesReady ? $this->stats($isp?->id) : $this->emptyStats(),
            'setting' => $tablesReady ? $this->settingPayload($isp ? $this->whatsapp->workspaceSetting((int) $isp->id) : null) : null,
            'platformSetting' => $tablesReady ? $this->settingPayload($this->whatsapp->platformSetting()) : null,
            'providerOptions' => $this->providerOptions(),
            'templateVariables' => WhatsappManager::TEMPLATE_VARIABLES,
            'templateCategories' => ['payment', 'renewal', 'support', 'receipt', 'system', 'broadcast'],
            'botMenu' => $this->botMenu(),
            'botKeywords' => $this->botKeywords(),
            'conversations' => $tablesReady ? $this->conversationRows($isp?->id) : [],
            'selectedConversation' => $selectedConversation ? $this->conversationPayload($selectedConversation) : null,
            'conversationMessages' => $selectedConversation ? $this->messageRows($selectedConversation) : [],
            'templates' => $tablesReady ? $this->templateRows($isp?->id, $isPlatform) : [],
            'paymentRequests' => $tablesReady ? $this->paymentRequestRows($isp?->id) : [],
            'receipts' => $tablesReady ? $this->receiptRows($isp?->id) : [],
            'supportTickets' => $tablesReady ? $this->ticketRows($isp?->id) : [],
            'broadcasts' => $tablesReady ? $this->broadcastRows($isp?->id) : [],
            'usageLogs' => $tablesReady ? $this->usageRows($isp?->id) : [],
            'logs' => $tablesReady ? $this->logRows($isp?->id) : [],
            'customers' => $tablesReady ? $this->customerOptions($isp?->id) : [],
            'packages' => $tablesReady ? $this->packageOptions($isp?->id) : [],
            'routes' => $this->routeMap(),
        ]);
    }

    private function tabs(): array
    {
        return [
            ['key' => 'overview', 'label' => 'Overview', 'route' => route('isp.whatsapp.index')],
            ['key' => 'inbox', 'label' => 'Inbox', 'route' => route('isp.whatsapp.inbox')],
            ['key' => 'bot-flows', 'label' => 'Bot Flows', 'route' => route('isp.whatsapp.bot-flows')],
            ['key' => 'payment-requests', 'label' => 'Payment Requests', 'route' => route('isp.whatsapp.payment-requests')],
            ['key' => 'receipts', 'label' => 'Receipts', 'route' => route('isp.whatsapp.receipts')],
            ['key' => 'support-tickets', 'label' => 'Support Tickets', 'route' => route('isp.whatsapp.support-tickets')],
            ['key' => 'broadcasts', 'label' => 'Broadcasts', 'route' => route('isp.whatsapp.broadcasts')],
            ['key' => 'templates', 'label' => 'Templates', 'route' => route('isp.whatsapp.templates')],
            ['key' => 'usage', 'label' => 'Message Usage', 'route' => route('isp.whatsapp.usage')],
            ['key' => 'api-settings', 'label' => 'API Settings', 'route' => route('isp.whatsapp.api-settings')],
            ['key' => 'logs', 'label' => 'Logs', 'route' => route('isp.whatsapp.logs')],
            ['key' => 'settings', 'label' => 'Settings', 'route' => route('isp.whatsapp.settings')],
        ];
    }

    private function routeMap(): array
    {
        return [
            'sendReply' => route('isp.whatsapp.conversations.reply', ['conversation' => '__ID__']),
            'sendTemplate' => route('isp.whatsapp.conversations.template', ['conversation' => '__ID__']),
            'internalNote' => route('isp.whatsapp.conversations.note', ['conversation' => '__ID__']),
            'handover' => route('isp.whatsapp.conversations.handover', ['conversation' => '__ID__']),
            'updateConversation' => route('isp.whatsapp.conversations.update', ['conversation' => '__ID__']),
            'saveSettings' => route('isp.whatsapp.api-settings.save'),
            'testConnection' => route('isp.whatsapp.api-settings.test'),
            'saveTemplate' => route('isp.whatsapp.templates.save'),
            'createPaymentRequest' => route('isp.whatsapp.payment-requests.store'),
            'confirmPaymentRequest' => route('isp.whatsapp.payment-requests.confirm', ['paymentRequest' => '__ID__']),
            'saveBroadcast' => route('isp.whatsapp.broadcasts.save'),
            'confirmBroadcast' => route('isp.whatsapp.broadcasts.confirm', ['broadcast' => '__ID__']),
        ];
    }

    private function stats(?int $ispId): array
    {
        $messageQuery = IspWhatsappMessage::query()->when($ispId, fn ($query) => $query->where('isp_id', $ispId));
        $usageQuery = IspWhatsappUsageLog::query()->when($ispId, fn ($query) => $query->where('isp_id', $ispId));
        $settingQuery = IspWhatsappSetting::query()->where('scope', 'isp')->when($ispId, fn ($query) => $query->where('isp_id', $ispId));
        $monthStart = now()->startOfMonth();

        $balance = $ispId
            ? (float) ($this->whatsapp->workspaceSetting($ispId)->whatsapp_balance ?? 0)
            : (float) $settingQuery->sum('whatsapp_balance');

        $sentThisMonth = (clone $messageQuery)
            ->where('direction', 'outbound')
            ->where('status', 'sent')
            ->where('created_at', '>=', $monthStart)
            ->count();

        $failed = (clone $messageQuery)
            ->where('direction', 'outbound')
            ->where('status', 'failed')
            ->count();

        $estimatedCost = (float) (clone $usageQuery)
            ->where('status', 'sent')
            ->where('created_at', '>=', $monthStart)
            ->sum('cost');

        $latestTopup = IspWhatsappTopup::query()
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->latest()
            ->first();

        return [
            'whatsapp_balance' => number_format($balance, 2),
            'messages_sent_this_month' => $sentThisMonth,
            'estimated_cost_this_month' => number_format($estimatedCost, 2),
            'failed_messages' => $failed,
            'topup_payment_status' => $latestTopup?->status ?: ($ispId ? ($this->whatsapp->workspaceSetting($ispId)->topup_payment_status ?: 'not_started') : 'mixed'),
            'open_conversations' => (clone $messageQuery)->where('direction', 'inbound')->where('created_at', '>=', now()->subDay())->count(),
        ];
    }

    private function emptyStats(): array
    {
        return [
            'whatsapp_balance' => '0.00',
            'messages_sent_this_month' => 0,
            'estimated_cost_this_month' => '0.00',
            'failed_messages' => 0,
            'topup_payment_status' => 'not_started',
            'open_conversations' => 0,
        ];
    }

    private function selectedConversation(Request $request, ?int $ispId): ?IspWhatsappConversation
    {
        $query = IspWhatsappConversation::query()
            ->with(['customer.internetPackage', 'isp'])
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId));

        if ($id = $request->query('conversation')) {
            $selected = (clone $query)->where('id', $id)->first();
            if ($selected) {
                return $selected;
            }
        }

        return $query->latest('last_message_at')->latest('id')->first();
    }

    private function conversationRows(?int $ispId): array
    {
        return IspWhatsappConversation::query()
            ->with(['customer.internetPackage', 'isp'])
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->latest('last_message_at')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (IspWhatsappConversation $conversation) => $this->conversationPayload($conversation))
            ->values()
            ->all();
    }

    private function conversationPayload(IspWhatsappConversation $conversation): array
    {
        $seconds = $conversation->reply_window_expires_at
            ? max(0, now()->diffInSeconds($conversation->reply_window_expires_at, false))
            : 0;

        return [
            'id' => $conversation->id,
            'isp' => $conversation->isp ? ['id' => $conversation->isp->id, 'name' => $conversation->isp->name] : null,
            'customer' => $conversation->customer ? [
                'id' => $conversation->customer->id,
                'name' => $conversation->customer->name,
                'phone' => $conversation->customer->phone,
                'plan_name' => $conversation->customer->internetPackage?->name,
                'expiry_time' => $this->dateValue($conversation->customer->next_due_date),
                'status' => $conversation->customer->connection_status,
            ] : null,
            'phone' => $conversation->phone,
            'customer_name' => $conversation->customer_name ?: $conversation->customer?->name ?: 'Unlinked WhatsApp contact',
            'status' => $conversation->status,
            'opted_out' => (bool) $conversation->opted_out,
            'blocked' => (bool) $conversation->blocked,
            'reply_window_status' => $conversation->replyWindowStatus(),
            'reply_window_expires_at' => $this->dateTimeValue($conversation->reply_window_expires_at),
            'reply_window_seconds_remaining' => $seconds,
            'template_required' => $conversation->requiresTemplate(),
            'last_message_preview' => $conversation->last_message_preview,
            'last_message_at' => $this->dateTimeValue($conversation->last_message_at),
        ];
    }

    private function messageRows(IspWhatsappConversation $conversation): array
    {
        return IspWhatsappMessage::query()
            ->where('conversation_id', $conversation->id)
            ->with(['template', 'sender'])
            ->oldest()
            ->limit(200)
            ->get()
            ->map(fn (IspWhatsappMessage $message) => [
                'id' => $message->id,
                'direction' => $message->direction,
                'message_type' => $message->message_type,
                'body' => $message->body,
                'status' => $message->status,
                'error_message' => $message->error_message,
                'provider_mode' => $message->provider_mode,
                'provider' => $message->provider,
                'cost' => (float) $message->cost,
                'template' => $message->template ? ['id' => $message->template->id, 'name' => $message->template->name] : null,
                'sender' => $message->sender ? ['id' => $message->sender->id, 'name' => $message->sender->name] : null,
                'sent_at' => $this->dateTimeValue($message->sent_at ?: $message->created_at),
            ])
            ->values()
            ->all();
    }

    private function templateRows(?int $ispId, bool $isPlatform): array
    {
        return IspWhatsappTemplate::query()
            ->when($ispId, fn ($query) => $query->where(function ($subQuery) use ($ispId) {
                $subQuery->whereNull('isp_id')->orWhere('isp_id', $ispId);
            }))
            ->when(! $ispId && ! $isPlatform, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('category')
            ->orderBy('name')
            ->limit(200)
            ->get()
            ->map(fn (IspWhatsappTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'key' => $template->key,
                'category' => $template->category,
                'provider_template_name' => $template->provider_template_name,
                'language' => $template->language,
                'body' => $template->body,
                'variables' => $template->variables ?: [],
                'status' => $template->status,
                'enabled' => (bool) $template->enabled,
                'scope' => $template->isp_id ? 'workspace' : 'platform',
            ])
            ->values()
            ->all();
    }

    private function paymentRequestRows(?int $ispId): array
    {
        return IspWhatsappPaymentRequest::query()
            ->with(['conversation', 'customer', 'internetPackage'])
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (IspWhatsappPaymentRequest $payment) => [
                'id' => $payment->id,
                'customer' => $payment->customer?->name ?: 'Unlinked WhatsApp contact',
                'phone' => $payment->phone,
                'plan_name' => $payment->internetPackage?->name,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'method' => $payment->method,
                'status' => $payment->status,
                'checkout_request_id' => $payment->checkout_request_id,
                'receipt_code' => $payment->receipt_code,
                'payment_center_record_id' => $payment->payment_center_record_id,
                'requested_at' => $this->dateTimeValue($payment->requested_at ?: $payment->created_at),
                'confirmed_at' => $this->dateTimeValue($payment->confirmed_at),
            ])
            ->values()
            ->all();
    }

    private function receiptRows(?int $ispId): array
    {
        return IspWhatsappReceipt::query()
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (IspWhatsappReceipt $receipt) => [
                'id' => $receipt->id,
                'phone' => $receipt->phone,
                'receipt_code' => $receipt->receipt_code,
                'amount' => (float) $receipt->amount,
                'currency' => $receipt->currency,
                'status' => $receipt->status,
                'sent_at' => $this->dateTimeValue($receipt->sent_at),
                'created_at' => $this->dateTimeValue($receipt->created_at),
            ])
            ->values()
            ->all();
    }

    private function ticketRows(?int $ispId): array
    {
        return IspWhatsappSupportTicket::query()
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (IspWhatsappSupportTicket $ticket) => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'phone' => $ticket->phone,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'description' => $ticket->description,
                'created_at' => $this->dateTimeValue($ticket->created_at),
            ])
            ->values()
            ->all();
    }

    private function broadcastRows(?int $ispId): array
    {
        return IspWhatsappBroadcast::query()
            ->with('template')
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (IspWhatsappBroadcast $broadcast) => [
                'id' => $broadcast->id,
                'name' => $broadcast->name,
                'template' => $broadcast->template ? ['id' => $broadcast->template->id, 'name' => $broadcast->template->name] : null,
                'audience' => $broadcast->audience,
                'recipient_count' => $broadcast->recipient_count,
                'status' => $broadcast->status,
                'requires_confirmation' => (bool) $broadcast->requires_confirmation,
                'confirmed_at' => $this->dateTimeValue($broadcast->confirmed_at),
                'created_at' => $this->dateTimeValue($broadcast->created_at),
            ])
            ->values()
            ->all();
    }

    private function usageRows(?int $ispId): array
    {
        return IspWhatsappUsageLog::query()
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (IspWhatsappUsageLog $log) => [
                'id' => $log->id,
                'phone' => $log->phone,
                'message_type' => $log->message_type,
                'provider_mode' => $log->provider_mode,
                'provider' => $log->provider,
                'cost' => (float) $log->cost,
                'status' => $log->status,
                'error_message' => $log->error_message,
                'sent_at' => $this->dateTimeValue($log->sent_at ?: $log->created_at),
            ])
            ->values()
            ->all();
    }

    private function logRows(?int $ispId): array
    {
        return IspWhatsappMessage::query()
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (IspWhatsappMessage $message) => [
                'id' => $message->id,
                'phone' => $message->phone,
                'direction' => $message->direction,
                'message_type' => $message->message_type,
                'provider_mode' => $message->provider_mode,
                'provider' => $message->provider,
                'cost' => (float) $message->cost,
                'status' => $message->status,
                'error_message' => $message->error_message,
                'sent_at' => $this->dateTimeValue($message->sent_at ?: $message->created_at),
            ])
            ->values()
            ->all();
    }

    private function settingPayload(?IspWhatsappSetting $setting): ?array
    {
        if (! $setting) {
            return null;
        }

        $credentials = $setting->credentials ?: [];

        return [
            'id' => $setting->id,
            'scope' => $setting->scope,
            'provider_mode' => $setting->provider_mode,
            'provider' => $setting->provider,
            'business_phone' => $setting->business_phone,
            'phone_number_id' => $setting->phone_number_id,
            'waba_id' => $setting->waba_id,
            'sender_name' => $setting->sender_name,
            'api_base_url' => $setting->api_base_url,
            'webhook_verify_token_saved' => filled($setting->webhook_verify_token),
            'credentials_saved' => collect($credentials)->filter(fn ($value) => filled($value))->keys()->values()->all(),
            'is_active' => (bool) $setting->is_active,
            'allow_platform_api' => (bool) $setting->allow_platform_api,
            'allow_own_api' => (bool) $setting->allow_own_api,
            'reply_window_minutes' => (int) $setting->reply_window_minutes,
            'whatsapp_balance' => (float) $setting->whatsapp_balance,
            'estimated_cost_per_message' => (float) $setting->estimated_cost_per_message,
            'low_balance_threshold' => (float) $setting->low_balance_threshold,
            'billing_enabled' => (bool) $setting->billing_enabled,
            'billing_status' => $setting->billing_status,
            'topup_payment_status' => $setting->topup_payment_status,
            'messages_sent' => (int) $setting->messages_sent,
            'messages_failed' => (int) $setting->messages_failed,
            'last_billed_at' => $this->dateTimeValue($setting->last_billed_at),
            'last_tested_at' => $this->dateTimeValue($setting->last_tested_at),
            'last_test_status' => $setting->last_test_status,
            'last_test_message' => $setting->last_test_message,
        ];
    }

    private function customerOptions(?int $ispId): array
    {
        return Customer::query()
            ->with('internetPackage')
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'plan_name' => $customer->internetPackage?->name,
                'amount' => (float) ($customer->monthly_amount ?: $customer->internetPackage?->price ?: 0),
                'expiry_time' => $this->dateValue($customer->next_due_date),
            ])
            ->values()
            ->all();
    }

    private function packageOptions(?int $ispId): array
    {
        return InternetPackage::query()
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->map(fn (InternetPackage $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'price' => (float) $package->price,
                'validity_days' => $package->validity_days,
                'status' => $package->status,
            ])
            ->values()
            ->all();
    }

    private function providerOptions(): array
    {
        return [
            ['value' => 'platform', 'label' => 'Platform WhatsApp API'],
            ['value' => 'meta_cloud', 'label' => 'Meta Cloud API'],
            ['value' => 'africastalking', 'label' => "Africa's Talking"],
            ['value' => 'twilio', 'label' => 'Twilio'],
            ['value' => 'custom_http', 'label' => 'Other / Custom HTTP API'],
            ['value' => 'other', 'label' => 'Other provider'],
        ];
    }

    private function botMenu(): array
    {
        return [
            '1. Buy internet package',
            '2. Renew current plan',
            '3. Check my active plan',
            '4. Check expiry time',
            '5. Request M-Pesa payment push',
            '6. Get receipt',
            '7. Report issue',
            '8. Talk to support',
        ];
    }

    private function botKeywords(): array
    {
        return ['plan', 'expiry', 'balance', 'status', 'renew', 'mpesa', 'receipt', 'support', 'issue'];
    }

    private function broadcastRecipientCount(array $data, ?int $ispId): int
    {
        $query = Customer::query()
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->whereNotNull('phone')
            ->where('phone', '<>', '');

        match ($data['audience']) {
            'specific' => $query->whereIn('id', $data['customer_ids'] ?? []),
            'active_customers' => $query->where('connection_status', 'active'),
            'expired_customers' => $query->whereNotNull('next_due_date')->whereDate('next_due_date', '<', now()->toDateString()),
            default => null,
        };

        return $query->count();
    }

    private function assertTablesReady(): void
    {
        abort_unless(Schema::hasTable('isp_whatsapp_messages'), 500, 'WhatsApp Desk tables are not migrated yet.');
    }

    private function resolveIsp(Request $request, $ispId = null): Isp
    {
        return app(IspTenantResolver::class)->resolve($request, $ispId);
    }

    private function isPlatform(Request $request): bool
    {
        return app(IspTenantResolver::class)->isPlatform($request);
    }

    private function authorizeIspRecord(Request $request, int $ispId): void
    {
        app(IspTenantResolver::class)->authorize($request, $ispId);
    }

    private function authorizeConversation(Request $request, IspWhatsappConversation $conversation): void
    {
        if ($conversation->isp_id) {
            $this->authorizeIspRecord($request, (int) $conversation->isp_id);
        }
    }

    private function authorizeView(Request $request): void
    {
        abort_unless(
            $this->isPlatform($request)
            || $request->user()->can('view-wifi-dashboard')
            || $request->user()->can('manage-wifi-dashboard')
            || $request->user()->can('view-isp-customers')
            || $request->user()->can('manage-isp-customers'),
            403
        );
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $this->isPlatform($request)
            || $request->user()->can('manage-wifi-dashboard')
            || $request->user()->can('manage-isp-customers'),
            403
        );
    }

    private function dateValue($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->toDateString();
        }

        return (string) $value;
    }

    private function dateTimeValue($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d H:i');
        }

        return (string) $value;
    }
}
