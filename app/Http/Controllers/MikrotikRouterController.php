<?php

namespace App\Http\Controllers;

use App\Models\Isp;
use App\Models\MikrotikRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MikrotikRouterController extends Controller
{
    public function index(Request $request)
    {
        return view('isp-routers.index', [
            'routers' => $this->routerQuery($request)->with('isp')->latest()->paginate(15),
        ]);
    }

    public function create(Request $request)
    {
        return view('isp-routers.create', [
            'isps' => $this->availableIsps($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $isp = $this->resolveIsp($request, $data['isp_id'] ?? null);

        $router = MikrotikRouter::create([
            'isp_id' => $isp->id,
            'name' => $data['name'],
            'host' => $data['host'],
            'api_port' => $data['api_port'],
            'username' => $data['username'],
            'password' => $data['password'] ?? null,
            'provision_token' => Str::random(80),
            'provision_status' => 'pending',
            'connection_type' => $data['connection_type'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('isp.routers.setup-script', $router)->with('success', 'Router saved. Copy the setup command into MikroTik Terminal.');
    }

    public function edit(Request $request, MikrotikRouter $router)
    {
        $this->authorizeIspRecord($request, $router->isp_id);

        return view('isp-routers.edit', [
            'router' => $router,
            'isps' => $this->availableIsps($request),
        ]);
    }

    public function update(Request $request, MikrotikRouter $router)
    {
        $this->authorizeIspRecord($request, $router->isp_id);
        $data = $this->validated($request, false);
        $isp = $this->resolveIsp($request, $data['isp_id'] ?? $router->isp_id);

        $update = [
            'isp_id' => $isp->id,
            'name' => $data['name'],
            'host' => $data['host'],
            'api_port' => $data['api_port'],
            'username' => $data['username'],
            'connection_type' => $data['connection_type'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ];

        if (! empty($data['password'])) {
            $update['password'] = $data['password'];
        }

        if (! $router->provision_token) {
            $update['provision_token'] = Str::random(80);
        }

        $router->update($update);

        return redirect()->route('isp.routers.index')->with('success', 'Router updated.');
    }

    public function setupScript(Request $request, MikrotikRouter $router)
    {
        $this->authorizeIspRecord($request, $router->isp_id);
        $this->ensureProvisionToken($router);

        $provisionUrl = route('mikrotik.provision.script', $router->provision_token);
        $fetchMode = str_starts_with(strtolower($provisionUrl), 'https://') ? 'https' : 'http';
        $command = '/tool fetch mode=' . $fetchMode . ' url="' . $provisionUrl . '" dst-path=billing-system.rsc;:delay 2s;/import billing-system.rsc;';

        return view('isp-routers.setup-script', compact('router', 'command', 'provisionUrl'));
    }

    public function test(Request $request, MikrotikRouter $router)
    {
        $this->authorizeIspRecord($request, $router->isp_id);
        $router->update([
            'status' => 'inactive',
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('isp.routers.index')->with('success', 'Router test placeholder completed. Install a RouterOS API package to enable live testing.');
    }

    public function provisionScript(string $token)
    {
        $router = MikrotikRouter::where('provision_token', $token)->firstOrFail();
        $router->update([
            'provision_status' => 'downloaded',
            'provisioned_at' => now(),
        ]);

        $identity = $this->routerOsSafe($router->name);
        $username = $this->routerOsSafe($router->username);
        $password = $this->routerOsSafe($router->password ?? '');
        $apiPort = (int) $router->api_port;

        $script = "# StudyRoom TechLab Billing MikroTik Provisioning Script\r\n";
        $script .= "# Router: {$identity}\r\n";
        $script .= "# Generated: " . now()->format('Y-m-d H:i:s') . "\r\n\r\n";
        $script .= ":put \"Starting StudyRoom TechLab provisioning...\"\r\n";
        $script .= "/system identity set name=\"{$identity}\"\r\n";
        $script .= "/ip service set api disabled=no port={$apiPort}\r\n";
        if ($router->connection_type === 'api_ssl') {
            $script .= "/ip service set api-ssl disabled=no port=8729\r\n";
        }
        $script .= ":if ([:len [/user find name=\"{$username}\"]] = 0) do={\r\n";
        $script .= "    /user add name=\"{$username}\" password=\"{$password}\" group=full comment=\"StudyRoom TechLab Billing API User\"\r\n";
        $script .= "} else={\r\n";
        $script .= "    /user set [find name=\"{$username}\"] password=\"{$password}\" group=full comment=\"StudyRoom TechLab Billing API User\"\r\n";
        $script .= "}\r\n";
        $script .= ":put \"StudyRoom TechLab provisioning completed.\"\r\n";

        return response($script, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Content-Disposition', 'inline; filename="billing-system.rsc"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function validated(Request $request, bool $passwordRequired = true): array
    {
        return $request->validate([
            'isp_id' => ['nullable', 'exists:isps,id'],
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => [$passwordRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'connection_type' => ['required', Rule::in(['api', 'api_ssl'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'failed'])],
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

    private function routerQuery(Request $request)
    {
        return MikrotikRouter::query()->when(! $this->isPlatform($request), function ($query) use ($request) {
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

    private function routerOsSafe(?string $value): string
    {
        $value = str_replace('\\', '\\\\', (string) $value);
        $value = str_replace('"', '\"', $value);
        $value = str_replace(["\r", "\n"], ' ', $value);

        return trim($value);
    }
}
