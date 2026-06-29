<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PlanModuleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,$moduleName = null): Response
    {
        $user = Auth::user();
        if (!$user) {
            return $next($request);
        }

        $moduleNames = [];
        if ($moduleName !== null) {
            $moduleNames = array_values(array_filter(explode('-', $moduleName)));
        }

        // Super Admin is a platform owner. It should not get an operational bypass
        // into tenant/company-only modules such as WiFi Billing.
        $isSuperAdmin = $user->type === 'superadmin' || $user->hasRole('superadmin');
        if ($isSuperAdmin) {
            if (!empty($moduleNames)) {
                foreach ($moduleNames as $m) {
                    if (module_is_active($m, $user->id)) {
                        return $next($request);
                    }
                }

                return redirect()->route('dashboard')->with('error', __('Tenant module is only available inside a company account.'));
            }

            return $next($request);
        } elseif ($user->hasRole('company')) {
            $planExpired = $user->plan_expire_date && now()->gt($user->plan_expire_date);
            $isTrialActive = ($user->is_trial_done == 2);

            if ($planExpired || ($user->active_plan == 0)) {
                $allowedRoutes = ['users.leave-impersonation','plans.index', 'plans.subscribe', 'plans.start-trial', 'plans.apply-coupon', 'payment.*.store','payment.*.status', 'mpesa-payment.plan-subscription.*', 'bank-transfer.index','plans.assign-free'];
                if (!$request->routeIs($allowedRoutes)) {
                    if ($planExpired && $isTrialActive) {
                        // Trial expired
                        return redirect()->route('plans.index')
                            ->with('error', __("Your trial has expired. Please subscribe to a plan"));
                    } else {
                        // Paid plan expired
                        return redirect()->route('plans.index')
                            ->with('error', __('Your plan has expired. Please renew your subscription'));
                    }
                }
            }
        } else {
            // For sub-users - check creator's plan
            $creator = $user->createdBy;
            if ($creator && ($creator->plan_expire_date && now()->gt($creator->plan_expire_date) || ($creator->active_plan == 0))) {
                Auth::logout();
                return redirect()->route('login')
                    ->with('error', __('Company plan has expired. Please contact your administrator.'));
            }
        }

        if (!empty($moduleNames)) {
            foreach ($moduleNames as $m) {
                if (module_is_active($m, $user->id)) {
                    return $next($request);
                }
            }

            return redirect()->route('dashboard')->with('error', __('Permission denied '));
        }

        $response = $next($request);
        return $response;
    }
}
