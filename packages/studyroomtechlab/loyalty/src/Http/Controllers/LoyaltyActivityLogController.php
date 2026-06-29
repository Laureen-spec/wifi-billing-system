<?php

namespace StudyRoomTechLab\Loyalty\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Loyalty\Models\LoyaltyPointTransaction;

class LoyaltyActivityLogController extends LoyaltyController
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $filters = [
            'type' => $request->query('type', 'all'),
            'q' => trim((string) $request->query('q', '')),
            'isp_id' => $request->integer('isp_id') ?: null,
        ];

        $query = $this->scopedToTenant(
            LoyaltyPointTransaction::query()->with(['customer', 'isp', 'creator']),
            $request,
            'loyalty_point_transactions'
        );

        if (array_key_exists((string) $filters['type'], LoyaltyPointTransaction::types())) {
            $query->where('type', $filters['type']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function ($query) use ($search): void {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('source_type', 'like', "%{$search}%")
                    ->orWhere('source_id', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query
            ->latest()
            ->paginate((int) $request->integer('per_page', 20))
            ->withQueryString()
            ->through(fn (LoyaltyPointTransaction $transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'points' => (int) $transaction->points,
                'source_type' => $transaction->source_type,
                'source_id' => $transaction->source_id,
                'description' => $transaction->description,
                'expires_at' => optional($transaction->expires_at)->toDateTimeString(),
                'expired_at' => optional($transaction->expired_at)->toDateTimeString(),
                'created_at' => optional($transaction->created_at)->toDateTimeString(),
                'customer' => $transaction->customer ? $this->customerPayload($transaction->customer) : null,
                'isp' => $transaction->isp ? ['id' => $transaction->isp->id, 'name' => $transaction->isp->name] : null,
                'created_by' => $transaction->creator?->name,
            ]);

        return Inertia::render('loyalty/logs', [
            'isp' => $this->pageIsp($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'filters' => $filters,
            'logs' => $logs,
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    private function typeOptions(): array
    {
        return collect(['all' => 'All'] + LoyaltyPointTransaction::types())
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }
}
