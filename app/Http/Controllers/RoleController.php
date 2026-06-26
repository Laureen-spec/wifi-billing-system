<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-roles')){
            $roles = Role::select('id', 'name', 'label','editable')
                ->where('created_by', creatorId())
                ->when(request('name'), fn($q) => $q->where('name', 'like', '%' . request('name') . '%'))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')))
                ->with(['users' => function($query) {
                    $query->select('id', 'name')->limit(5);
                }])
                ->withCount('permissions')
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('roles/index', [
                'roles' => $roles,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if(Auth::user()->can('create-roles')){
            return Inertia::render('roles/create', [
                'permissions' => $this->assignablePermissions(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreRoleRequest $request)
    {
        if(Auth::user()->can('create-roles')){
            $role = new Role();
            $role->name = $request->name;
            $role->label = $request->label;
            $role->created_by = creatorId();
            $role->save();
            $role->syncPermissions($this->filterAssignablePermissionNames($request->permissions ?? []));
            return redirect()->route('roles.index')->with('success', __('The role has been created successfully.'));
        }
        else{
            return redirect()->route('roles.index')->with('error', __('Permission denied'));
        }
    }

    public function edit(Role $role)
    {
        if(Auth::user()->can('edit-roles')){
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            return Inertia::render('roles/edit', [
                'role' => $role,
                'permissions' => $this->assignablePermissions(),
                'rolePermissions' => $rolePermissions
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        if(Auth::user()->can('edit-roles')){
            $role->update([
                'name' => $request->name,
                'label' => $request->label
            ]);
            $role->syncPermissions($this->filterAssignablePermissionNames($request->permissions ?? []));
            return redirect()->route('roles.index')->with('success', __('The role details are updated successfully.'));
        }
        else{
            return redirect()->route('roles.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Role $role)
    {
        if(Auth::user()->can('delete-roles')){
            if($role->editable == 0){
                return redirect()->route('roles.index')->with('error', __('This role is not editable'));
            }
            $role->delete();
            return redirect()->route('roles.index')->with('success', __('The role has been deleted.'));
        }
        else{
            return redirect()->route('roles.index')->with('error', __('Permission denied'));
        }
    }

    private function assignablePermissions()
    {
        return Permission::query()
            ->select('id', 'name', 'label', 'add_on', 'module', 'guard_name')
            ->where('guard_name', 'web')
            ->orderBy('add_on')
            ->orderBy('module')
            ->orderBy('label')
            ->get()
            ->filter(fn (Permission $permission) => $this->permissionIsAssignable($permission))
            ->map(function (Permission $permission) {
                $addOn = trim((string) $permission->add_on);
                $module = trim((string) $permission->module);
                $label = trim((string) $permission->label);

                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'label' => $label !== '' ? $label : Str::headline(str_replace(['-', '_'], ' ', $permission->name)),
                    'module' => $module !== '' ? $module : 'General',
                    'add_on' => $addOn !== '' ? $addOn : 'Core',
                    'guard_name' => $permission->guard_name,
                ];
            })
            ->groupBy('add_on')
            ->map(fn ($addOnPermissions) => $addOnPermissions->groupBy('module')->map(fn ($modulePermissions) => $modulePermissions->values()))
            ->toArray();
    }

    private function permissionIsAssignable(Permission $permission): bool
    {
        $addOn = trim((string) $permission->add_on);

        if ($addOn === '' || strcasecmp($addOn, 'general') === 0 || strcasecmp($addOn, 'core') === 0) {
            return true;
        }

        if (Module_is_active($addOn)) {
            return true;
        }

        $activeModules = collect(ActivatedModule(creatorId()))
            ->map(fn ($module) => strtolower((string) $module))
            ->all();

        return in_array(strtolower($addOn), $activeModules, true);
    }

    private function filterAssignablePermissionNames(array $permissionNames): array
    {
        $assignable = collect($this->assignablePermissions())
            ->flatMap(fn ($modules) => collect($modules)->flatMap(fn ($permissions) => collect($permissions)->pluck('name')))
            ->flip();

        return collect($permissionNames)
            ->filter(fn ($permissionName) => $assignable->has($permissionName))
            ->values()
            ->all();
    }
}
