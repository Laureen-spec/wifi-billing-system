<?php

namespace App\Services;

use App\Models\Isp;
use App\Models\User;
use Illuminate\Http\Request;

class IspTenantResolver
{
    public function resolve(Request $request, mixed $ispId = null): Isp
    {
        if ($this->isPlatform($request)) {
            $isp = $ispId ? Isp::find($ispId) : Isp::first();
            abort_if(! $isp, 403, 'No ISP is assigned to this account.');

            return $isp;
        }

        $user = $request->user();
        $owner = $this->tenantOwner($user);

        if ($ispId) {
            $isp = Isp::find($ispId);
            abort_if(! $isp, 403, 'No ISP is assigned to this account.');
            $this->authorize($request, (int) $isp->id);
            $this->rememberIsp($user, $owner, $isp);

            return $isp;
        }

        $isp = $this->findTenantIsp($user, $owner);

        if (! $isp) {
            $isp = $this->createTenantIsp($owner, $user);
        }

        $this->rememberIsp($user, $owner, $isp);

        return $isp;
    }

    public function authorize(Request $request, ?int $ispId): void
    {
        abort_if(! $ispId, 403, 'No ISP is assigned to this account.');

        if ($this->isPlatform($request)) {
            return;
        }

        $user = $request->user();
        $owner = $this->tenantOwner($user);
        $allowed = ((int) $user->isp_id === $ispId)
            || ((int) $owner->isp_id === $ispId)
            || Isp::where('id', $ispId)
                ->where(function ($query) use ($user, $owner) {
                    $query->where('admin_user_id', $owner->id)
                        ->orWhere('created_by', $owner->id)
                        ->orWhere('admin_user_id', $user->id);
                })
                ->exists();

        abort_unless($allowed, 403);
    }

    public function isPlatform(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return in_array($user->type, ['superadmin', 'super_admin', 'control_isp'], true)
            || $user->hasAnyRole(['superadmin', 'super_admin', 'control_isp']);
    }

    private function tenantOwner(User $user): User
    {
        if (in_array($user->type, ['company', 'isp_admin'], true) || $user->hasAnyRole(['company', 'isp_admin'])) {
            return $user;
        }

        if ($user->created_by) {
            $creator = User::find($user->created_by);
            if ($creator) {
                return $creator;
            }
        }

        return $user;
    }

    private function findTenantIsp(User $user, User $owner): ?Isp
    {
        if ($user->isp_id) {
            $isp = Isp::find($user->isp_id);
            if ($isp) {
                return $isp;
            }
        }

        if ($owner->isp_id) {
            $isp = Isp::find($owner->isp_id);
            if ($isp) {
                return $isp;
            }
        }

        return Isp::where('admin_user_id', $owner->id)->first()
            ?: Isp::where('created_by', $owner->id)->orderBy('id')->first()
            ?: Isp::where('admin_user_id', $user->id)->first();
    }

    private function createTenantIsp(User $owner, User $actor): Isp
    {
        $isp = Isp::create([
            'name' => $owner->name ?: 'StudyRoom ISP',
            'email' => $owner->email,
            'phone' => $owner->mobile_no ?? null,
            'status' => 'active',
            'admin_user_id' => $owner->id,
            'created_by' => $owner->id,
            'updated_by' => $actor->id,
        ]);

        $this->rememberIsp($actor, $owner, $isp);

        return $isp;
    }

    private function rememberIsp(User $user, User $owner, Isp $isp): void
    {
        if ((int) $owner->isp_id !== (int) $isp->id) {
            $owner->forceFill(['isp_id' => $isp->id])->save();
        }

        if ((int) $user->isp_id !== (int) $isp->id) {
            $user->forceFill(['isp_id' => $isp->id])->save();
        }
    }
}
