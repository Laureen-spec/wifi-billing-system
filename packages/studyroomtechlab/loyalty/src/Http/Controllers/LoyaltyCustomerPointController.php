<?php

namespace StudyRoomTechLab\Loyalty\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Loyalty\Models\LoyaltyCustomerPoint;
use StudyRoomTechLab\Loyalty\Models\LoyaltyPointTransaction;
use StudyRoomTechLab\Loyalty\Services\LoyaltyManager;

class LoyaltyCustomerPointController extends LoyaltyController
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'isp_id' => $request->integer('isp_id') ?: null,
        ];

        $query = $this->scopedToTenant(
            LoyaltyCustomerPoint::query()->with(['customer', 'isp']),
            $request,
            'loyalty_customer_points'
        );

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->whereHas('customer', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query
            ->orderByDesc('current_points')
            ->paginate((int) $request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (LoyaltyCustomerPoint $point): array => $this->pointPayload($point));

        return Inertia::render('loyalty/customers', [
            'isp' => $this->pageIsp($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'filters' => $filters,
            'customers' => $customers,
            'recentTransactions' => $this->recentTransactions($request),
        ]);
    }

    public function awardManualPoints(Request $request, Customer $customer, LoyaltyManager $manager): RedirectResponse
    {
        $this->authorizeAccess($request, true);

        if (! $this->isPlatform($request)) {
            $this->authorizeIspRecord($request, (int) $customer->isp_id);
        }

        $data = $request->validate([
            'points' => ['required', 'integer', 'min:1', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $manager->awardManualPoints($customer, (int) $data['points'], $data['description'] ?? null);

        return back()->with('success', 'Manual loyalty points awarded.');
    }

    private function recentTransactions(Request $request): array
    {
        return $this->scopedToTenant(
            LoyaltyPointTransaction::query()->with(['customer', 'isp']),
            $request,
            'loyalty_point_transactions'
        )
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (LoyaltyPointTransaction $transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'points' => (int) $transaction->points,
                'description' => $transaction->description,
                'customer' => $transaction->customer ? $this->customerPayload($transaction->customer) : null,
                'created_at' => optional($transaction->created_at)->toDateTimeString(),
            ])
            ->all();
    }

    private function pointPayload(LoyaltyCustomerPoint $point): array
    {
        return [
            'id' => $point->id,
            'customer_id' => $point->customer_id,
            'current_points' => (int) $point->current_points,
            'lifetime_points' => (int) $point->lifetime_points,
            'redeemed_points' => (int) $point->redeemed_points,
            'last_awarded_at' => optional($point->last_awarded_at)->toDateTimeString(),
            'customer' => $point->customer ? $this->customerPayload($point->customer) : null,
            'isp' => $point->isp ? ['id' => $point->isp->id, 'name' => $point->isp->name] : null,
            'manual_points_url' => route('loyalty.customers.manual-points', $point->customer_id),
        ];
    }
}
