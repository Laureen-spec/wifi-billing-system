<?php

namespace StudyRoomTechLab\Loyalty\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Loyalty\Models\LoyaltyRewardRule;

class LoyaltyRewardRuleController extends LoyaltyController
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $filters = [
            'status' => $request->query('status', 'all'),
            'trigger_type' => $request->query('trigger_type', 'all'),
            'isp_id' => $request->integer('isp_id') ?: null,
        ];

        $query = $this->scopedToTenant(
            LoyaltyRewardRule::query()->with('isp'),
            $request,
            'loyalty_reward_rules'
        );

        if ($filters['status'] === 'active') {
            $query->where('is_active', true);
        } elseif ($filters['status'] === 'inactive') {
            $query->where('is_active', false);
        }

        if (array_key_exists((string) $filters['trigger_type'], LoyaltyRewardRule::triggers())) {
            $query->where('trigger_type', $filters['trigger_type']);
        }

        $rules = $query
            ->orderByDesc('is_active')
            ->latest()
            ->paginate((int) $request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (LoyaltyRewardRule $rule): array => $this->rulePayload($rule));

        return Inertia::render('loyalty/rules/index', [
            'isp' => $this->pageIsp($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'rules' => $rules,
            'filters' => $filters,
            'triggerOptions' => $this->triggerOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeAccess($request, true);

        return Inertia::render('loyalty/rules/form', [
            'mode' => 'create',
            'rule' => $this->blankRule($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'triggerOptions' => $this->triggerOptions(),
            'storeUrl' => route('loyalty.rules.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request, true);

        $data = $this->validated($request);
        $ispId = $this->tenantIdForWrite($request);

        LoyaltyRewardRule::query()->create(array_merge($data, [
            'isp_id' => $ispId,
            'auto_voucher' => $request->boolean('auto_voucher'),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        return redirect()
            ->route('loyalty.rules.index', $this->routeScope($request, $ispId))
            ->with('success', 'Loyalty reward rule created.');
    }

    public function edit(Request $request, LoyaltyRewardRule $rule): Response
    {
        $this->authorizeAccess($request, true);
        $this->authorizeIspRecord($request, (int) $rule->isp_id);

        return Inertia::render('loyalty/rules/form', [
            'mode' => 'edit',
            'rule' => $this->rulePayload($rule),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'triggerOptions' => $this->triggerOptions(),
            'updateUrl' => route('loyalty.rules.update', $rule),
        ]);
    }

    public function update(Request $request, LoyaltyRewardRule $rule): RedirectResponse
    {
        $this->authorizeAccess($request, true);
        $this->authorizeIspRecord($request, (int) $rule->isp_id);

        $data = $this->validated($request);
        $ispId = $this->tenantIdForWrite($request);

        $rule->update(array_merge($data, [
            'isp_id' => $ispId,
            'auto_voucher' => $request->boolean('auto_voucher'),
            'is_active' => $request->boolean('is_active'),
            'updated_by' => $request->user()->id,
        ]));

        return redirect()
            ->route('loyalty.rules.index', $this->routeScope($request, $ispId))
            ->with('success', 'Loyalty reward rule updated.');
    }

    public function destroy(Request $request, LoyaltyRewardRule $rule): RedirectResponse
    {
        $this->authorizeAccess($request, true);
        $this->authorizeIspRecord($request, (int) $rule->isp_id);

        $rule->delete();

        return back()->with('success', 'Loyalty reward rule removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'isp_id' => ['nullable', 'integer', 'exists:isps,id'],
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'string', Rule::in(array_keys(LoyaltyRewardRule::triggers()))],
            'points_value' => ['required', 'integer', 'min:0', 'max:1000000'],
            'amount_step' => ['nullable', 'numeric', 'min:0.01', 'max:999999999.99'],
            'renewal_count' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'auto_voucher' => ['nullable', 'boolean'],
            'voucher_threshold' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'voucher_package_name' => ['nullable', 'string', 'max:255'],
            'voucher_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function triggerOptions(): array
    {
        return collect(LoyaltyRewardRule::triggers())
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function blankRule(Request $request): array
    {
        return [
            'id' => null,
            'isp_id' => $this->isPlatform($request) ? ($request->integer('isp_id') ?: null) : $this->resolveIsp($request)->id,
            'name' => '',
            'trigger_type' => LoyaltyRewardRule::TRIGGER_SUCCESSFUL_PAYMENT,
            'points_value' => 10,
            'amount_step' => '',
            'renewal_count' => '',
            'auto_voucher' => false,
            'voucher_threshold' => '',
            'voucher_package_name' => '',
            'voucher_duration_minutes' => '',
            'is_active' => true,
        ];
    }

    private function rulePayload(LoyaltyRewardRule $rule): array
    {
        return [
            'id' => $rule->id,
            'isp_id' => $rule->isp_id,
            'name' => $rule->name,
            'trigger_type' => $rule->trigger_type,
            'trigger_label' => LoyaltyRewardRule::triggers()[$rule->trigger_type] ?? ucfirst(str_replace('_', ' ', $rule->trigger_type)),
            'points_value' => (int) $rule->points_value,
            'amount_step' => $rule->amount_step !== null ? (float) $rule->amount_step : null,
            'renewal_count' => $rule->renewal_count,
            'auto_voucher' => (bool) $rule->auto_voucher,
            'voucher_threshold' => $rule->voucher_threshold,
            'voucher_package_name' => $rule->voucher_package_name,
            'voucher_duration_minutes' => $rule->voucher_duration_minutes,
            'is_active' => (bool) $rule->is_active,
            'isp' => $rule->isp ? ['id' => $rule->isp->id, 'name' => $rule->isp->name] : null,
            'edit_url' => route('loyalty.rules.edit', $rule),
            'destroy_url' => route('loyalty.rules.destroy', $rule),
        ];
    }

    private function routeScope(Request $request, int $ispId): array
    {
        return $this->isPlatform($request) ? ['isp_id' => $ispId] : [];
    }
}
