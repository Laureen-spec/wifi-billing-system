<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Isp;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        return view('isp-customers.index', [
            'customers' => $this->customerQuery($request)->with(['isp', 'internetPackage'])->latest()->paginate(15),
        ]);
    }

    public function create(Request $request)
    {
        $isp = $this->isPlatform($request) ? null : $this->resolveIsp($request);

        return view('isp-customers.create', [
            'isps' => $this->availableIsps($request),
            'packages' => InternetPackage::query()
                ->when($isp, fn ($query) => $query->where('isp_id', $isp->id))
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $isp = $this->resolveIsp($request, $data['isp_id'] ?? null);
        $packageId = $data['internet_package_id'] ?? null;
        $this->authorizePackage($isp->id, $packageId);

        Customer::create([
            'isp_id' => $isp->id,
            'internet_package_id' => $packageId,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'location' => $data['location'] ?? null,
            'address' => $data['address'] ?? null,
            'connection_status' => $data['connection_status'],
            'billing_status' => $data['billing_status'],
            'monthly_amount' => $data['monthly_amount'],
            'installation_date' => $data['installation_date'] ?? null,
            'next_due_date' => $data['next_due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('isp.customers.index')->with('success', 'Customer saved.');
    }

    public function edit(Request $request, Customer $customer)
    {
        $this->authorizeIspRecord($request, $customer->isp_id);

        return view('isp-customers.edit', [
            'customer' => $customer,
            'isps' => $this->availableIsps($request),
            'packages' => InternetPackage::where('isp_id', $customer->isp_id)->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeIspRecord($request, $customer->isp_id);
        $data = $this->validated($request);
        $isp = $this->resolveIsp($request, $data['isp_id'] ?? $customer->isp_id);
        $packageId = $data['internet_package_id'] ?? null;
        $this->authorizePackage($isp->id, $packageId);

        $customer->update([
            'isp_id' => $isp->id,
            'internet_package_id' => $packageId,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'location' => $data['location'] ?? null,
            'address' => $data['address'] ?? null,
            'connection_status' => $data['connection_status'],
            'billing_status' => $data['billing_status'],
            'monthly_amount' => $data['monthly_amount'],
            'installation_date' => $data['installation_date'] ?? null,
            'next_due_date' => $data['next_due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('isp.customers.index')->with('success', 'Customer updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'isp_id' => ['nullable', 'exists:isps,id'],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'connection_status' => ['required', Rule::in(['pending', 'active', 'suspended', 'disconnected'])],
            'billing_status' => ['required', Rule::in(['paid', 'unpaid', 'overdue'])],
            'monthly_amount' => ['required', 'numeric', 'min:0'],
            'installation_date' => ['nullable', 'date'],
            'next_due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function customerQuery(Request $request)
    {
        return Customer::query()->when(! $this->isPlatform($request), function ($query) use ($request) {
            $query->where('isp_id', $this->resolveIsp($request)->id);
        });
    }

    private function availableIsps(Request $request)
    {
        return $this->isPlatform($request) ? Isp::orderBy('name')->get() : collect([$this->resolveIsp($request)]);
    }

    private function resolveIsp(Request $request, $ispId = null): Isp
    {
        if ($this->isPlatform($request)) {
            $isp = $ispId ? Isp::find($ispId) : Isp::first();
        } else {
            $user = $request->user();
            $isp = $user->isp_id ? Isp::find($user->isp_id) : Isp::where('admin_user_id', $user->id)->first();
        }

        abort_if(! $isp, 403, 'No ISP is assigned to this account.');
        $this->authorizeIspRecord($request, $isp->id);

        return $isp;
    }

    private function authorizePackage(int $ispId, $packageId): void
    {
        if ($packageId) {
            abort_unless(InternetPackage::where('id', $packageId)->where('isp_id', $ispId)->exists(), 403);
        }
    }

    private function authorizeIspRecord(Request $request, int $ispId): void
    {
        if ($this->isPlatform($request)) {
            return;
        }

        $user = $request->user();
        abort_if((int) $user->isp_id !== $ispId && ! Isp::where('id', $ispId)->where('admin_user_id', $user->id)->exists(), 403);
    }

    private function isPlatform(Request $request): bool
    {
        $user = $request->user();
        return in_array($user->type, ['superadmin', 'super_admin', 'control_isp'], true)
            || $user->hasAnyRole(['superadmin', 'super_admin', 'control_isp']);
    }
}
