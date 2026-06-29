<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\MenuVisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MenuVisibilityController extends Controller
{
    public function __construct(private readonly MenuVisibilityService $menuVisibility)
    {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        if (! $this->menuVisibility->isSuperAdmin($request->user())) {
            return back()->with('error', __('Permission denied'));
        }

        return Inertia::render('super-admin/menu-control', [
            'menus' => $this->menuVisibility->settingsForManage(Auth::id()),
            'roleLabels' => [
                'visible_to_superadmin' => __('Super Admin'),
                'visible_to_admin' => __('Admin / Company'),
                'visible_to_isp_admin' => __('ISP Admin'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (! $this->menuVisibility->isSuperAdmin($request->user())) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'menus' => ['required', 'array'],
            'menus.*.menu_key' => ['required', 'string', 'max:120'],
            'menus.*.visible_to_superadmin' => ['sometimes', 'boolean'],
            'menus.*.visible_to_admin' => ['sometimes', 'boolean'],
            'menus.*.visible_to_isp_admin' => ['sometimes', 'boolean'],
            'menus.*.block_route_access' => ['sometimes', 'boolean'],
        ]);

        $this->menuVisibility->updateSettings($validated['menus'], Auth::id());

        return back()->with('success', __('Menu visibility settings updated successfully.'));
    }
}
