<?php

namespace App\Http\Controllers;

use App\Models\Isp;
use App\Models\MikrotikRouter;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MikrotikRouterController extends Controller
{
    public function index(Request $request)
    {
        $this->requirePermission($request, ['view-mikrotik-routers', 'manage-mikrotik-routers']);

        return view('isp-routers.index', [
            'routers' => $this->routerQuery($request)->with('isp')->latest()->paginate(15),
        ]);
    }

    public function show(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['view-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);

        return view('isp-routers.show', [
            'router' => $router->load(['isp', 'internetPackages']),
        ]);
    }

    public function create(Request $request)
    {
        $this->requirePermission($request, ['create-mikrotik-routers', 'manage-mikrotik-routers']);

        return view('isp-routers.create', [
            'isps' => $this->availableIsps($request),
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

            // Modern linking flow:
            // These are temporary safe values. The router will be linked using the command.
            'host' => 'pending-link',
            'api_port' => 8728,
            'username' => 'billing-api',
            'password' => Str::random(32),
            'connection_type' => 'api',

            'provision_token' => Str::random(80),
            'heartbeat_token' => Str::random(80),
            'provision_status' => 'pending',
            'status' => $data['status'] ?? 'inactive',

            // Services are not selected here. Provisioning handles setup.
            'hotspot_status' => 'provision_pending',
            'pppoe_status' => 'provision_pending',

            'sync_status' => 'pending',
            'time_sync_status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('isp.routers.setup-script', $router)
            ->with('success', 'MikroTik link created. Copy the command into MikroTik Terminal.');
    }

    public function edit(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);

        return view('isp-routers.edit', [
            'router' => $router,
            'isps' => $this->availableIsps($request),
        ]);
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
            'status' => $data['status'] ?? $router->status,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ];

        if (! $router->provision_token) {
            $update['provision_token'] = Str::random(80);
            $update['provision_status'] = 'pending';
        }

        if (! $router->heartbeat_token) {
            $update['heartbeat_token'] = Str::random(80);
        }

        if (! $router->host) {
            $update['host'] = 'pending-link';
        }

        if (! $router->api_port) {
            $update['api_port'] = 8728;
        }

        if (! $router->username) {
            $update['username'] = 'billing-api';
        }

        if (! $router->password) {
            $update['password'] = Str::random(32);
        }

        if (! $router->connection_type) {
            $update['connection_type'] = 'api';
        }

        if (! $router->hotspot_status) {
            $update['hotspot_status'] = 'provision_pending';
        }

        if (! $router->pppoe_status) {
            $update['pppoe_status'] = 'provision_pending';
        }

        $router->update($update);

        return redirect()->route('isp.routers.index')->with('success', 'MikroTik link updated.');
    }

    public function setupScript(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['view-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);
        $this->ensureProvisionToken($router);
        $this->ensureHeartbeatToken($router);

        $provisionUrl = route('provision.show', $router->provision_token);
        $fetchMode = str_starts_with(strtolower($provisionUrl), 'https://') ? 'https' : 'http';

        $command = '/tool fetch mode=' . $fetchMode . ' url="' . $provisionUrl . '" dst-path=billing-system.rsc;:delay 2s;/import billing-system.rsc;';

        return view('isp-routers.setup-script', compact('router', 'command', 'provisionUrl'));
    }

    public function test(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);

        $router->update([
            'status' => 'inactive',
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('isp.routers.index')
            ->with('success', 'Router test placeholder completed. Live testing can be added after the RouterOS API package is connected.');
    }

    public function regenerateWinbox(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);

        $password = $this->generateWinboxPassword();

        $router->update([
            'winbox_endpoint' => $this->winboxEndpoint($router),
            'winbox_port' => $router->winbox_port ?: 8291,
            'winbox_username' => $router->winbox_username ?: 'studyroom-winbox',
            'winbox_password' => $password,
            'remote_winbox_status' => $this->hasRealWinboxEndpoint($router) ? 'enabled' : 'unavailable',
            'remote_winbox_error' => $this->hasRealWinboxEndpoint($router) ? null : 'Router endpoint is not available until heartbeat reports a real link.',
            'sync_status' => 'winbox_password_generated',
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('isp.routers.show', $router)
            ->with('success', 'Remote Winbox password regenerated: ' . $password);
    }

    public function enableWinbox(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);

        $password = $router->winbox_password ?: $this->generateWinboxPassword();

        $router->update([
            'winbox_endpoint' => $this->winboxEndpoint($router),
            'winbox_port' => $router->winbox_port ?: 8291,
            'winbox_username' => $router->winbox_username ?: 'studyroom-winbox',
            'winbox_password' => $password,
            'remote_winbox_status' => $this->hasRealWinboxEndpoint($router) ? 'enabled' : 'unavailable',
            'remote_winbox_error' => $this->hasRealWinboxEndpoint($router) ? null : 'Router endpoint is not available until heartbeat reports a real link.',
            'sync_status' => 'winbox_enabled',
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('isp.routers.show', $router)
            ->with('success', 'Remote Winbox enabled. Password: ' . $password);
    }

    public function reprovision(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['reprovision-mikrotik-routers', 'edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);

        $router->update([
            'provision_token' => Str::random(80),
            'heartbeat_token' => $router->heartbeat_token ?: Str::random(80),
            'provision_status' => 'pending',
            'provisioned_at' => null,
            'hotspot_status' => 'provision_pending',
            'pppoe_status' => 'provision_pending',
            'sync_status' => 'pending',
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('isp.routers.setup-script', $router)
            ->with('success', 'New MikroTik link command generated.');
    }

    public function syncHotspot(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['sync-hotspot-files', 'edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);

        $router->update([
            'hotspot_status' => 'sync_pending',
            'sync_status' => 'hotspot_files_pending',
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('isp.routers.show', $router)->with('success', 'Hotspot file sync queued.');
    }

    public function syncTime(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['sync-router-time', 'edit-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);

        $router->update([
            'time_sync_status' => 'sync_pending',
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('isp.routers.show', $router)->with('success', 'Router time sync queued.');
    }

    public function destroy(Request $request, MikrotikRouter $router)
    {
        $this->requirePermission($request, ['delete-mikrotik-routers', 'manage-mikrotik-routers']);
        $this->authorizeIspRecord($request, $router->isp_id);

        $router->delete();

        return redirect()->route('isp.routers.index')->with('success', 'MikroTik router deleted.');
    }

    public function provisionScript(string $token)
    {
        $router = MikrotikRouter::where('provision_token', $token)->firstOrFail();
        $this->ensureHeartbeatToken($router);

        $router->update([
            'provision_status' => 'downloaded',
            'provisioned_at' => now(),
            'sync_status' => 'link_command_downloaded',
        ]);

        $identity = $this->routerOsSafe($router->name);
        $username = $this->routerOsSafe($router->username ?: 'billing-api');
        $password = $this->routerOsSafe($router->password ?: Str::random(32));
        $apiPort = (int) ($router->api_port ?: 8728);
        $heartbeatUrl = route('provision.heartbeat', $router->heartbeat_token ?: $router->provision_token);
        $fetchMode = str_starts_with(strtolower($heartbeatUrl), 'https://') ? 'https' : 'http';

        $script = "# StudyRoom Connect MikroTik Link Script\r\n";
        $script .= "# Router: {$identity}\r\n";
        $script .= "# Generated: " . now()->format('Y-m-d H:i:s') . "\r\n\r\n";
        $script .= ":put \"Starting StudyRoom Connect MikroTik link...\"\r\n";
        $script .= "/system identity set name=\"{$identity}\"\r\n";
        $script .= "/ip service set api disabled=no port={$apiPort}\r\n";
        $script .= ":if ([:len [/user find name=\"{$username}\"]] = 0) do={\r\n";
        $script .= "    /user add name=\"{$username}\" password=\"{$password}\" group=full comment=\"StudyRoom Connect Billing API User\"\r\n";
        $script .= "} else={\r\n";
        $script .= "    /user set [find name=\"{$username}\"] password=\"{$password}\" group=full comment=\"StudyRoom Connect Billing API User\"\r\n";
        $script .= "}\r\n";
        $script .= $this->heartbeatSchedulerScript($heartbeatUrl, $fetchMode);
        $script .= ":put \"StudyRoom Connect MikroTik link completed. Heartbeat scheduler studyroom-heartbeat installed.\"\r\n";

        return response($script, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Content-Disposition', 'inline; filename="billing-system.rsc"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function heartbeatSchedulerScript(string $heartbeatUrl, string $fetchMode): string
    {
        $script = "/system scheduler remove [find name=\"studyroom-heartbeat\"]\r\n";
        $script .= "/system scheduler add name=\"studyroom-heartbeat\" interval=20s start-time=startup on-event={\r\n";
        $script .= "    :local heartbeatUrl \"{$heartbeatUrl}\"\r\n";
        $script .= "    :local srEncode do={\r\n";
        $script .= "        :local value [:tostr \$1]\r\n";
        $script .= "        :local result \"\"\r\n";
        $script .= "        :for i from=0 to=([:len \$value] - 1) do={\r\n";
        $script .= "            :local ch [:pick \$value \$i (\$i + 1)]\r\n";
        $script .= "            :local encoded \$ch\r\n";
        $script .= "            :if (\$ch = \" \") do={:set encoded \"%20\"}\r\n";
        $script .= "            :if (\$ch = \":\") do={:set encoded \"%3A\"}\r\n";
        $script .= "            :if (\$ch = \"/\") do={:set encoded \"%2F\"}\r\n";
        $script .= "            :if (\$ch = \"(\") do={:set encoded \"%28\"}\r\n";
        $script .= "            :if (\$ch = \")\") do={:set encoded \"%29\"}\r\n";
        $script .= "            :if (\$ch = \"&\") do={:set encoded \"%26\"}\r\n";
        $script .= "            :if (\$ch = \"%\") do={:set encoded \"%25\"}\r\n";
        $script .= "            :if (\$ch = \"?\") do={:set encoded \"%3F\"}\r\n";
        $script .= "            :if (\$ch = \"=\") do={:set encoded \"%3D\"}\r\n";
        $script .= "            :if (\$ch = \"+\") do={:set encoded \"%2B\"}\r\n";
        $script .= "            :if (\$ch = \"#\") do={:set encoded \"%23\"}\r\n";
        $script .= "            :set result (\$result . \$encoded)\r\n";
        $script .= "        }\r\n";
        $script .= "        :return \$result\r\n";
        $script .= "    }\r\n";
        $script .= "    :local identity [/system identity get name]\r\n";
        $script .= "    :local version [/system resource get version]\r\n";
        $script .= "    :local board [/system resource get board-name]\r\n";
        $script .= "    :local cpu [/system resource get cpu-load]\r\n";
        $script .= "    :local memfree [/system resource get free-memory]\r\n";
        $script .= "    :local memtotal [/system resource get total-memory]\r\n";
        $script .= "    :local uptime [/system resource get uptime]\r\n";
        $script .= "    :local rdate [/system clock get date]\r\n";
        $script .= "    :local rtime [/system clock get time]\r\n";
        $script .= "    :local hotspotFiles \"missing\"\r\n";
        $script .= "    :if ([:len [/file find name=\"hotspot/login.html\"]] > 0) do={:set hotspotFiles \"present\"}\r\n";
        $script .= "    :local hotspot \"missing\"\r\n";
        $script .= "    :do {:if ([/ip hotspot print count-only] > 0) do={:set hotspot \"present\"}} on-error={:set hotspot \"missing\"}\r\n";
        $script .= "    :local pppoe \"missing\"\r\n";
        $script .= "    :do {:if ([/ppp profile print count-only] > 0) do={:set pppoe \"present\"}} on-error={:set pppoe \"missing\"}\r\n";
        $script .= "    :local hbUrl (\$heartbeatUrl . \"?identity=\" . [\$srEncode \$identity] . \"&version=\" . [\$srEncode \$version] . \"&board=\" . [\$srEncode \$board] . \"&cpu=\" . \$cpu . \"&memfree=\" . \$memfree . \"&memtotal=\" . \$memtotal . \"&uptime=\" . [\$srEncode \$uptime] . \"&date=\" . [\$srEncode \$rdate] . \"&time=\" . [\$srEncode \$rtime] . \"&hotspot_files=\" . \$hotspotFiles . \"&hotspot=\" . \$hotspot . \"&pppoe=\" . \$pppoe)\r\n";
        $script .= "    /tool fetch mode={$fetchMode} url=\$hbUrl keep-result=no\r\n";
        $script .= "}\r\n";
        $script .= ":delay 1s\r\n";
        $script .= "/system scheduler run [find name=\"studyroom-heartbeat\"]\r\n";

        return $script;
    }

    private function validated(Request $request, bool $isCreate = true): array
    {
        return $request->validate([
            'isp_id' => ['nullable', 'exists:isps,id'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'failed'])],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function ensureProvisionToken(MikrotikRouter $router): void
    {
        if (! $router->provision_token) {
            $router->forceFill([
                'provision_token' => Str::random(80),
                'provision_status' => 'pending',
            ])->save();
        }
    }

    private function ensureHeartbeatToken(MikrotikRouter $router): void
    {
        if (! $router->heartbeat_token) {
            $router->forceFill(['heartbeat_token' => Str::random(80)])->save();
            $router->refresh();
        }
    }

    private function routerQuery(Request $request)
    {
        return MikrotikRouter::query()->when(! $this->isPlatform($request), function ($query) use ($request) {
            $query->where('isp_id', $this->resolveIsp($request)->id);
        });
    }

    private function availableIsps(Request $request)
    {
        return $this->isPlatform($request)
            ? Isp::orderBy('name')->get()
            : collect([$this->resolveIsp($request)]);
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

    private function requirePermission(Request $request, array|string $permissions): void
    {
        if ($this->isPlatform($request)) {
            return;
        }

        abort_unless($request->user()->canAny((array) $permissions), 403);
    }

    private function routerOsSafe(?string $value): string
    {
        $value = str_replace('\\', '\\\\', (string) $value);
        $value = str_replace('"', '\"', (string) $value);
        $value = str_replace(["\r", "\n"], ' ', $value);

        return trim($value);
    }

    private function winboxEndpoint(MikrotikRouter $router): string
    {
        if ($router->winbox_endpoint && ! str_contains($router->winbox_endpoint, 'pending-link')) {
            return $router->winbox_endpoint;
        }

        if (! $router->host || str_contains($router->host, 'pending-link')) {
            return '';
        }

        return $router->host . ':' . ($router->winbox_port ?: 8291);
    }

    private function hasRealWinboxEndpoint(MikrotikRouter $router): bool
    {
        $endpoint = $this->winboxEndpoint($router);

        return $endpoint !== '' && ! str_contains($endpoint, 'pending-link');
    }

    private function generateWinboxPassword(): string
    {
        return 'SW-' . Str::upper(Str::random(4)) . '-' . Str::upper(Str::random(4)) . '-' . random_int(1000, 9999);
    }

    private function createUserIsp($user): Isp
    {
        $isp = Isp::create([
            'name' => $user->name ?: 'StudyRoom ISP',
            'email' => $user->email,
            'phone' => $user->mobile_no ?? null,
            'status' => 'active',
            'admin_user_id' => $user->id,
            'created_by' => $user->created_by ?: $user->id,
            'updated_by' => $user->id,
        ]);

        $user->forceFill(['isp_id' => $isp->id])->save();

        return $isp;
    }
}
