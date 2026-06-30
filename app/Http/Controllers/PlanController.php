<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\AddOn;
use App\Models\Order;
use App\Classes\Module;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Http\Requests\UpdateModulePriceRequest;
use App\Http\Requests\ApplyCouponRequest;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->can('manage-plans')) {
            return back()->with('error', __('Permission denied'));
        }

        $isSuperAdmin = $this->isSuperAdmin($user);
        $adminSettings = getAdminAllSetting();

        $plans = Plan::query()
            ->when(!$isSuperAdmin, function ($query) use ($user) {
                $query->where('status', true)
                    ->where(function ($q) use ($user) {
                        $q->where('custom_plan', false)
                            ->orWhere('created_by', $user->id);
                    });
            })
            ->with('creator')
            ->withCount(['orders' => function ($query) {
                $query->where('payment_status', 'succeeded');
            }])
            ->latest()
            ->get();

        $activeModules = AddOn::query()
            ->where('for_admin', false)
            ->whereNotIn('module', User::$superadmin_activated_module)
            ->when(!$isSuperAdmin, function ($query) {
                $query->where('is_enable', 1);
            })
            ->select('module', 'name', 'image', 'monthly_price', 'yearly_price', 'is_enable')
            ->orderByDesc('is_enable')
            ->orderBy('name')
            ->get()
            ->map(function ($addon) {
                return [
                    'module' => $addon->module,
                    'alias' => $addon->name,
                    'image' => $this->addonImage($addon),
                    'monthly_price' => $addon->monthly_price ?? 0,
                    'yearly_price' => $addon->yearly_price ?? 0,
                    'is_enable' => (bool) $addon->is_enable,
                ];
            })
            ->toArray();

        $userTrialInfo = null;
        if (!$isSuperAdmin) {
            $userTrialInfo = [
                'is_trial_done' => $user->is_trial_done ?? 0,
            ];
        }

        return Inertia::render('plans/index', [
            'plans' => $plans,
            'canCreate' => ($isSuperAdmin && $user->can('create-plans')),
            'activeModules' => $activeModules,
            'bankTransferEnabled' => $adminSettings['bankTransferEnabled'] ?? false,
            'bankTransferInstructions' => $adminSettings['instructions'] ?? '',
            'userTrialInfo' => $userTrialInfo,
            'planExpireDate' => $user->plan_expire_date,
            'activePlanId' => $user->active_plan,
            'isSuperAdmin' => $isSuperAdmin,
            'mode' => $isSuperAdmin ? 'manage' : 'subscribe',
            'createPackageEnabled' => ($adminSettings['create_package_enabled'] ?? 'on') === 'on',
            'customDesignPackageEnabled' => ($adminSettings['custom_design_package_enabled'] ?? 'on') === 'on',
            'planUsage' => $isSuperAdmin ? null : $this->companyPlanUsage($user),
            'currencySettings' => $this->currencySettings(),
        ]);
    }

    public function create()
    {
        if (Auth::user()->can('create-plans') && Auth::user()->hasRole('superadmin')) {
            $user = Auth::user();

            // Get all enabled addons
            $allAddons = AddOn::where('is_enable', 1)->where('for_admin',false)
                ->select('module', 'name', 'image')
                ->get();

            // Filter modules based on user's subscription
            $availableModules = [];
            if ($user->hasRole('superadmin')) {
                // Super admin can see all modules except superadmin_activated_module
                $availableModules = $allAddons->whereNotIn('module', User::$superadmin_activated_module)->map(function ($addon) {
                    return [
                        'module' => $addon->module,
                        'alias' => $addon->name,
                        'image' => $this->addonImage($addon),
                    ];
                })->values()->toArray();
            } else {
                // Company users see only modules from their subscription
                $userAvailableModules = (new Plan())->getAvailableModulesForUser($user->id);

                $availableModules = $allAddons->whereNotIn('module', User::$superadmin_activated_module)->filter(function ($addon) use ($userAvailableModules) {
                    return in_array($addon->module, $userAvailableModules);
                })->map(function ($addon) {
                    return [
                        'module' => $addon->module,
                        'alias' => $addon->name,
                        'image' => $this->addonImage($addon),
                    ];
                })->values()->toArray();
            }

            return Inertia::render('plans/create', [
                'activeModules' => $availableModules,
                'userSubscriptionInfo' => [
                    'is_superadmin' => $user->hasRole('superadmin'),
                    'active_plan_id' => $user->active_plan,
                    'available_modules_count' => count($availableModules),
                ],
                'currencySettings' => $this->currencySettings(),
            ]);
        } else {
            return redirect()->route('plans.index')->with('error', __('Only Super Admin can manage subscription packages.'));
        }
    }

    public function store(StorePlanRequest $request)
    {
        if (Auth::user()->can('create-plans') && Auth::user()->hasRole('superadmin')) {
            $validated = $request->validated();
            $plan = new Plan();
            $plan->name = $validated['name'];
            $plan->description = $validated['description'];
            $plan->number_of_users = $validated['number_of_users'];
            $plan->storage_limit = (int) ($validated['storage_limit'] ?? 0) * 1024 * 1024;
            $plan->status = $request->boolean('status', true);
            $plan->free_plan = $request->boolean('free_plan', false);
            $plan->modules = $validated['modules'] ?? [];
            $plan->package_price_yearly = $validated['package_price_yearly'];
            $plan->package_price_monthly = $validated['package_price_monthly'];
            $plan->trial = $request->boolean('trial', false);
            $plan->trial_days = $validated['trial_days'] ?? 0;
            if (Schema::hasColumn('plans', 'hotspot_revenue_fee_percent')) {
                $plan->hotspot_revenue_fee_percent = $validated['hotspot_revenue_fee_percent'] ?? 2.5;
            }
            if (Schema::hasColumn('plans', 'router_limit')) {
                $plan->router_limit = null;
            }
            $plan->created_by = creatorId();
            $plan->custom_plan = !Auth::user()->hasRole('superadmin');
            $plan->save();

            return redirect()->route('plans.index')
                ->with('success', __('The plan has been created successfully.'));
        } else {
            return redirect()->route('plans.index')->with('error', __('Permission denied'));
        }
    }

    public function show(Plan $plan)
    {
        return redirect()->back();
    }

    public function edit(Plan $plan)
    {
        if (Auth::user()->can('edit-plans') && (Auth::user()->hasRole('superadmin'))) {
            $user = Auth::user();

            // Get all enabled addons
            $allAddons = AddOn::where('is_enable', 1)->where('for_admin',false)
                ->select('module', 'name', 'image')
                ->get();

            // Filter modules based on user's subscription
            $availableModules = [];
            if ($user->hasRole('superadmin')) {
                // Super admin can see all modules except superadmin_activated_module
                $availableModules = $allAddons->whereNotIn('module',User::$superadmin_activated_module)->map(function ($addon) {
                    return [
                        'module' => $addon->module,
                        'alias' => $addon->name,
                        'image' => $this->addonImage($addon),
                    ];
                })->values()->toArray();
            } else {
                // Company users see only modules from their subscription
                $userAvailableModules = (new Plan())->getAvailableModulesForUser($user->id);

                $availableModules = $allAddons->whereNotIn('module', User::$superadmin_activated_module)->filter(function ($addon) use ($userAvailableModules) {
                    return in_array($addon->module, $userAvailableModules);
                })->map(function ($addon) {
                    return [
                        'module' => $addon->module,
                        'alias' => $addon->name,
                        'image' => $this->addonImage($addon),
                    ];
                })->values()->toArray();
            }

            // Convert storage_limit from KB to GB for display
            $planData = $plan->toArray();
            $planData['storage_limit'] = $plan->storage_limit ? round($plan->storage_limit / (1024 * 1024)) : 0;

            return Inertia::render('plans/edit', [
                'plan' => $planData,
                'activeModules' => $availableModules,
                'userSubscriptionInfo' => [
                    'is_superadmin' => $user->hasRole('superadmin'),
                    'active_plan_id' => $user->active_plan,
                    'available_modules_count' => count($availableModules),
                ],
                'currencySettings' => $this->currencySettings(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        if (Auth::user()->can('edit-plans') && Auth::user()->hasRole('superadmin')) {
            $validated = $request->validated();

            if ($plan->custom_plan) {
                $plan->package_price_yearly = $validated['package_price_yearly'];
                $plan->package_price_monthly = $validated['package_price_monthly'];
                $plan->price_per_user_monthly = $validated['price_per_user_monthly'] ?? 0;
                $plan->price_per_user_yearly = $validated['price_per_user_yearly'] ?? 0;
                $plan->price_per_storage_monthly = $validated['price_per_storage_monthly'] ?? 0;
                $plan->price_per_storage_yearly = $validated['price_per_storage_yearly'] ?? 0;
                if (Schema::hasColumn('plans', 'hotspot_revenue_fee_percent')) {
                    $plan->hotspot_revenue_fee_percent = $validated['hotspot_revenue_fee_percent'] ?? 2.5;
                }
            } else {
                $plan->name = $validated['name'];
                $plan->description = $validated['description'];
                $plan->number_of_users = $validated['number_of_users'];
                $plan->storage_limit = (int) ($validated['storage_limit'] ?? 0) * 1024 * 1024;
                $plan->status = $request->boolean('status', true);
                $plan->free_plan = $request->boolean('free_plan', false);
                $plan->modules = $validated['modules'] ?? [];
                $plan->package_price_yearly = $validated['package_price_yearly'];
                $plan->package_price_monthly = $validated['package_price_monthly'];
                $plan->trial = $request->boolean('trial', false);
                $plan->trial_days = $validated['trial_days'] ?? 0;
                if (Schema::hasColumn('plans', 'hotspot_revenue_fee_percent')) {
                    $plan->hotspot_revenue_fee_percent = $validated['hotspot_revenue_fee_percent'] ?? 2.5;
                }
                if (Schema::hasColumn('plans', 'router_limit')) {
                    $plan->router_limit = null;
                }
            }

            $plan->save();
            $this->syncPlanModulesForExistingCompanies($plan);

            return redirect()->route('plans.index')->with('success', __('The plan details are updated successfully.'));
        } else {
            return redirect()->route('plans.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Plan $plan)
    {
        if (Auth::user()->can('delete-plans') && Auth::user()->hasRole('superadmin')) {
            $userPlan = User::where('active_plan', $plan->id)->first();
            if ($userPlan != null) {
                return redirect()->back()->with('error', __('The company has subscribed to this plan, so it cannot be deleted.'));
            }

            $plan->delete();
            return redirect()->route('plans.index')
                ->with('success', __('The plan has been deleted.'));
        } else {
            return redirect()->route('plans.index')->with('error', __('Permission denied'));
        }
    }

    public function updateModulePrice(UpdateModulePriceRequest $request)
    {
        if (!$this->isSuperAdmin(Auth::user())) {
            return redirect()->route('plans.index')->with('error', __('Only Super Admin can manage subscription packages.'));
        }

        $validated = $request->validated();

        $addon = AddOn::where('module', $validated['module'])->first();

        if (!$addon) {
            return back()->with('error', __('Module not found.'));
        }

        $updateData = [
            'monthly_price' => $validated['monthly_price'],
            'yearly_price' => $validated['yearly_price'],
        ];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        if($request->hasFile('image')){
            $name = $addon->module . '.'.$request->image->getClientOriginalExtension();
            $file = upload_file($request,'image',$name,'add-ons');
            if($file['flag'])
            {
                $updateData['image'] = $file['url'];
            }
            else
            {
                return back()->with('error', $file['msg']);
            }
        }

        $addon->update($updateData);

        (new Module())->moduleCacheForget($validated['module']);

        return back()->with('success', __('Add-On price updated successfully.'));
    }

    public function applyCoupon(ApplyCouponRequest $request)
    {
        $validated = $request->validated();

        $result = applyCouponDiscount($validated['coupon_code'], $validated['total_amount'], Auth::id());
        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ]);
        }
        return response()->json([
            'success' => true,
            'discount_amount' => $result['discount_amount'],
            'final_amount' => $result['final_amount'],
            'coupon' => [
                'code' => $result['coupon']->code,
                'name' => $result['coupon']->name,
                'type' => $result['coupon']->type,
                'discount' => $result['coupon']->discount
            ]
        ]);
    }

    public function subscribe(Plan $plan)
    {
        if (Auth::user()->can('view-plans')) {
            $user = Auth::user();

            // Get enabled addons with details
            $activeModules = AddOn::where('is_enable', 1)->where('for_admin',false)
                ->select('module', 'name', 'image', 'monthly_price', 'yearly_price')
                ->whereNotIn('module',User::$superadmin_activated_module)
                ->get()
                ->map(function ($addon) {
                    return [
                        'module' => $addon->module,
                        'alias' => $addon->name,
                        'image' => $this->addonImage($addon),
                        'monthly_price' => $addon->monthly_price ?? 0,
                        'yearly_price' => $addon->yearly_price ?? 0,
                    ];
                })
                ->toArray();

            // Get user's active modules
            $userActiveModules = UserActiveModule::where('user_id', $user->id)
                ->pluck('module')
                ->toArray();

            return Inertia::render('plans/subscribe', [
                'plan' => $plan,
                'activeModules' => $activeModules,
                'userActiveModules' => $userActiveModules,
                'bankTransferEnabled' => getAdminAllSetting()['bankTransferEnabled'] ?? false,
                'bankTransferInstructions' => getAdminAllSetting()['instructions'] ?? '',
                'mpesaPaymentAvailable' => $this->platformMpesaGatewayConfigured(),
                'mpesaMissingMessage' => __('Payment gateway is not configured. Please contact platform support.'),
                'planExpireDate' => $user->plan_expire_date,
                'planUsage' => $this->companyPlanUsage($user),
                'checkoutInvoice' => $this->planCheckoutInvoice($user, $plan),
                'currencySettings' => $this->currencySettings(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function startTrial(Plan $plan)
    {
        $user = Auth::user();
        // Check if trial already done or active
        if ($user->is_trial_done == 1 || $user->is_trial_done == 2) {
            return back()->with('error', __('Your Plan trial already done.'));
        }

        $counter = [
            'user_counter' => $plan->number_of_users ?? '0',
            'storage_limit' => ($plan->storage_limit ?? 0) / (1024 * 1024),
        ];
        try {
            // Use assignPlan method similar to old code
            $result = assignPlan($plan->id, 'Trial', implode(',', $plan->modules ?? []),$counter,  $user->id);
            if ($result['is_success']) {
                $orderId = strtoupper(substr(uniqid('TR'), -12));

                Order::firstOrCreate(
                    [
                        'order_id' => $orderId,
                        'created_by' => $user->id,
                    ],
                    [
                        'name' => $user->name,
                        'email' => $user->email,
                        'card_number' => null,
                        'card_exp_month' => null,
                        'card_exp_year' => null,
                        'plan_name' => $plan->name ?: 'Trial Package',
                        'plan_id' => $plan->id,
                        'price' => 0,
                        'discount_amount' => 0,
                        'currency' => admin_setting('defaultCurrency') ?? 'KES',
                        'txn_id' => 'TRIAL-' . now()->format('YmdHis'),
                        'payment_type' => 'Trial',
                        'payment_status' => 'succeeded',
                        'receipt' => json_encode([
                            'invoice_type' => 'trial',
                            'duration_days' => $plan->trial_days ?? null,
                            'issued_from' => 'plan_free_trial',
                        ]),
                    ]
                );

                return back()->with('success', __('Your trial has been started. Invoice created under Plan Orders.'));
            } else {
                return back()->with('error', $result['error'] ?? __('Failed to start trial.'));
            }
        } catch (\Exception $e) {
            return back()->with('error', __('Plan Not Found.'));
        }
    }

    public function assignFreePlan(Request $request, Plan $plan)
    {
        $user = Auth::user();

        if (!$plan->free_plan) {
            return back()->with('error', __('This plan is not a free plan.'));
        }

        $duration = $request->duration == 'Year' ? 'Year' : 'Month';
        $counter = [
            'user_counter' => $plan->number_of_users ?? '0',
            'storage_limit' => ($plan->storage_limit ?? 0) / (1024 * 1024),
        ];
        $result = assignPlan($plan->id, $duration, implode(',', $plan->modules ?? []), $counter, $user->id);
        $orderID = strtoupper(substr(uniqid(), -12));

        if ($result['is_success']) {
            $order = new Order();
            $order->order_id = $orderID;
            $order->name = $user->name;
            $order->email = $user->email;
            $order->card_number = null;
            $order->card_exp_month = null;
            $order->card_exp_year = null;
            $order->plan_name = $plan->name;
            $order->plan_id = $plan->id;
            $order->price = 0;
            $order->currency = admin_setting('defaultCurrency') ?? 'USD';
            $order->txn_id = '';
            $order->payment_type = 'Free Plan';
            $order->payment_status = 'succeeded';
            $order->receipt = json_encode([
                'invoice_type' => 'free_plan',
                'duration' => $duration,
                'issued_from' => 'plan_free_subscription',
            ]);
            $order->created_by = $user->id;
            $order->save();
            return back()->with('success', __('Free plan has been assigned successfully.'));
        } else {
            return back()->with('error', $result['error'] ?? 'Failed to assign free plan.');
        }
    }

    public function updatePackageSettings(Request $request)
    {
        if ($this->isSuperAdmin(Auth::user()) && Auth::user()->can('manage-plans')) {
            $request->validate([
                'create_package_enabled' => 'required|string|in:on,off',
                'custom_design_package_enabled' => 'required|string|in:on,off',
            ]);

            setSetting('create_package_enabled', $request->input('create_package_enabled'), null, false);
            setSetting('custom_design_package_enabled', $request->input('custom_design_package_enabled'), null, false);

            return redirect()->back()->with('success', __('Package settings updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    private function isSuperAdmin($user): bool
    {
        return $user && (($user->type ?? null) === 'superadmin' || (method_exists($user, 'hasRole') && $user->hasRole('superadmin')));
    }

    private function syncPlanModulesForExistingCompanies(Plan $plan): void
    {
        $modules = is_array($plan->modules) ? $plan->modules : (json_decode($plan->modules ?? '[]', true) ?: []);

        if (empty($modules)) {
            return;
        }

        User::where('type', 'company')
            ->where('active_plan', $plan->id)
            ->select('id')
            ->chunkById(100, function ($users) use ($modules) {
                foreach ($users as $company) {
                    foreach ($modules as $module) {
                        UserActiveModule::firstOrCreate([
                            'user_id' => $company->id,
                            'module' => $module,
                        ]);
                    }
                }
            });
    }

    private function companyPlanUsage($user): array
    {
        $companyId = $user->id;
        $createdBy = $user->created_by ?: $user->id;
        $tenantIds = array_values(array_filter([$companyId, $createdBy]));

        $customerQuery = function () use ($tenantIds) {
            $query = DB::table('customers');
            if (Schema::hasColumn('customers', 'created_by')) {
                $query->whereIn('created_by', $tenantIds);
            } elseif (Schema::hasColumn('customers', 'company_id')) {
                $query->whereIn('company_id', $tenantIds);
            } elseif (Schema::hasColumn('customers', 'user_id')) {
                $query->whereIn('user_id', $tenantIds);
            }
            return $query;
        };

        $pppoeUsers = 0;
        if (Schema::hasTable('customers')) {
            $query = $customerQuery();
            if (Schema::hasColumn('customers', 'access_type')) {
                $query->where(function ($q) {
                    $q->where('access_type', 'pppoe')
                        ->orWhere('access_type', 'pppoe_static')
                        ->orWhere('access_type', 'PPPoE');
                });
            }
            if (Schema::hasColumn('customers', 'status')) {
                $query->whereIn('status', ['active', 'enabled', 'Active']);
            }
            $pppoeUsers = (int) $query->count();
        }

        $routersCount = 0;
        if (Schema::hasTable('mikrotik_routers')) {
            $routerQuery = DB::table('mikrotik_routers');
            if (Schema::hasColumn('mikrotik_routers', 'created_by')) {
                $routerQuery->whereIn('created_by', $tenantIds);
            } elseif (Schema::hasColumn('mikrotik_routers', 'company_id')) {
                $routerQuery->whereIn('company_id', $tenantIds);
            } elseif (Schema::hasColumn('mikrotik_routers', 'user_id')) {
                $routerQuery->whereIn('user_id', $tenantIds);
            }
            $routersCount = (int) $routerQuery->count();
        }

        $hotspotRevenueThisMonth = 0;
        if (Schema::hasTable('mpesa_transactions')) {
            $paymentQuery = DB::table('mpesa_transactions')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);

            $paymentQuery->where(function ($ownerQuery) use ($tenantIds) {
                if (Schema::hasColumn('mpesa_transactions', 'created_by')) {
                    $ownerQuery->orWhereIn('created_by', $tenantIds);
                }
                if (Schema::hasColumn('mpesa_transactions', 'company_id')) {
                    $ownerQuery->orWhereIn('company_id', $tenantIds);
                }
                if (Schema::hasColumn('mpesa_transactions', 'user_id')) {
                    $ownerQuery->orWhereIn('user_id', $tenantIds);
                }
                if (Schema::hasColumn('mpesa_transactions', 'isp_id')) {
                    $ownerQuery->orWhereIn('isp_id', $tenantIds);
                }
            });

            if (Schema::hasColumn('mpesa_transactions', 'status')) {
                $paymentQuery->whereIn('status', ['paid', 'success', 'successful', 'completed', 'succeeded']);
            }

            if (Schema::hasColumn('mpesa_transactions', 'payment_type')) {
                $paymentQuery->where(function ($q) {
                    $q->whereNull('payment_type')
                        ->orWhere('payment_type', '!=', 'plan_subscription');
                });
            }

            if (Schema::hasColumn('mpesa_transactions', 'customer_id')) {
                $paymentQuery->whereNotNull('customer_id');
            }

            $hotspotRevenueThisMonth = (float) $paymentQuery->sum('amount');
        }

        $activePlan = null;
        if (! empty($user->active_plan)) {
            $activePlan = Plan::find($user->active_plan);
        }

        $hotspotFeePercent = $this->planHotspotFee($activePlan);
        $hotspotFeeThisMonth = round($hotspotRevenueThisMonth * ($hotspotFeePercent / 100), 2);

        return [
            'pppoeUsers' => $pppoeUsers,
            'routersCount' => $routersCount,
            'hotspotRevenueThisMonth' => $hotspotRevenueThisMonth,
            'hotspotRevenueFeePercent' => $hotspotFeePercent,
            'hotspotRevenueFeeThisMonth' => $hotspotFeeThisMonth,
        ];
    }

    private function planCheckoutInvoice(User $user, Plan $plan): array
    {
        $usage = $this->companyPlanUsage($user);
        $hotspotRevenue = (float) ($usage['hotspotRevenueThisMonth'] ?? 0);
        $feePercent = $this->planHotspotFee($plan);
        $feeAmount = round($hotspotRevenue * ($feePercent / 100), 2);

        return [
            'hotspotRevenueThisMonth' => $hotspotRevenue,
            'hotspotRevenueFeePercent' => $feePercent,
            'hotspotRevenueFeeAmount' => $feeAmount,
            'note' => 'Hotspot fee applies only to successful hotspot payments collected through the system for the current month.',
        ];
    }

    private function platformMpesaGatewayConfigured(): bool
    {
        try {
            if (! Schema::hasTable('mpesa_settings')) {
                return false;
            }

            $query = DB::table('mpesa_settings')
                ->whereNull('isp_id')
                ->where('is_active', true);

            if (Schema::hasColumn('mpesa_settings', 'owner_type')) {
                $query->where('owner_type', 'platform');
            }

            $setting = $query->orderByDesc('is_default')->first();

            return (bool) ($setting && ! empty($setting->shortcode));
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    private function currencySettings(): array
    {
        $settings = getAdminAllSetting();

        return [
            'code' => $settings['defaultCurrency'] ?? 'KES',
            'symbol' => $settings['currencySymbol'] ?? 'KES',
            'symbolPosition' => $settings['currencySymbolPosition'] ?? 'before',
            'symbolSpace' => ($settings['currencySymbolSpace'] ?? '1') == '1',
            'decimalPlaces' => (int) ($settings['decimalFormat'] ?? 0),
            'decimalSeparator' => $settings['decimalSeparator'] ?? '.',
            'thousandSeparator' => ($settings['thousandsSeparator'] ?? ',') === 'none' ? '' : ($settings['thousandsSeparator'] ?? ','),
        ];
    }

    private function planHotspotFee($plan): float
    {
        if ($plan && isset($plan->hotspot_revenue_fee_percent)) {
            return (float) $plan->hotspot_revenue_fee_percent;
        }

        return 2.5;
    }

    private function addonImage(AddOn $addon): string
    {
        if ($addon->image) {
            return $addon->image;
        }

        if (file_exists(base_path('packages/studyroomtechlab/' . $addon->module . '/favicon.png'))) {
            return url('/packages/studyroomtechlab/' . $addon->module . '/favicon.png');
        }

        return url('/packages/workdo/' . $addon->module . '/favicon.png');
    }
}
