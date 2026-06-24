<?php

namespace App\Http\Controllers;

use App\Models\InternetPackage;
use App\Models\Isp;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InternetPackageController extends Controller
{
    public function index(Request $request)
    {
        return view('isp-packages.index', [
            'packages' => $this->packageQuery($request)->with('isp')->latest()->paginate(15),
        ]);
    }

    public function create(Request $request)
    {
        return view('isp-packages.create', [
            'isps' => $this->availableIsps($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $isp = $this->resolveIsp($request, $data['isp_id'] ?? null);

        InternetPackage::create([
            'isp_id' => $isp->id,
            'name' => $data['name'],
            'price' => $data['price'],
            'download_speed_mbps' => $data['download_speed_mbps'] ?? null,
            'upload_speed_mbps' => $data['upload_speed_mbps'] ?? null,
            'billing_cycle' => $data['billing_cycle'],
            'validity_days' => $data['validity_days'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('isp.packages.index')->with('success', 'Internet package saved.');
    }

    public function edit(Request $request, InternetPackage $package)
    {
        $this->authorizeIspRecord($request, $package->isp_id);

        return view('isp-packages.edit', [
            'package' => $package,
            'isps' => $this->availableIsps($request),
        ]);
    }

    public function update(Request $request, InternetPackage $package)
    {
        $this->authorizeIspRecord($request, $package->isp_id);
        $data = $this->validated($request);
        $isp = $this->resolveIsp($request, $data['isp_id'] ?? $package->isp_id);

        $package->update([
            'isp_id' => $isp->id,
            'name' => $data['name'],
            'price' => $data['price'],
            'download_speed_mbps' => $data['download_speed_mbps'] ?? null,
            'upload_speed_mbps' => $data['upload_speed_mbps'] ?? null,
            'billing_cycle' => $data['billing_cycle'],
            'validity_days' => $data['validity_days'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('isp.packages.index')->with('success', 'Internet package updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'isp_id' => ['nullable', 'exists:isps,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'download_speed_mbps' => ['nullable', 'integer', 'min:1'],
            'upload_speed_mbps' => ['nullable', 'integer', 'min:1'],
            'billing_cycle' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'custom'])],
            'validity_days' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function packageQuery(Request $request)
    {
        return InternetPackage::query()->when(! $this->isPlatform($request), function ($query) use ($request) {
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
