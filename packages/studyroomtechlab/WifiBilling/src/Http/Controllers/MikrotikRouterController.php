<?php

namespace StudyRoomTechLab\WifiBilling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Isp;
use App\Models\MikrotikRouter;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use StudyRoomTechLab\WifiBilling\Services\MikrotikRouterLiveService;

class MikrotikRouterController extends Controller
{
    public function index(Request $request)
    {
        $this->requirePermission($request, ['view-mikrotik-routers', 'manage-mikrotik-routers']);
        $baseQuery = $this->routerQuery($request);
        $filteredQuery = $this->applyRouterFilters(clone $baseQuery, $request);

        return Inertia::render('wifi-billing/routers/index', [
            'routers' => (clone $filteredQuery)
                ->with('isp')
                ->withCount('internetPackages')
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (MikrotikRouter $router): array => $this->routerPayload($router)),
            'stats' => $this->routerStats(clone $baseQuery),
            'createUrl' => route('wifi-billing.routers.create'),
            'filters' => [
                'q' => trim((string) $request->query('q')),
                'status' => strtolower((string) $request->query('status')),
                'connection_type' => strtolower((string) $request->query('connection_type')),
                'hotspot_files_status' => strtolower((string) $request->query('hotspot_files_status')),
            ],
        ]);
    }

    public function show(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['view-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);
        return view('wifi-billing::isp-routers.show', ['router' => $router->load(['isp', 'internetPackages'])]);
    }

    public function create(Request $request)
    {
        $this->requirePermission($request, ['create-mikrotik-routers', 'manage-mikrotik-routers']);

        return Inertia::render('wifi-billing/routers/create', [
            'defaults' => [
                'api_port' => 8728,
                'status' => 'active',
                'connection_type' => 'agent',
                'host' => 'pending-link',
            ],
            'isps' => $this->availableIsps($request)
                ->map(fn (Isp $isp): array => ['id' => $isp->id, 'name' => $isp->name])
                ->values(),
            'storeUrl' => route('wifi-billing.routers.store'),
        ]);
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, ['create-mikrotik-routers', 'manage-mikrotik-routers']);
        $data = $this->validated($request);
        $isp = $this->resolveIsp($request, $data['isp_id'] ?? null);

        $router = MikrotikRouter::create([
            'isp_id' => $isp->id,
            'name' => $data['name'],
            'identity' => $data['name'],
            'host' => $data['host'] ?: 'pending-link',
            'api_port' => $data['api_port'] ?: 8728,
            'username' => $data['username'] ?: 'billing-api',
            'password' => $data['password'] ?: Str::random(32),
            'connection_type' => $data['connection_type'] ?? 'agent',
            'provision_token' => Str::random(80),
            'heartbeat_token' => Str::random(80),
            'provision_status' => 'pending',
            'status' => $data['status'] ?? 'active',
            'hotspot_status' => 'provision_pending',
            'hotspot_files_status' => 'provision_pending',
            'pppoe_status' => 'provision_pending',
            'sync_status' => 'pending',
            'time_sync_status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('wifi-billing.routers.setup-script', $router)->with('success', 'MikroTik created. Copy the provisioning command into MikroTik Terminal.');
    }

    public function edit(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);
        return view('wifi-billing::isp-routers.edit', ['router' => $router, 'isps' => $this->availableIsps($request)]);
    }

    public function update(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);
        $data = $this->validated($request, false);
        $isp = $this->resolveIsp($request, $data['isp_id'] ?? $router->isp_id);

        $update = [
            'isp_id' => $isp->id,
            'name' => $data['name'],
            'identity' => $data['name'],
            'host' => $data['host'] ?: $router->host ?: 'pending-link',
            'api_port' => $data['api_port'] ?: 8728,
            'username' => $data['username'] ?: $router->username ?: 'billing-api',
            'connection_type' => $data['connection_type'] ?? $router->connection_type ?? 'agent',
            'status' => $data['status'] ?? $router->status,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ];
        if (! empty($data['password'])) {
            $update['password'] = $data['password'];
        }
        if (! $router->provision_token) { $update['provision_token'] = Str::random(80); }
        if (! $router->heartbeat_token) { $update['heartbeat_token'] = Str::random(80); }
        $router->update($update);

        return redirect()->route('isp.routers.show', $router)->with('success', 'MikroTik settings updated.');
    }

    public function setupScript(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['view-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);
        $this->ensureTokens($router);
        $ros = $request->query('ros', '6');
        $provisionUrl = route('provision.show', $router->provision_token) . '?ros=' . urlencode((string) $ros);
        $mode = str_starts_with(strtolower($provisionUrl), 'https://') ? 'https' : 'http';
        $command = '/tool fetch mode=' . $mode . ' url="' . $provisionUrl . '" dst-path=billing-system.rsc;:delay 2s;/import billing-system.rsc;';
        return Inertia::render('wifi-billing/routers/setup-script', [
            'router' => [
                'id' => $router->id,
                'name' => $router->name,
                'host' => $router->host,
                'api_port' => $router->api_port,
                'connection_type' => $router->connection_type,
                'status' => $router->status,
                'last_seen_at' => $router->last_seen_at?->diffForHumans(),
            ],
            'command' => $command,
            'provisionUrl' => $provisionUrl,
            'ros' => $ros,
            'rosVersions' => ['6' => 'RouterOS 6.x', '7' => 'RouterOS 7.x'],
        ]);
    }

    public function live(Request $request, MikrotikRouter $router, MikrotikRouterLiveService $liveService)
    {
        $this->requirePermission($request, ['view-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);
        return view('wifi-billing::isp-routers.live', ['router' => $router->load('isp'), 'snapshot' => $liveService->snapshot($router)]);
    }

    public function liveSessions(Request $request)
    {
        $this->requirePermission($request, ['view-mikrotik-routers', 'manage-mikrotik-routers']);
        $baseQuery = $this->routerQuery($request);
        $filteredQuery = $this->applyRouterFilters(clone $baseQuery, $request);

        $activeUsers = $this->activeUserQuery($request)
            ->with(['internetPackage', 'mikrotikRouter'])
            ->latest('last_online_at')
            ->limit(100)
            ->get()
            ->map(fn (Customer $customer): array => $this->activeUserPayload($customer))
            ->values();

        $stats = $this->routerStats(clone $baseQuery);
        $stats['active_users'] = $activeUsers->count();
        $stats['hotspot_users'] = $activeUsers->where('access_type', 'hotspot')->count();
        $stats['pppoe_users'] = $activeUsers->where('access_type', 'pppoe')->count();

        return Inertia::render('wifi-billing/live-sessions/index', [
            'activeUsers' => $activeUsers,
            'routers' => (clone $filteredQuery)
                ->with('isp')
                ->withCount('internetPackages')
                ->latest('last_seen_at')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (MikrotikRouter $router): array => $this->routerPayload($router)),
            'stats' => $stats,
            'filters' => [
                'q' => trim((string) $request->query('q')),
                'status' => strtolower((string) $request->query('status')),
                'connection_type' => strtolower((string) $request->query('connection_type')),
                'hotspot_files_status' => strtolower((string) $request->query('hotspot_files_status')),
            ],
        ]);
    }

    public function reprovision(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);
        $router->update(['provision_token' => Str::random(80), 'heartbeat_token' => $router->heartbeat_token ?: Str::random(80), 'provision_status' => 'pending', 'updated_by' => $request->user()->id]);
        return redirect()->route('wifi-billing.routers.setup-script', $router)->with('success', 'New provisioning command generated.');
    }

    public function syncHotspot(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);
        $router->update(['hotspot_files_status' => 'sync_pending', 'sync_status' => 'hotspot_files_pending']);
        return redirect()->route('wifi-billing.routers.setup-script', $router)->with('success', 'Open setup script and rerun command to sync all hotspot files.');
    }

    private function validated(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'isp_id' => ['nullable', 'exists:isps,id'],
            'name' => ['required', 'string', 'max:255'],
            'host' => ['nullable', 'string', 'max:255'],
            'api_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => [$creating ? 'nullable' : 'nullable', 'string', 'max:255'],
            'connection_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function ensureTokens(MikrotikRouter $router): void
    {
        $updates = [];
        if (! $router->provision_token) { $updates['provision_token'] = Str::random(80); }
        if (! $router->heartbeat_token) { $updates['heartbeat_token'] = Str::random(80); }
        if ($updates) { $router->update($updates); $router->refresh(); }
    }

    private function routerQuery(Request $request)
    {
        return MikrotikRouter::query()->when(! $this->isPlatform($request), fn ($q) => $q->where('isp_id', $this->resolveIsp($request)->id));
    }

    private function applyRouterFilters($query, Request $request)
    {
        $search = trim((string) $request->query('q'));
        $status = strtolower((string) $request->query('status'));
        $connectionType = strtolower((string) $request->query('connection_type'));
        $hotspotFilesStatus = strtolower((string) $request->query('hotspot_files_status'));

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('identity', 'like', "%{$search}%")
                    ->orWhere('host', 'like', "%{$search}%")
                    ->orWhere('board_name', 'like', "%{$search}%")
                    ->orWhere('routeros_version', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['active', 'inactive', 'offline', 'pending'], true)) {
            $query->where('status', $status);
        }

        if (in_array($connectionType, ['agent', 'api', 'pending'], true)) {
            $query->where('connection_type', $connectionType);
        }

        if (in_array($hotspotFilesStatus, ['present', 'missing', 'pending', 'provision_pending', 'sync_pending', 'unknown'], true)) {
            $hotspotFilesStatus === 'unknown'
                ? $query->whereNull('hotspot_files_status')
                : $query->where('hotspot_files_status', $hotspotFilesStatus);
        }

        return $query;
    }

    private function routerPayload(MikrotikRouter $router): array
    {
        return [
            'id' => $router->id,
            'name' => $router->name,
            'identity' => $router->identity,
            'host' => $router->host,
            'api_port' => $router->api_port,
            'board_name' => $router->board_name,
            'routeros_version' => $router->routeros_version,
            'architecture' => $router->architecture,
            'uptime' => $router->uptime,
            'cpu_load' => $router->cpu_load ?? $router->cpu_usage,
            'memory_free' => $router->memory_free,
            'memory_total' => $router->memory_total,
            'memory_usage' => $router->memory_usage,
            'connection_type' => $router->connection_type,
            'status' => $router->status,
            'live_status' => $this->routerLiveStatus($router),
            'provision_status' => $router->provision_status,
            'hotspot_status' => $router->hotspot_status,
            'hotspot_files_status' => $router->hotspot_files_status ?: 'unknown',
            'pppoe_status' => $router->pppoe_status,
            'sync_status' => $router->sync_status,
            'time_sync_status' => $router->time_sync_status,
            'last_seen_at' => $router->last_seen_at ? $router->last_seen_at->diffForHumans() : null,
            'last_seen_iso' => $router->last_seen_at?->toIso8601String(),
            'packages_count' => $router->internet_packages_count ?? 0,
            'isp' => $router->isp ? [
                'id' => $router->isp->id,
                'name' => $router->isp->name,
            ] : null,
            'show_url' => route('isp.routers.show', $router),
            'edit_url' => route('isp.routers.edit', $router),
            'setup_url' => route('wifi-billing.routers.setup-script', $router),
            'live_url' => route('wifi-billing.live-sessions.index', ['q' => $router->name]),
        ];
    }


    private function activeUserQuery(Request $request)
    {
        return Customer::query()
            ->when(! $this->isPlatform($request), fn ($q) => $q->where('isp_id', $this->resolveIsp($request)->id))
            ->where(function ($query) {
                $query->where('connection_status', 'active')
                    ->orWhereNotNull('last_online_at');
            });
    }

    private function activeUserPayload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'username' => $customer->username,
            'access_type' => $customer->access_type ?: 'hotspot',
            'ip_address' => $customer->ip_address,
            'mac_address' => $customer->mac_address,
            'data_used_human' => $customer->data_used_human,
            'connection_status' => $customer->connection_status,
            'billing_status' => $customer->billing_status,
            'next_due_date' => optional($customer->next_due_date)->toDateString(),
            'last_online_at' => $customer->last_online_at?->diffForHumans(),
            'package' => $customer->internetPackage ? [
                'id' => $customer->internetPackage->id,
                'name' => $customer->internetPackage->name,
            ] : null,
            'router' => $customer->mikrotikRouter ? [
                'id' => $customer->mikrotikRouter->id,
                'name' => $customer->mikrotikRouter->name,
            ] : null,
        ];
    }

    private function routerStats($query): array
    {
        $routers = $query->get();

        return [
            'total' => $routers->count(),
            'active' => $routers->where('status', 'active')->count(),
            'online' => $routers->filter(fn (MikrotikRouter $router) => $this->routerLiveStatus($router) === 'online')->count(),
            'offline' => $routers->filter(fn (MikrotikRouter $router) => $this->routerLiveStatus($router) === 'offline')->count(),
            'waiting_for_link' => $routers->filter(fn (MikrotikRouter $router) => $this->routerLiveStatus($router) === 'waiting_for_link')->count(),
            'hotspot_files_missing' => $routers->where('hotspot_files_status', 'missing')->count(),
        ];
    }

    private function routerLiveStatus(MikrotikRouter $router): string
    {
        if (! $router->last_seen_at) {
            return 'waiting_for_link';
        }

        return $router->last_seen_at->gt(now()->subSeconds(60)) ? 'online' : 'offline';
    }

    private function availableIsps(Request $request)
    {
        return $this->isPlatform($request) ? Isp::orderBy('name')->get() : collect([$this->resolveIsp($request)]);
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

    private function requirePermission(Request $request, array $permissions): void
    {
        abort_unless(collect($permissions)->contains(fn ($permission) => $request->user()->can($permission)), 403);
    }
}
