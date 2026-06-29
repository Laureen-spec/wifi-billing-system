<?php

namespace StudyRoomTechLab\Loyalty\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Isp;
use App\Models\User;
use App\Services\IspTenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class LoyaltyController extends Controller
{
    protected function authorizeAccess(Request $request, bool $manage = false): void
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($this->isLoyaltyAdmin($user)) {
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

    protected function tenantIdForWrite(Request $request): int
    {
        $ispId = $request->input('isp_id') ?: $request->query('isp_id');

        return (int) $this->resolveIsp($request, $ispId)->id;
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

    protected function availableIsps(Request $request)
    {
        return $this->isPlatform($request)
            ? Isp::query()->orderBy('name')->get(['id', 'name'])
            : collect([$this->resolveIsp($request)]);
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
        return $this->availableIsps($request)
            ->map(fn (Isp $isp): array => [
                'id' => $isp->id,
                'name' => $isp->name,
            ])
            ->values()
            ->all();
    }

    protected function customerForRequest(Request $request, int $customerId): Customer
    {
        $query = Customer::query()->whereKey($customerId);

        if (! $this->isPlatform($request)) {
            $query->where('isp_id', $this->resolveIsp($request)->id);
        }

        return $query->firstOrFail();
    }

    protected function customerPayload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name ?: 'Customer #' . $customer->id,
            'phone' => $customer->phone,
            'username' => $customer->username,
            'email' => $customer->email,
            'isp_id' => $customer->isp_id,
        ];
    }

    protected function isLoyaltyAdmin(User $user): bool
    {
        $types = ['superadmin', 'super_admin', 'control_isp', 'admin', 'company', 'isp_admin'];

        if (in_array((string) $user->type, $types, true)) {
            return true;
        }

        return method_exists($user, 'hasAnyRole') && $user->hasAnyRole($types);
    }
}
