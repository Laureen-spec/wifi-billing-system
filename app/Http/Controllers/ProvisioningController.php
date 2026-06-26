<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Isp;
use App\Models\MikrotikRouter;
use App\Models\ProvisioningLog;
use App\Models\ProvisioningToken;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProvisioningController extends Controller
{
    public function index(Request $request)
    {
        $this->requirePermission($request, ['view-provisioning-logs', 'manage-provisioning']);

        $isp = $this->isPlatform($request) ? null : $this->resolveIsp($request);

        return view('isp-provisioning.index', [
            'tokens' => $this->tokenQuery($request)->with(['isp', 'router', 'customer', 'internetPackage'])->latest()->paginate(15),
            'routers' => MikrotikRouter::query()
                ->when($isp, fn ($query) => $query->where('isp_id', $isp->id))
                ->orderBy('name')
                ->get(),
            'customers' => Customer::query()
                ->when($isp, fn ($query) => $query->where('isp_id', $isp->id))
                ->orderBy('name')
                ->get(),
            'packages' => InternetPackage::query()
                ->when($isp, fn ($query) => $query->where('isp_id', $isp->id))
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function generate(Request $request)
    {
        $this->requirePermission($request, ['create-provisioning-token', 'manage-provisioning']);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:isp_customers,id'],
            'mikrotik_router_id' => ['nullable', 'exists:mikrotik_routers,id'],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $customer = Customer::with('internetPackage')->findOrFail($data['customer_id']);
        $this->authorizeIspRecord($request, $customer->isp_id);

        $router = ! empty($data['mikrotik_router_id'])
            ? MikrotikRouter::findOrFail($data['mikrotik_router_id'])
            : MikrotikRouter::where('isp_id', $customer->isp_id)->orderBy('id')->first();

        abort_if(! $router, 422, 'Create a MikroTik router before generating provisioning.');
        $this->authorizeIspRecord($request, $router->isp_id);
        abort_unless((int) $router->isp_id === (int) $customer->isp_id, 403);

        $packageId = $data['internet_package_id'] ?? $customer->internet_package_id;
        if ($packageId) {
            abort_unless(InternetPackage::where('id', $packageId)->where('isp_id', $customer->isp_id)->exists(), 403);
        }

        $token = ProvisioningToken::create([
            'isp_id' => $customer->isp_id,
            'mikrotik_router_id' => $router->id,
            'customer_id' => $customer->id,
            'internet_package_id' => $packageId,
            'token' => Str::random(64),
            'provision_type' => 'hotspot',
            'status' => 'active',
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('isp.provisioning.show', $token->token)->with('success', 'Provisioning token generated.');
    }

    public function details(Request $request, string $token)
    {
        $this->requirePermission($request, ['view-provisioning-logs', 'manage-provisioning']);

        $provisioningToken = ProvisioningToken::with(['isp', 'router', 'customer', 'internetPackage', 'logs'])
            ->where('token', $token)
            ->firstOrFail();

        $this->authorizeIspRecord($request, $provisioningToken->isp_id);

        return view('isp-provisioning.show', [
            'token' => $provisioningToken,
            'provisionUrl' => route('provision.show', $provisioningToken->token),
            'command' => $this->fetchCommand(route('provision.show', $provisioningToken->token)),
            'logs' => $provisioningToken->logs()->latest()->paginate(20),
        ]);
    }

    public function deactivate(Request $request, string $token)
    {
        $this->requirePermission($request, ['deactivate-provisioning-token', 'manage-provisioning']);

        $provisioningToken = ProvisioningToken::where('token', $token)->firstOrFail();
        $this->authorizeIspRecord($request, $provisioningToken->isp_id);
        $provisioningToken->update(['status' => 'inactive']);

        return redirect()->route('isp.provisioning.show', $provisioningToken->token)->with('success', 'Provisioning token deactivated.');
    }

    public function show(Request $request, string $token)
    {
        $provisioningToken = ProvisioningToken::with(['router', 'customer', 'internetPackage'])
            ->where('token', $token)
            ->first();

        if (! $provisioningToken) {
            $router = MikrotikRouter::where('provision_token', $token)->first();
            if ($router) {
                $this->ensureHeartbeatToken($router);

                $router->update([
                    'provision_status' => 'downloaded',
                    'provisioned_at' => now(),
                    'identity' => $router->identity ?: $router->name,
                    'sync_status' => 'link_command_downloaded',
                ]);
                ProvisioningLog::create([
                    'mikrotik_router_id' => $router->id,
                    'token' => $token,
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                    'status' => 'router_setup_served',
                    'message' => 'Router setup provisioning script served.',
                ]);

                return $this->routerOsResponse($this->buildRouterSetupScript($router), 'billing-system.rsc');
            }

            $this->logPublicRequest($request, null, $token, 'not_found', 'Provisioning token was not found.');

            return $this->routerOsResponse("# StudyRoom ISP Billing Provisioning\r\n#error token not found\r\n");
        }

        if ($provisioningToken->status !== 'active') {
            $this->logPublicRequest($request, $provisioningToken, $token, 'inactive', 'Provisioning token is inactive.');

            return $this->routerOsResponse("# StudyRoom ISP Billing Provisioning\r\n#error token inactive\r\n");
        }

        if ($provisioningToken->expires_at && $provisioningToken->expires_at->isPast()) {
            $this->logPublicRequest($request, $provisioningToken, $token, 'expired', 'Provisioning token is expired.');

            return $this->routerOsResponse("# StudyRoom ISP Billing Provisioning\r\n#error token expired\r\n");
        }

        if (! $provisioningToken->customer || ! $provisioningToken->router) {
            $this->logPublicRequest($request, $provisioningToken, $token, 'missing_data', 'Customer or router is missing.');

            return $this->routerOsResponse("# StudyRoom ISP Billing Provisioning\r\n#error customer or router missing\r\n");
        }

        $script = $this->buildRouterOsScript($provisioningToken);

        $provisioningToken->forceFill(['used_at' => now()])->save();
        $this->logPublicRequest($request, $provisioningToken, $token, 'served', 'Provisioning script served.');

        return $this->routerOsResponse($script);
    }

    public function heartbeat(Request $request, string $token)
    {
        $router = MikrotikRouter::query()
            ->where('provision_token', $token)
            ->orWhere('heartbeat_token', $token)
            ->firstOrFail();

        $payload = $request->only([
            'identity',
            'version',
            'board',
            'cpu',
            'memfree',
            'memtotal',
            'uptime',
            'date',
            'time',
            'hotspot_files',
            'hotspot',
            'pppoe',
        ]);

        $memoryFree = $this->unsignedIntegerOrNull($request->query('memfree'));
        $memoryTotal = $this->unsignedIntegerOrNull($request->query('memtotal'));
        $cpuLoad = $this->boundedTinyIntegerOrNull($request->query('cpu'));
        $routerTime = trim((string) $request->query('date') . ' ' . (string) $request->query('time'));
        $hotspotFilesStatus = $this->statusValue($request->query('hotspot_files'), ['present', 'missing']);
        $hotspotStatus = $this->statusValue($request->query('hotspot'), ['present', 'missing']);
        $pppoeStatus = $this->statusValue($request->query('pppoe'), ['present', 'missing']);

        $updates = [
            'status' => 'active',
            'last_seen_at' => now(),
            'last_connected_at' => now(),
            'last_heartbeat_payload' => $payload,
            'sync_status' => 'heartbeat_received',
        ];

        if ($request->filled('identity')) {
            $updates['identity'] = Str::limit((string) $request->query('identity'), 255, '');
        }
        if ($request->filled('version')) {
            $updates['routeros_version'] = Str::limit((string) $request->query('version'), 255, '');
        }
        if ($request->filled('board')) {
            $updates['board_name'] = Str::limit((string) $request->query('board'), 255, '');
        }
        if ($cpuLoad !== null) {
            $updates['cpu_load'] = $cpuLoad;
            $updates['cpu_usage'] = $cpuLoad;
        }
        if ($memoryFree !== null) {
            $updates['memory_free'] = $memoryFree;
        }
        if ($memoryTotal !== null) {
            $updates['memory_total'] = $memoryTotal;
        }
        if ($memoryFree !== null && $memoryTotal !== null && $memoryTotal >= $memoryFree) {
            $updates['memory_usage'] = round(($memoryTotal - $memoryFree) / 1048576, 2);
        }
        if ($request->filled('uptime')) {
            $updates['uptime'] = Str::limit((string) $request->query('uptime'), 255, '');
        }
        if ($routerTime !== '') {
            $updates['router_time'] = Str::limit($routerTime, 255, '');
            $updates['time_sync_status'] = 'router_time_reported';
        }
        if ($hotspotFilesStatus !== null) {
            $updates['hotspot_files_status'] = $hotspotFilesStatus;
            if ($hotspotFilesStatus === 'missing') {
                $updates['sync_status'] = 'hotspot_files_missing';
            }
        }
        if ($hotspotStatus !== null) {
            $updates['hotspot_status'] = $hotspotStatus;
        }
        if ($pppoeStatus !== null) {
            $updates['pppoe_status'] = $pppoeStatus;
        }

        $router->update($updates);

        return response('OK', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function buildRouterOsScript(ProvisioningToken $token): string
    {
        $customer = $token->customer;
        $router = $token->router;
        $package = $token->internetPackage ?: $customer->internetPackage;

        $username = $this->routerOsSafe($this->usernameForCustomer($customer));
        $password = $this->routerOsSafe($this->passwordForCustomer($customer, $token));
        $customerName = $this->routerOsSafe($customer->name);
        $packageName = $this->routerOsSafe($package?->name ?: 'No package');
        $routerName = $this->routerOsSafe($router->name);
        $comment = 'StudyRoom ISP Billing';
        $target = $this->customerTarget($customer);
        $maxLimit = $this->speedLimit($package);

        $script = "# StudyRoom ISP Billing Provisioning\r\n";
        $script .= "# Powered by StudyRoom TechLab\r\n";
        $script .= "# Router: {$routerName}\r\n";
        $script .= "# Customer: {$customerName}\r\n";
        $script .= "# Package: {$packageName}\r\n";
        $script .= "# Generated: " . now()->format('Y-m-d H:i:s') . "\r\n\r\n";
        $script .= ":local username \"{$username}\"\r\n";
        $script .= ":local password \"{$password}\"\r\n";
        $script .= ":local comment \"{$comment} - {$customerName}\"\r\n\r\n";
        $script .= "/ip hotspot user\r\n";
        $script .= ":if ([:len [find name=\$username]] = 0) do={\r\n";
        $script .= "    add name=\$username password=\$password comment=\$comment\r\n";
        $script .= "} else={\r\n";
        $script .= "    set [find name=\$username] password=\$password comment=(\$comment . \" Updated\")\r\n";
        $script .= "}\r\n\r\n";

        if ($target && $maxLimit) {
            $script .= "/queue simple\r\n";
            $script .= ":if ([:len [find name=\$username]] = 0) do={\r\n";
            $script .= "    add name=\$username target={$target} max-limit={$maxLimit} comment=\$comment\r\n";
            $script .= "} else={\r\n";
            $script .= "    set [find name=\$username] target={$target} max-limit={$maxLimit} comment=(\$comment . \" Updated\")\r\n";
            $script .= "}\r\n\r\n";
        } elseif ($maxLimit) {
            $script .= "# Package speed {$maxLimit} found, but customer has no valid IP target for a simple queue.\r\n";
            $script .= "# Add the customer IP to Location or Address, then regenerate provisioning.\r\n\r\n";
        }

        $script .= ":put \"StudyRoom ISP Billing provisioning completed for {$customerName}.\"\r\n";

        return $script;
    }

    private function logPublicRequest(Request $request, ?ProvisioningToken $tokenModel, string $token, string $status, string $message): void
    {
        ProvisioningLog::create([
            'provisioning_token_id' => $tokenModel?->id,
            'mikrotik_router_id' => $tokenModel?->mikrotik_router_id,
            'customer_id' => $tokenModel?->customer_id,
            'token' => $token,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'status' => $status,
            'message' => $message,
        ]);
    }

    private function buildRouterSetupScript(MikrotikRouter $router): string
    {
        $this->ensureHeartbeatToken($router);

        $identity = $this->routerOsSafe($router->name);
        $username = $this->routerOsSafe($router->username);
        $password = $this->routerOsSafe($router->password ?? '');
        $apiPort = (int) $router->api_port;
        $heartbeatUrl = route('provision.heartbeat', $router->heartbeat_token ?: $router->provision_token);
        $fetchMode = str_starts_with(strtolower($heartbeatUrl), 'https://') ? 'https' : 'http';

        $script = "# StudyRoom TechLab Billing MikroTik Provisioning Script\r\n";
        $script .= "# Router: {$identity}\r\n";
        $script .= "# Generated: " . now()->format('Y-m-d H:i:s') . "\r\n\r\n";
        $script .= ":put \"Starting StudyRoom TechLab provisioning...\"\r\n";
        $script .= "/system identity set name=\"{$identity}\"\r\n";
        $script .= "/ip service set api disabled=no port={$apiPort}\r\n";
        if ($router->connection_type === 'api_ssl') {
            $script .= "/ip service set api-ssl disabled=no port=8729\r\n";
        }
        if ($router->winbox_password) {
            $winboxUser = $this->routerOsSafe($router->winbox_username ?: 'studyroom-winbox');
            $winboxPassword = $this->routerOsSafe($router->winbox_password);
            $winboxPort = (int) ($router->winbox_port ?: 8291);
            $script .= "/ip service set winbox disabled=no port={$winboxPort}\r\n";
            $script .= ":if ([:len [/user find name=\"{$winboxUser}\"]] = 0) do={\r\n";
            $script .= "    /user add name=\"{$winboxUser}\" password=\"{$winboxPassword}\" group=full comment=\"StudyRoom TechLab Remote Winbox\"\r\n";
            $script .= "} else={\r\n";
            $script .= "    /user set [find name=\"{$winboxUser}\"] password=\"{$winboxPassword}\" group=full comment=\"StudyRoom TechLab Remote Winbox\"\r\n";
            $script .= "}\r\n";
        }
        $script .= ":if ([:len [/user find name=\"{$username}\"]] = 0) do={\r\n";
        $script .= "    /user add name=\"{$username}\" password=\"{$password}\" group=full comment=\"StudyRoom TechLab Billing API User\"\r\n";
        $script .= "} else={\r\n";
        $script .= "    /user set [find name=\"{$username}\"] password=\"{$password}\" group=full comment=\"StudyRoom TechLab Billing API User\"\r\n";
        $script .= "}\r\n";
        $script .= $this->heartbeatSchedulerScript($heartbeatUrl, $fetchMode);
        $script .= ":put \"StudyRoom TechLab provisioning completed. Heartbeat scheduler studyroom-heartbeat installed.\"\r\n";

        return $script;
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

    private function routerOsResponse(string $script, string $filename = 'studyroom_provision.rsc')
    {
        return response($script, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function fetchCommand(string $url): string
    {
        $mode = str_starts_with(strtolower($url), 'https://') ? 'https' : 'http';

        return '/tool fetch mode=' . $mode . ' url="' . $url . '" dst-path=studyroom_provision.rsc' . "\n"
            . ':delay 2s' . "\n"
            . '/import studyroom_provision.rsc';
    }

    private function tokenQuery(Request $request)
    {
        return ProvisioningToken::query()->when(! $this->isPlatform($request), function ($query) use ($request) {
            $query->where('isp_id', $this->resolveIsp($request)->id);
        });
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

    private function ensureHeartbeatToken(MikrotikRouter $router): void
    {
        if (! $router->heartbeat_token) {
            $router->forceFill(['heartbeat_token' => Str::random(80)])->save();
            $router->refresh();
        }
    }

    private function boundedTinyIntegerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0, min(100, (int) $value));
    }

    private function unsignedIntegerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private function statusValue(mixed $value, array $allowed): ?string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function requirePermission(Request $request, array|string $permissions): void
    {
        if ($this->isPlatform($request)) {
            return;
        }

        abort_unless($request->user()->canAny((array) $permissions), 403);
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

    private function usernameForCustomer(Customer $customer): string
    {
        $base = Str::slug($customer->name, '');

        return $base ? $base . $customer->id : 'customer' . $customer->id;
    }

    private function passwordForCustomer(Customer $customer, ProvisioningToken $token): string
    {
        $digits = preg_replace('/\D+/', '', (string) $customer->phone);

        return $digits ? substr($digits, -6) : substr($token->token, 0, 10);
    }

    private function speedLimit(?InternetPackage $package): ?string
    {
        if (! $package || ! $package->download_speed_mbps || ! $package->upload_speed_mbps) {
            return null;
        }

        return (int) $package->upload_speed_mbps . 'M/' . (int) $package->download_speed_mbps . 'M';
    }

    private function customerTarget(Customer $customer): ?string
    {
        foreach ([$customer->location, $customer->address] as $value) {
            $value = trim((string) $value);
            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value . '/32';
            }
        }

        return null;
    }

    private function routerOsSafe(?string $value): string
    {
        $value = str_replace('\\', '\\\\', (string) $value);
        $value = str_replace('"', '\"', $value);
        $value = str_replace(["\r", "\n"], ' ', $value);

        return trim($value);
    }
}
