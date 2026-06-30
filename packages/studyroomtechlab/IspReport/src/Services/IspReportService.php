<?php

namespace StudyRoomTechLab\IspReport\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IspReportService
{
    public function overview(Request $request, int $ispId): array
    {
        $summary = [
            [
                'label' => 'Active subscribers',
                'value' => $this->number($this->customerCount($ispId, ['active', 'online', 'connected'])),
                'description' => 'Customers currently marked active',
            ],
            [
                'label' => '30 day revenue',
                'value' => $this->money($this->paymentAmount($ispId, 30)),
                'description' => 'Confirmed collections in the selected window',
            ],
            [
                'label' => 'Payment logs',
                'value' => $this->number($this->paymentCount($ispId, 30)),
                'description' => 'M-Pesa/manual records captured',
            ],
            [
                'label' => 'Connection records',
                'value' => $this->number($this->connectionCount($ispId)),
                'description' => 'Hotspot and PPPoE subscribers',
            ],
        ];

        return [
            'summary' => $summary,
            'quickLinks' => [
                ['label' => 'Staff logs', 'description' => 'Audit sign-ins and staff actions.', 'href' => route('isp-reports.staff-logs')],
                ['label' => 'Connection logs', 'description' => 'Review Hotspot and PPPoE activity.', 'href' => route('isp-reports.connection-logs')],
                ['label' => 'Payment logs', 'description' => 'Trace collections and receipts.', 'href' => route('isp-reports.payment-logs')],
            ],
            'suggestions' => $this->suggestions(),
            'recentStaffLogs' => $this->staffRows($request, $ispId, 5)->items(),
            'recentConnections' => $this->connectionRows($request, $ispId, 5)->items(),
            'recentPayments' => $this->paymentRows($request, $ispId, 5)->items(),
        ];
    }

    public function staffLogs(Request $request, int $ispId): array
    {
        $logs = $this->staffRows($request, $ispId, 15);

        return [
            'filters' => [
                'search' => (string) $request->query('search', ''),
                'event' => (string) $request->query('event', 'all'),
            ],
            'eventOptions' => [
                ['value' => 'all', 'label' => 'All events'],
                ['value' => 'login', 'label' => 'Logins'],
                ['value' => 'package', 'label' => 'Packages'],
                ['value' => 'router', 'label' => 'Routers'],
                ['value' => 'customer', 'label' => 'Customers'],
            ],
            'logs' => $logs->items(),
            'pagination' => $this->pagination($logs),
            'stats' => [
                ['label' => 'Logs shown', 'value' => $logs->total()],
                ['label' => 'Login records', 'value' => $this->tableCount('login_histories', $ispId, 'created_by')],
                ['label' => 'Package changes', 'value' => $this->tableCount('internet_packages', $ispId, 'isp_id')],
            ],
        ];
    }

    public function connectionLogs(Request $request, int $ispId): array
    {
        $logs = $this->connectionRows($request, $ispId, 15);

        return [
            'filters' => [
                'search' => (string) $request->query('search', ''),
                'type' => (string) $request->query('type', 'all'),
                'status' => (string) $request->query('status', 'all'),
            ],
            'typeOptions' => [
                ['value' => 'all', 'label' => 'All connections'],
                ['value' => 'hotspot', 'label' => 'Hotspot'],
                ['value' => 'pppoe', 'label' => 'PPPoE'],
            ],
            'statusOptions' => [
                ['value' => 'all', 'label' => 'Any status'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'expired', 'label' => 'Expired'],
                ['value' => 'suspended', 'label' => 'Suspended'],
                ['value' => 'offline', 'label' => 'Offline'],
            ],
            'logs' => $logs->items(),
            'pagination' => $this->pagination($logs),
            'stats' => [
                ['label' => 'Hotspot', 'value' => $this->accessTypeCount($ispId, 'hotspot')],
                ['label' => 'PPPoE', 'value' => $this->accessTypeCount($ispId, 'pppoe')],
                ['label' => 'Free access logs', 'value' => $this->tableCount('hotspot_free_access_logs', $ispId, 'isp_id')],
            ],
        ];
    }

    public function paymentLogs(Request $request, int $ispId): array
    {
        $logs = $this->paymentRows($request, $ispId, 15);

        return [
            'filters' => [
                'search' => (string) $request->query('search', ''),
                'status' => (string) $request->query('status', 'all'),
                'method' => (string) $request->query('method', 'all'),
            ],
            'statusOptions' => [
                ['value' => 'all', 'label' => 'Any status'],
                ['value' => 'paid', 'label' => 'Paid'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'failed', 'label' => 'Failed'],
                ['value' => 'confirmed', 'label' => 'Confirmed'],
            ],
            'methodOptions' => [
                ['value' => 'all', 'label' => 'All methods'],
                ['value' => 'mpesa', 'label' => 'M-Pesa'],
                ['value' => 'manual', 'label' => 'Manual'],
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'bank', 'label' => 'Bank'],
                ['value' => 'voucher', 'label' => 'Voucher'],
            ],
            'logs' => $logs->items(),
            'pagination' => $this->pagination($logs),
            'stats' => [
                ['label' => '30 day revenue', 'value' => $this->money($this->paymentAmount($ispId, 30))],
                ['label' => 'Paid records', 'value' => $this->paymentCount($ispId, 30, ['paid', 'success', 'successful', 'confirmed', 'completed'])],
                ['label' => 'Pending records', 'value' => $this->paymentCount($ispId, 30, ['pending', 'stk_sent'])],
            ],
        ];
    }

    private function staffRows(Request $request, int $ispId, int $perPage): LengthAwarePaginator
    {
        $rows = collect();
        $search = trim((string) $request->query('search', ''));
        $event = (string) $request->query('event', 'all');

        if (Schema::hasTable('login_histories')) {
            $query = DB::table('login_histories as lh')
                ->leftJoin('users as u', 'u.id', '=', 'lh.user_id')
                ->selectRaw("lh.id, 'login' as category, COALESCE(lh.type, 'login') as event, 'User sign-in' as target, COALESCE(u.name, 'System user') as actor, COALESCE(lh.ip, '-') as source, COALESCE(lh.created_at, lh.date) as occurred_at, lh.details as changes")
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('u.name', 'like', "%{$search}%")
                            ->orWhere('lh.ip', 'like', "%{$search}%")
                            ->orWhere('lh.type', 'like', "%{$search}%");
                    });
                });

            $this->scopeByCreatorOrIsp($query, 'lh', $ispId);
            $rows = $rows->merge($query->orderByDesc(DB::raw('COALESCE(lh.created_at, lh.date)'))->limit(60)->get());
        }

        foreach ([
            ['internet_packages', 'package', 'Package'],
            ['mikrotik_routers', 'router', 'Router'],
            ['isp_customers', 'customer', 'Customer'],
        ] as [$table, $category, $target]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $dateColumn = $this->dateColumn($table);
            if (! $dateColumn) {
                continue;
            }

            $labelColumn = $this->firstExistingColumn($table, ['name', 'username', 'phone', 'host', 'identity']);
            $createdByColumn = $this->firstExistingColumn($table, ['updated_by', 'created_by']);
            $selectName = $labelColumn ? "COALESCE({$table}.{$labelColumn}, CONCAT('{$target} #', {$table}.id))" : "CONCAT('{$target} #', {$table}.id)";
            $actorJoin = $createdByColumn ? "u.id = {$table}.{$createdByColumn}" : '1 = 0';

            $query = DB::table($table)
                ->leftJoin('users as u', DB::raw($actorJoin), '=', DB::raw($actorJoin === '1 = 0' ? '1' : "{$table}.{$createdByColumn}"));

            if ($createdByColumn) {
                $query = DB::table($table)
                    ->leftJoin('users as u', 'u.id', '=', "{$table}.{$createdByColumn}");
            } else {
                $query = DB::table($table)->leftJoin('users as u', DB::raw('1'), '=', DB::raw('0'));
            }

            $query->selectRaw("{$table}.id, '{$category}' as category, CASE WHEN {$table}.updated_at > {$table}.created_at THEN 'updated' ELSE 'created' END as event, CONCAT('{$target} #', {$table}.id) as target, COALESCE(u.name, 'System') as actor, ? as source, {$table}.{$dateColumn} as occurred_at, {$selectName} as changes", ['Workspace'])
                ->when(Schema::hasColumn($table, 'isp_id'), fn ($query) => $query->where("{$table}.isp_id", $ispId))
                ->when($search !== '', function ($query) use ($search, $labelColumn, $table) {
                    if ($labelColumn) {
                        $query->where("{$table}.{$labelColumn}", 'like', "%{$search}%");
                    }
                });

            $rows = $rows->merge($query->orderByDesc("{$table}.{$dateColumn}")->limit(60)->get());
        }

        if ($event !== 'all') {
            $rows = $rows->filter(fn ($row) => (string) ($row->category ?? '') === $event || str_contains((string) ($row->event ?? ''), $event));
        }

        return $this->paginateCollection($rows->sortByDesc('occurred_at')->values()->map(function ($row) {
            return [
                'id' => (string) ($row->category ?? 'log') . '-' . (string) $row->id,
                'event' => $this->title((string) ($row->event ?? 'activity')),
                'target' => (string) ($row->target ?? 'System'),
                'changes' => $this->normalizeChanges($row->changes ?? null),
                'actor' => (string) ($row->actor ?? 'System'),
                'source' => (string) ($row->source ?? '-'),
                'when' => $this->dateText($row->occurred_at ?? null),
                'tone' => $this->eventTone((string) ($row->event ?? '')),
            ];
        }), $perPage);
    }

    private function connectionRows(Request $request, int $ispId, int $perPage): LengthAwarePaginator
    {
        $rows = collect();
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', 'all');
        $status = (string) $request->query('status', 'all');

        if (Schema::hasTable('isp_customers')) {
            $query = DB::table('isp_customers as c')
                ->leftJoin('internet_packages as p', 'p.id', '=', 'c.internet_package_id')
                ->leftJoin('mikrotik_routers as r', 'r.id', '=', 'c.mikrotik_router_id')
                ->selectRaw("c.id, COALESCE(c.access_type, 'hotspot') as access_type, COALESCE(c.name, c.username, c.phone, CONCAT('Customer #', c.id)) as customer, c.phone, c.username, COALESCE(c.connection_status, c.billing_status, 'unknown') as status, c.billing_status, c.ip_address, c.mac_address, c.data_used_bytes, c.last_online_at, c.next_due_date, COALESCE(p.name, '-') as package_name, COALESCE(r.name, r.host, '-') as router_name, COALESCE(c.last_online_at, c.updated_at, c.created_at) as occurred_at")
                ->where('c.isp_id', $ispId)
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('c.name', 'like', "%{$search}%")
                            ->orWhere('c.phone', 'like', "%{$search}%")
                            ->orWhere('c.username', 'like', "%{$search}%")
                            ->orWhere('c.ip_address', 'like', "%{$search}%")
                            ->orWhere('c.mac_address', 'like', "%{$search}%");
                    });
                })
                ->when($type !== 'all', fn ($query) => $query->where('c.access_type', $type))
                ->when($status !== 'all', fn ($query) => $query->where(function ($query) use ($status) {
                    $query->where('c.connection_status', $status)->orWhere('c.billing_status', $status);
                }));

            $rows = $rows->merge($query->orderByDesc(DB::raw('COALESCE(c.last_online_at, c.updated_at, c.created_at)'))->limit(100)->get()->map(function ($row) {
                return [
                    'id' => 'customer-' . $row->id,
                    'type' => $this->title((string) $row->access_type),
                    'customer' => (string) $row->customer,
                    'phone' => $row->phone,
                    'username' => $row->username,
                    'router' => (string) $row->router_name,
                    'package' => (string) $row->package_name,
                    'ip' => $row->ip_address,
                    'mac' => $row->mac_address,
                    'status' => $this->title((string) $row->status),
                    'billingStatus' => $this->title((string) ($row->billing_status ?? '')),
                    'usage' => $this->bytes($row->data_used_bytes ?? 0),
                    'lastSeen' => $this->dateText($row->last_online_at ?? null),
                    'expires' => $this->dateText($row->next_due_date ?? null),
                    'when' => $this->dateText($row->occurred_at ?? null),
                ];
            }));
        }

        if (Schema::hasTable('hotspot_free_access_logs') && ($type === 'all' || $type === 'hotspot')) {
            $query = DB::table('hotspot_free_access_logs as h')
                ->leftJoin('mikrotik_routers as r', 'r.id', '=', 'h.mikrotik_router_id')
                ->selectRaw("h.id, 'Hotspot' as access_type, COALESCE(h.username, h.phone, h.mac_address, CONCAT('Free access #', h.id)) as customer, h.phone, h.username, h.status, h.ip_address, h.mac_address, h.started_at, h.expires_at, COALESCE(r.name, r.host, '-') as router_name, COALESCE(h.started_at, h.created_at) as occurred_at")
                ->where('h.isp_id', $ispId)
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('h.phone', 'like', "%{$search}%")
                            ->orWhere('h.username', 'like', "%{$search}%")
                            ->orWhere('h.mac_address', 'like', "%{$search}%")
                            ->orWhere('h.ip_address', 'like', "%{$search}%");
                    });
                })
                ->when($status !== 'all', fn ($query) => $query->where('h.status', $status));

            $rows = $rows->merge($query->orderByDesc(DB::raw('COALESCE(h.started_at, h.created_at)'))->limit(60)->get()->map(function ($row) {
                return [
                    'id' => 'free-' . $row->id,
                    'type' => 'Hotspot Free',
                    'customer' => (string) $row->customer,
                    'phone' => $row->phone,
                    'username' => $row->username,
                    'router' => (string) $row->router_name,
                    'package' => 'Free access',
                    'ip' => $row->ip_address,
                    'mac' => $row->mac_address,
                    'status' => $this->title((string) $row->status),
                    'billingStatus' => 'Free',
                    'usage' => '-',
                    'lastSeen' => $this->dateText($row->started_at ?? null),
                    'expires' => $this->dateText($row->expires_at ?? null),
                    'when' => $this->dateText($row->occurred_at ?? null),
                ];
            }));
        }

        return $this->paginateCollection($rows->sortByDesc('when')->values(), $perPage);
    }

    private function paymentRows(Request $request, int $ispId, int $perPage): LengthAwarePaginator
    {
        $rows = collect();
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        $method = (string) $request->query('method', 'all');

        if (Schema::hasTable('mpesa_transactions')) {
            $query = DB::table('mpesa_transactions as m')
                ->leftJoin('isp_customers as c', 'c.id', '=', 'm.customer_id')
                ->leftJoin('internet_packages as p', 'p.id', '=', 'm.internet_package_id')
                ->selectRaw("m.id, COALESCE(c.name, c.username, m.phone, CONCAT('Payment #', m.id)) as customer, m.phone, COALESCE(m.mpesa_receipt_number, m.checkout_request_id, m.merchant_request_id, '-') as receipt, 'M-Pesa' as method, COALESCE(m.payment_type, 'stk_push') as source, COALESCE(p.name, m.account_reference, '-') as package_name, m.amount, COALESCE(m.currency, 'KES') as currency, m.status, m.wallet_posted, m.provisioning_triggered, COALESCE(m.paid_at, m.failed_at, m.created_at) as paid_at")
                ->where('m.isp_id', $ispId)
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('c.name', 'like', "%{$search}%")
                            ->orWhere('c.username', 'like', "%{$search}%")
                            ->orWhere('m.phone', 'like', "%{$search}%")
                            ->orWhere('m.mpesa_receipt_number', 'like', "%{$search}%")
                            ->orWhere('m.checkout_request_id', 'like', "%{$search}%");
                    });
                })
                ->when($status !== 'all', fn ($query) => $query->where('m.status', $status))
                ->when($method !== 'all' && $method !== 'mpesa', fn ($query) => $query->whereRaw('1 = 0'));

            $rows = $rows->merge($query->orderByDesc(DB::raw('COALESCE(m.paid_at, m.failed_at, m.created_at)'))->limit(100)->get()->map(function ($row) {
                return [
                    'id' => 'mpesa-' . $row->id,
                    'customer' => (string) $row->customer,
                    'phone' => $row->phone,
                    'receipt' => (string) $row->receipt,
                    'method' => (string) $row->method,
                    'source' => $this->title((string) $row->source),
                    'package' => (string) $row->package_name,
                    'amount' => $this->money($row->amount ?? 0, (string) $row->currency),
                    'status' => $this->title((string) $row->status),
                    'wallet' => (bool) $row->wallet_posted ? 'Posted' : 'Not posted',
                    'provisioning' => (bool) $row->provisioning_triggered ? 'Triggered' : 'Not triggered',
                    'when' => $this->dateText($row->paid_at ?? null),
                ];
            }));
        }

        if (Schema::hasTable('isp_payment_center_manual_payments')) {
            $query = DB::table('isp_payment_center_manual_payments as p')
                ->selectRaw("p.id, COALESCE(p.customer_name, p.phone, p.account, CONCAT('Manual #', p.id)) as customer, p.phone, COALESCE(p.receipt, '-') as receipt, COALESCE(p.method, 'Manual') as method, COALESCE(p.source, 'Manual') as source, COALESCE(p.package, '-') as package_name, p.amount, COALESCE(p.currency, 'KES') as currency, p.status, COALESCE(p.recorded_at, p.created_at) as paid_at")
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('p.customer_name', 'like', "%{$search}%")
                            ->orWhere('p.phone', 'like', "%{$search}%")
                            ->orWhere('p.receipt', 'like', "%{$search}%")
                            ->orWhere('p.account', 'like', "%{$search}%");
                    });
                })
                ->when($status !== 'all', fn ($query) => $query->where('p.status', $status))
                ->when($method !== 'all', function ($query) use ($method) {
                    if ($method === 'manual') {
                        return;
                    }
                    $query->where('p.method', 'like', "%{$method}%");
                });

            if (Schema::hasColumn('isp_payment_center_manual_payments', 'isp_id')) {
                $query->where('p.isp_id', $ispId);
            } elseif (Schema::hasColumn('isp_payment_center_manual_payments', 'recorded_by') && Schema::hasTable('users')) {
                $userIds = $this->tenantUserIds($ispId);
                if ($userIds !== []) {
                    $query->whereIn('p.recorded_by', $userIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $rows = $rows->merge($query->orderByDesc(DB::raw('COALESCE(p.recorded_at, p.created_at)'))->limit(80)->get()->map(function ($row) {
                return [
                    'id' => 'manual-' . $row->id,
                    'customer' => (string) $row->customer,
                    'phone' => $row->phone,
                    'receipt' => (string) $row->receipt,
                    'method' => (string) $row->method,
                    'source' => (string) $row->source,
                    'package' => (string) $row->package_name,
                    'amount' => $this->money($row->amount ?? 0, (string) $row->currency),
                    'status' => $this->title((string) $row->status),
                    'wallet' => 'Manual',
                    'provisioning' => 'Review',
                    'when' => $this->dateText($row->paid_at ?? null),
                ];
            }));
        }

        return $this->paginateCollection($rows->sortByDesc('when')->values(), $perPage);
    }

    private function suggestions(): array
    {
        return [
            [
                'title' => 'Router health report',
                'description' => 'Add uptime, CPU, memory, and last heartbeat trends for each MikroTik router.',
            ],
            [
                'title' => 'Subscriber lifecycle report',
                'description' => 'Track new, renewed, expired, suspended, and reactivated subscribers by date range.',
            ],
            [
                'title' => 'Package performance report',
                'description' => 'Compare package revenue, uptake, expiry rate, and average data usage.',
            ],
            [
                'title' => 'SMS delivery report',
                'description' => 'Add SMS balance usage, failed messages, and delivery audit once SMS logs are connected.',
            ],
        ];
    }

    private function customerCount(int $ispId, array $statuses = []): int
    {
        if (! Schema::hasTable('isp_customers')) {
            return 0;
        }

        return (int) DB::table('isp_customers')
            ->where('isp_id', $ispId)
            ->when($statuses !== [], fn ($query) => $query->whereIn('connection_status', $statuses))
            ->count();
    }

    private function connectionCount(int $ispId): int
    {
        return $this->tableCount('isp_customers', $ispId, 'isp_id')
            + $this->tableCount('hotspot_free_access_logs', $ispId, 'isp_id');
    }

    private function paymentCount(int $ispId, int $days, array $statuses = []): int
    {
        if (! Schema::hasTable('mpesa_transactions')) {
            return 0;
        }

        return (int) DB::table('mpesa_transactions')
            ->where('isp_id', $ispId)
            ->where('created_at', '>=', now()->subDays($days))
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->count();
    }

    private function paymentAmount(int $ispId, int $days): float
    {
        if (! Schema::hasTable('mpesa_transactions')) {
            return 0.0;
        }

        return (float) DB::table('mpesa_transactions')
            ->where('isp_id', $ispId)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereIn('status', ['paid', 'success', 'successful', 'confirmed', 'completed'])
            ->sum('amount');
    }

    private function accessTypeCount(int $ispId, string $type): int
    {
        if (! Schema::hasTable('isp_customers')) {
            return 0;
        }

        return (int) DB::table('isp_customers')
            ->where('isp_id', $ispId)
            ->where('access_type', $type)
            ->count();
    }

    private function tableCount(string $table, int $ispId, string $scopeColumn): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);
        if (Schema::hasColumn($table, $scopeColumn)) {
            $query->where($scopeColumn, $ispId);
        }

        return (int) $query->count();
    }

    private function scopeByCreatorOrIsp(Builder $query, string $alias, int $ispId): void
    {
        $table = match ($alias) {
            'lh' => 'login_histories',
            default => $alias,
        };

        if (Schema::hasColumn($table, 'isp_id')) {
            $query->where("{$alias}.isp_id", $ispId);
            return;
        }

        if (Schema::hasColumn($table, 'created_by') && Schema::hasTable('users')) {
            $userIds = $this->tenantUserIds($ispId);
            if ($userIds !== []) {
                $query->whereIn("{$alias}.created_by", $userIds);
                return;
            }

            $query->whereRaw('1 = 0');
        }
    }


    private function tenantUserIds(int $ispId): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        $ownerIds = [];
        if (Schema::hasTable('isps')) {
            $isp = DB::table('isps')->where('id', $ispId)->first();
            foreach (['admin_user_id', 'created_by', 'updated_by'] as $column) {
                if ($isp && isset($isp->{$column}) && $isp->{$column}) {
                    $ownerIds[] = (int) $isp->{$column};
                }
            }
        }

        if (! Schema::hasColumn('users', 'isp_id') && $ownerIds === []) {
            return [];
        }

        $query = DB::table('users')->select('id');
        $query->where(function ($query) use ($ispId, $ownerIds) {
            if (Schema::hasColumn('users', 'isp_id')) {
                $query->orWhere('isp_id', $ispId);
            }

            if ($ownerIds !== []) {
                $query->orWhereIn('id', $ownerIds);
                if (Schema::hasColumn('users', 'created_by')) {
                    $query->orWhereIn('created_by', $ownerIds);
                }
            }
        });

        return $query->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    private function dateColumn(string $table): ?string
    {
        foreach (['updated_at', 'created_at', 'paid_at', 'recorded_at', 'last_online_at'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = max(1, (int) request('page', 1));
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'next_page_url' => $paginator->nextPageUrl(),
        ];
    }

    private function normalizeChanges(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                return str($value)->limit(80)->toString();
            }
        }

        if (is_array($value)) {
            $keys = array_keys($value);
            if ($keys === range(0, count($keys) - 1)) {
                return collect($value)->take(3)->implode(', ');
            }

            return collect($keys)->take(3)->implode(', ');
        }

        return (string) $value;
    }

    private function eventTone(string $event): string
    {
        $event = strtolower($event);
        if (str_contains($event, 'delete') || str_contains($event, 'fail')) {
            return 'danger';
        }

        if (str_contains($event, 'create')) {
            return 'success';
        }

        if (str_contains($event, 'login')) {
            return 'info';
        }

        return 'default';
    }

    private function money(mixed $value, string $currency = 'KES'): string
    {
        return trim($currency . ' ' . number_format((float) $value, 2));
    }

    private function number(int|float $value): string
    {
        return number_format((float) $value);
    }

    private function bytes(mixed $value): string
    {
        $bytes = (float) ($value ?? 0);
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return number_format($bytes) . ' B';
    }

    private function title(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '—';
        }

        return str($value)->replace(['_', '-'], ' ')->title()->toString();
    }

    private function dateText(mixed $value): string
    {
        if (! $value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('M d, h:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
