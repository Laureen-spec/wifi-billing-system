<?php

namespace StudyRoomTechLab\WifiBilling\Services;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikRouter;
use App\Models\ProvisioningLog;
use App\Models\ProvisioningToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CustomerAutoProvisioningService
{
    public function queueCustomer(Customer $customer, ?int $actorId = null): array
    {
        $customer->loadMissing('internetPackage');
        $this->ensureCustomerCredentials($customer);

        $routers = $this->targetRouters($customer);

        if ($routers->isEmpty()) {
            $this->safeCustomerUpdate($customer, ['provisioning_status' => 'pending']);
            return [
                'total' => 0,
                'pending' => 0,
                'message' => 'Customer saved. No MikroTik router exists yet; provisioning will start after a router is linked.',
            ];
        }

        $pending = 0;
        foreach ($routers as $router) {
            $token = $this->pendingToken($customer, $router, $actorId);
            $this->log($token, 'pending', 'Command queued for MikroTik agent pull.');
            $pending++;
        }

        $online = $routers->filter(fn (MikrotikRouter $router) => $this->routerLooksOnline($router))->count();
        $this->safeCustomerUpdate($customer, ['provisioning_status' => $online > 0 ? 'pending' : 'pending']);

        return [
            'total' => $routers->count(),
            'pending' => $pending,
            'online' => $online,
            'message' => $online > 0
                ? 'Customer saved. MikroTik is online and will pull provisioning automatically.'
                : 'Customer saved. MikroTik offline, provisioning will run automatically when router comes online.',
        ];
    }

    public function retryCustomer(Customer $customer, ?int $actorId = null): array
    {
        return $this->queueCustomer($customer, $actorId);
    }

    public function pendingCommandsForRouter(MikrotikRouter $router, int $limit = 20): Collection
    {
        return ProvisioningToken::query()
            ->with(['customer.internetPackage', 'router'])
            ->where('mikrotik_router_id', $router->id)
            ->whereNotNull('customer_id')
            ->whereIn('status', ['pending', 'failed', 'active'])
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function markResult(MikrotikRouter $router, ?int $commandId, string $status, string $message = ''): void
    {
        if (! $commandId) {
            return;
        }

        $token = ProvisioningToken::query()
            ->where('id', $commandId)
            ->where('mikrotik_router_id', $router->id)
            ->first();

        if (! $token) {
            return;
        }

        $normalized = in_array($status, ['done', 'ok', 'success', 'provisioned'], true) ? 'provisioned' : 'failed';
        $token->update([
            'status' => $normalized,
            'result_message' => $message ?: $normalized,
            'attempted_at' => now(),
            'provisioned_at' => $normalized === 'provisioned' ? now() : null,
            'used_at' => $normalized === 'provisioned' ? now() : $token->used_at,
        ]);

        if ($token->customer) {
            $this->safeCustomerUpdate($token->customer, [
                'provisioning_status' => $normalized,
                'mikrotik_router_id' => $router->id,
            ]);
        }

        $this->log($token, $normalized, $message ?: 'Router reported ' . $normalized . '.');
    }

    public function refreshPendingSummary(): array
    {
        return [
            'pending' => ProvisioningToken::query()->whereIn('status', ['pending', 'failed', 'active'])->whereNotNull('customer_id')->count(),
        ];
    }

    private function targetRouters(Customer $customer): Collection
    {
        if ($this->hasColumn('isp_customers', 'mikrotik_router_id') && $customer->mikrotik_router_id) {
            return MikrotikRouter::query()->where('id', $customer->mikrotik_router_id)->get();
        }

        return MikrotikRouter::query()
            ->where('isp_id', $customer->isp_id)
            ->orderBy('id')
            ->get();
    }



   private function pendingToken(Customer $customer, MikrotikRouter $router, ?int $actorId): ProvisioningToken
{
    $package = $customer->internetPackage ?: InternetPackage::find($customer->internet_package_id);
    $type = $this->accessType($customer, $package);

    $token = ProvisioningToken::query()
        ->where('customer_id', $customer->id)
        ->where('mikrotik_router_id', $router->id)
        ->where('provision_type', $type)
        ->whereIn('status', ['pending', 'failed', 'active'])
        ->latest()
        ->first();

    if ($token) {
        $token->forceFill([
            'isp_id' => $customer->isp_id,
            'internet_package_id' => $customer->internet_package_id ?: $package?->id,
            'status' => 'pending',
            'result_message' => null,
            'created_by' => $actorId,
            'attempted_at' => null,
            'provisioned_at' => null,
            'used_at' => null,
        ])->save();

        return $token;
    }

    return ProvisioningToken::create([
        'isp_id' => $customer->isp_id,
        'customer_id' => $customer->id,
        'mikrotik_router_id' => $router->id,
        'internet_package_id' => $customer->internet_package_id ?: $package?->id,
        'provision_type' => $type,
        'token' => Str::random(64),
        'status' => 'pending',
        'created_by' => $actorId,
    ]);
}

    public function accessType(Customer $customer, ?InternetPackage $package = null): string
    {
        $type = strtolower((string) ($customer->access_type ?: $package?->access_type ?: $package?->package_type ?: 'hotspot'));
        return $type === 'pppoe' ? 'pppoe' : 'hotspot';
    }

    public function usernameForCustomer(Customer $customer): string
    {
        return (string) ($customer->username ?: $customer->phone ?: 'customer' . $customer->id);
    }

    public function passwordForCustomer(Customer $customer): string
    {
        if ($customer->password) {
            return (string) $customer->password;
        }

        $digits = preg_replace('/\D+/', '', (string) $customer->phone);
        return $digits ? substr($digits, -6) : (string) random_int(100000, 999999);
    }

    public function profileName(?InternetPackage $package, string $accessType): string
    {
        if (! $package) {
            return $accessType === 'pppoe' ? 'default' : 'default';
        }

        return 'SR-' . strtoupper($accessType) . '-' . Str::limit(Str::slug($package->name, '-'), 24, '');
    }

    public function rateLimit(?InternetPackage $package): ?string
    {
        if (! $package || ! $package->download_speed_mbps || ! $package->upload_speed_mbps) {
            return null;
        }

        return (int) $package->upload_speed_mbps . 'M/' . (int) $package->download_speed_mbps . 'M';
    }

    private function ensureCustomerCredentials(Customer $customer): void
    {
        $updates = [];
        if ($this->hasColumn('isp_customers', 'username') && ! $customer->username) {
            $updates['username'] = $customer->phone ?: 'customer' . $customer->id;
        }
        if ($this->hasColumn('isp_customers', 'password') && ! $customer->password) {
            $updates['password'] = $this->passwordForCustomer($customer);
        }
        if ($this->hasColumn('isp_customers', 'access_type') && ! $customer->access_type) {
            $updates['access_type'] = $this->accessType($customer, $customer->internetPackage);
        }
        if ($updates) {
            $customer->forceFill($updates)->save();
            $customer->refresh();
        }
    }

    private function routerLooksOnline(MikrotikRouter $router): bool
    {
        return $router->last_seen_at && $router->last_seen_at->gt(now()->subMinutes(2));
    }

    private function safeCustomerUpdate(Customer $customer, array $updates): void
    {
        $safe = [];
        foreach ($updates as $key => $value) {
            if ($this->hasColumn('isp_customers', $key)) {
                $safe[$key] = $value;
            }
        }
        if ($safe) {
            $customer->forceFill($safe)->save();
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function log(ProvisioningToken $token, string $status, string $message): void
    {
        ProvisioningLog::create([
            'provisioning_token_id' => $token->id,
            'mikrotik_router_id' => $token->mikrotik_router_id,
            'customer_id' => $token->customer_id,
            'token' => $token->token,
            'status' => $status,
            'message' => $message,
        ]);
    }
}
