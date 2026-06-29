<?php

namespace StudyRoomTechLab\Loyalty\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Loyalty\Models\LoyaltyVoucher;
use StudyRoomTechLab\Loyalty\Services\LoyaltyManager;

class LoyaltyVoucherController extends LoyaltyController
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status', 'all'),
            'isp_id' => $request->integer('isp_id') ?: null,
        ];

        $query = $this->scopedToTenant(
            LoyaltyVoucher::query()->with(['customer', 'isp']),
            $request,
            'loyalty_vouchers'
        );

        if (array_key_exists((string) $filters['status'], LoyaltyVoucher::statuses())) {
            $query->where('status', $filters['status']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function ($query) use ($search): void {
                $query->where('voucher_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        $vouchers = $query
            ->latest()
            ->paginate((int) $request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (LoyaltyVoucher $voucher): array => $this->voucherPayload($voucher));

        return Inertia::render('loyalty/vouchers', [
            'isp' => $this->pageIsp($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'filters' => $filters,
            'vouchers' => $vouchers,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function redeem(Request $request, LoyaltyVoucher $voucher, LoyaltyManager $manager): RedirectResponse
    {
        $this->authorizeAccess($request, true);
        $this->authorizeIspRecord($request, (int) $voucher->isp_id);

        $manager->redeemVoucher($voucher);

        return back()->with('success', 'Loyalty voucher marked as redeemed.');
    }

    private function statusOptions(): array
    {
        return collect(['all' => 'All'] + LoyaltyVoucher::statuses())
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function voucherPayload(LoyaltyVoucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'voucher_code' => $voucher->voucher_code,
            'points_used' => (int) $voucher->points_used,
            'package_name' => $voucher->package_name,
            'duration_minutes' => $voucher->duration_minutes,
            'status' => $voucher->status,
            'expires_at' => optional($voucher->expires_at)->toDateTimeString(),
            'redeemed_at' => optional($voucher->redeemed_at)->toDateTimeString(),
            'created_at' => optional($voucher->created_at)->toDateTimeString(),
            'customer' => $voucher->customer ? $this->customerPayload($voucher->customer) : null,
            'isp' => $voucher->isp ? ['id' => $voucher->isp->id, 'name' => $voucher->isp->name] : null,
            'redeem_url' => route('loyalty.vouchers.redeem', $voucher),
        ];
    }
}
