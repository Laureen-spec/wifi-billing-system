<?php

namespace StudyRoomTechLab\WifiBilling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Isp;
use App\Models\MikrotikRouter;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use StudyRoomTechLab\WifiBilling\Services\CustomerAutoProvisioningService;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->can('view-isp-customers') || $request->user()->can('manage-isp-customers'),
            403
        );

        $baseQuery = $this->customerQuery($request);
        $filteredQuery = $this->applyCustomerFilters(clone $baseQuery, $request);

        $customers = (clone $filteredQuery)
            ->with(['isp', 'internetPackage', 'mikrotikRouter'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('connection_status', 'active')->count(),
            'hotspot' => $this->hasColumn('isp_customers', 'access_type')
                ? (clone $baseQuery)->where('access_type', 'hotspot')->count()
                : 0,
            'pppoe' => $this->hasColumn('isp_customers', 'access_type')
                ? (clone $baseQuery)->where('access_type', 'pppoe')->count()
                : 0,
            'overdue' => (clone $baseQuery)->where('billing_status', 'overdue')->count(),
            'expiring_soon' => (clone $baseQuery)
                ->whereNotNull('next_due_date')
                ->whereDate('next_due_date', '>=', now()->toDateString())
                ->whereDate('next_due_date', '<=', now()->addDays(7)->toDateString())
                ->count(),
        ];

        return view('wifi-billing::isp-customers.index', [
            'customers' => $customers,
            'stats' => $stats,
            'pageTitle' => $this->customerPageTitle($request),
            'activeFilter' => $this->customerActiveFilter($request),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless(
            $request->user()->can('create-isp-customers') || $request->user()->can('manage-isp-customers'),
            403
        );

        $isp = $this->isPlatform($request) ? null : $this->resolveIsp($request);

        return view('wifi-billing::isp-customers.create', [
            'customer' => new Customer([
                'connection_status' => 'active',
                'billing_status' => 'paid',
                'shared_users' => 1,
                'access_type' => 'hotspot',
            ]),
            'isps' => $this->availableIsps($request),
            'routers' => MikrotikRouter::query()
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

    public function store(Request $request, CustomerAutoProvisioningService $provisioning)
    {
        abort_unless(
            $request->user()->can('create-isp-customers') || $request->user()->can('manage-isp-customers'),
            403
        );

        $data = $this->validated($request);
        $isp = $this->resolveIsp($request, $data['isp_id'] ?? null);
        $packageId = $data['internet_package_id'] ?? null;

        $this->authorizePackage($isp->id, $packageId);
        $this->authorizeRouter($isp->id, $data['mikrotik_router_id'] ?? null);

        $customer = Customer::create($this->customerPayload($data, $request, $isp->id, $packageId));

        /*
         * Keep existing safe MikroTik agent-mode provisioning.
         * Do not replace this with direct RouterOS API push.
         */
        $result = $provisioning->queueCustomer($customer, $request->user()->id);

        return redirect()
            ->route('isp.customers.index')
            ->with('success', $result['message'] ?? 'Customer saved. Provisioning queued.');
    }

    public function show(Request $request, Customer $customer)
    {
        abort_unless(
            $request->user()->can('view-isp-customers') || $request->user()->can('manage-isp-customers'),
            403
        );

        $this->authorizeIspRecord($request, $customer->isp_id);

        return view('wifi-billing::isp-customers.show', [
            'customer' => $customer->load(['isp', 'internetPackage', 'mikrotikRouter', 'provisioningTokens.router']),
        ]);
    }

    public function edit(Request $request, Customer $customer)
    {
        abort_unless(
            $request->user()->can('edit-isp-customers') || $request->user()->can('manage-isp-customers'),
            403
        );

        $this->authorizeIspRecord($request, $customer->isp_id);

        return view('wifi-billing::isp-customers.edit', [
            'customer' => $customer,
            'isps' => $this->availableIsps($request),
            'routers' => MikrotikRouter::where('isp_id', $customer->isp_id)->orderBy('name')->get(),
            'packages' => InternetPackage::where('isp_id', $customer->isp_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Customer $customer, CustomerAutoProvisioningService $provisioning)
    {
        abort_unless(
            $request->user()->can('edit-isp-customers') || $request->user()->can('manage-isp-customers'),
            403
        );

        $this->authorizeIspRecord($request, $customer->isp_id);

        $data = $this->validated($request);
        $isp = $this->resolveIsp($request, $data['isp_id'] ?? $customer->isp_id);
        $packageId = $data['internet_package_id'] ?? null;

        $this->authorizePackage($isp->id, $packageId);
        $this->authorizeRouter($isp->id, $data['mikrotik_router_id'] ?? null);

        $customer->update($this->customerPayload($data, $request, $isp->id, $packageId, false));

        /*
         * Keep existing safe MikroTik agent-mode provisioning.
         * This queues only this customer and does not reset router admin users or hotspot HTML.
         */
        $result = $provisioning->queueCustomer($customer->fresh(), $request->user()->id);

        return redirect()
            ->route('isp.customers.show', $customer)
            ->with('success', 'Customer updated. ' . ($result['message'] ?? 'Provisioning queued.'));
    }

    private function customerPayload(array $data, Request $request, int $ispId, ?int $packageId, bool $creating = true): array
    {
        $payload = [
            'isp_id' => $ispId,
            'internet_package_id' => $packageId,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'location' => $data['location'] ?? null,
            'address' => $data['address'] ?? null,
            'connection_status' => $data['connection_status'],
            'billing_status' => $data['billing_status'],
            'monthly_amount' => $data['monthly_amount'],
            'installation_date' => $data['installation_date'] ?? null,
            'next_due_date' => $data['next_due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ];

        if ($creating) {
            $payload['created_by'] = $request->user()->id;
        }

        foreach (['mikrotik_router_id', 'access_type', 'username', 'password', 'mac_address', 'ip_address', 'shared_users'] as $field) {
            if ($this->hasColumn('isp_customers', $field) && array_key_exists($field, $data)) {
                $payload[$field] = $data[$field] ?: null;
            }
        }

        if ($this->hasColumn('isp_customers', 'provisioning_status')) {
            $payload['provisioning_status'] = 'pending';
        }

        return $payload;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'isp_id' => ['nullable', 'exists:isps,id'],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'mikrotik_router_id' => ['nullable', 'exists:mikrotik_routers,id'],
            'access_type' => ['nullable', Rule::in(['hotspot', 'pppoe'])],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'mac_address' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:255'],
            'shared_users' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'connection_status' => ['required', Rule::in(['pending', 'active', 'suspended', 'disconnected'])],
            'billing_status' => ['required', Rule::in(['paid', 'unpaid', 'overdue'])],
            'monthly_amount' => ['required', 'numeric', 'min:0'],
            'installation_date' => ['nullable', 'date'],
            'next_due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function customerQuery(Request $request)
    {
        return Customer::query()->when(! $this->isPlatform($request), function ($query) use ($request) {
            $query->where('isp_id', $this->resolveIsp($request)->id);
        });
    }

    private function applyCustomerFilters($query, Request $request)
    {
        $search = trim((string) ($request->query('q') ?: $request->query('search')));
        $accessType = strtolower((string) $request->query('access_type'));
        $connectionStatus = strtolower((string) $request->query('connection_status'));
        $billingStatus = strtolower((string) $request->query('billing_status'));
        $view = strtolower((string) $request->query('view'));

        if ($this->hasColumn('isp_customers', 'access_type') && in_array($accessType, ['hotspot', 'pppoe'], true)) {
            $query->where('access_type', $accessType);
        }

        if (in_array($connectionStatus, ['pending', 'active', 'suspended', 'disconnected'], true)) {
            $query->where('connection_status', $connectionStatus);
        }

        if (in_array($billingStatus, ['paid', 'unpaid', 'overdue'], true)) {
            $query->where('billing_status', $billingStatus);
        }

        if ($view === 'installations') {
            $query->where('connection_status', 'pending');
        }

        if ($view === 'subscriptions') {
            $query->where(function ($subQuery) {
                $subQuery->whereNotNull('next_due_date')
                    ->orWhere('monthly_amount', '>', 0)
                    ->orWhereNotNull('billing_status')
                    ->orWhereNotNull('connection_status');
            });
        }

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('mac_address', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function customerPageTitle(Request $request): string
    {
        if ($request->query('view') === 'subscriptions') {
            return 'Subscriptions';
        }

        if ($request->query('view') === 'installations' || $request->query('connection_status') === 'pending') {
            return 'Installations';
        }

        return match ($request->query('access_type')) {
            'pppoe' => 'PPPoE Users',
            'hotspot' => 'Hotspot Users',
            default => 'Customers',
        };
    }

    private function customerActiveFilter(Request $request): ?string
    {
        return $this->customerPageTitle($request) === 'Customers' ? null : $this->customerPageTitle($request);
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

    private function authorizePackage(int $ispId, $packageId): void
    {
        if ($packageId) {
            abort_unless(InternetPackage::where('id', $packageId)->where('isp_id', $ispId)->exists(), 403);
        }
    }

    private function authorizeRouter(int $ispId, $routerId): void
    {
        if ($routerId) {
            abort_unless(MikrotikRouter::where('id', $routerId)->where('isp_id', $ispId)->exists(), 403);
        }
    }

    private function authorizeIspRecord(Request $request, ?int $ispId): void
    {
        app(IspTenantResolver::class)->authorize($request, $ispId);
    }

    private function isPlatform(Request $request): bool
    {
        return app(IspTenantResolver::class)->isPlatform($request);
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }
}
