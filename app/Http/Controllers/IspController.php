<?php

namespace App\Http\Controllers;

use App\Models\Isp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class IspController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePlatform($request);

        return view('isps.index', [
            'isps' => Isp::with('admin')->latest()->paginate(15),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizePlatform($request);

        return view('isps.create');
    }

    public function store(Request $request)
    {
        $this->authorizePlatform($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'pending', 'suspended'])],
            'admin_name' => ['nullable', 'required_with:admin_email', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['nullable', 'required_with:admin_email', 'string', 'min:8'],
        ]);

        $isp = Isp::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'],
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        if (! empty($data['admin_email'])) {
            $admin = $this->createIspAdmin($isp, $data, $request->user()->id);
            $isp->update(['admin_user_id' => $admin->id]);
        }

        return redirect()->route('isps.index')->with('success', 'ISP created successfully.');
    }

    public function edit(Request $request, Isp $isp)
    {
        $this->authorizePlatform($request);
        $isp->load('admin');

        return view('isps.edit', compact('isp'));
    }

    public function update(Request $request, Isp $isp)
    {
        $this->authorizePlatform($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'pending', 'suspended'])],
            'admin_name' => ['nullable', 'required_with:admin_email', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($isp->admin_user_id)],
            'admin_password' => ['nullable', 'string', 'min:8'],
        ]);

        $isp->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'],
            'updated_by' => $request->user()->id,
        ]);

        if (! empty($data['admin_email'])) {
            $admin = $isp->admin ?: new User();
            $admin->fill([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'type' => 'isp_admin',
                'isp_id' => $isp->id,
                'created_by' => $request->user()->id,
                'email_verified_at' => now(),
                'is_enable_login' => 1,
            ]);
            if (! empty($data['admin_password'])) {
                $admin->password = Hash::make($data['admin_password']);
            }
            if (! $admin->exists && empty($admin->password)) {
                $admin->password = Hash::make('password');
            }
            $admin->save();
            $this->assignRole($admin, 'isp_admin', 'ISP Admin');
            $isp->update(['admin_user_id' => $admin->id]);
        }

        return redirect()->route('isps.index')->with('success', 'ISP updated successfully.');
    }

    private function createIspAdmin(Isp $isp, array $data, int $creatorId): User
    {
        $admin = User::create([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'type' => 'isp_admin',
            'isp_id' => $isp->id,
            'created_by' => $creatorId,
            'email_verified_at' => now(),
            'is_enable_login' => 1,
        ]);

        $this->assignRole($admin, 'isp_admin', 'ISP Admin');

        return $admin;
    }

    private function assignRole(User $user, string $name, string $label): void
    {
        $creatorId = User::where('type', 'superadmin')->value('id')
            ?? User::where('type', 'super_admin')->value('id')
            ?? $user->created_by;

        $role = Role::firstOrCreate(
            ['name' => $name, 'guard_name' => 'web'],
            ['label' => $label, 'editable' => false, 'created_by' => $creatorId]
        );
        $user->assignRole($role);
    }

    private function authorizePlatform(Request $request): void
    {
        $user = $request->user();
        if (! in_array($user->type, ['superadmin', 'super_admin', 'control_isp'], true)
            && ! $user->hasAnyRole(['superadmin', 'super_admin', 'control_isp'])) {
            abort(403, 'Only platform admins can manage ISPs.');
        }
    }
}
