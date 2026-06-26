<?php

namespace App\Http\Controllers;

use App\Models\InternetPackage;
use App\Models\Isp;
use App\Models\MikrotikRouter;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class InternetPackageController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->can('view-internet-packages') || $request->user()->can('manage-internet-packages'),
            403
        );

        $filter = $request->string('tab')->toString()
            ?: $request->string('type')->toString()
            ?: 'all';

        $search = trim($request->string('q')->toString());

        $typeColumn = Schema::hasColumn('internet_packages', 'package_type')
            ? 'package_type'
            : 'access_type';

        $baseQuery = $this->packageQuery($request);

        $packageStats = [
            'all' => (clone $baseQuery)->count(),
            'hotspot' => (clone $baseQuery)->where($typeColumn, 'hotspot')->count(),
            'pppoe' => (clone $baseQuery)->where($typeColumn, 'pppoe')->count(),
            'data_bundle' => (clone $baseQuery)->where($typeColumn, 'data_bundle')->count(),
            'free_trial' => (clone $baseQuery)->where($typeColumn, 'free_trial')->count(),
        ];

        $packagesQuery = $this->packageQuery($request)->with(['isp', 'mikrotikRouters']);

        if ($search !== '') {
            $packagesQuery->where(function ($query) use ($search, $typeColumn) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('billing_cycle', 'like', "%{$search}%")
                    ->orWhere($typeColumn, 'like', "%{$search}%");
            });
        }

        if (in_array($filter, ['hotspot', 'pppoe', 'data_bundle', 'free_trial'], true)) {
            $packagesQuery->where($typeColumn, $filter);
        } else {
            $filter = 'all';
        }

        return view('isp-packages.index', [
            'packages' => $packagesQuery->latest()->paginate(15)->withQueryString(),
            'packageStats' => $packageStats,
            'activeFilter' => $filter,
            'searchTerm' => $search,
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('create-internet-packages'), 403);

        return view('isp-packages.create', [
            'routers' => $this->availableRouters($request),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('create-internet-packages'), 403);

        $data = $this->validated($request);
        $enableBurst = $request->boolean('enable_burst');
        $enableSchedule = $request->boolean('enable_schedule');
        $isp = $this->resolveIsp($request);

        $attributes = [
            'isp_id' => $isp->id,
            'name' => $data['name'],
            'price' => $data['price'],
            'download_speed_mbps' => $data['download_speed_mbps'] ?? null,
            'upload_speed_mbps' => $data['upload_speed_mbps'] ?? null,
            'billing_cycle' => $data['billing_cycle'],
            'validity_days' => $data['validity_days'],
            'status' => $data['status'],
            'enable_burst' => $enableBurst,
            'burst_download' => $enableBurst ? ($data['burst_download'] ?? null) : null,
            'burst_upload' => $enableBurst ? ($data['burst_upload'] ?? null) : null,
            'burst_threshold' => $enableBurst ? ($data['burst_threshold'] ?? null) : null,
            'burst_time' => $enableBurst ? ($data['burst_time'] ?? null) : null,
            'priority' => $enableBurst ? ($data['priority'] ?? null) : null,
            'limit_at' => $enableBurst ? ($data['limit_at'] ?? null) : null,
            'enable_schedule' => $enableSchedule,
            'schedule_start' => $enableSchedule ? ($data['schedule_start'] ?? null) : null,
            'schedule_end' => $enableSchedule ? ($data['schedule_end'] ?? null) : null,
            'schedule_days' => $enableSchedule ? ($data['schedule_days'] ?? null) : null,
            'schedule_recurring' => $enableSchedule ? $request->boolean('schedule_recurring', true) : false,
            'available_on_all_mikrotik' => $request->boolean('available_on_all_mikrotik'),
            'hidden_from_client' => $request->boolean('hidden_from_client'),
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ];

        $attributes = $this->withPackageTypeAttributes($attributes, $data['package_type']);

        $package = InternetPackage::create($attributes);

        $this->syncRouters($package, $isp->id, $data['router_ids'] ?? []);

        return redirect()->route('isp.packages.index')->with('success', 'Internet package saved.');
    }

    public function edit(Request $request, InternetPackage $package)
    {
        abort_unless($request->user()->can('edit-internet-packages'), 403);

        $this->authorizeIspRecord($request, $package->isp_id);

        return view('isp-packages.edit', [
            'package' => $package->load('mikrotikRouters'),
            'routers' => $this->availableRouters($request, $package->isp_id),
        ]);
    }

    public function update(Request $request, InternetPackage $package)
    {
        abort_unless($request->user()->can('edit-internet-packages'), 403);

        $this->authorizeIspRecord($request, $package->isp_id);

        $data = $this->validated($request);
        $enableBurst = $request->boolean('enable_burst');
        $enableSchedule = $request->boolean('enable_schedule');
        $isp = $this->resolveIsp($request);

        $attributes = [
            'isp_id' => $isp->id,
            'name' => $data['name'],
            'price' => $data['price'],
            'download_speed_mbps' => $data['download_speed_mbps'] ?? null,
            'upload_speed_mbps' => $data['upload_speed_mbps'] ?? null,
            'billing_cycle' => $data['billing_cycle'],
            'validity_days' => $data['validity_days'],
            'status' => $data['status'],
            'enable_burst' => $enableBurst,
            'burst_download' => $enableBurst ? ($data['burst_download'] ?? null) : null,
            'burst_upload' => $enableBurst ? ($data['burst_upload'] ?? null) : null,
            'burst_threshold' => $enableBurst ? ($data['burst_threshold'] ?? null) : null,
            'burst_time' => $enableBurst ? ($data['burst_time'] ?? null) : null,
            'priority' => $enableBurst ? ($data['priority'] ?? null) : null,
            'limit_at' => $enableBurst ? ($data['limit_at'] ?? null) : null,
            'enable_schedule' => $enableSchedule,
            'schedule_start' => $enableSchedule ? ($data['schedule_start'] ?? null) : null,
            'schedule_end' => $enableSchedule ? ($data['schedule_end'] ?? null) : null,
            'schedule_days' => $enableSchedule ? ($data['schedule_days'] ?? null) : null,
            'schedule_recurring' => $enableSchedule ? $request->boolean('schedule_recurring', true) : false,
            'available_on_all_mikrotik' => $request->boolean('available_on_all_mikrotik'),
            'hidden_from_client' => $request->boolean('hidden_from_client'),
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ];

        $attributes = $this->withPackageTypeAttributes($attributes, $data['package_type']);

        $package->update($attributes);

        $this->syncRouters($package, $isp->id, $data['router_ids'] ?? []);

        return redirect()->route('isp.packages.index')->with('success', 'Internet package updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'package_type' => ['required', Rule::in(['hotspot', 'pppoe', 'data_bundle', 'free_trial'])],
            'price' => ['required', 'numeric', 'min:0'],
            'download_speed_mbps' => ['nullable', 'numeric', 'min:0'],
            'upload_speed_mbps' => ['nullable', 'numeric', 'min:0'],
            'billing_cycle' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'custom'])],
            'validity_days' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['active', 'inactive'])],

            'enable_burst' => ['nullable', 'boolean'],
            'burst_download' => ['nullable', 'numeric', 'min:0'],
            'burst_upload' => ['nullable', 'numeric', 'min:0'],
            'burst_threshold' => ['nullable', 'string', 'max:100'],
            'burst_time' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:8'],
            'limit_at' => ['nullable', 'string', 'max:100'],

            'enable_schedule' => ['nullable', 'boolean'],
            'schedule_start' => ['nullable'],
            'schedule_end' => ['nullable'],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => ['nullable', 'string'],
            'schedule_recurring' => ['nullable', 'boolean'],

            'available_on_all_mikrotik' => ['nullable', 'boolean'],
            'hidden_from_client' => ['nullable', 'boolean'],
            'router_ids' => ['nullable', 'array'],
            'router_ids.*' => ['integer', 'exists:mikrotik_routers,id'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function withPackageTypeAttributes(array $attributes, string $packageType): array
    {
        if (Schema::hasColumn('internet_packages', 'package_type')) {
            $attributes['package_type'] = $packageType;
        }

        if (Schema::hasColumn('internet_packages', 'access_type')) {
            $attributes['access_type'] = $packageType === 'pppoe' ? 'pppoe' : 'hotspot';
        }

        return $attributes;
    }

    private function syncRouters(InternetPackage $package, int $ispId, array $routerIds): void
    {
        if ($package->available_on_all_mikrotik) {
            $package->mikrotikRouters()->sync([]);
            return;
        }

        $validRouterIds = MikrotikRouter::where('isp_id', $ispId)
            ->whereIn('id', $routerIds)
            ->pluck('id')
            ->all();

        $package->mikrotikRouters()->sync($validRouterIds);
    }

    private function packageQuery(Request $request)
    {
        return InternetPackage::query()
            ->when(! $this->isPlatform($request), function ($query) use ($request) {
                $query->where('isp_id', $this->resolveIsp($request)->id);
            });
    }

    private function availableRouters(Request $request, $ispId = null)
    {
        if ($this->isPlatform($request)) {
            return MikrotikRouter::query()
                ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
                ->orderBy('name')
                ->get();
        }

        return MikrotikRouter::where('isp_id', $this->resolveIsp($request)->id)
            ->orderBy('name')
            ->get();
    }

    private function resolveIsp(Request $request, $ispId = null): Isp
    {
        return app(IspTenantResolver::class)->resolve($request, $ispId);
    }

    private function authorizeIspRecord(Request $request, ?int $ispId): void
    {
        app(IspTenantResolver::class)->authorize($request, $ispId);
    }

    private function isPlatform(Request $request): bool
    {
        return app(IspTenantResolver::class)->isPlatform($request);
    }
}
