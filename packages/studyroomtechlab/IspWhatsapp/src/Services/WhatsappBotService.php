<?php

namespace StudyRoomTechLab\IspWhatsapp\Services;

use App\Models\Customer;
use App\Models\InternetPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappConversation;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappPaymentRequest;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappReceipt;
use StudyRoomTechLab\IspWhatsapp\Models\IspWhatsappSupportTicket;

class WhatsappBotService
{
    public function __construct(private readonly WhatsappManager $whatsapp)
    {
    }

    public function handleIncoming(int $ispId, string $phone, string $body, array $payload = []): IspWhatsappConversation
    {
        $conversation = $this->whatsapp->receiveInbound($ispId, $phone, $body, $payload);
        $text = trim(mb_strtolower($body));

        if (in_array($text, ['stop', 'unsubscribe', 'opt out', 'opt-out'], true)) {
            $conversation->forceFill(['opted_out' => true])->save();
            $this->whatsapp->internalNote($conversation->refresh(), 'Customer opted out from WhatsApp messages.', null, 'system');
            return $conversation->refresh();
        }

        if (in_array($text, ['start', 'subscribe', 'resume'], true)) {
            $conversation->forceFill(['opted_out' => false, 'blocked' => false])->save();
            $conversation = $conversation->refresh();
            $this->sendMenu($conversation);
            return $conversation;
        }

        match (true) {
            in_array($text, ['', 'hi', 'hello', 'menu', 'help', '0'], true) => $this->sendMenu($conversation),
            in_array($text, ['1', 'buy', 'packages', 'package', 'internet packages'], true) => $this->sendPackages($conversation),
            in_array($text, ['2', 'renew', 'renewal'], true) => $this->renewCurrentPlan($conversation),
            in_array($text, ['3', 'plan', 'active plan', 'my plan'], true) => $this->sendPlanStatus($conversation),
            in_array($text, ['4', 'expiry', 'expire', 'expires'], true) => $this->sendExpiry($conversation),
            in_array($text, ['5', 'mpesa', 'm-pesa', 'stk', 'pay', 'payment push'], true) => $this->requestMpesaPush($conversation),
            in_array($text, ['6', 'receipt', 'get receipt'], true) => $this->sendLatestReceipt($conversation),
            in_array($text, ['7', 'issue', 'problem', 'payment issue', 'report issue'], true) => $this->reportIssue($conversation, $body),
            in_array($text, ['8', 'support', 'talk to support', 'agent'], true) => $this->handoverToSupport($conversation),
            in_array($text, ['balance', 'status', 'account'], true) => $this->sendPlanStatus($conversation),
            default => $this->sendMenu($conversation),
        };

        return $conversation->refresh();
    }

    public function createPaymentRequest(
        IspWhatsappConversation $conversation,
        ?int $internetPackageId = null,
        string $method = 'mpesa',
        ?int $userId = null
    ): IspWhatsappPaymentRequest {
        $conversation->loadMissing(['customer.internetPackage']);
        $customer = $conversation->customer;
        $package = $internetPackageId ? InternetPackage::find($internetPackageId) : $customer?->internetPackage;
        $amount = $this->resolveAmount($customer, $package);
        $currency = config('isp-whatsapp.default_currency', 'KES');

        $paymentRequest = IspWhatsappPaymentRequest::create([
            'isp_id' => $conversation->isp_id,
            'conversation_id' => $conversation->id,
            'customer_id' => $customer?->id,
            'internet_package_id' => $package?->id,
            'phone' => $conversation->phone,
            'amount' => $amount,
            'currency' => $currency,
            'method' => $method,
            'status' => $customer ? 'pending' : 'unlinked',
            'notes' => $customer
                ? 'WhatsApp payment request created from customer self-service.'
                : 'Unlinked WhatsApp conversation. No customer was auto-created.',
            'requested_at' => now(),
            'created_by' => $userId,
        ]);

        if (! $customer) {
            $this->whatsapp->sendSystemMessage(
                $conversation,
                'We could not match your WhatsApp number to an account. Please contact support or provide your account number.',
                'payment_request',
                ['payment_request_id' => $paymentRequest->id]
            );

            return $paymentRequest->refresh();
        }

        if ($amount <= 0) {
            $paymentRequest->forceFill([
                'status' => 'failed',
                'notes' => 'Payment amount could not be resolved from the customer or package.',
            ])->save();

            $this->whatsapp->sendSystemMessage(
                $conversation,
                'We could not determine the amount for your current plan. Please contact support.',
                'payment_request',
                ['payment_request_id' => $paymentRequest->id]
            );

            return $paymentRequest->refresh();
        }

        if ($method === 'mpesa') {
            $this->triggerMpesa($paymentRequest, $customer, $package?->id, $userId);
        }

        $message = $paymentRequest->status === 'failed'
            ? 'Payment push could not be started. Please try again later or contact support.'
            : sprintf(
                'Payment request created for %s %.2f%s. If M-Pesa was selected, check your phone for the STK prompt.',
                $currency,
                (float) $paymentRequest->amount,
                $package ? ' for ' . $package->name : ''
            );

        $this->whatsapp->sendSystemMessage($conversation, $message, 'payment_request', [
            'payment_request_id' => $paymentRequest->id,
            'mpesa_transaction_id' => $paymentRequest->mpesa_transaction_id,
            'checkout_request_id' => $paymentRequest->checkout_request_id,
        ]);

        return $paymentRequest->refresh();
    }

    public function confirmPayment(IspWhatsappPaymentRequest $paymentRequest, ?string $receiptCode = null): IspWhatsappReceipt
    {
        $receiptCode = $receiptCode ?: $paymentRequest->receipt_code ?: 'WA-' . now()->format('YmdHis') . '-' . $paymentRequest->id;

        $paymentRequest->forceFill([
            'status' => 'paid',
            'receipt_code' => $receiptCode,
            'confirmed_at' => now(),
            'payment_center_record_id' => $paymentRequest->payment_center_record_id
                ?: ($paymentRequest->mpesa_transaction_id ? 'mpesa-' . $paymentRequest->mpesa_transaction_id : null),
        ])->save();

        $receipt = IspWhatsappReceipt::firstOrCreate(
            ['payment_request_id' => $paymentRequest->id, 'receipt_code' => $receiptCode],
            [
                'isp_id' => $paymentRequest->isp_id,
                'conversation_id' => $paymentRequest->conversation_id,
                'customer_id' => $paymentRequest->customer_id,
                'phone' => $paymentRequest->phone,
                'amount' => $paymentRequest->amount,
                'currency' => $paymentRequest->currency,
                'status' => 'issued',
                'payload' => [
                    'payment_center_record_id' => $paymentRequest->payment_center_record_id,
                    'mpesa_transaction_id' => $paymentRequest->mpesa_transaction_id,
                ],
            ]
        );

        $conversation = $paymentRequest->conversation;
        if ($conversation) {
            $this->whatsapp->sendSystemMessage(
                $conversation,
                sprintf(
                    'Payment confirmed. Receipt %s for %s %.2f has been issued.',
                    $receiptCode,
                    $paymentRequest->currency,
                    (float) $paymentRequest->amount
                ),
                'receipt',
                ['receipt_id' => $receipt->id, 'payment_request_id' => $paymentRequest->id]
            );

            $receipt->forceFill(['sent_at' => now()])->save();
        }

        return $receipt->refresh();
    }

    private function sendMenu(IspWhatsappConversation $conversation): void
    {
        $this->whatsapp->sendSystemMessage($conversation, implode("\n", [
            'WhatsApp self-service menu',
            '1. Buy internet package',
            '2. Renew current plan',
            '3. Check my active plan',
            '4. Check expiry time',
            '5. Request M-Pesa payment push',
            '6. Get receipt',
            '7. Report issue',
            '8. Talk to support',
        ]), 'bot_menu');
    }

    private function sendPackages(IspWhatsappConversation $conversation): void
    {
        $packages = InternetPackage::query()
            ->where('isp_id', $conversation->isp_id)
            ->when(Schema::hasColumn('internet_packages', 'status'), fn ($query) => $query->where('status', 'active'))
            ->when(Schema::hasColumn('internet_packages', 'hidden_from_client'), fn ($query) => $query->where('hidden_from_client', false))
            ->orderBy('price')
            ->limit(10)
            ->get();

        if ($packages->isEmpty()) {
            $this->whatsapp->sendSystemMessage($conversation, 'No public internet packages are available right now. Please contact support.', 'bot_menu');
            return;
        }

        $lines = ['Available internet packages:'];
        foreach ($packages as $index => $package) {
            $lines[] = sprintf(
                '%d. %s - %s %.2f%s',
                $index + 1,
                $package->name,
                config('isp-whatsapp.default_currency', 'KES'),
                (float) $package->price,
                $package->validity_days ? ' for ' . $package->validity_days . ' days' : ''
            );
        }
        $lines[] = 'Reply 5 to request an M-Pesa payment push.';

        $this->whatsapp->sendSystemMessage($conversation, implode("\n", $lines), 'bot_menu');
    }

    private function renewCurrentPlan(IspWhatsappConversation $conversation): void
    {
        if (! $conversation->customer_id) {
            $this->whatsapp->sendSystemMessage($conversation, 'We could not match your WhatsApp number to an account. Please contact support or provide your account number.', 'renewal');
            return;
        }

        $this->createPaymentRequest($conversation, null, 'mpesa');
    }

    private function sendPlanStatus(IspWhatsappConversation $conversation): void
    {
        $conversation->loadMissing(['customer.internetPackage']);

        if (! $conversation->customer) {
            $this->whatsapp->sendSystemMessage($conversation, 'We could not match your WhatsApp number to an account. Please contact support or provide your account number.', 'status');
            return;
        }

        $customer = $conversation->customer;
        $package = $customer->internetPackage;

        $this->whatsapp->sendSystemMessage($conversation, implode("\n", [
            'Your account status',
            'Plan: ' . ($package?->name ?: 'Not assigned'),
            'Expiry: ' . ($customer->next_due_date ? $customer->next_due_date->format('Y-m-d') : 'Not set'),
            'Account status: ' . ($customer->connection_status ?: 'Unknown'),
            'Billing: ' . ($customer->billing_status ?: 'Unknown'),
            'Reply 2 to renew or 5 for M-Pesa payment push.',
        ]), 'status');
    }

    private function sendExpiry(IspWhatsappConversation $conversation): void
    {
        $conversation->loadMissing('customer');

        if (! $conversation->customer) {
            $this->whatsapp->sendSystemMessage($conversation, 'We could not match your WhatsApp number to an account. Please contact support or provide your account number.', 'status');
            return;
        }

        $expiry = $conversation->customer->next_due_date
            ? $conversation->customer->next_due_date->format('Y-m-d')
            : 'not set';

        $this->whatsapp->sendSystemMessage($conversation, 'Your current plan expiry time is ' . $expiry . '.', 'status');
    }

    private function requestMpesaPush(IspWhatsappConversation $conversation): void
    {
        $this->createPaymentRequest($conversation, null, 'mpesa');
    }

    private function sendLatestReceipt(IspWhatsappConversation $conversation): void
    {
        $receipt = IspWhatsappReceipt::query()
            ->where('conversation_id', $conversation->id)
            ->latest()
            ->first();

        if (! $receipt) {
            $this->whatsapp->sendSystemMessage($conversation, 'No WhatsApp receipt was found for this conversation yet.', 'receipt');
            return;
        }

        $this->whatsapp->sendSystemMessage(
            $conversation,
            sprintf('Receipt %s: %s %.2f, status %s.', $receipt->receipt_code, $receipt->currency, (float) $receipt->amount, $receipt->status),
            'receipt',
            ['receipt_id' => $receipt->id]
        );
    }

    private function reportIssue(IspWhatsappConversation $conversation, string $body): void
    {
        $ticket = IspWhatsappSupportTicket::create([
            'isp_id' => $conversation->isp_id,
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'ticket_number' => 'WA-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'phone' => $conversation->phone,
            'subject' => 'WhatsApp payment/support issue',
            'status' => 'open',
            'priority' => 'normal',
            'description' => $body,
        ]);

        $this->whatsapp->systemHandover($conversation, 'Support ticket ' . $ticket->ticket_number . ' created from WhatsApp.');
        $this->whatsapp->sendSystemMessage($conversation, 'Your issue has been reported. Ticket number: ' . $ticket->ticket_number . '.', 'support', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
        ]);
    }

    private function handoverToSupport(IspWhatsappConversation $conversation): void
    {
        $this->whatsapp->systemHandover($conversation, 'Customer requested a support handover.');
        $this->whatsapp->sendSystemMessage($conversation, 'A support admin has been notified. You can continue typing here.', 'support');
    }

    private function triggerMpesa(IspWhatsappPaymentRequest $paymentRequest, Customer $customer, ?int $packageId, ?int $userId): void
    {
        if (! class_exists(\StudyRoomTechLab\MpesaPayment\Services\MpesaPaymentService::class)) {
            $paymentRequest->forceFill([
                'status' => 'failed',
                'notes' => 'M-Pesa add-on is not available.',
            ])->save();
            return;
        }

        try {
            $transaction = app(\StudyRoomTechLab\MpesaPayment\Services\MpesaPaymentService::class)
                ->initiateStkForCustomer(
                    $customer,
                    $packageId,
                    (float) $paymentRequest->amount,
                    $paymentRequest->phone,
                    $userId
                );

            $paymentRequest->forceFill([
                'status' => in_array($transaction->status, ['pending', 'stk_sent'], true) ? 'requested' : $transaction->status,
                'mpesa_transaction_id' => $transaction->id,
                'checkout_request_id' => $transaction->checkout_request_id,
                'payment_center_record_id' => 'mpesa-' . $transaction->id,
                'receipt_code' => $transaction->mpesa_receipt_number,
            ])->save();
        } catch (\Throwable $e) {
            $paymentRequest->forceFill([
                'status' => 'failed',
                'notes' => $e->getMessage(),
            ])->save();
        }
    }

    private function resolveAmount(?Customer $customer, ?InternetPackage $package): float
    {
        foreach (['price', 'amount', 'monthly_price', 'monthly_amount', 'selling_price'] as $field) {
            if ($package && isset($package->{$field}) && (float) $package->{$field} > 0) {
                return (float) $package->{$field};
            }
        }

        foreach (['monthly_amount', 'amount', 'price'] as $field) {
            if ($customer && isset($customer->{$field}) && (float) $customer->{$field} > 0) {
                return (float) $customer->{$field};
            }
        }

        return 0.0;
    }
}
