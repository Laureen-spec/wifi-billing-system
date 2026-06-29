<?php

namespace StudyRoomTechLab\WifiBilling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Isp;
use App\Models\MikrotikRouter;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use StudyRoomTechLab\WifiBilling\Services\MikrotikRouterLiveService;

class MikrotikRouterController extends Controller
{
    public function index(Request $request)
    {
        $this->requirePermission($request, ['view-mikrotik-routers', 'manage-mikrotik-routers']);
        return view('wifi-billing::isp-routers.index', ['routers' => $this->routerQuery($request)->with('isp')->latest()->paginate(15)]);
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
        return view('wifi-billing::isp-routers.create', ['router' => new MikrotikRouter(['api_port' => 8728, 'status' => 'active']), 'isps' => $this->availableIsps($request)]);
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

        return redirect()->route('isp.routers.setup-script', $router)->with('success', 'MikroTik created. Copy the provisioning command into MikroTik Terminal.');
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
        return view('wifi-billing::isp-routers.setup-script', compact('router', 'command', 'provisionUrl', 'ros'));
    }

    public function live(Request $request, MikrotikRouter $router, MikrotikRouterLiveService $liveService)
    {
        $this->requirePermission($request, ['view-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);
        return view('wifi-billing::isp-routers.live', ['router' => $router->load('isp'), 'snapshot' => $liveService->snapshot($router)]);
    }

    public function reprovision(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);
        $router->update(['provision_token' => Str::random(80), 'heartbeat_token' => $router->heartbeat_token ?: Str::random(80), 'provision_status' => 'pending', 'updated_by' => $request->user()->id]);
        return redirect()->route('isp.routers.setup-script', $router)->with('success', 'New provisioning command generated.');
    }

    public function syncHotspot(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);
        $router->update(['hotspot_files_status' => 'sync_pending', 'sync_status' => 'hotspot_files_pending']);
        return redirect()->route('isp.routers.setup-script', $router)->with('success', 'Open setup script and rerun command to sync all hotspot files.');
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
