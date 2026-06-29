<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user || ! $user->can('manage-orders')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', 'all'),
            'per_page' => (int) $request->query('per_page', 10),
            'sort' => (string) $request->query('sort', 'id'),
            'direction' => strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc',
        ];

        $filters['per_page'] = in_array($filters['per_page'], [10, 25, 50, 100], true) ? $filters['per_page'] : 10;

        $baseQuery = Order::query()
            ->with(['plan', 'user', 'total_coupon_used.coupon_detail'])
            ->when($user->type !== 'superadmin', function ($query) use ($user) {
                $query->where('created_by', $user->id);
            });

        $summaryQuery = clone $baseQuery;
        $tabQuery = clone $baseQuery;

        $orders = (clone $baseQuery)
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('order_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('plan_name', 'like', "%{$search}%")
                        ->orWhere('payment_type', 'like', "%{$search}%")
                        ->orWhere('payment_status', 'like', "%{$search}%")
                        ->orWhere('receipt', 'like', "%{$search}%")
                        ->orWhere('txn_id', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] !== 'all', function ($query) use ($filters) {
                $this->applyStatusFilter($query, $filters['status']);
            });

        $allowedSorts = ['id', 'order_id', 'name', 'plan_name', 'price', 'payment_status', 'payment_type', 'created_at'];
        $sort = in_array($filters['sort'], $allowedSorts, true) ? $filters['sort'] : 'id';

        $orders = $orders
            ->orderBy($sort, $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString();

        $orders->through(fn (Order $order) => $this->mapOrder($order));

        return Inertia::render('orders/index', [
            'orders' => $orders,
            'summary' => $this->summary($summaryQuery),
            'statusTabs' => $this->statusTabs($tabQuery),
            'filters' => $filters,
        ]);
    }

    private function applyStatusFilter($query, string $status): void
    {
        match ($status) {
            'pending' => $query->whereIn('payment_status', ['pending', 'waiting', 'processing', 'unpaid']),
            'paid' => $query->whereIn('payment_status', ['paid', 'succeeded', 'success', 'confirmed', 'completed']),
            'failed' => $query->whereIn('payment_status', ['failed', 'cancelled', 'canceled', 'rejected']),
            'expired' => $query->whereIn('payment_status', ['expired']),
            'manual' => $query->whereIn('payment_type', ['Manual', 'manual', 'Cash', 'cash', 'Bank', 'bank', 'Bank Transfer', 'bank_transfer']),
            'refunded' => $query->whereIn('payment_status', ['refunded', 'reversed', 'chargeback']),
            default => null,
        };
    }

    private function summary($query): array
    {
        $all = (clone $query)->count();
        $pending = (clone $query)->whereIn('payment_status', ['pending', 'waiting', 'processing', 'unpaid'])->count();
        $paid = (clone $query)->whereIn('payment_status', ['paid', 'succeeded', 'success', 'confirmed', 'completed'])->count();
        $failed = (clone $query)->whereIn('payment_status', ['failed', 'cancelled', 'canceled', 'rejected'])->count();
        $collected = (float) (clone $query)
            ->whereIn('payment_status', ['paid', 'succeeded', 'success', 'confirmed', 'completed'])
            ->sum('price');

        return [
            [
                'key' => 'all',
                'title' => 'All Orders',
                'value' => (string) $all,
                'description' => 'total checkout records',
                'tone' => 'slate',
            ],
            [
                'key' => 'pending',
                'title' => 'Pending Payment',
                'value' => (string) $pending,
                'description' => 'waiting for confirmation',
                'tone' => 'amber',
            ],
            [
                'key' => 'paid',
                'title' => 'Paid Orders',
                'value' => (string) $paid,
                'description' => 'confirmed collections',
                'tone' => 'green',
            ],
            [
                'key' => 'failed',
                'title' => 'Failed Orders',
                'value' => (string) $failed,
                'description' => 'failed or cancelled attempts',
                'tone' => 'red',
            ],
            [
                'key' => 'collected',
                'title' => 'Total Collected',
                'value' => (string) $collected,
                'description' => 'paid orders only',
                'tone' => 'blue',
                'isMoney' => true,
            ],
        ];
    }

    private function statusTabs($query): array
    {
        return [
            ['key' => 'all', 'label' => 'All', 'count' => (clone $query)->count()],
            ['key' => 'pending', 'label' => 'Pending', 'count' => (clone $query)->whereIn('payment_status', ['pending', 'waiting', 'processing', 'unpaid'])->count()],
            ['key' => 'paid', 'label' => 'Paid', 'count' => (clone $query)->whereIn('payment_status', ['paid', 'succeeded', 'success', 'confirmed', 'completed'])->count()],
            ['key' => 'failed', 'label' => 'Failed', 'count' => (clone $query)->whereIn('payment_status', ['failed', 'cancelled', 'canceled', 'rejected'])->count()],
            ['key' => 'expired', 'label' => 'Expired', 'count' => (clone $query)->whereIn('payment_status', ['expired'])->count()],
            ['key' => 'manual', 'label' => 'Manual', 'count' => (clone $query)->whereIn('payment_type', ['Manual', 'manual', 'Cash', 'cash', 'Bank', 'bank', 'Bank Transfer', 'bank_transfer'])->count()],
            ['key' => 'refunded', 'label' => 'Refunded', 'count' => (clone $query)->whereIn('payment_status', ['refunded', 'reversed', 'chargeback'])->count()],
        ];
    }

    private function mapOrder(Order $order): array
    {
        $paymentStatus = $this->normalizePaymentStatus((string) $order->payment_status);
        $paymentType = $order->payment_type ?: 'Unknown';
        $receipt = $order->receipt ?: $order->txn_id;

        return [
            'id' => $order->id,
            'order_id' => $order->order_id,
            'customer_name' => $order->name ?: $order->user?->name ?: 'Unknown customer',
            'customer_email' => $order->email ?: $order->user?->email,
            'source' => $this->sourceFromPaymentType($paymentType),
            'plan_name' => $order->plan_name ?: $order->plan?->name ?: 'No package',
            'method' => $paymentType,
            'amount' => (float) $order->price,
            'original_price' => $order->original_price ?? null,
            'discount_amount' => (float) ($order->discount_amount ?? 0),
            'currency' => $order->currency ?: 'KES',
            'payment_status' => $paymentStatus,
            'payment_status_raw' => $order->payment_status ?: 'unknown',
            'posting_status' => $paymentStatus === 'paid' ? 'Posted' : 'Not Posted',
            'provisioning_status' => $paymentStatus === 'paid' ? 'Provisioned' : 'Not Provisioned',
            'receipt' => $receipt ?: null,
            'txn_id' => $order->txn_id,
            'created_at' => optional($order->created_at)->toDateTimeString(),
            'created_at_display' => optional($order->created_at)->format('M d, Y H:i'),
            'coupon_code' => $order->total_coupon_used?->coupon_detail?->code,
            'coupon_name' => $order->total_coupon_used?->coupon_detail?->name,
            'timeline' => $this->timeline($order, $paymentStatus),
        ];
    }

    private function normalizePaymentStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match (true) {
            in_array($status, ['paid', 'succeeded', 'success', 'confirmed', 'completed'], true) => 'paid',
            in_array($status, ['pending', 'waiting', 'processing', 'unpaid', ''], true) => 'pending',
            in_array($status, ['failed', 'cancelled', 'canceled', 'rejected'], true) => 'failed',
            in_array($status, ['expired'], true) => 'expired',
            in_array($status, ['refunded', 'reversed', 'chargeback'], true) => 'refunded',
            default => $status ?: 'unknown',
        };
    }

    private function sourceFromPaymentType(string $paymentType): string
    {
        $value = strtolower($paymentType);

        return match (true) {
            str_contains($value, 'mpesa') || str_contains($value, 'm-pesa') => 'Gateway',
            str_contains($value, 'cash') || str_contains($value, 'manual') || str_contains($value, 'bank') => 'Manual',
            default => 'Checkout',
        };
    }

    private function timeline(Order $order, string $paymentStatus): array
    {
        $createdAt = optional($order->created_at)->format('M d, Y H:i');

        $events = [
            [
                'title' => 'Order created',
                'description' => 'Checkout record was created.',
                'date' => $createdAt,
                'status' => 'completed',
            ],
        ];

        if ($paymentStatus === 'paid') {
            $events[] = [
                'title' => 'Payment confirmed',
                'description' => 'Payment was confirmed and the order was posted.',
                'date' => $createdAt,
                'status' => 'completed',
            ];
            $events[] = [
                'title' => 'Provisioning completed',
                'description' => 'The order is marked as provisioned.',
                'date' => $createdAt,
                'status' => 'completed',
            ];
        } elseif ($paymentStatus === 'failed') {
            $events[] = [
                'title' => 'Payment failed',
                'description' => 'The gateway or manual confirmation marked this order as failed.',
                'date' => $createdAt,
                'status' => 'failed',
            ];
        } else {
            $events[] = [
                'title' => 'Waiting for payment',
                'description' => 'No confirmed payment receipt has been posted yet.',
                'date' => null,
                'status' => 'pending',
            ];
        }

        return $events;
    }
}
