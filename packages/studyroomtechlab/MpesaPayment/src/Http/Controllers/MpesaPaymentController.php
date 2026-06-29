<?php

namespace StudyRoomTechLab\MpesaPayment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Plan;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use StudyRoomTechLab\MpesaPayment\Models\MpesaTransaction;
use StudyRoomTechLab\MpesaPayment\Services\MpesaPaymentService;

class MpesaPaymentController extends Controller
{
    public function initiate(Request $request, MpesaPaymentService $service)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:isp_customers,id'],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'amount' => ['nullable', 'numeric', 'min:1'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $customer = Customer::findOrFail($data['customer_id']);

        $this->authorizeCustomer($request, $customer);

        try {
            $transaction = $service->initiateStkForCustomer(
                customer: $customer,
                internetPackageId: $data['internet_package_id'] ?? null,
                amount: isset($data['amount']) ? (float) $data['amount'] : null,
                phone: $data['phone'] ?? null,
                userId: $request->user()?->id
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'M-Pesa STK Push sent.',
                    'transaction' => [
                        'id' => $transaction->id,
                        'status' => $transaction->status,
                        'phone' => $transaction->phone,
                        'amount' => $transaction->amount,
                        'checkout_request_id' => $transaction->checkout_request_id,
                    ],
                ]);
            }

            return back()->with('success', 'M-Pesa STK Push sent to ' . $transaction->phone . '.');
        } catch (\Throwable $e) {
            Log::error('M-Pesa STK initiation failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function initiatePlanSubscription(Request $request, MpesaPaymentService $service)
    {
        $user = $request->user();

        abort_unless($user && $user->can('view-plans'), 403);

        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'time_period' => ['nullable', 'in:Month,Year'],
            'phone' => ['required', 'string', 'max:30'],
            'coupon_code' => ['nullable', 'string', 'max:191'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);

        try {
            $transaction = $service->initiateStkForPlanSubscription(
                user: $user,
                plan: $plan,
                duration: $data['time_period'] ?? 'Month',
                phone: $data['phone'],
                couponCode: $data['coupon_code'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'M-Pesa STK Push sent. Enter your PIN to complete the subscription payment.',
                'transaction' => [
                    'id' => $transaction->id,
                    'status' => $transaction->status,
                    'phone' => $transaction->phone,
                    'amount' => $transaction->amount,
                    'checkout_request_id' => $transaction->checkout_request_id,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('M-Pesa plan subscription initiation failed', [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function callback(Request $request, MpesaPaymentService $service)
    {
        $payload = $request->all();

        Log::info('M-Pesa callback received', [
            'payload' => $payload,
        ]);

        try {
            $transaction = $service->handleCallback($payload);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => $transaction
                    ? 'Callback processed successfully'
                    : 'Callback accepted',
            ]);
        } catch (\Throwable $e) {
            Log::error('M-Pesa callback processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Callback accepted',
            ]);
        }
    }

    public function status(Request $request, int $transactionId)
    {
        $transaction = MpesaTransaction::findOrFail($transactionId);

        if ($transaction->customer_id) {
            $customer = Customer::find($transaction->customer_id);

            if ($customer) {
                $this->authorizeCustomer($request, $customer);
            }
        } else {
            $this->authorizePlanTransaction($request, $transaction);
        }

        return $this->transactionStatusResponse($transaction);
    }

    public function planSubscriptionStatus(Request $request, int $transactionId)
    {
        $transaction = MpesaTransaction::findOrFail($transactionId);

        abort_unless($transaction->payment_type === 'plan_subscription', 404);

        $this->authorizePlanTransaction($request, $transaction);

        return $this->transactionStatusResponse($transaction);
    }

    private function transactionStatusResponse(MpesaTransaction $transaction)
    {
        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'phone' => $transaction->phone,
                'amount' => $transaction->amount,
                'receipt' => $transaction->mpesa_receipt_number,
                'result_code' => $transaction->result_code,
                'result_desc' => $transaction->result_desc,
                'paid_at' => $transaction->paid_at?->toDateTimeString(),
                'failed_at' => $transaction->failed_at?->toDateTimeString(),
                'provisioning_triggered' => $transaction->provisioning_triggered,
                'invoice' => data_get($transaction->request_payload, 'plan_subscription.invoice_lines', []),
                'hotspot_revenue' => data_get($transaction->request_payload, 'plan_subscription.hotspot_revenue'),
                'hotspot_fee_percent' => data_get($transaction->request_payload, 'plan_subscription.hotspot_fee_percent'),
                'hotspot_fee_amount' => data_get($transaction->request_payload, 'plan_subscription.hotspot_fee_amount'),
            ],
        ]);
    }

    private function authorizeCustomer(Request $request, Customer $customer): void
    {
        if (! $request->user()) {
            abort(403);
        }

        if (class_exists(IspTenantResolver::class)) {
            app(IspTenantResolver::class)->authorize($request, $customer->isp_id);
        }
    }

    private function authorizePlanTransaction(Request $request, MpesaTransaction $transaction): void
    {
        $user = $request->user();

        abort_unless($user, 403);

        $type = strtolower((string) ($user->type ?? ''));
        $isSuperAdmin = in_array($type, ['superadmin', 'super_admin', 'super admin'], true)
            || (method_exists($user, 'hasRole') && $user->hasRole('superadmin'));

        if ($isSuperAdmin) {
            return;
        }

        abort_unless(
            (int) $transaction->created_by === (int) $user->id
            || (int) $transaction->isp_id === (int) $user->id,
            403
        );
    }
}
