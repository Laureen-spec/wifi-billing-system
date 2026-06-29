<?php

namespace StudyRoomTechLab\Tr069\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Isp;
use App\Models\User;
use App\Services\IspTenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class Tr069Controller extends Controller
{
    protected function authorizeAccess(Request $request, bool $manage = false): void
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($this->isAdminActor($user)) {
            return;
        }

        $permissions = $manage
            ? ['manage-wifi-dashboard', 'manage-isp-customers']
            : ['view-wifi-dashboard', 'manage-wifi-dashboard', 'view-isp-customers', 'manage-isp-customers'];

        abort_unless(
            collect($permissions)->contains(fn (string $permission): bool => $user->can($permission)),
            403
        );
    }

    protected function scopedToTenant(Builder $query, Request $request, string $table): Builder
    {
        if ($this->isPlatform($request)) {
            $ispId = $request->integer('isp_id');

            return $ispId > 0 ? $query->where($table . '.isp_id', $ispId) : $query;
        }

        return $query->where($table . '.isp_id', $this->resolveIsp($request)->id);
    }

    protected function tenantIdForWrite(Request $request): ?int
    {
        $ispId = $request->input('isp_id') ?: $request->query('isp_id');

        return (int) $this->resolveIsp($request, $ispId)->id;
    }

    protected function companyIdForWrite(Request $request): ?int
    {
        $user = $request->user();

        return $user ? (int) ($user->created_by ?: $user->id) : null;
    }

    protected function authorizeIspRecord(Request $request, ?int $ispId): void
    {
        app(IspTenantResolver::class)->authorize($request, $ispId);
    }

    protected function resolveIsp(Request $request, mixed $ispId = null): Isp
    {
        return app(IspTenantResolver::class)->resolve($request, $ispId);
    }

    protected function isPlatform(Request $request): bool
    {
        return app(IspTenantResolver::class)->isPlatform($request);
    }

    protected function pageIsp(Request $request): ?array
    {
        if ($this->isPlatform($request) && ! $request->integer('isp_id')) {
            return null;
        }

        $isp = $this->resolveIsp($request, $request->integer('isp_id') ?: null);

        return [
            'id' => $isp->id,
            'name' => $isp->name,
        ];
    }

    protected function ispOptions(Request $request): array
    {
        $isps = $this->isPlatform($request)
            ? Isp::query()->orderBy('name')->get(['id', 'name'])
            : collect([$this->resolveIsp($request)]);

        return $isps->map(fn (Isp $isp): array => [
            'id' => $isp->id,
            'name' => $isp->name,
        ])->values()->all();
    }

    protected function customerOptions(Request $request): array
    {
        return Customer::query()
            ->when(! $this->isPlatform($request), fn ($query) => $query->where('isp_id', $this->resolveIsp($request)->id))
            ->when($this->isPlatform($request) && $request->integer('isp_id'), fn ($query) => $query->where('isp_id', $request->integer('isp_id')))
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'isp_id', 'name', 'phone', 'username'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'isp_id' => $customer->isp_id,
                'name' => $customer->name ?: 'Customer #' . $customer->id,
                'phone' => $customer->phone,
                'username' => $customer->username,
                'label' => trim(($customer->name ?: 'Customer #' . $customer->id) . ' - ' . ($customer->phone ?: $customer->username ?: 'No contact')),
            ])
            ->values()
            ->all();
    }

    protected function optionList(array $options): array
    {
        return collect($options)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    protected function decodeJson(?string $value, string $field): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        abort_if(json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded), 422, "{$field} must contain valid JSON.");

        return $decoded;
    }

    protected function mask(?string $value): ?string
    {
        return $value ? '********' : null;
    }

    private function isAdminActor(User $user): bool
    {
        $types = ['superadmin', 'super_admin', 'control_isp', 'admin', 'company', 'isp_admin'];

        if (in_array((string) $user->type, $types, true)) {
            return true;
        }

        return method_exists($user, 'hasAnyRole') && $user->hasAnyRole($types);
    }
}
