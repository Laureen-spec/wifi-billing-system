<?php

namespace StudyRoomTechLab\Leads\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Services\IspTenantResolver;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeadDeskController extends Controller
{
    private array $statuses = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'quoted' => 'Quoted',
        'scheduled' => 'Scheduled',
        'converted' => 'Converted',
        'lost' => 'Lost',
    ];

    private array $priorities = [
        'hot' => 'Hot',
        'warm' => 'Warm',
        'cold' => 'Cold',
    ];

    private array $sources = [
        'walk-in' => 'Walk-in',
        'whatsapp' => 'WhatsApp',
        'referral' => 'Referral',
        'website' => 'Website',
        'facebook' => 'Facebook',
        'field-agent' => 'Field agent',
        'support-call' => 'Support call',
        'other' => 'Other',
    ];

    public function index(Request $request): Response
    {
        $this->authorizeView($request);
        $this->ensureLeadTable();

        $baseQuery = $this->leadQuery($request);
        $filteredQuery = $this->applyFilters(clone $baseQuery, $request);

        $leads = (clone $filteredQuery)
            ->orderByRaw("CASE priority WHEN 'hot' THEN 1 WHEN 'warm' THEN 2 ELSE 3 END")
            ->orderByRaw('CASE WHEN next_follow_up_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('next_follow_up_at')
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 15))
            ->withQueryString()
            ->through(fn ($lead): array => $this->serializeLead($lead));

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'new' => (clone $baseQuery)->where('status', 'new')->count(),
            'contacted' => (clone $baseQuery)->whereIn('status', ['contacted', 'quoted', 'scheduled'])->count(),
            'converted' => (clone $baseQuery)->where('status', 'converted')->count(),
            'due' => (clone $baseQuery)
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<=', now())
                ->whereNotIn('status', ['converted', 'lost'])
                ->count(),
        ];

        return Inertia::render('Leads/index', [
            'pageTitle' => 'Lead Desk',
            'subtitle' => 'Capture prospects before they become active WiFi billing customers.',
            'leads' => $leads,
            'stats' => $stats,
            'filters' => [
                'q' => trim((string) $request->query('q')),
                'status' => trim((string) $request->query('status')),
                'source' => trim((string) $request->query('source')),
                'priority' => trim((string) $request->query('priority')),
            ],
            'options' => [
                'statuses' => $this->optionList($this->statuses),
                'priorities' => $this->optionList($this->priorities),
                'sources' => $this->optionList($this->sources),
                'users' => $this->availableUsers($request),
            ],
            'permissions' => [
                'canManage' => $this->canManage($request),
                'canConvert' => $this->canManage($request) && Schema::hasTable('isp_customers'),
            ],
            'routes' => [
                'index' => route('studyroom-leads.index'),
                'store' => route('studyroom-leads.store'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->ensureLeadTable();

        $data = $this->validated($request);
        $isp = app(IspTenantResolver::class)->resolve($request, $data['isp_id'] ?? null);

        $payload = $this->payload($data, $request, (int) $isp->id);
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        DB::table('isp_leads')->insert($payload);

        return redirect()
            ->route('studyroom-leads.index')
            ->with('success', 'Lead saved. Follow-up pipeline updated.');
    }

    public function update(Request $request, int $lead): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->ensureLeadTable();

        $record = $this->findLeadForRequest($request, $lead);
        $data = $this->validated($request, $record);

        DB::table('isp_leads')
            ->where('id', $record->id)
            ->update(array_merge(
                $this->payload($data, $request, (int) $record->isp_id, false),
                ['updated_at' => now()]
            ));

        return redirect()
            ->route('studyroom-leads.index', $request->only(['q', 'status', 'source', 'priority']))
            ->with('success', 'Lead updated.');
    }

    public function markContacted(Request $request, int $lead): RedirectResponse
    {
        $this->authorizeManage($request);
        $record = $this->findLeadForRequest($request, $lead);

        DB::table('isp_leads')
            ->where('id', $record->id)
            ->update([
                'status' => $record->status === 'new' ? 'contacted' : $record->status,
                'last_contact_at' => now(),
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Lead marked as contacted.');
    }

    public function convert(Request $request, int $lead): RedirectResponse
    {
        $this->authorizeManage($request);
        $record = $this->findLeadForRequest($request, $lead);

        if (! Schema::hasTable('isp_customers')) {
            return back()->with('error', 'Customer table is not ready yet. Run migrations first.');
        }

        $customer = null;

        if (! empty($record->converted_customer_id)) {
            $customer = Customer::find($record->converted_customer_id);
        }

        if (! $customer && ! empty($record->phone)) {
            $customer = Customer::query()
                ->where('isp_id', $record->isp_id)
                ->where('phone', $record->phone)
                ->first();
        }

        if (! $customer) {
            $customer = Customer::create([
                'isp_id' => $record->isp_id,
                'name' => $record->name ?: 'Lead #' . $record->id,
                'phone' => $record->phone,
                'email' => $record->email,
                'location' => $record->location,
                'address' => $record->location,
                'access_type' => 'hotspot',
                'username' => $this->customerUsername($record),
                'shared_users' => 1,
                'connection_status' => 'inactive',
                'billing_status' => 'pending',
                'provisioning_status' => 'pending',
                'monthly_amount' => $record->value_estimate,
                'notes' => trim(($record->notes ? $record->notes . "\n\n" : '') . 'Converted from Lead Desk on ' . now()->format('M d, Y H:i') . '.'),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        }

        DB::table('isp_leads')
            ->where('id', $record->id)
            ->update([
                'status' => 'converted',
                'converted_customer_id' => $customer->id,
                'converted_at' => now(),
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('isp.customers.show', $customer)
            ->with('success', 'Lead converted to customer. Review package and provisioning details before activation.');
    }

    public function destroy(Request $request, int $lead): RedirectResponse
    {
        $this->authorizeManage($request);
        $record = $this->findLeadForRequest($request, $lead);

        DB::table('isp_leads')->where('id', $record->id)->delete();

        return back()->with('success', 'Lead removed.');
    }

    private function validated(Request $request, mixed $record = null): array
    {
        return $request->validate([
            'isp_id' => ['nullable', 'integer'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'location' => ['nullable', 'string', 'max:190'],
            'source' => ['required', 'string', Rule::in(array_keys($this->sources))],
            'interest' => ['nullable', 'string', 'max:190'],
            'status' => ['required', 'string', Rule::in(array_keys($this->statuses))],
            'priority' => ['required', 'string', Rule::in(array_keys($this->priorities))],
            'value_estimate' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function payload(array $data, Request $request, int $ispId, bool $creating = true): array
    {
        $payload = [
            'isp_id' => $ispId,
            'assigned_user_id' => $data['assigned_user_id'] ?? null,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'location' => $data['location'] ?? null,
            'source' => $data['source'] ?? 'walk-in',
            'interest' => $data['interest'] ?? null,
            'status' => $data['status'] ?? 'new',
            'priority' => $data['priority'] ?? 'warm',
            'value_estimate' => $data['value_estimate'] ?? null,
            'next_follow_up_at' => $this->nullableDateTime($data['next_follow_up_at'] ?? null),
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ];

        if ($creating) {
            $payload['created_by'] = $request->user()->id;
        }

        return $payload;
    }

    private function leadQuery(Request $request)
    {
        $query = DB::table('isp_leads')
            ->leftJoin('users as assigned_users', 'assigned_users.id', '=', 'isp_leads.assigned_user_id')
            ->select('isp_leads.*', 'assigned_users.name as assigned_user_name');

        if (! app(IspTenantResolver::class)->isPlatform($request)) {
            $query->where('isp_leads.isp_id', app(IspTenantResolver::class)->resolve($request)->id);
        } elseif ($request->filled('isp_id')) {
            $query->where('isp_leads.isp_id', (int) $request->query('isp_id'));
        }

        return $query;
    }

    private function applyFilters($query, Request $request)
    {
        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('isp_leads.name', 'like', "%{$search}%")
                    ->orWhere('isp_leads.phone', 'like', "%{$search}%")
                    ->orWhere('isp_leads.email', 'like', "%{$search}%")
                    ->orWhere('isp_leads.location', 'like', "%{$search}%")
                    ->orWhere('isp_leads.interest', 'like', "%{$search}%");
            });
        }

        foreach (['status', 'source', 'priority'] as $filter) {
            $value = trim((string) $request->query($filter));
            if ($value !== '') {
                $query->where("isp_leads.{$filter}", $value);
            }
        }

        return $query;
    }

    private function serializeLead(mixed $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'location' => $lead->location,
            'source' => $lead->source,
            'source_label' => $this->sources[$lead->source] ?? ucfirst((string) $lead->source),
            'interest' => $lead->interest,
            'status' => $lead->status,
            'status_label' => $this->statuses[$lead->status] ?? ucfirst((string) $lead->status),
            'priority' => $lead->priority,
            'priority_label' => $this->priorities[$lead->priority] ?? ucfirst((string) $lead->priority),
            'value_estimate' => $lead->value_estimate,
            'value_formatted' => $lead->value_estimate !== null ? 'KSh ' . number_format((float) $lead->value_estimate, 2) : '—',
            'next_follow_up_at' => $this->formatDate($lead->next_follow_up_at),
            'next_follow_up_raw' => $lead->next_follow_up_at,
            'last_contact_at' => $this->formatDate($lead->last_contact_at),
            'notes' => $lead->notes,
            'assigned_user_id' => $lead->assigned_user_id,
            'assigned_user_name' => $lead->assigned_user_name,
            'converted_customer_id' => $lead->converted_customer_id,
            'created_at' => $this->formatDate($lead->created_at),
            'is_due' => $lead->next_follow_up_at
                && ! in_array($lead->status, ['converted', 'lost'], true)
                && Carbon::parse($lead->next_follow_up_at)->lte(now()),
            'routes' => [
                'update' => route('studyroom-leads.update', $lead->id),
                'contacted' => route('studyroom-leads.contacted', $lead->id),
                'convert' => route('studyroom-leads.convert', $lead->id),
                'destroy' => route('studyroom-leads.destroy', $lead->id),
            ],
        ];
    }

    private function availableUsers(Request $request): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        $query = User::query()->select('id', 'name', 'type', 'created_by');

        if (! app(IspTenantResolver::class)->isPlatform($request)) {
            $ownerId = $request->user()->type === 'company'
                ? $request->user()->id
                : ($request->user()->created_by ?: $request->user()->id);

            $query->where(function ($query) use ($ownerId) {
                $query->where('id', $ownerId)->orWhere('created_by', $ownerId);
            });
        }

        return $query->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }

    private function findLeadForRequest(Request $request, int $lead): mixed
    {
        $record = DB::table('isp_leads')->where('id', $lead)->first();
        abort_unless($record, 404);

        app(IspTenantResolver::class)->authorize($request, (int) $record->isp_id);

        return $record;
    }

    private function ensureLeadTable(): void
    {
        abort_unless(Schema::hasTable('isp_leads'), 503, 'Lead Desk is not installed. Run migrations first.');
    }

    private function authorizeView(Request $request): void
    {
        abort_unless(
            $request->user()->can('view-isp-customers') || $request->user()->can('manage-isp-customers'),
            403
        );
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($this->canManage($request), 403);
    }

    private function canManage(Request $request): bool
    {
        return $request->user()->can('manage-isp-customers') || $request->user()->can('create-isp-customers');
    }

    private function nullableDateTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    private function formatDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('M d, Y H:i');
    }

    private function optionList(array $items): array
    {
        return collect($items)
            ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    private function customerUsername(mixed $lead): string
    {
        $source = preg_replace('/\D+/', '', (string) $lead->phone);

        return $source !== '' ? $source : 'lead' . $lead->id;
    }
}
