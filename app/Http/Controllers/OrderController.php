<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::user()->can('manage-orders')) {
            return back()->with('error', __('Permission denied'));
        }

        $viewMode = $request->query('view') === 'invoices' ? 'invoices' : 'orders';
        $records = collect($this->orderRecords());
        $orderIds = $records->pluck('order_id')->filter()->values()->all();

        $records = $records->merge($this->smsTopupRecords($orderIds));

        if ($search = trim((string) $request->query('search'))) {
            $needle = Str::lower($search);
            $records = $records->filter(function (array $record) use ($needle) {
                return Str::contains(Str::lower(implode(' ', array_filter([
                    $record['order_id'] ?? null,
                    $record['invoice_number'] ?? null,
                    $record['name'] ?? null,
                    $record['email'] ?? null,
                    $record['plan_name'] ?? null,
                    $record['txn_id'] ?? null,
                    $record['receipt'] ?? null,
                    $record['source_label'] ?? null,
                    $record['invoice_type'] ?? null,
                ]))), $needle);
            });
        }

        if ($source = $request->query('source')) {
            $records = $records->where('source_type', $source);
        }

        if ($status = $request->query('status')) {
            $records = $records->filter(fn (array $record) => $this->statusGroup((string) ($record['payment_status'] ?? '')) === $status);
        }

        $records = $records
            ->sortByDesc(fn (array $record) => strtotime((string) ($record['created_at'] ?? now())) ?: 0)
            ->values();

        $stats = $this->stats($records);
        $orders = $this->paginate($records, (int) $request->query('per_page', 10));

        return Inertia::render('orders/index', [
            'orders' => $orders,
            'viewMode' => $viewMode,
            'stats' => $stats,
            'filters' => $request->only(['search', 'source', 'status']),
        ]);
    }

    private function orderRecords(): array
    {
        $query = Order::with(['plan', 'user', 'total_coupon_used.coupon_detail'])
            ->where(function ($q) {
                if (Auth::user()->type !== 'superadmin') {
                    $q->where('created_by', Auth::id());
                }
            });

        return $query
            ->latest('id')
            ->get()
            ->map(fn (Order $order) => $this->orderPayload($order))
            ->values()
            ->all();
    }

    private function smsTopupRecords(array $existingOrderIds)
    {
        if (! Schema::hasTable('isp_sms_topups')) {
            return collect();
        }

        $query = DB::table('isp_sms_topups')->latest('id');

        if (Auth::user()->type !== 'superadmin') {
            $ispId = null;

            try {
                $isp = app(IspTenantResolver::class)->resolve(request());
                $ispId = $isp?->id;
            } catch (\Throwable) {
                $ispId = null;
            }

            $query->where(function ($q) use ($ispId) {
                $q->where('user_id', Auth::id());

                if ($ispId) {
                    $q->orWhere('isp_id', $ispId);
                }
            });
        }

        $topups = $query->get();
        $userIds = $topups->pluck('user_id')->filter()->unique()->values();
        $users = $userIds->isNotEmpty() && Schema::hasTable('users')
            ? DB::table('users')->whereIn('id', $userIds)->get()->keyBy('id')
            : collect();

        return $topups
            ->reject(fn ($topup) => $topup->order_id && in_array($topup->order_id, $existingOrderIds, true))
            ->map(function ($topup) use ($users) {
                $createdAt = $topup->created_at ?: now();
                $invoicePrefix = date('Ym', strtotime((string) $createdAt));
                $user = $users->get($topup->user_id);
                $status = $this->topupStatus((string) $topup->status);

                return [
                    'id' => 'sms-topup-' . $topup->id,
                    'order_id' => $topup->order_id ?: $topup->topup_number,
                    'invoice_number' => 'INV-SMS-' . $invoicePrefix . '-' . str_pad((string) $topup->id, 5, '0', STR_PAD_LEFT),
                    'invoice_type' => 'SMS Top-up',
                    'source_type' => 'sms_topup',
                    'source_label' => 'SMS Top-up',
                    'name' => $user->name ?? 'SMS Top-up',
                    'email' => $user->email ?? null,
                    'plan_name' => 'System SMS Wallet Top-up',
                    'plan_id' => null,
                    'price' => (float) $topup->amount,
                    'currency' => $topup->currency ?: 'KES',
                    'payment_status' => $status,
                    'payment_type' => $topup->payment_method ?: 'SMS Checkout',
                    'txn_id' => null,
                    'receipt' => $topup->topup_number,
                    'created_at' => $topup->created_at,
                    'original_price' => null,
                    'total_coupon_used' => null,
                    'user' => $user ? [
                        'name' => $user->name,
                        'email' => $user->email,
                    ] : null,
                ];
            })
            ->values();
    }

    private function orderPayload(Order $order): array
    {
        $createdAt = $order->created_at;
        $invoicePrefix = $createdAt ? $createdAt->format('Ym') : now()->format('Ym');
        $paymentType = (string) ($order->payment_type ?: '-');
        $price = (float) ($order->price ?? 0);
        $receiptData = $this->receiptData($order->receipt);
        $sourceType = $this->sourceType($paymentType, $price, (string) ($order->plan_name ?? ''), $receiptData);

        return [
            'id' => $order->id,
            'order_id' => $order->order_id,
            'invoice_number' => 'INV-' . $invoicePrefix . '-' . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
            'invoice_type' => $this->invoiceType($paymentType, $price, $sourceType),
            'source_type' => $sourceType,
            'source_label' => $this->sourceLabel($sourceType),
            'name' => $order->name,
            'email' => $order->email,
            'plan_name' => $order->plan_name,
            'plan_id' => $order->plan_id,
            'price' => $order->price,
            'currency' => $order->currency,
            'payment_status' => $order->payment_status,
            'payment_type' => $paymentType,
            'txn_id' => $order->txn_id,
            'receipt' => $order->receipt,
            'created_at' => $order->created_at,
            'original_price' => $order->original_price ?? null,
            'total_coupon_used' => $order->total_coupon_used,
            'user' => $order->user ? [
                'name' => $order->user->name,
                'email' => $order->user->email,
            ] : null,
        ];
    }

    private function receiptData($receipt): array
    {
        if (! is_string($receipt) || trim($receipt) === '') {
            return [];
        }

        $decoded = json_decode($receipt, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function sourceType(string $paymentType, float $price, string $planName, array $receiptData): string
    {
        if (($receiptData['invoice_type'] ?? null) === 'sms_topup' || Str::contains(Str::lower($planName), 'sms')) {
            return 'sms_topup';
        }

        if (Str::lower($paymentType) === 'trial') {
            return 'trial';
        }

        if ($price <= 0) {
            return 'free_plan';
        }

        return 'subscription';
    }

    private function sourceLabel(string $sourceType): string
    {
        return match ($sourceType) {
            'sms_topup' => 'SMS Top-up',
            'trial' => 'Free Trial',
            'free_plan' => 'Free Plan',
            default => 'Subscription',
        };
    }

    private function invoiceType(string $paymentType, float $price, string $sourceType): string
    {
        if ($sourceType === 'sms_topup') {
            return 'SMS Top-up';
        }

        if (strtolower($paymentType) === 'trial') {
            return 'Free Trial';
        }

        if ($price <= 0) {
            return 'Free Plan';
        }

        return 'Subscription';
    }

    private function topupStatus(string $status): string
    {
        return match ($status) {
            'paid', 'approved' => 'paid',
            'failed', 'cancelled', 'canceled' => 'failed',
            default => 'pending',
        };
    }

    private function statusGroup(string $status): string
    {
        $value = strtolower($status);

        if (in_array($value, ['succeeded', 'paid', 'success', 'approved'], true)) {
            return 'paid';
        }

        if (in_array($value, ['pending', 'processing', 'pending_mpesa', 'pending_approval'], true)) {
            return 'pending';
        }

        if (in_array($value, ['failed', 'cancelled', 'canceled'], true)) {
            return 'failed';
        }

        return 'other';
    }

    private function stats($records): array
    {
        return [
            'total' => $records->count(),
            'paid' => $records->filter(fn ($record) => $this->statusGroup((string) ($record['payment_status'] ?? '')) === 'paid')->count(),
            'pending' => $records->filter(fn ($record) => $this->statusGroup((string) ($record['payment_status'] ?? '')) === 'pending')->count(),
            'failed' => $records->filter(fn ($record) => $this->statusGroup((string) ($record['payment_status'] ?? '')) === 'failed')->count(),
            'trial' => $records->where('source_type', 'trial')->count(),
            'subscription' => $records->where('source_type', 'subscription')->count(),
            'sms_topup' => $records->where('source_type', 'sms_topup')->count(),
            'free_plan' => $records->where('source_type', 'free_plan')->count(),
            'collected' => $records
                ->filter(fn ($record) => $this->statusGroup((string) ($record['payment_status'] ?? '')) === 'paid')
                ->sum(fn ($record) => (float) ($record['price'] ?? 0)),
        ];
    }

    private function paginate($records, int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage ?: 10, 100));
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $records->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($items, $records->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }
}
