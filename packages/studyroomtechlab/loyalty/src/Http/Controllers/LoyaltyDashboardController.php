<?php

namespace StudyRoomTechLab\Loyalty\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Loyalty\Models\LoyaltyCustomerPoint;
use StudyRoomTechLab\Loyalty\Models\LoyaltyPointTransaction;
use StudyRoomTechLab\Loyalty\Models\LoyaltyVoucher;

class LoyaltyDashboardController extends LoyaltyController
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $transactions = $this->scopedToTenant(
            LoyaltyPointTransaction::query(),
            $request,
            'loyalty_point_transactions'
        );
        $points = $this->scopedToTenant(
            LoyaltyCustomerPoint::query(),
            $request,
            'loyalty_customer_points'
        );
        $vouchers = $this->scopedToTenant(
            LoyaltyVoucher::query(),
            $request,
            'loyalty_vouchers'
        );

        $recentActivity = $this->scopedToTenant(
            LoyaltyPointTransaction::query()->with(['customer', 'isp']),
            $request,
            'loyalty_point_transactions'
        )
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (LoyaltyPointTransaction $transaction): array => $this->activityPayload($transaction))
            ->all();

        $topCustomers = $this->scopedToTenant(
            LoyaltyCustomerPoint::query()->with(['customer', 'isp']),
            $request,
            'loyalty_customer_points'
        )
            ->orderByDesc('lifetime_points')
            ->limit(8)
            ->get()
            ->map(fn (LoyaltyCustomerPoint $point): array => $this->customerPointPayload($point))
            ->all();

        return Inertia::render('loyalty/index', [
            'isp' => $this->pageIsp($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'filters' => [
                'isp_id' => $request->integer('isp_id') ?: null,
            ],
            'stats' => [
                'total_points_issued' => (int) (clone $transactions)
                    ->whereIn('type', [LoyaltyPointTransaction::TYPE_EARNED, LoyaltyPointTransaction::TYPE_ADJUSTED])
                    ->sum('points'),
                'active_customers' => (int) (clone $points)->where('current_points', '>', 0)->count(),
                'vouchers_generated' => (int) (clone $vouchers)->count(),
                'redeemed_vouchers' => (int) (clone $vouchers)->where('status', LoyaltyVoucher::STATUS_REDEEMED)->count(),
            ],
            'recentActivity' => $recentActivity,
            'topCustomers' => $topCustomers,
        ]);
    }

    private function activityPayload(LoyaltyPointTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'points' => (int) $transaction->points,
            'description' => $transaction->description,
            'customer' => $transaction->customer ? $this->customerPayload($transaction->customer) : null,
            'isp' => $transaction->isp ? ['id' => $transaction->isp->id, 'name' => $transaction->isp->name] : null,
            'created_at' => optional($transaction->created_at)->toDateTimeString(),
        ];
    }

    private function customerPointPayload(LoyaltyCustomerPoint $point): array
    {
        return [
            'id' => $point->id,
            'current_points' => (int) $point->current_points,
            'lifetime_points' => (int) $point->lifetime_points,
            'redeemed_points' => (int) $point->redeemed_points,
            'last_awarded_at' => optional($point->last_awarded_at)->toDateTimeString(),
            'customer' => $point->customer ? $this->customerPayload($point->customer) : null,
            'isp' => $point->isp ? ['id' => $point->isp->id, 'name' => $point->isp->name] : null,
        ];
    }
}
