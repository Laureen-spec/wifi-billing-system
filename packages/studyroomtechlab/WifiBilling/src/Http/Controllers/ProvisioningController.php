<?php

namespace StudyRoomTechLab\WifiBilling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Isp;
use App\Models\MikrotikRouter;
use App\Models\ProvisioningLog;
use App\Models\ProvisioningToken;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use StudyRoomTechLab\WifiBilling\Services\CustomerAutoProvisioningService;

class ProvisioningController extends Controller
{
    public function index(Request $request)
    {
        $this->requirePermission($request, ['view-provisioning-logs', 'manage-provisioning']);
        return view('wifi-billing::isp-provisioning.index', [
            'tokens' => $this->tokenQuery($request)->with(['isp', 'router', 'customer', 'internetPackage'])->latest()->paginate(15),
        ]);
    }

    public function generate(Request $request, CustomerAutoProvisioningService $service)
    {
        $this->requirePermission($request, ['create-provisioning-token', 'manage-provisioning']);
        $data = $request->validate([
            'customer_id' => ['required', 'exists:isp_customers,id'],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
        ]);
        $customer = Customer::findOrFail($data['customer_id']);
        $this->authorizeIspRecord($request, $customer->isp_id);
        if (! empty($data['internet_package_id'])) {
            $customer->internet_package_id = $data['internet_package_id'];
            $customer->save();
        }
        $result = $service->retryCustomer($customer, $request->user()->id);
        return back()->with('success', $result['message']);
    }

    public function details(Request $request, string $token)
    {
        $this->requirePermission($request, ['view-provisioning-logs', 'manage-provisioning']);
        $tokenModel = ProvisioningToken::with(['isp', 'router', 'customer', 'internetPackage', 'logs'])->where('token', $token)->firstOrFail();
        $this->authorizeIspRecord($request, $tokenModel->isp_id);
        return view('wifi-billing::isp-provisioning.show', ['token' => $tokenModel, 'logs' => $tokenModel->logs()->latest()->paginate(20)]);
    }

    public function deactivate(Request $request, string $token)
    {
        $this->requirePermission($request, ['deactivate-provisioning-token', 'manage-provisioning']);
        $tokenModel = ProvisioningToken::where('token', $token)->firstOrFail();
        $this->authorizeIspRecord($request, $tokenModel->isp_id);
        $tokenModel->update(['status' => 'inactive']);
        return back()->with('success', 'Provisioning token deactivated.');
    }

    public function show(Request $request, string $token)
    {
        $router = MikrotikRouter::where('provision_token', $token)->first();
        if ($router) {
            $this->ensureRouterTokens($router);
            $router->update(['provision_status' => 'downloaded', 'provisioned_at' => now(), 'sync_status' => 'setup_script_downloaded']);
            $this->log(null, $router, $token, 'router_setup_served', 'Router setup and hotspot files script served.', $request);
            return $this->routerOsResponse($this->buildRouterSetupScript($router, (string) $request->query('ros', '6')), 'billing-system.rsc');
        }

        $tokenModel = ProvisioningToken::with(['router', 'customer.internetPackage'])->where('token', $token)->first();
        if (! $tokenModel || ! $tokenModel->customer) {
            return $this->routerOsResponse("# StudyRoom provisioning\r\n#error token not found\r\n");
        }

        return $this->routerOsResponse($this->buildCustomerCommand($tokenModel, $tokenModel->router, $tokenModel->customer, $tokenModel->customer->internetPackage));
    }

    public function hotspotFile(Request $request, string $token, string $file)
    {
        $router = MikrotikRouter::where('provision_token', $token)->orWhere('heartbeat_token', $token)->firstOrFail();
        $allowed = ['login.html', 'status.html', 'logout.html', 'error.html', 'alogin.html', 'redirect.html', 'md5.js'];
        abort_unless(in_array($file, $allowed, true), 404);
        $path = __DIR__ . '/../../resources/hotspot/' . $file;
        abort_unless(is_file($path), 404);
        $type = str_ends_with($file, '.js') ? 'application/javascript' : 'text/html';
        return response(file_get_contents($path), 200)->header('Content-Type', $type . '; charset=UTF-8')->header('Cache-Control', 'no-store');
    }

    public function heartbeat(Request $request, string $token)
    {
        $router = $this->findRouterByAgentToken($token);
        if (! $router) {
            return $this->routerOsResponse('# unknown router' . "\r\n");
        }

        $payload = $request->only(['identity', 'version', 'board', 'cpu', 'memfree', 'memtotal', 'uptime', 'hotspot_active', 'pppoe_active']);
        $router->forceFill([
            'status' => 'active',
            'last_seen_at' => now(),
            'last_connected_at' => $router->last_connected_at ?: now(),
            'identity' => $payload['identity'] ?? $router->identity,
            'routeros_version' => $payload['version'] ?? $router->routeros_version,
            'board_name' => $payload['board'] ?? $router->board_name,
            'cpu_load' => isset($payload['cpu']) ? (int) $payload['cpu'] : $router->cpu_load,
            'memory_free' => isset($payload['memfree']) ? (int) $payload['memfree'] : $router->memory_free,
            'memory_total' => isset($payload['memtotal']) ? (int) $payload['memtotal'] : $router->memory_total,
            'uptime' => $payload['uptime'] ?? $router->uptime,
            'hotspot_files_status' => 'synced',
            'provision_status' => 'linked',
            'last_heartbeat_payload' => array_filter($payload, fn ($v) => $v !== null && $v !== ''),
        ])->save();

        return $this->routerOsResponse(':put "StudyRoom heartbeat ok"' . "\r\n");
    }

    public function commands(Request $request, string $token, CustomerAutoProvisioningService $service)
    {
        $router = $this->findRouterByAgentToken($token);
        if (! $router) {
            return $this->routerOsResponse('# unknown router' . "\r\n");
        }

        $router->forceFill(['status' => 'active', 'last_seen_at' => now(), 'provision_status' => 'linked'])->save();
        $commands = $service->pendingCommandsForRouter($router, 20);
        $script = "# StudyRoom pending commands for {$this->rosQuote($router->name)}\r\n";
        if ($commands->isEmpty()) {
            return $this->routerOsResponse($script . ':put "No pending StudyRoom commands"' . "\r\n", 'studyroom-commands.rsc');
        }

        foreach ($commands as $command) {
            if (! $command->customer) {
                continue;
            }
            $command->update(['attempted_at' => now()]);
            $script .= $this->buildCustomerCommand($command, $router, $command->customer, $command->customer->internetPackage);
        }

        return $this->routerOsResponse($script, 'studyroom-commands.rsc');
    }

    public function result(Request $request, string $token, CustomerAutoProvisioningService $service)
    {
        $router = $this->findRouterByAgentToken($token);
        if (! $router) {
            return $this->routerOsResponse('# unknown router' . "\r\n");
        }
        $service->markResult($router, (int) $request->query('command_id'), (string) $request->query('status', 'failed'), (string) $request->query('message', ''));
        return $this->routerOsResponse(':put "Result received"' . "\r\n");
    }

    private function buildRouterSetupScript(MikrotikRouter $router, string $ros): string
    {
        $this->ensureRouterTokens($router);
        $baseToken = $router->provision_token;
        $agentToken = $router->heartbeat_token ?: $router->provision_token;
        $mode = $this->modeFor(route('provision.show', $baseToken));
        $heartbeatUrl = route('provision.heartbeat', $agentToken);
        $commandsUrl = route('router-agent.commands', $agentToken);
        $identity = $this->rosQuote($router->name);

        $script = "# StudyRoom Connect full MikroTik provisioning\r\n";
        $script .= "# Includes agent scripts and all hotspot portal files\r\n";
        $script .= ':put "Starting StudyRoom provisioning"' . "\r\n";
        $script .= "/system identity set name=\"{$identity}\"\r\n";
        $script .= "/ip service set api disabled=no port=" . (int) ($router->api_port ?: 8728) . "\r\n";

        if ($router->username && $router->password) {
            $u = $this->rosQuote($router->username);
            $p = $this->rosQuote($router->password);
            $script .= ":if ([:len [/user find name=\"{$u}\"]] = 0) do={/user add name=\"{$u}\" password=\"{$p}\" group=full comment=\"StudyRoom billing agent/API\"} else={/user set [find name=\"{$u}\"] password=\"{$p}\" group=full comment=\"StudyRoom billing agent/API\"}\r\n";
        }

        $script .= $this->hotspotFilesScript($baseToken);
        $script .= $this->agentScripts($heartbeatUrl, $commandsUrl, $mode);
        $script .= ':put "StudyRoom provisioning completed"' . "\r\n";
        return $script;
    }

    private function hotspotFilesScript(string $token): string
    {
        $files = ['login.html', 'status.html', 'logout.html', 'error.html', 'alogin.html', 'redirect.html', 'md5.js'];
        $script = "\r\n# Sync hotspot captive portal files\r\n";
        foreach ($files as $file) {
            $url = route('provision.hotspot-file', ['token' => $token, 'file' => $file]);
            $mode = $this->modeFor($url);
            $dst = 'hotspot/' . $file;
            $script .= ":do {/file remove [find name=\"{$dst}\"]} on-error={}\r\n";
            $script .= ":do {/tool fetch mode={$mode} url=\"{$url}\" dst-path=\"{$dst}\"} on-error={:log warning \"StudyRoom failed to fetch {$file}\"}\r\n";
        }
        return $script;
    }

    private function agentScripts(string $heartbeatUrl, string $commandsUrl, string $mode): string
    {
        $script = "\r\n# StudyRoom Agent Mode - no MikroTik password required by Laravel\r\n";
        $script .= "/system scheduler remove [find name=\"studyroom-heartbeat\"]\r\n";
        $script .= "/system scheduler remove [find name=\"studyroom-pull-commands\"]\r\n";
        $script .= "/system script remove [find name=\"studyroom-heartbeat\"]\r\n";
        $script .= "/system script remove [find name=\"studyroom-pull-commands\"]\r\n";
        $script .= "/system script add name=\"studyroom-heartbeat\" policy=read,write,test source={\r\n";
        $script .= "    :local cpu [/system resource get cpu-load]\r\n";
        $script .= "    :local uptime [/system resource get uptime]\r\n";
        $script .= "    :local hs 0\r\n";
        $script .= "    :local pp 0\r\n";
        $script .= "    :do {:set hs [/ip hotspot active print count-only]} on-error={:set hs 0}\r\n";
        $script .= "    :do {:set pp [/ppp active print count-only]} on-error={:set pp 0}\r\n";
        $script .= "    :local u (\"{$heartbeatUrl}\" . \"?cpu=\" . \$cpu . \"&uptime=\" . \$uptime . \"&hotspot_active=\" . \$hs . \"&pppoe_active=\" . \$pp)\r\n";
        $script .= "    :do {/tool fetch mode={$mode} url=\$u keep-result=no} on-error={:log warning \"StudyRoom heartbeat failed\"}\r\n";
        $script .= "}\r\n";
        $script .= "/system script add name=\"studyroom-pull-commands\" policy=read,write,test,sensitive source={\r\n";
        $script .= "    :do {/tool fetch mode={$mode} url=\"{$commandsUrl}\" dst-path=\"studyroom-commands.rsc\"} on-error={:log warning \"StudyRoom command fetch failed\"}\r\n";
        $script .= "    :delay 1s\r\n";
        $script .= "    :do {/import studyroom-commands.rsc} on-error={:log warning \"StudyRoom command import failed\"}\r\n";
        $script .= "}\r\n";
        $script .= "/system scheduler add name=\"studyroom-heartbeat\" interval=20s start-time=startup on-event=\"/system script run studyroom-heartbeat\"\r\n";
        $script .= "/system scheduler add name=\"studyroom-pull-commands\" interval=30s start-time=startup on-event=\"/system script run studyroom-pull-commands\"\r\n";
        $script .= ":delay 1s\r\n";
        $script .= ":do {/system script run studyroom-heartbeat} on-error={}\r\n";
        $script .= ":do {/system script run studyroom-pull-commands} on-error={}\r\n";
        return $script;
    }

    private function buildCustomerCommand(ProvisioningToken $token, ?MikrotikRouter $router, Customer $customer, ?InternetPackage $package): string
    {
        $service = app(CustomerAutoProvisioningService::class);
        $type = $service->accessType($customer, $package);
        $username = $this->rosQuote($service->usernameForCustomer($customer));
        $password = $this->rosQuote($service->passwordForCustomer($customer));
        $profile = $this->rosQuote($service->profileName($package, $type));
        $rawRate = $service->rateLimit($package);
        $rate = $rawRate ? $this->rosQuote($rawRate) : null;
        $ratePart = $rate ? " rate-limit=\"{$rate}\"" : '';
        $comment = $this->rosQuote('StudyRoom customer #' . $customer->id . ' ' . $customer->name);
        $resultBase = route('router-agent.result', $router?->heartbeat_token ?: $router?->provision_token ?: 'missing') . '?command_id=' . $token->id;
        $resultOk = $resultBase . '&status=done&message=ok';
        $resultFail = $resultBase . '&status=failed&message=router-error';
        $mode = $this->modeFor($resultBase);

        $script = "\r\n# Provision customer {$customer->id}: {$this->rosQuote($customer->name)}\r\n";
        $script .= ":do {\r\n";

        if ($type === 'pppoe') {
            $script .= "    :if ([:len [/ppp profile find name=\"{$profile}\"]] = 0) do={\r\n";
            $script .= "        /ppp profile add name=\"{$profile}\"{$ratePart}\r\n";
            $script .= "    }\r\n";
            if ($rate) {
                $script .= "    /ppp profile set [/ppp profile find name=\"{$profile}\"] rate-limit=\"{$rate}\"\r\n";
            }
            $script .= "    :if ([:len [/ppp secret find name=\"{$username}\"]] = 0) do={\r\n";
            $script .= "        /ppp secret add name=\"{$username}\" password=\"{$password}\" service=pppoe profile=\"{$profile}\" comment=\"{$comment}\"\r\n";
            $script .= "    }\r\n";
            $script .= "    /ppp secret set [/ppp secret find name=\"{$username}\"] password=\"{$password}\" service=pppoe profile=\"{$profile}\" comment=\"{$comment}\"\r\n";
        } else {
            $script .= "    :if ([:len [/ip hotspot user profile find name=\"{$profile}\"]] = 0) do={\r\n";
            $script .= "        /ip hotspot user profile add name=\"{$profile}\" shared-users=1{$ratePart}\r\n";
            $script .= "    }\r\n";
            $script .= "    /ip hotspot user profile set [/ip hotspot user profile find name=\"{$profile}\"] shared-users=1" . ($rate ? " rate-limit=\"{$rate}\"" : '') . "\r\n";
            $script .= "    :if ([:len [/ip hotspot user find name=\"{$username}\"]] = 0) do={\r\n";
            $script .= "        /ip hotspot user add name=\"{$username}\" password=\"{$password}\" profile=\"{$profile}\" comment=\"{$comment}\"\r\n";
            $script .= "    }\r\n";
            $script .= "    /ip hotspot user set [/ip hotspot user find name=\"{$username}\"] password=\"{$password}\" profile=\"{$profile}\" comment=\"{$comment}\"\r\n";
        }

        $script .= "    :do {/tool fetch mode={$mode} url=\"{$resultOk}\" keep-result=no} on-error={}\r\n";
        $script .= "} on-error={\r\n";
        $script .= "    :do {/tool fetch mode={$mode} url=\"{$resultFail}\" keep-result=no} on-error={}\r\n";
        $script .= "}\r\n";

        return $script;
    }

    private function findRouterByAgentToken(string $token): ?MikrotikRouter
    {
        return MikrotikRouter::where('heartbeat_token', $token)->orWhere('provision_token', $token)->first();
    }

    private function ensureRouterTokens(MikrotikRouter $router): void
    {
        $updates = [];
        if (! $router->provision_token) { $updates['provision_token'] = Str::random(80); }
        if (! $router->heartbeat_token) { $updates['heartbeat_token'] = Str::random(80); }
        if ($updates) { $router->update($updates); $router->refresh(); }
    }

    private function routerOsResponse(string $script, string $filename = 'studyroom.rsc')
    {
        return response($script, 200)->header('Content-Type', 'text/plain; charset=UTF-8')->header('Content-Disposition', 'inline; filename="' . $filename . '"')->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function modeFor(string $url): string
    {
        return str_starts_with(strtolower($url), 'https://') ? 'https' : 'http';
    }

    private function rosQuote(?string $value): string
    {
        return str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', ' ', ' '], (string) $value);
    }

    private function log(?ProvisioningToken $tokenModel, ?MikrotikRouter $router, string $token, string $status, string $message, Request $request): void
    {
        ProvisioningLog::create([
            'provisioning_token_id' => $tokenModel?->id,
            'mikrotik_router_id' => $router?->id,
            'customer_id' => $tokenModel?->customer_id,
            'token' => $token,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'status' => $status,
            'message' => $message,
        ]);
    }

    private function tokenQuery(Request $request)
    {
        return ProvisioningToken::query()->when(! $this->isPlatform($request), fn ($q) => $q->where('isp_id', $this->resolveIsp($request)->id));
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
