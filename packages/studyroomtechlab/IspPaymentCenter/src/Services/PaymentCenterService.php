<?php

namespace StudyRoomTechLab\IspPaymentCenter\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentCenterService
{
    private const MPESA_TABLE = 'mpesa_transactions';
    private const MANUAL_TABLE = 'isp_payment_center_manual_payments';

    private array $tableColumns = [];

    public function dashboard(Request $request): array
    {
        $filters = $this->filters($request);
        $query = $this->baseQuery();

        if (! $query) {
            return [
                'summary' => $this->emptySummary(),
                'transactions' => [],
                'pagination' => $this->emptyPagination(),
                'filters' => $filters,
                'filterOptions' => $this->filterOptions(),
                'columnsUsed' => $this->columnsUsed(),
                'exportEnabled' => false,
                'recordPaymentEnabled' => $this->manualPaymentsReady(),
            ];
        }

        $this->applyFilters($query, $filters);
        $this->applySearch($query, $filters['search']);
        $this->applySorting($query);

        $paginator = $query->paginate($filters['perPage'])->withQueryString();

        return [
            'summary' => $this->summary(),
            'transactions' => $this->mapRows($paginator),
            'pagination' => $this->pagination($paginator),
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
            'columnsUsed' => $this->columnsUsed(),
            'exportEnabled' => true,
            'recordPaymentEnabled' => $this->manualPaymentsReady(),
        ];
    }

    public function exportRows(Request $request, int $limit = 5000): array
    {
        $query = $this->baseQuery();
        if (! $query) {
            return [];
        }

        $filters = $this->filters($request);
        $this->applyFilters($query, $filters);
        $this->applySearch($query, $filters['search']);
        $this->applySorting($query);

        return $query->limit($limit)->get()->map(fn ($row) => $this->mapRow($row))->all();
    }

    public function storeManualPayment(Request $request, array $validated): void
    {
        if (! $this->manualPaymentsReady()) {
            throw ValidationException::withMessages([
                'manual_payment' => 'Manual payment storage is not ready. Run package migrations first.',
            ]);
        }

        $record = [];
        $this->setManualValue($record, 'customer_name', $validated['customer_name']);
        $this->setManualValue($record, 'phone', $validated['phone'] ?? null);
        $this->setManualValue($record, 'account', $validated['account'] ?? null);
        $this->setManualValue($record, 'receipt', $validated['receipt'] ?: $this->manualReceipt());
        $this->setManualValue($record, 'method', $validated['method']);
        $this->setManualValue($record, 'source', 'Manual');
        $this->setManualValue($record, 'amount', $validated['amount']);
        $this->setManualValue($record, 'currency', strtoupper($validated['currency'] ?? 'KES'));
        $this->setManualValue($record, 'status', $validated['status']);
        $this->setManualValue($record, 'notes', $validated['notes'] ?? null);
        $this->setManualValue($record, 'recorded_by', $request->user()?->getAuthIdentifier());
        $this->setManualValue($record, 'recorded_at', now());
        $this->setManualValue($record, 'created_at', now());
        $this->setManualValue($record, 'updated_at', now());

        DB::table(self::MANUAL_TABLE)->insert($record);
    }

    public function columnsUsed(): array
    {
        return [
            'mpesa_transactions' => array_values(array_intersect([
                'id',
                'mpesa_receipt_number',
                'checkout_request_id',
                'phone',
                'amount',
                'currency',
                'status',
                'wallet_posted',
                'provisioning_triggered',
                'internet_package_id',
                'customer_id',
                'account_reference',
                'payment_type',
                'collection_mode',
                'environment',
                'created_at',
            ], $this->columns(self::MPESA_TABLE))),
            'isp_payment_center_manual_payments' => array_values(array_intersect([
                'id',
                'customer_name',
                'phone',
                'account',
                'receipt',
                'method',
                'source',
                'package',
                'amount',
                'currency',
                'status',
                'notes',
                'recorded_by',
                'recorded_at',
                'created_at',
            ], $this->columns(self::MANUAL_TABLE))),
            'customer_table' => $this->customerTable(),
            'internet_packages' => Schema::hasTable('internet_packages') ? array_values(array_intersect([
                'id',
                'name',
                'title',
            ], $this->columns('internet_packages'))) : [],
            'provisioning_tokens' => Schema::hasTable('provisioning_tokens') ? 'available_not_joined' : 'missing',
        ];
    }

    private function baseQuery(): ?Builder
    {
        $queries = array_values(array_filter([
            $this->mpesaQuery(),
            $this->manualQuery(),
        ]));

        if ($queries === []) {
            return null;
        }

        $union = array_shift($queries);
        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        return DB::query()->fromSub($union, 'payments');
    }

    private function mpesaQuery(): ?Builder
    {
        if (! Schema::hasTable(self::MPESA_TABLE) || ! $this->hasColumn(self::MPESA_TABLE, 'id')) {
            return null;
        }

        $query = DB::table(self::MPESA_TABLE . ' as t');
        $selects = [
            DB::raw("CONCAT('mpesa-', t.id) as id"),
            't.id as sort_id',
            DB::raw("'mpesa' as record_source"),
            $this->selectTableColumn(self::MPESA_TABLE, 't', 'customer_id', 'customer_id'),
            $this->coalesceTableColumns(self::MPESA_TABLE, 't', ['mpesa_receipt_number', 'checkout_request_id'], 'receipt'),
            $this->selectTableColumn(self::MPESA_TABLE, 't', 'phone', 'phone'),
            $this->selectTableColumn(self::MPESA_TABLE, 't', 'account_reference', 'account'),
            DB::raw("'M-Pesa' as method"),
            $this->mpesaSourceColumn(),
            $this->selectTableColumn(self::MPESA_TABLE, 't', 'amount', 'amount', '0'),
            $this->selectTableColumn(self::MPESA_TABLE, 't', 'currency', 'currency', "'KES'"),
            $this->selectTableColumn(self::MPESA_TABLE, 't', 'status', 'status', "'unknown'"),
            $this->selectTableColumn(self::MPESA_TABLE, 't', 'wallet_posted', 'wallet_posted'),
            $this->selectTableColumn(self::MPESA_TABLE, 't', 'provisioning_triggered', 'provisioning_triggered'),
            $this->selectTableColumn(self::MPESA_TABLE, 't', 'created_at', 'payment_date'),
            DB::raw('NULL as notes'),
        ];

        $packageNameColumn = $this->firstColumn('internet_packages', ['name', 'title']);
        if ($this->hasColumn(self::MPESA_TABLE, 'internet_package_id') && Schema::hasTable('internet_packages') && $this->hasColumn('internet_packages', 'id')) {
            $query->leftJoin('internet_packages as p', 'p.id', '=', 't.internet_package_id');
            $selects[] = $packageNameColumn ? "p.{$packageNameColumn} as package_name" : DB::raw('NULL as package_name');
        } else {
            $selects[] = DB::raw('NULL as package_name');
        }

        $customerTable = $this->customerTable();
        $customerNameColumn = $customerTable ? $this->firstColumn($customerTable, ['name', 'full_name', 'customer_name', 'company_name', 'contact_person_name', 'username']) : null;
        $customerPhoneColumn = $customerTable ? $this->firstColumn($customerTable, ['phone', 'phone_number', 'mobile', 'mobile_number', 'contact_person_mobile', 'customer_phone']) : null;
        $customerAccountColumn = $customerTable ? $this->firstColumn($customerTable, ['account_number', 'customer_code', 'username']) : null;
        $customerJoined = $customerTable && $this->hasColumn(self::MPESA_TABLE, 'customer_id') && $this->hasColumn($customerTable, 'id');

        if ($customerJoined) {
            $query->leftJoin($customerTable . ' as c', 'c.id', '=', 't.customer_id');
            $selects[] = $customerNameColumn ? "c.{$customerNameColumn} as customer_name" : DB::raw('NULL as customer_name');
            $selects[] = $this->coalesceExpressions(array_filter([
                $this->hasColumn(self::MPESA_TABLE, 'phone') ? 't.phone' : null,
                $customerPhoneColumn ? "c.{$customerPhoneColumn}" : null,
            ]), 'display_phone');
            $selects[] = $this->coalesceExpressions(array_filter([
                $this->hasColumn(self::MPESA_TABLE, 'account_reference') ? 't.account_reference' : null,
                $customerAccountColumn ? "c.{$customerAccountColumn}" : null,
            ]), 'display_account');
        } else {
            $selects[] = DB::raw('NULL as customer_name');
            $selects[] = $this->selectTableColumn(self::MPESA_TABLE, 't', 'phone', 'display_phone');
            $selects[] = $this->selectTableColumn(self::MPESA_TABLE, 't', 'account_reference', 'display_account');
        }

        return $query->select($selects);
    }

    private function manualQuery(): ?Builder
    {
        if (! $this->manualPaymentsReady() || ! $this->hasColumn(self::MANUAL_TABLE, 'id')) {
            return null;
        }

        return DB::table(self::MANUAL_TABLE . ' as m')->select([
            DB::raw("CONCAT('manual-', m.id) as id"),
            'm.id as sort_id',
            DB::raw("'manual' as record_source"),
            DB::raw('NULL as customer_id'),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'receipt', 'receipt'),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'phone', 'phone'),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'account', 'account'),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'method', 'method', "'Cash'"),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'source', 'source', "'Manual'"),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'amount', 'amount', '0'),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'currency', 'currency', "'KES'"),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'status', 'status', "'confirmed'"),
            DB::raw('NULL as wallet_posted'),
            DB::raw('NULL as provisioning_triggered'),
            $this->coalesceTableColumns(self::MANUAL_TABLE, 'm', ['recorded_at', 'created_at'], 'payment_date'),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'notes', 'notes'),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'package', 'package_name'),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'customer_name', 'customer_name'),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'phone', 'display_phone'),
            $this->selectTableColumn(self::MANUAL_TABLE, 'm', 'account', 'display_account'),
        ]);
    }

    private function filters(Request $request): array
    {
        $filter = Str::slug((string) $request->query('filter', 'all'));
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 15);

        return [
            'filter' => $filter ?: 'all',
            'search' => $search,
            'perPage' => min(max($perPage, 10), 100),
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $filter = $filters['filter'];

        if ($filter === 'all') {
            return;
        }

        match ($filter) {
            'm-pesa' => $query->where('method', 'M-Pesa'),
            'cash' => $query->where('method', 'Cash'),
            'bank' => $query->where('method', 'Bank'),
            'voucher' => $query->where('method', 'Voucher'),
            'pending' => $query->whereIn('status', ['pending', 'processing', 'queued']),
            'confirmed' => $query->whereIn('status', ['paid', 'confirmed', 'success', 'completed']),
            'failed' => $query->whereIn('status', ['failed', 'cancelled', 'canceled', 'expired']),
            'reversed' => $query->whereIn('status', ['reversed', 'refunded', 'chargeback']),
            'wallet-posted' => $query->where('wallet_posted', 1),
            'not-posted' => $query->where(function (Builder $query) {
                $query->whereNull('wallet_posted')->orWhere('wallet_posted', 0);
            }),
            'provisioned' => $query->where('provisioning_triggered', 1),
            'not-provisioned' => $query->where(function (Builder $query) {
                $query->whereNull('provisioning_triggered')->orWhere('provisioning_triggered', 0);
            }),
            default => null,
        };
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';

        $query->where(function (Builder $query) use ($like) {
            foreach (['receipt', 'display_phone', 'display_account', 'customer_name'] as $column) {
                $query->orWhere($column, 'like', $like);
            }
        });
    }

    private function applySorting(Builder $query): void
    {
        $query->orderByDesc('payment_date')->orderByDesc('sort_id');
    }

    private function summary(): array
    {
        $todayQuery = $this->confirmedQuery();
        $weekQuery = $this->confirmedQuery();
        $monthQuery = $this->confirmedQuery();

        if ($todayQuery) {
            $todayQuery->whereDate('payment_date', now()->toDateString());
        }

        if ($weekQuery) {
            $weekQuery->whereBetween('payment_date', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        if ($monthQuery) {
            $monthQuery->whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        return [
            $this->amountCard('todays-collections', "Today's Collections", $todayQuery, 'Collected today'),
            $this->amountCard('this-week', 'This Week', $weekQuery, 'Confirmed this week'),
            $this->amountCard('this-month', 'This Month', $monthQuery, 'Confirmed this month'),
            $this->countCard('pending-verification', 'Pending Verification', $this->statusQuery(['pending', 'processing', 'queued']), 'Awaiting confirmation'),
            $this->countCard('wallet-posted', 'Wallet Posted', $this->booleanQuery('wallet_posted', true), 'Posted to wallet'),
            $this->countCard('provisioned-payments', 'Provisioned Payments', $this->booleanQuery('provisioning_triggered', true), 'Provisioning triggered'),
            $this->countCard('failed-reversed', 'Failed / Reversed', $this->statusQuery(['failed', 'cancelled', 'canceled', 'expired', 'reversed', 'refunded', 'chargeback']), 'Failed or reversed records'),
        ];
    }

    private function emptySummary(): array
    {
        return [
            ['key' => 'todays-collections', 'title' => "Today's Collections", 'value' => 'KES 0.00', 'description' => 'Collected today', 'tone' => 'pink'],
            ['key' => 'this-week', 'title' => 'This Week', 'value' => 'KES 0.00', 'description' => 'Confirmed this week', 'tone' => 'blue'],
            ['key' => 'this-month', 'title' => 'This Month', 'value' => 'KES 0.00', 'description' => 'Confirmed this month', 'tone' => 'green'],
            ['key' => 'pending-verification', 'title' => 'Pending Verification', 'value' => '0', 'description' => 'Awaiting confirmation', 'tone' => 'amber'],
            ['key' => 'wallet-posted', 'title' => 'Wallet Posted', 'value' => '0', 'description' => 'Posted to wallet', 'tone' => 'slate'],
            ['key' => 'provisioned-payments', 'title' => 'Provisioned Payments', 'value' => '0', 'description' => 'Provisioning triggered', 'tone' => 'violet'],
            ['key' => 'failed-reversed', 'title' => 'Failed / Reversed', 'value' => '0', 'description' => 'Failed or reversed records', 'tone' => 'red'],
        ];
    }

    private function amountCard(string $key, string $title, ?Builder $query, string $description): array
    {
        $amount = $query ? (float) $query->sum('amount') : 0.0;

        return [
            'key' => $key,
            'title' => $title,
            'value' => $this->formatMoney($amount, 'KES'),
            'description' => $description,
            'tone' => match ($key) {
                'todays-collections' => 'pink',
                'this-week' => 'blue',
                default => 'green',
            },
        ];
    }

    private function countCard(string $key, string $title, ?Builder $query, string $description): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'value' => (string) ($query ? $query->count() : 0),
            'description' => $description,
            'tone' => match ($key) {
                'pending-verification' => 'amber',
                'failed-reversed' => 'red',
                'provisioned-payments' => 'violet',
                default => 'slate',
            },
        ];
    }

    private function confirmedQuery(): ?Builder
    {
        return $this->statusQuery(['paid', 'confirmed', 'success', 'completed']);
    }

    private function statusQuery(array $statuses): ?Builder
    {
        $query = $this->baseQuery();

        return $query?->whereIn('status', $statuses);
    }

    private function booleanQuery(string $column, bool $value): ?Builder
    {
        $query = $this->baseQuery();

        return $query?->where($column, $value ? 1 : 0);
    }

    private function mapRows(LengthAwarePaginator $paginator): array
    {
        return $paginator->getCollection()->map(fn ($row) => $this->mapRow($row))->values()->all();
    }

    private function mapRow(object $row): array
    {
        $currency = $row->currency ?: 'KES';

        return [
            'id' => $row->id,
            'customer' => $row->customer_name ?: ($row->customer_id ? 'Customer #' . $row->customer_id : 'Walk-in / Hotspot customer'),
            'phone' => $row->display_phone ?: $row->phone,
            'account' => $row->display_account ?: $row->account,
            'receipt' => $row->receipt,
            'method' => $row->method ?: 'Unknown',
            'source' => $row->source ?: 'Hotspot',
            'package' => $row->package_name,
            'amount' => (float) ($row->amount ?? 0),
            'amount_formatted' => $this->formatMoney((float) ($row->amount ?? 0), $currency),
            'currency' => $currency,
            'status' => $row->status ?: 'unknown',
            'wallet' => $this->booleanLabel($row->wallet_posted, 'Posted', 'Not Posted'),
            'wallet_posted' => (bool) $row->wallet_posted,
            'provisioning' => $this->booleanLabel($row->provisioning_triggered, 'Provisioned', 'Not Provisioned'),
            'provisioning_triggered' => (bool) $row->provisioning_triggered,
            'date' => $row->payment_date,
            'notes' => $row->notes,
            'view_url' => null,
            'download_receipt_url' => null,
        ];
    }

    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'next_page_url' => $paginator->nextPageUrl(),
        ];
    }

    private function emptyPagination(): array
    {
        return [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 15,
            'from' => null,
            'to' => null,
            'total' => 0,
            'prev_page_url' => null,
            'next_page_url' => null,
        ];
    }

    private function filterOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'All'],
            ['value' => 'm-pesa', 'label' => 'M-Pesa'],
            ['value' => 'cash', 'label' => 'Cash'],
            ['value' => 'bank', 'label' => 'Bank'],
            ['value' => 'voucher', 'label' => 'Voucher'],
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'confirmed', 'label' => 'Confirmed'],
            ['value' => 'failed', 'label' => 'Failed'],
            ['value' => 'reversed', 'label' => 'Reversed'],
            ['value' => 'wallet-posted', 'label' => 'Wallet Posted'],
            ['value' => 'not-posted', 'label' => 'Not Posted'],
            ['value' => 'provisioned', 'label' => 'Provisioned'],
            ['value' => 'not-provisioned', 'label' => 'Not Provisioned'],
        ];
    }

    private function mpesaSourceColumn(): mixed
    {
        $paymentType = $this->hasColumn(self::MPESA_TABLE, 'payment_type') ? 'LOWER(COALESCE(t.payment_type, \'\'))' : "''";
        $collectionMode = $this->hasColumn(self::MPESA_TABLE, 'collection_mode') ? 'LOWER(COALESCE(t.collection_mode, \'\'))' : "''";
        $environment = $this->hasColumn(self::MPESA_TABLE, 'environment') ? 'LOWER(COALESCE(t.environment, \'\'))' : "''";

        return DB::raw("CASE
            WHEN {$paymentType} LIKE '%hotspot%' OR {$collectionMode} LIKE '%hotspot%' THEN 'Hotspot'
            WHEN {$paymentType} LIKE '%simulation%' OR {$environment} = 'simulation' THEN 'Simulation'
            WHEN {$collectionMode} <> '' THEN {$collectionMode}
            ELSE 'Hotspot'
        END as source");
    }

    private function selectTableColumn(string $table, string $tableAlias, string $column, string $alias, string $fallback = 'NULL'): mixed
    {
        if ($this->hasColumn($table, $column)) {
            return "{$tableAlias}.{$column} as {$alias}";
        }

        return DB::raw("{$fallback} as {$alias}");
    }

    private function coalesceTableColumns(string $table, string $tableAlias, array $columns, string $alias, string $fallback = 'NULL'): mixed
    {
        $expressions = [];
        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                $expressions[] = "{$tableAlias}.{$column}";
            }
        }

        return $this->coalesceExpressions($expressions, $alias, $fallback);
    }

    private function coalesceExpressions(array $expressions, string $alias, string $fallback = 'NULL'): mixed
    {
        $expressions = array_values(array_filter($expressions));

        if ($expressions === []) {
            return DB::raw("{$fallback} as {$alias}");
        }

        return DB::raw('COALESCE(' . implode(', ', $expressions) . ") as {$alias}");
    }

    private function setManualValue(array &$record, string $column, mixed $value): void
    {
        if ($this->hasColumn(self::MANUAL_TABLE, $column)) {
            $record[$column] = $value;
        }
    }

    private function manualPaymentsReady(): bool
    {
        return Schema::hasTable(self::MANUAL_TABLE);
    }

    private function manualReceipt(): string
    {
        return 'MANUAL-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }

    private function booleanLabel(mixed $value, string $trueLabel, string $falseLabel): string
    {
        if ($value === null) {
            return 'Unknown';
        }

        return (bool) $value ? $trueLabel : $falseLabel;
    }

    private function formatMoney(float $amount, string $currency): string
    {
        return trim(($currency ?: 'KES') . ' ' . number_format($amount, 2));
    }

    private function customerTable(): ?string
    {
        foreach (['isp_customers', 'customers'] as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    private function firstColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }

    private function columns(string $table): array
    {
        if (! array_key_exists($table, $this->tableColumns)) {
            $this->tableColumns[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }

        return $this->tableColumns[$table];
    }
}
