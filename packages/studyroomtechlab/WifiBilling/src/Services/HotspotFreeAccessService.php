<?php

namespace StudyRoomTechLab\WifiBilling\Services;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikRouter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class HotspotFreeAccessService
{
    private const LOG_TABLE = 'hotspot_free_access_logs';

    public function status(Request $request, array $settings): array
    {
        $base = $this->baseResponse($settings);

        if (! $base['enabled']) {
            return $base;
        }

        if (! Schema::hasTable(self::LOG_TABLE)) {
            return array_merge($base, [
                'status' => 'unavailable',
                'allowed' => false,
                'message' => 'Free access is not ready yet.',
            ]);
        }

        $this->expireOldActiveLogs();

        $identity = $this->identityFromRequest($request, $settings);

        if (! $identity['ok']) {
            return array_merge($base, [
                'status' => 'needs_identity',
                'allowed' => false,
                'message' => $identity['message'],
            ]);
        }

        $blocked = $this->cooldownLog($identity['checks']);

        if ($blocked) {
            return $this->blockedResponse($settings, $blocked, $base);
        }

        return array_merge($base, [
            'status' => 'available',
            'allowed' => true,
            'message' => $settings['free_access_button_text'] ?? 'Get free access',
        ]);
    }

    public function start(Request $request, array $settings): array
    {
        $base = $this->baseResponse($settings);

        if (! $base['enabled']) {
            return $base;
        }

        if (! Schema::hasTable(self::LOG_TABLE)) {
            return array_merge($base, [
                'status' => 'unavailable',
                'allowed' => false,
                'message' => 'Free access is not ready yet.',
            ]);
        }

        $this->expireOldActiveLogs();

        $identity = $this->identityFromRequest($request, $settings);

        if (! $identity['ok']) {
            return array_merge($base, [
                'status' => 'needs_identity',
                'allowed' => false,
                'message' => $identity['message'],
            ]);
        }

        $blocked = $this->cooldownLog($identity['checks']);

        if ($blocked) {
            return $this->blockedResponse($settings, $blocked, $base);
        }

        $package = $this->freeAccessPackage($settings);

        if (! $package) {
            return array_merge($base, [
                'status' => 'package_missing',
                'allowed' => false,
                'message' => 'Free access package is not configured.',
            ]);
        }

        $paidCustomer = $this->activePaidCustomer($identity);

        if ($paidCustomer) {
            return array_merge($base, [
                'status' => 'paid_exists',
                'allowed' => false,
                'customer_id' => $paidCustomer->id,
                'message' => 'You already have an active paid package. Continue using your current package.',
            ]);
        }

        $router = $this->freeAccessRouter($settings, $package);

        if (! $router) {
            return array_merge($base, [
                'status' => 'router_unavailable',
                'allowed' => false,
                'message' => 'No MikroTik router is available for free access. Please contact customer care.',
            ]);
        }

        $now = Carbon::now();
        $durationMinutes = $this->positiveInt($settings['free_access_duration_minutes'] ?? 60, 60);
        $cooldownHours = $this->positiveInt($settings['free_access_cooldown_hours'] ?? 24, 24);
        $expiresAt = $now->copy()->addMinutes($durationMinutes);
        $cooldownUntil = $now->copy()->addHours($cooldownHours);

        try {
            $result = DB::transaction(function () use ($request, $settings, $identity, $package, $router, $now, $expiresAt, $cooldownUntil, $durationMinutes) {
                $customer = $this->createOrUpdateFreeAccessCustomer($identity, $settings, $package, $router, $expiresAt);

                $queueResult = app(CustomerAutoProvisioningService::class)->queueCustomer($customer, null);
                $token = $this->latestProvisioningToken($customer->id, (int) $package->id);

                $logId = DB::table(self::LOG_TABLE)->insertGetId([
                    'isp_id' => $customer->isp_id ?? ($settings['isp_id'] ?? $package->isp_id ?? null),
                    'mikrotik_router_id' => $token?->mikrotik_router_id ?? $customer->mikrotik_router_id ?? $router->id,
                    'customer_id' => $customer->id,
                    'phone' => $identity['phone'],
                    'mac_address' => $identity['mac_address'],
                    'ip_address' => $identity['ip_address'] ?: $request->ip(),
                    'username' => $customer->username ?: $identity['username'],
                    'package_id' => $package->id,
                    'started_at' => $now,
                    'expires_at' => $expiresAt,
                    'cooldown_until' => $cooldownUntil,
                    'status' => 'active',
                    'metadata' => json_encode([
                        'name' => $identity['name'],
                        'identity_mode' => $identity['mode'],
                        'speed_limit' => $settings['free_access_speed_limit'] ?? null,
                        'user_agent' => $request->userAgent(),
                        'provisioning_status' => $token ? 'queued' : 'pending',
                        'provisioning_token_id' => $token?->id,
                        'queue_result' => $queueResult,
                        'note' => 'DataLAN free access queued via CustomerAutoProvisioningService::queueCustomer; no M-Pesa transaction or wallet posting was created.',
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return compact('customer', 'queueResult', 'token', 'logId');
            });
        } catch (Throwable $e) {
            report($e);

            return array_merge($base, [
                'status' => 'provisioning_failed',
                'allowed' => false,
                'message' => 'Free access could not be queued. Please contact customer care.',
            ]);
        }

        $token = $result['token'];
        $queueResult = $result['queueResult'];

        return array_merge($base, [
            'status' => 'active',
            'allowed' => true,
            'log_id' => $result['logId'],
            'customer_id' => $result['customer']->id,
            'username' => $result['customer']->username,
            'password' => $result['customer']->password,
            'login_url' => $this->hotspotLoginUrl($settings, (string) $result['customer']->username, (string) $result['customer']->password),
            'provisioning_token_id' => $token?->id,
            'provisioning_status' => $token ? 'queued' : 'pending',
            'started_at' => $now->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'cooldown_until' => $cooldownUntil->toIso8601String(),
            'message' => $this->message($settings['free_access_success_message'] ?? null, [
                '@duration' => (string) $durationMinutes,
                '@time_remaining' => $this->timeRemaining($expiresAt),
            ], 'Free access is active for @duration minutes.') . ' ' . ($queueResult['message'] ?? 'Activation has been queued.'),
        ]);
    }

    private function baseResponse(array $settings): array
    {
        $durationMinutes = $this->positiveInt($settings['free_access_duration_minutes'] ?? 60, 60);
        $cooldownHours = $this->positiveInt($settings['free_access_cooldown_hours'] ?? 24, 24);

        return [
            'enabled' => (bool) ($settings['enable_datalan_free_access'] ?? false),
            'status' => (bool) ($settings['enable_datalan_free_access'] ?? false) ? 'ready' : 'disabled',
            'allowed' => false,
            'duration_minutes' => $durationMinutes,
            'cooldown_hours' => $cooldownHours,
            'identity_mode' => $settings['free_access_identity_mode'] ?? 'mac',
            'requires_phone' => (bool) ($settings['free_access_requires_phone'] ?? false),
            'requires_name' => (bool) ($settings['free_access_requires_name'] ?? false),
            'button_text' => $settings['free_access_button_text'] ?? 'Get 1 hour free access',
            'message' => (bool) ($settings['enable_datalan_free_access'] ?? false)
                ? ''
                : 'Free access is not enabled.',
        ];
    }

    private function identityFromRequest(Request $request, array $settings): array
    {
        $mode = $this->identityMode($settings['free_access_identity_mode'] ?? 'mac');
        $phone = $this->normalizePhone((string) $request->input('phone', $request->query('phone', '')));
        $macAddress = $this->normalizeMac((string) $request->input('mac_address', $request->query('mac', $request->query('mac_address', ''))));
        $name = trim((string) $request->input('name', $request->query('name', '')));
        $ipAddress = trim((string) $request->input('ip_address', $request->query('ip', '')));
        $username = trim((string) $request->input('username', $request->query('username', '')));

        if ((bool) ($settings['free_access_requires_name'] ?? false) && $name === '') {
            return $this->identityError('Enter your name to start free access.', $mode, $phone, $macAddress, $ipAddress, $username, $name);
        }

        if ((bool) ($settings['free_access_requires_phone'] ?? false) && $phone === '') {
            return $this->identityError('Enter your phone number to start free access.', $mode, $phone, $macAddress, $ipAddress, $username, $name);
        }

        if ($mode === 'mac' && $macAddress === '') {
            return $this->identityError('Device MAC address is required for free access.', $mode, $phone, $macAddress, $ipAddress, $username, $name);
        }

        if ($mode === 'phone' && $phone === '') {
            return $this->identityError('Enter your phone number to start free access.', $mode, $phone, $macAddress, $ipAddress, $username, $name);
        }

        if ($mode === 'both' && $macAddress === '' && $phone === '') {
            return $this->identityError('Enter your phone number or connect with a detectable device MAC address.', $mode, $phone, $macAddress, $ipAddress, $username, $name);
        }

        $checks = [];

        if (($mode === 'mac' || $mode === 'both') && $macAddress !== '') {
            $checks['mac_address'] = $macAddress;
        }

        if (($mode === 'phone' || $mode === 'both') && $phone !== '') {
            $checks['phone'] = $phone;
        }

        return [
            'ok' => true,
            'mode' => $mode,
            'phone' => $phone ?: null,
            'mac_address' => $macAddress ?: null,
            'ip_address' => $ipAddress ?: null,
            'username' => $username ?: null,
            'name' => $name ?: null,
            'checks' => $checks,
        ];
    }

    private function identityError(string $message, string $mode, string $phone, string $macAddress, string $ipAddress, string $username, string $name): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'mode' => $mode,
            'phone' => $phone ?: null,
            'mac_address' => $macAddress ?: null,
            'ip_address' => $ipAddress ?: null,
            'username' => $username ?: null,
            'name' => $name ?: null,
            'checks' => [],
        ];
    }

    private function cooldownLog(array $checks): ?object
    {
        if ($checks === []) {
            return null;
        }

        $now = Carbon::now();

        return DB::table(self::LOG_TABLE)
            ->where('cooldown_until', '>', $now)
            ->where(function ($query) use ($checks) {
                foreach ($checks as $column => $value) {
                    $query->orWhere($column, $value);
                }
            })
            ->orderByDesc('cooldown_until')
            ->orderByDesc('started_at')
            ->first();
    }

    private function blockedResponse(array $settings, object $log, array $base): array
    {
        $cooldownUntil = Carbon::parse($log->cooldown_until);
        $remaining = $this->timeRemaining($cooldownUntil);

        return array_merge($base, [
            'status' => 'blocked',
            'allowed' => false,
            'cooldown_until' => $cooldownUntil->toIso8601String(),
            'time_remaining' => $remaining,
            'message' => $this->message($settings['free_access_cooldown_message'] ?? null, [
                '@time_remaining' => $remaining,
            ], 'You already used free access. Come back after @time_remaining.'),
        ]);
    }

    private function freeAccessPackage(array $settings): ?InternetPackage
    {
        $packageId = (int) ($settings['free_access_package_id'] ?? 0);

        if ($packageId <= 0 || ! Schema::hasTable('internet_packages')) {
            return null;
        }

        return InternetPackage::query()
            ->whereKey($packageId)
            ->when($this->hasColumn('internet_packages', 'status'), fn ($query) => $query->where('status', 'active'))
            ->first();
    }

    private function freeAccessRouter(array $settings, InternetPackage $package): ?MikrotikRouter
    {
        if (! Schema::hasTable('mikrotik_routers')) {
            return null;
        }

        $routerId = (int) ($settings['mikrotik_router_id'] ?? 0);

        if ($routerId > 0) {
            return MikrotikRouter::query()->whereKey($routerId)->first();
        }

        $ispId = (int) (($settings['isp_id'] ?? null) ?: ($package->isp_id ?? 0));

        return MikrotikRouter::query()
            ->when($ispId > 0 && $this->hasColumn('mikrotik_routers', 'isp_id'), fn ($query) => $query->where('isp_id', $ispId))
            ->orderByRaw($this->hasColumn('mikrotik_routers', 'last_seen_at') ? 'last_seen_at IS NULL, last_seen_at DESC' : 'id DESC')
            ->orderByDesc('id')
            ->first();
    }

    private function activePaidCustomer(array $identity): ?Customer
    {
        if (! Schema::hasTable('isp_customers')) {
            return null;
        }

        $query = Customer::query();
        $hasIdentity = false;

        $query->where(function ($subQuery) use ($identity, &$hasIdentity) {
            if (! empty($identity['phone']) && $this->hasColumn('isp_customers', 'phone')) {
                $subQuery->orWhere('phone', $identity['phone']);
                $hasIdentity = true;
            }

            if (! empty($identity['mac_address']) && $this->hasColumn('isp_customers', 'mac_address')) {
                $subQuery->orWhere('mac_address', $identity['mac_address']);
                $hasIdentity = true;
            }
        });

        if (! $hasIdentity) {
            return null;
        }

        if ($this->hasColumn('isp_customers', 'monthly_amount')) {
            $query->where('monthly_amount', '>', 0);
        }

        $query->where(function ($subQuery) {
            $added = false;

            if ($this->hasColumn('isp_customers', 'billing_status')) {
                $subQuery->orWhere('billing_status', 'paid');
                $added = true;
            }

            if ($this->hasColumn('isp_customers', 'connection_status')) {
                $subQuery->orWhere('connection_status', 'active');
                $added = true;
            }

            if (! $added) {
                $subQuery->whereRaw('1 = 1');
            }
        });

        if ($this->hasColumn('isp_customers', 'next_due_date')) {
            $query->where(function ($subQuery) {
                $subQuery->whereNull('next_due_date')
                    ->orWhere('next_due_date', '>=', Carbon::now());
            });
        }

        return $query->latest('id')->first();
    }

    private function createOrUpdateFreeAccessCustomer(array $identity, array $settings, InternetPackage $package, MikrotikRouter $router, Carbon $expiresAt): Customer
    {
        $customer = $this->findReusableFreeAccessCustomer($identity);
        $isNew = ! $customer;
        $customer ??= new Customer();
        $table = $customer->getTable();
        $name = $identity['name'] ?: 'DataLAN Free Access';
        $phone = $identity['phone'];
        $email = $phone ? 'datalan-' . preg_replace('/\D+/', '', $phone) . '@studyroom.local' : null;
        $username = $this->uniqueFreeUsername($identity, $customer->exists ? (int) $customer->id : null);
        $password = (string) random_int(1000, 9999);
        $ispId = (int) (($settings['isp_id'] ?? null) ?: ($package->isp_id ?? null) ?: ($router->isp_id ?? 0));

        $payload = [];
        $this->setIfColumnExists($table, $payload, 'isp_id', $ispId ?: null);
        $this->setIfColumnExists($table, $payload, 'internet_package_id', $package->id);
        $this->setIfColumnExists($table, $payload, 'mikrotik_router_id', $router->id);
        $this->setIfColumnExists($table, $payload, 'access_type', 'hotspot');
        $this->setIfColumnExists($table, $payload, 'username', $username);
        $this->setIfColumnExists($table, $payload, 'password', $password);
        $this->setIfColumnExists($table, $payload, 'mac_address', $identity['mac_address']);
        $this->setIfColumnExists($table, $payload, 'ip_address', $identity['ip_address']);
        $this->setIfColumnExists($table, $payload, 'shared_users', 1);
        $this->setIfColumnExists($table, $payload, 'name', $name);
        $this->setIfColumnExists($table, $payload, 'phone', $phone);
        $this->setIfColumnExists($table, $payload, 'email', $email);
        $this->setIfColumnExists($table, $payload, 'location', 'DataLAN');
        $this->setIfColumnExists($table, $payload, 'address', 'Free hotspot access');
        $this->setIfColumnExists($table, $payload, 'connection_status', 'active');
        $this->setIfColumnExists($table, $payload, 'billing_status', 'paid');
        $this->setIfColumnExists($table, $payload, 'provisioning_status', 'pending');
        $this->setIfColumnExists($table, $payload, 'monthly_amount', 0);
        $this->setIfColumnExists($table, $payload, 'installation_date', Carbon::now()->toDateString());
        $this->setIfColumnExists($table, $payload, 'next_due_date', $expiresAt);
        $this->setIfColumnExists($table, $payload, 'notes', 'DataLAN free access. No M-Pesa payment. No wallet posting.');
        $this->setIfColumnExists($table, $payload, 'status', 'active');

        if ($isNew) {
            $this->setIfColumnExists($table, $payload, 'created_by', null);
        }

        $this->setIfColumnExists($table, $payload, 'updated_by', null);

        $customer->forceFill($payload)->save();

        return $customer->refresh();
    }

    private function findReusableFreeAccessCustomer(array $identity): ?Customer
    {
        if (! Schema::hasTable('isp_customers')) {
            return null;
        }

        $query = Customer::query();
        $hasIdentity = false;

        $query->where(function ($subQuery) use ($identity, &$hasIdentity) {
            if (! empty($identity['phone']) && $this->hasColumn('isp_customers', 'phone')) {
                $subQuery->orWhere('phone', $identity['phone']);
                $hasIdentity = true;
            }

            if (! empty($identity['mac_address']) && $this->hasColumn('isp_customers', 'mac_address')) {
                $subQuery->orWhere('mac_address', $identity['mac_address']);
                $hasIdentity = true;
            }
        });

        if (! $hasIdentity) {
            return null;
        }

        if ($this->hasColumn('isp_customers', 'monthly_amount')) {
            $query->where(function ($subQuery) {
                $subQuery->whereNull('monthly_amount')->orWhere('monthly_amount', '<=', 0);
            });
        }

        return $query->latest('id')->first();
    }

    private function latestProvisioningToken(int $customerId, int $packageId): ?object
    {
        if (! Schema::hasTable('provisioning_tokens')) {
            return null;
        }

        return DB::table('provisioning_tokens')
            ->where('customer_id', $customerId)
            ->where('internet_package_id', $packageId)
            ->whereIn('status', ['pending', 'failed', 'active'])
            ->latest('id')
            ->first();
    }

    private function uniqueFreeUsername(array $identity, ?int $ignoreCustomerId = null): string
    {
        $seed = $identity['phone'] ?: $identity['mac_address'] ?: Str::upper(Str::random(8));
        $seed = preg_replace('/[^0-9A-Za-z]+/', '', $seed) ?: Str::upper(Str::random(8));
        $base = 'DATALAN-' . Str::upper(substr($seed, -6));

        for ($i = 0; $i < 20; $i++) {
            $username = $i === 0 ? $base : $base . '-' . ($i + 1);

            if (! $this->usernameExists($username, $ignoreCustomerId)) {
                return $username;
            }
        }

        return 'DATALAN-' . Str::upper(Str::random(8));
    }

    private function usernameExists(string $username, ?int $ignoreCustomerId): bool
    {
        if (! Schema::hasTable('isp_customers') || ! $this->hasColumn('isp_customers', 'username')) {
            return false;
        }

        return Customer::query()
            ->where('username', $username)
            ->when($ignoreCustomerId, fn ($query) => $query->where('id', '<>', $ignoreCustomerId))
            ->exists();
    }

    private function hotspotLoginUrl(array $settings, string $username, string $password): ?string
    {
        $url = trim((string) ($settings['redirect_url'] ?? ''));

        if ($url === '' || $username === '' || $password === '') {
            return null;
        }

        if (! preg_match('/\/login(\?|$)/i', $url)) {
            return null;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query([
            'username' => $username,
            'password' => $password,
        ]);
    }

    private function setIfColumnExists(string $table, array &$data, string $column, mixed $value): void
    {
        if ($value !== null && $this->hasColumn($table, $column)) {
            $data[$column] = $value;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }

    private function expireOldActiveLogs(): void
    {
        DB::table(self::LOG_TABLE)
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now())
            ->update([
                'status' => 'expired',
                'updated_at' => Carbon::now(),
            ]);
    }

    private function identityMode(?string $mode): string
    {
        return in_array($mode, ['mac', 'phone', 'both'], true) ? $mode : 'mac';
    }

    private function positiveInt(mixed $value, int $fallback): int
    {
        $value = (int) $value;

        return $value > 0 ? $value : $fallback;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($phone, '0')) {
            return '254' . substr($phone, 1);
        }

        if (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            return '254' . $phone;
        }

        return $phone;
    }

    private function normalizeMac(string $macAddress): string
    {
        $macAddress = strtoupper(trim($macAddress));
        $macAddress = str_replace('-', ':', $macAddress);

        return preg_replace('/[^0-9A-F:]/', '', $macAddress) ?: '';
    }

    private function timeRemaining(Carbon $until): string
    {
        $minutes = (int) max(1, ceil(Carbon::now()->diffInMinutes($until, false)));
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours <= 0) {
            return $minutes . ' minute' . ($minutes === 1 ? '' : 's');
        }

        if ($remainingMinutes === 0) {
            return $hours . ' hour' . ($hours === 1 ? '' : 's');
        }

        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ' . $remainingMinutes . ' minute' . ($remainingMinutes === 1 ? '' : 's');
    }

    private function message(?string $template, array $replacements, string $fallback): string
    {
        $template = trim((string) $template) ?: $fallback;

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
