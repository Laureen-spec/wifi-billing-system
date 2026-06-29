<?php

namespace StudyRoomTechLab\WifiBilling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikRouter;
use App\Models\ProvisioningToken;
use App\Models\User;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly IspTenantResolver $tenantResolver)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        abort_if(
            $this->tenantResolver->isPlatform($request),
            403,
            'Tenant module is only available inside a company account.'
        );

        if (! $this->isCompanyOwner($user)) {
            abort_unless(
                $user->can('view-wifi-dashboard') || $user->can('manage-wifi-dashboard'),
                403
            );
        }

        $isp = $this->tenantResolver->resolve($request);
        $dashboard = $this->buildDashboardData((int) $isp->id);

        return Inertia::render('wifi-billing/dashboard', array_merge($dashboard, [
            'isp' => [
                'id' => $isp->id,
                'name' => $isp->name,
                'status' => $isp->status,
            ],
        ]));
    }

    public function packages(Request $request)
    {
        $this->authorizeTenantArea($request, [
            'view-internet-packages',
            'manage-internet-packages',
        ]);

        return Inertia::location(route('isp.packages.index'));
    }

    public function customers(Request $request)
    {
        $this->authorizeTenantArea($request, [
            'view-isp-customers',
            'manage-isp-customers',
        ]);

        return Inertia::location(route('isp.customers.index'));
    }

    public function routers(Request $request)
    {
        $this->authorizeTenantArea($request, [
            'view-mikrotik-routers',
            'manage-mikrotik-routers',
        ]);

        return Inertia::location(route('isp.routers.index'));
    }

    private function authorizeTenantArea(Request $request, array $permissions): void
    {
        $user = $request->user();

        abort_unless($user, 403);

        abort_if(
            $this->tenantResolver->isPlatform($request),
            403,
            'Tenant module is only available inside a company account.'
        );

        if ($this->isCompanyOwner($user)) {
            return;
        }

        abort_unless(
            collect($permissions)->contains(fn (string $permission): bool => $user->can($permission)),
            403
        );
    }

    private function isCompanyOwner(User $user): bool
    {
        if (in_array((string) $user->type, ['company', 'admin', 'isp_admin'], true)) {
            return true;
        }

        return method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['company', 'admin', 'isp_admin']);
    }

    private function buildDashboardData(int $ispId): array
    {
        $today = Carbon::today();
        $tomorrow = Carbon::today()->addDay();

        $customers = $this->hasCustomerTable()
            ? Customer::query()->where('isp_id', $ispId)
            : null;

        $packages = $this->hasInternetPackageTable()
            ? InternetPackage::query()->where('isp_id', $ispId)
            : null;

        $routers = $this->hasRouterTable()
            ? MikrotikRouter::query()->where('isp_id', $ispId)
            : null;

        $paymentTotals = $this->paymentTotals($ispId);
        $routerStats = $this->routerStats($ispId);

        return [
            'stats' => [
                'total_customers' => $customers ? (clone $customers)->count() : 0,
                'active_customers' => $customers ? (clone $customers)->where('connection_status', 'active')->count() : 0,
                'expired_customers' => $customers
                    ? (clone $customers)->where(function ($query) use ($today) {
                        $query->where('billing_status', 'overdue')
                            ->orWhereDate('next_due_date', '<', $today);
                    })->count()
                    : 0,
                'today_payments' => $paymentTotals['today'],
                'monthly_revenue' => $paymentTotals['month'],
                'monthly_billing_value' => $customers ? (float) (clone $customers)->sum('monthly_amount') : 0,
                'total_packages' => $packages ? (clone $packages)->count() : 0,
                'total_routers' => $routers ? (clone $routers)->count() : 0,
                'online_routers' => $routerStats['online'],
                'offline_routers' => $routerStats['offline'],
                'waiting_for_link_routers' => $routerStats['waiting'],
                'hotspot_files_missing_count' => $routerStats['hotspot_files_missing'],
                'provisioning_issues' => $this->provisioningIssues($ispId, $routerStats),
            ],
            'recentCustomers' => $this->recentCustomers($ispId),
            'expiringSoon' => $this->expiringSoon($ispId, $today, $tomorrow),
            'routers' => $this->routerStatus($ispId),
        ];
    }

    private function recentCustomers(int $ispId): array
    {
        if (! $this->hasCustomerTable()) {
            return [];
        }

        return Customer::query()
            ->with('internetPackage:id,name')
            ->where('isp_id', $ispId)
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'package' => $customer->internetPackage?->name ?? 'No package',
                'connection_status' => $customer->connection_status,
                'billing_status' => $customer->billing_status,
                'created_at' => optional($customer->created_at)->toDateString(),
            ])
            ->all();
    }

    private function expiringSoon(int $ispId, Carbon $today, Carbon $tomorrow): array
    {
        if (! $this->hasCustomerTable()) {
            return [];
        }

        return Customer::query()
            ->with('internetPackage:id,name')
            ->where('isp_id', $ispId)
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '>=', $today)
            ->whereDate('next_due_date', '<=', $tomorrow)
            ->orderBy('next_due_date')
            ->limit(5)
            ->get()
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'package' => $customer->internetPackage?->name ?? 'No package',
                'expiry' => optional($customer->next_due_date)->toDateString(),
            ])
            ->all();
    }

    private function routerStatus(int $ispId): array
    {
        if (! $this->hasRouterTable()) {
            return [];
        }

        return MikrotikRouter::query()
            ->where('isp_id', $ispId)
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (MikrotikRouter $router): array => [
                'id' => $router->id,
                'name' => $router->name,
                'host' => $router->host,
                'board_name' => $router->board_name,
                'routeros_version' => $router->routeros_version,
                'cpu_load' => $router->cpu_load ?? $router->cpu_usage,
                'memory_free' => $router->memory_free,
                'memory_total' => $router->memory_total,
                'hotspot_files_status' => $router->hotspot_files_status ?: 'unknown',
                'status' => $this->routerLiveStatus($router),
                'last_seen_at' => $router->last_seen_at ? $router->last_seen_at->diffForHumans() : 'No heartbeat yet',
                'last_seen_iso' => $router->last_seen_at?->toIso8601String(),
                'action_url' => route('isp.routers.show', $router),
            ])
            ->all();
    }

    private function routerStats(int $ispId): array
    {
        $stats = [
            'online' => 0,
            'offline' => 0,
            'waiting' => 0,
            'hotspot_files_missing' => 0,
            'waiting_too_long' => 0,
        ];

        if (! $this->hasRouterTable()) {
            return $stats;
        }

        MikrotikRouter::query()
            ->where('isp_id', $ispId)
            ->get()
            ->each(function (MikrotikRouter $router) use (&$stats): void {
                $status = $this->routerLiveStatus($router);
                if ($status === 'online') {
                    $stats['online']++;
                } elseif ($status === 'waiting_for_link') {
                    $stats['waiting']++;
                    if ($router->created_at && $router->created_at->lt(now()->subMinutes(5))) {
                        $stats['waiting_too_long']++;
                    }
                } else {
                    $stats['offline']++;
                }

                if ($router->hotspot_files_status === 'missing') {
                    $stats['hotspot_files_missing']++;
                }
            });

        return $stats;
    }

    private function routerLiveStatus(MikrotikRouter $router): string
    {
        if (! $router->last_seen_at) {
            return 'waiting_for_link';
        }

        return $router->last_seen_at->gt(now()->subSeconds(60)) ? 'online' : 'offline';
    }

    private function provisioningIssues(int $ispId, array $routerStats): int
    {
        $routerIssues = $routerStats['hotspot_files_missing'] + $routerStats['waiting_too_long'];

        if (! Schema::hasTable((new ProvisioningToken())->getTable())) {
            return $routerIssues;
        }

        return $routerIssues + ProvisioningToken::query()
            ->where('isp_id', $ispId)
            ->whereIn('status', ['failed', 'error'])
            ->count();
    }

    private function paymentTotals(int $ispId): array
    {
        foreach (['isp_payments', 'isp_customer_payments', 'customer_internet_payments'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'isp_id') || ! Schema::hasColumn($table, 'amount')) {
                continue;
            }

            $dateColumn = collect(['paid_at', 'payment_date', 'created_at'])
                ->first(fn (string $column): bool => Schema::hasColumn($table, $column));

            if (! $dateColumn) {
                continue;
            }

            $baseQuery = DB::table($table)->where('isp_id', $ispId);

            if (Schema::hasColumn($table, 'status')) {
                $baseQuery->whereIn('status', ['paid', 'success', 'successful', 'completed', 'confirmed']);
            }

            return [
                'today' => (float) (clone $baseQuery)->whereDate($dateColumn, Carbon::today())->sum('amount'),
                'month' => (float) (clone $baseQuery)
                    ->whereBetween($dateColumn, [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    ->sum('amount'),
            ];
        }

        return ['today' => 0.0, 'month' => 0.0];
    }

    private function hasCustomerTable(): bool
    {
        return Schema::hasTable((new Customer())->getTable());
    }

    private function hasInternetPackageTable(): bool
    {
        return Schema::hasTable((new InternetPackage())->getTable());
    }

    private function hasRouterTable(): bool
    {
        return Schema::hasTable((new MikrotikRouter())->getTable());
    }
}
