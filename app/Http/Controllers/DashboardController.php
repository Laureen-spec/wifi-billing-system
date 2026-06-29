<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Isp;
use App\Models\MikrotikRouter;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Platform users should stay on the main platform dashboard.
        // WiFi Billing dashboards are tenant / ISP-admin screens only.
        if ($this->isPlatformUser($user)) {
            return redirect()->route('dashboard');
        }

        $isp = $this->currentIsp($user);
        if ($isp) {
            return view('dashboards.isp-admin', [
                'isp' => $isp,
                'packageCount' => $isp->internetPackages()->count(),
                'customerCount' => $isp->customers()->count(),
                'routerCount' => $isp->mikrotikRouters()->count(),
            ]);
        }

        return view('dashboards.isp-admin', [
            'isp' => null,
            'packageCount' => 0,
            'customerCount' => 0,
            'routerCount' => 0,
        ]);
    }

    private function isPlatformUser($user): bool
    {
        return in_array($user->type, ['superadmin', 'super_admin', 'control_isp'], true)
            || $user->hasAnyRole(['superadmin', 'super_admin', 'control_isp']);
    }

    private function currentIsp($user): ?Isp
    {
        if ($user->isp_id) {
            return Isp::find($user->isp_id);
        }

        $isp = Isp::where('admin_user_id', $user->id)->first();
        if ($isp) {
            $user->forceFill(['isp_id' => $isp->id])->save();
            return $isp;
        }

        $isp = Isp::create([
            'name' => $user->name ?: 'StudyRoom ISP',
            'email' => $user->email,
            'phone' => $user->mobile_no ?? null,
            'status' => 'active',
            'admin_user_id' => $user->id,
            'created_by' => $user->created_by ?: $user->id,
            'updated_by' => $user->id,
        ]);

        $user->forceFill(['isp_id' => $isp->id])->save();

        return $isp;
    }
}
