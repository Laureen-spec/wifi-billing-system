<?php

namespace StudyRoomTechLab\LandingPage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use App\Models\AddOn;
use App\Models\Plan;
use App\Models\User;
use App\Classes\Module;
use StudyRoomTechLab\LandingPage\Models\LandingPageSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\LandingPage\Models\CustomPage;
use Illuminate\Support\Facades\Auth;
use StudyRoomTechLab\LandingPage\Http\Requests\StoreLandingPageRequest;

class LandingPageController extends Controller
{
    public function index(Request $request)
    {
        $settings = Cache::remember('landing_page_settings', 3600, function () {
            return LandingPageSetting::first();
        });

        if (!isLandingPageEnabled()) {
            $enableRegistration = admin_setting('enableRegistration');

            return Inertia::render('auth/login', [
                'canResetPassword' => Route::has('password.request'),
                'status' => session('status'),
                'enableRegistration' => $enableRegistration === 'on',
            ]);
        }

        $enableRegistration = admin_setting('enableRegistration');

        $settingsData = $settings ? $settings->toArray() : [];
        $settingsData['enable_registration'] = $enableRegistration === 'on';
        $settingsData['is_authenticated'] = $request->user() !== null;

        return Inertia::render('LandingPage/Landing', [
            'auth' => [
                'user' => $request->user(),
                'lang' => app()->getLocale()
            ],
            'settings' => $settingsData
        ]);
    }

    public function addons(Request $request)
    {
        $landingPageSettings = LandingPageSetting::first();
        $user = $request->user();
        $canManageAddons = $user && ($user->hasRole('superadmin') || $user->can('manage-plans'));

        $query = AddOn::query()
            ->where('for_admin', false)
            ->whereNotIn('module', User::$superadmin_activated_module);

        if (! $canManageAddons) {
            $query->where('is_enable', true);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('module', 'like', '%' . $request->search . '%')
                    ->orWhere('package_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category')) {
            $query->where('module', $request->category);
        }

        if ($request->filled('status') && $canManageAddons) {
            if ($request->status === 'enabled') {
                $query->where('is_enable', true);
            } elseif ($request->status === 'disabled') {
                $query->where('is_enable', false);
            }
        }

        if ($request->filled('price')) {
            $priceColumn = $request->get('price_type', 'monthly') === 'yearly' ? 'yearly_price' : 'monthly_price';

            switch ($request->price) {
                case 'free':
                    $query->where(function ($q) use ($priceColumn) {
                        $q->whereNull($priceColumn)->orWhere($priceColumn, 0);
                    });
                    break;
                case '0-50':
                    $query->whereBetween($priceColumn, [0.00, 50]);
                    break;
                case '50-100':
                    $query->whereBetween($priceColumn, [50.00, 100]);
                    break;
                case '100+':
                    $query->where($priceColumn, '>', 100);
                    break;
            }
        }

        $priceColumn = $request->get('price_type', 'monthly') === 'yearly' ? 'yearly_price' : 'monthly_price';

        switch ($request->get('sort', 'name')) {
            case 'price_low':
                $query->orderBy($priceColumn, 'asc');
                break;
            case 'price_high':
                $query->orderBy($priceColumn, 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'status':
                $query->orderByDesc('is_enable')->orderBy('name');
                break;
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        $perPage = $landingPageSettings->config_sections['sections']['addons']['per_page'] ?? 20;
        $addons = $query->paginate($perPage)->withQueryString();

        $addons->getCollection()->transform(function (AddOn $addon) {
            $assignedCount = Plan::query()
                ->whereNotNull('modules')
                ->where('modules', 'like', '%"' . $addon->module . '"%')
                ->count();

            return [
                'id' => $addon->id,
                'module' => $addon->module,
                'name' => $addon->name,
                'image' => $this->addonImage($addon),
                'monthly_price' => $addon->monthly_price ?? 0,
                'yearly_price' => $addon->yearly_price ?? 0,
                'package_name' => $addon->package_name,
                'is_enable' => (bool) $addon->is_enable,
                'assigned_count' => $assignedCount,
            ];
        });

        $categories = AddOn::where('for_admin', false)
            ->whereNotIn('module', User::$superadmin_activated_module)
            ->distinct()
            ->pluck('module')
            ->filter()
            ->values();

        $settingsData = $landingPageSettings ? $landingPageSettings->toArray() : [];
        $settingsData['is_authenticated'] = $request->user() !== null;
        $settingsData['enable_registration'] = admin_setting('enableRegistration') === 'on';

        return Inertia::render('LandingPage/Addons', [
            'addons' => $addons,
            'settings' => $settingsData,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'price', 'price_type', 'sort', 'status']),
            'canManageAddons' => (bool) $canManageAddons,
            'addonStats' => [
                'total' => AddOn::where('for_admin', false)->whereNotIn('module', User::$superadmin_activated_module)->count(),
                'enabled' => AddOn::where('for_admin', false)->whereNotIn('module', User::$superadmin_activated_module)->where('is_enable', true)->count(),
                'disabled' => AddOn::where('for_admin', false)->whereNotIn('module', User::$superadmin_activated_module)->where('is_enable', false)->count(),
            ],
        ]);
    }

    public function pricing(Request $request)
    {
        // Get active plans from the main app
        $plans = Plan::where('status', true)
            ->where('custom_plan', false)
            ->withCount('orders')
            ->get();

        // Get active modules/addons
        $activeModules = AddOn::where('is_enable', true)
            ->whereNotIn('module', User::$superadmin_activated_module)
            ->select('module', 'name as alias', 'image', 'monthly_price', 'yearly_price')
            ->get();

        $landingPageSettings = LandingPageSetting::first();
        $enableRegistration = admin_setting('enableRegistration');

        $settingsData = $landingPageSettings ? $landingPageSettings->toArray() : [];
        $settingsData['enable_registration'] = $enableRegistration === 'on';
        $settingsData['is_authenticated'] = $request->user() !== null;

        return Inertia::render('LandingPage/Pricing', [
            'plans' => $plans->map(function($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'package_price_monthly' => $plan->package_price_monthly,
                    'package_price_yearly' => $plan->package_price_yearly,
                    'number_of_users' => $plan->number_of_users,
                    'storage_limit' => $plan->storage_limit,
                    'modules' => $plan->modules ?? [],
                    'free_plan' => $plan->free_plan,
                    'trial' => $plan->trial,
                    'trial_days' => $plan->trial_days,
                    'orders_count' => $plan->orders_count
                ];
            }),
            'activeModules' => $activeModules,
            'settings' => $settingsData,

        ]);
    }

    public function toggleAddon(Request $request, AddOn $addon)
    {
        $user = $request->user();

        if (! $user || (! $user->hasRole('superadmin') && ! $user->can('manage-plans'))) {
            abort(403, 'Permission denied.');
        }

        $data = $request->validate([
            'is_enable' => ['required', 'boolean'],
        ]);

        $addon->forceFill([
            'is_enable' => (bool) $data['is_enable'],
        ])->save();

        (new Module())->moduleCacheForget($addon->module);

        return back()->with('success', $addon->is_enable
            ? __('Add-on enabled successfully.')
            : __('Add-on disabled successfully.'));
    }

    public function settings()
    {
        if(Auth::user()->can('manage-landing-page')){
            $settings = LandingPageSetting::first();
            $customPages = CustomPage::where('is_active', true)->select('id', 'title', 'slug')->get();
            return Inertia::render('LandingPage/Settings', [
                'settings' => $settings ?: [
                    'company_name' => '',
                    'contact_email' => '',
                    'contact_phone' => '',
                    'contact_address' => '',
                    'config_sections' => [
                        'sections' => [],
                        'section_visibility' => [
                            'header' => true,
                            'hero' => true,
                            'stats' => true,
                            'features' => true,
                            'modules' => true,
                            'benefits' => true,
                            'gallery' => true,
                            'cta' => true,
                            'footer' => true
                        ],
                        'section_order' => ['header', 'hero', 'stats', 'features', 'modules', 'benefits', 'gallery', 'cta', 'footer']
                    ]
                ],
                'customPages' => $customPages
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreLandingPageRequest $request)
    {
        if(Auth::user()->can('edit-landing-page')){
            $validated = $request->validated();

            // Handle image paths - store only filename
            if (isset($validated['config_sections']['sections'])) {
                $this->processImagePaths($validated['config_sections']['sections']);
            }

            LandingPageSetting::updateOrCreate(['id' => 1], $validated);

            return back()->with('success', __('Settings saved successfully'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
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

    private function processImagePaths(&$sections)
    {
        foreach ($sections as $sectionKey => &$sectionData) {
            if (is_array($sectionData)) {
                // Handle single images (hero, cta)
                if (isset($sectionData['image'])) {
                    $sectionData['image'] = $this->processImagePath($sectionData['image']);
                }
                
                // Handle gallery images array
                if (isset($sectionData['images']) && is_array($sectionData['images'])) {
                    $sectionData['images'] = array_map([$this, 'processImagePath'], $sectionData['images']);
                }
            }
        }
    }

    private function processImagePath($imagePath)
    {
        if (strpos($imagePath, 'packages/StudyRoomTechLab') !== false) {
            return $imagePath;
        }
        return basename($imagePath);
    }
}