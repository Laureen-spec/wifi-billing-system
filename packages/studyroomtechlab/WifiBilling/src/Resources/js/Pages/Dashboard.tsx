import AuthenticatedLayout from '../../../../../../../resources/js/layouts/authenticated-layout';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarClock,
    CreditCard,
    Package,
    Plus,
    Router,
    ShieldCheck,
    ExternalLink,
    UserPlus,
    Users,
    Wifi,
} from 'lucide-react';
import { type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

declare function route(name: string, params?: string | number | Record<string, unknown> | Array<string | number>): string;

type DashboardStats = {
    total_customers: number;
    active_customers: number;
    expired_customers: number;
    today_payments: number;
    monthly_revenue: number;
    monthly_billing_value: number;
    total_packages: number;
    total_routers: number;
    online_routers: number;
    offline_routers: number;
    waiting_for_link_routers: number;
    hotspot_files_missing_count: number;
    provisioning_issues: number;
};

type CustomerRow = {
    id: number;
    name: string;
    phone?: string | null;
    package?: string | null;
    connection_status?: string | null;
    billing_status?: string | null;
    created_at?: string | null;
};

type ExpiringRow = {
    id: number;
    name: string;
    phone?: string | null;
    package?: string | null;
    expiry?: string | null;
};

type RouterRow = {
    id: number;
    name: string;
    host?: string | null;
    board_name?: string | null;
    routeros_version?: string | null;
    cpu_load?: number | null;
    memory_free?: number | null;
    memory_total?: number | null;
    hotspot_files_status?: string | null;
    status?: string | null;
    last_seen_at?: string | null;
    last_seen_iso?: string | null;
    action_url?: string | null;
};

type DashboardProps = {
    isp?: {
        id: number;
        name: string;
        status?: string | null;
    } | null;
    stats?: Partial<DashboardStats>;
    recentCustomers?: CustomerRow[];
    expiringSoon?: ExpiringRow[];
    routers?: RouterRow[];
};

const defaultStats: DashboardStats = {
    total_customers: 0,
    active_customers: 0,
    expired_customers: 0,
    today_payments: 0,
    monthly_revenue: 0,
    monthly_billing_value: 0,
    total_packages: 0,
    total_routers: 0,
    online_routers: 0,
    offline_routers: 0,
    waiting_for_link_routers: 0,
    hotspot_files_missing_count: 0,
    provisioning_issues: 0,
};

const money = (value: number | string | null | undefined) => {
    const amount = Number(value ?? 0);

    return `KES ${amount.toLocaleString('en-KE', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
};

const titleCase = (value?: string | null) => {
    if (!value) {
        return 'Not set';
    }

    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
};

const statusClass = (value?: string | null) => {
    const status = (value ?? '').toLowerCase();

    if (['active', 'paid', 'online', 'connected', 'success', 'successful', 'present'].includes(status)) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    }

    if (['pending', 'not_synced', 'not synced', 'unpaid', 'provision_pending', 'waiting_for_link', 'unknown', 'warning'].includes(status)) {
        return 'border-amber-200 bg-amber-50 text-amber-700';
    }

    if (['expired', 'overdue', 'failed', 'offline', 'disconnected', 'suspended', 'missing'].includes(status)) {
        return 'border-red-200 bg-red-50 text-red-700';
    }

    return 'border-slate-200 bg-slate-50 text-slate-700';
};

const memoryText = (free?: number | null, total?: number | null) => {
    if (free == null || total == null || total <= 0) {
        return 'Unknown';
    }

    const freeMiB = free / 1048576;
    const totalMiB = total / 1048576;

    return `${freeMiB.toFixed(1)} / ${totalMiB.toFixed(1)} MiB free`;
};

const StatusBadge = ({ value }: { value?: string | null }) => (
    <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-medium ${statusClass(value)}`}>
        {titleCase(value)}
    </span>
);

const EmptyState = ({ message }: { message: string }) => (
    <div className="rounded-lg border border-dashed bg-muted/20 p-6 text-center text-sm text-muted-foreground">
        {message}
    </div>
);

const QuickAction = ({ href, children }: { href: string; children: ReactNode }) => (
    <Link
        href={href}
        className="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-3 py-2 text-sm font-medium shadow-sm transition hover:bg-muted"
    >
        {children}
    </Link>
);

const RevenueChart = ({ today, month }: { today: number; month: number }) => {
    const hasMoney = today > 0 || month > 0;

    return (
        <div className="h-56 rounded-lg border bg-background p-4">
            <div className="flex h-full flex-col">
                <div className="mb-3 flex items-center justify-between">
                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">KES</p>
                        <p className="text-sm text-muted-foreground">Collections trend</p>
                    </div>

                    <span className="rounded-full border px-2 py-1 text-xs text-muted-foreground">
                        Last 30 days
                    </span>
                </div>

                <div className="relative flex-1">
                    <svg viewBox="0 0 420 150" className="h-full w-full">
                        <line x1="0" y1="30" x2="420" y2="30" className="stroke-muted" strokeDasharray="4 4" />
                        <line x1="0" y1="70" x2="420" y2="70" className="stroke-muted" strokeDasharray="4 4" />
                        <line x1="0" y1="110" x2="420" y2="110" className="stroke-muted" strokeDasharray="4 4" />

                        <polyline
                            points={
                                hasMoney
                                    ? '0,120 65,118 130,112 195,115 260,88 325,105 420,72'
                                    : '0,120 65,120 130,120 195,120 260,120 325,120 420,120'
                            }
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="3"
                            className="text-emerald-600"
                        />

                        <circle cx="420" cy={hasMoney ? '72' : '120'} r="4" className="fill-emerald-600" />
                    </svg>
                </div>

                <div className="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                    <span>Monthly: {money(month)}</span>
                    <span>Today: {money(today)}</span>
                </div>
            </div>
        </div>
    );
};

const SmallMetric = ({
    title,
    value,
    note,
    icon: Icon,
    warning = false,
}: {
    title: string;
    value: string | number;
    note: string;
    icon: React.ComponentType<{ className?: string }>;
    warning?: boolean;
}) => (
    <div className="rounded-xl border bg-card p-4 shadow-sm">
        <div className="flex items-start justify-between gap-3">
            <div>
                <p className="text-sm text-muted-foreground">{title}</p>
                <p className="mt-2 text-2xl font-semibold tracking-tight">{value}</p>
                <p className="mt-1 text-xs text-muted-foreground">{note}</p>
            </div>

            <span
                className={`rounded-lg p-2 ${
                    warning ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700'
                }`}
            >
                <Icon className="h-4 w-4" />
            </span>
        </div>
    </div>
);

export default function Dashboard({
    isp = null,
    stats: incomingStats = {},
    recentCustomers = [],
    expiringSoon = [],
    routers = [],
}: DashboardProps) {
    const { t } = useTranslation();
    const stats: DashboardStats = { ...defaultStats, ...incomingStats };

    const moneyCards = [
        {
            title: t('Today Payments'),
            value: money(stats.today_payments),
            note: t('Collected today'),
            icon: CreditCard,
        },
        {
            title: t('Monthly Revenue'),
            value: money(stats.monthly_revenue || stats.monthly_billing_value),
            note: stats.monthly_revenue > 0 ? t('Collected this month') : t('Expected monthly billing'),
            icon: CreditCard,
        },
    ];

    const operationCards = [
        {
            title: t('Total Customers'),
            value: stats.total_customers,
            note: t('All registered'),
            icon: Users,
        },
        {
            title: t('Active Customers'),
            value: stats.active_customers,
            note: t('Currently active'),
            icon: ShieldCheck,
        },
        {
            title: t('Expired Customers'),
            value: stats.expired_customers,
            note: t('Past due'),
            icon: CalendarClock,
            warning: stats.expired_customers > 0,
        },
        {
            title: t('Internet Packages'),
            value: stats.total_packages,
            note: t('Packages created'),
            icon: Package,
        },
        {
            title: t('MikroTik Routers'),
            value: stats.total_routers,
            note: t('Routers registered'),
            icon: Router,
        },
        {
            title: t('Online Routers'),
            value: stats.online_routers,
            note: t('Heartbeat within 60 seconds'),
            icon: ShieldCheck,
        },
        {
            title: t('Offline Routers'),
            value: stats.offline_routers,
            note: t('Heartbeat older than 60 seconds'),
            icon: AlertTriangle,
            warning: stats.offline_routers > 0,
        },
        {
            title: t('Waiting for Link'),
            value: stats.waiting_for_link_routers,
            note: t('No heartbeat received yet'),
            icon: Router,
            warning: stats.waiting_for_link_routers > 0,
        },
        {
            title: t('Hotspot Files Missing'),
            value: stats.hotspot_files_missing_count,
            note: t('Routers reporting missing files'),
            icon: AlertTriangle,
            warning: stats.hotspot_files_missing_count > 0,
        },
        {
            title: t('Provisioning Issues'),
            value: stats.provisioning_issues,
            note: t('Needs attention'),
            icon: AlertTriangle,
            warning: stats.provisioning_issues > 0,
        },
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('WiFi Billing') }]}
            pageTitle={t('WiFi Billing Dashboard')}
        >
            <Head title={t('WiFi Billing Dashboard')} />

            <div className="space-y-5">
                <div className="rounded-xl border bg-card p-5 shadow-sm">
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <h2 className="text-2xl font-semibold tracking-tight">
                                    {t('WiFi Billing Dashboard')}
                                </h2>

                                <span className="rounded-full border bg-background px-3 py-1 text-xs text-muted-foreground">
                                    {isp?.name || t('Company ISP')}
                                </span>
                            </div>

                            <p className="mt-1 max-w-3xl text-sm text-muted-foreground">
                                {t('Monitor customers, packages, payments, routers, and provisioning from one place.')}
                            </p>
                        </div>

                        <div className="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
                            <QuickAction href={route('isp.customers.create')}>
                                <UserPlus className="h-4 w-4" />
                                {t('Add Customer')}
                            </QuickAction>

                            <QuickAction href={route('isp.packages.create')}>
                                <Plus className="h-4 w-4" />
                                {t('Add Package')}
                            </QuickAction>

                            <QuickAction href={route('isp.routers.create')}>
                                <Router className="h-4 w-4" />
                                {t('Link MikroTik')}
                            </QuickAction>

                            <QuickAction href={route('isp.provisioning.index')}>
                                <Wifi className="h-4 w-4" />
                                {t('Provisioning')}
                            </QuickAction>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {moneyCards.map((card) => (
                        <SmallMetric
                            key={card.title}
                            title={card.title}
                            value={card.value}
                            note={card.note}
                            icon={card.icon}
                            warning={card.warning}
                        />
                    ))}
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {operationCards.map((card) => (
                        <SmallMetric
                            key={card.title}
                            title={card.title}
                            value={card.value}
                            note={card.note}
                            icon={card.icon}
                            warning={card.warning}
                        />
                    ))}
                </div>

                <div className="grid gap-4 xl:grid-cols-3">
                    <div className="rounded-xl border bg-card p-4 shadow-sm xl:col-span-1">
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <h3 className="font-semibold">{t('Revenue Trend')}</h3>
                                <p className="text-xs text-muted-foreground">{t('Collections overview')}</p>
                            </div>
                        </div>

                        <RevenueChart today={Number(stats.today_payments)} month={Number(stats.monthly_revenue)} />
                    </div>

                    <div className="rounded-xl border bg-card shadow-sm xl:col-span-2">
                        <div className="flex items-center justify-between border-b p-4">
                            <div>
                                <h3 className="font-semibold">{t('Router Live Status')}</h3>
                                <p className="text-xs text-muted-foreground">{t('RouterOS heartbeat, resources, and hotspot file status')}</p>
                            </div>

                            <Link href={route('isp.routers.index')} className="text-sm text-primary hover:underline">
                                {t('View routers')}
                            </Link>
                        </div>

                        <div className="p-4">
                            {routers.length === 0 ? (
                                <EmptyState message={t('No MikroTik routers added yet.')} />
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead className="text-left text-xs uppercase text-muted-foreground">
                                            <tr className="border-b">
                                                <th className="py-2 pr-3 font-medium">{t('Router')}</th>
                                                <th className="py-2 pr-3 font-medium">{t('Board')}</th>
                                                <th className="py-2 pr-3 font-medium">{t('RouterOS')}</th>
                                                <th className="py-2 pr-3 font-medium">{t('CPU')}</th>
                                                <th className="py-2 pr-3 font-medium">{t('Memory')}</th>
                                                <th className="py-2 pr-3 font-medium">{t('Status')}</th>
                                                <th className="py-2 pr-3 font-medium">{t('Last Seen')}</th>
                                                <th className="py-2 pr-3 font-medium">{t('Hotspot Files')}</th>
                                                <th className="py-2 pr-3 font-medium">{t('Action')}</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            {routers.map((router) => (
                                                <tr key={router.id} className="border-b last:border-0">
                                                    <td className="py-3 pr-3">
                                                        <div className="font-medium">{router.name}</div>
                                                        <div className="text-xs text-muted-foreground">{router.host || '-'}</div>
                                                    </td>
                                                    <td className="py-3 pr-3 text-muted-foreground">{router.board_name || '-'}</td>
                                                    <td className="py-3 pr-3 text-muted-foreground">{router.routeros_version || '-'}</td>
                                                    <td className="py-3 pr-3 text-muted-foreground">
                                                        {router.cpu_load == null ? 'Unknown' : `${router.cpu_load}%`}
                                                    </td>
                                                    <td className="py-3 pr-3 text-muted-foreground">
                                                        {memoryText(router.memory_free, router.memory_total)}
                                                    </td>
                                                    <td className="py-3 pr-3">
                                                        <StatusBadge value={router.status} />
                                                    </td>
                                                    <td className="py-3 pr-3 text-muted-foreground">
                                                        {router.last_seen_at || t('No heartbeat yet')}
                                                    </td>
                                                    <td className="py-3 pr-3">
                                                        <StatusBadge value={router.hotspot_files_status || 'unknown'} />
                                                        {router.hotspot_files_status === 'missing' && (
                                                            <div className="mt-1 text-xs text-red-600">{t('Hotspot files missing')}</div>
                                                        )}
                                                    </td>
                                                    <td className="py-3 pr-3">
                                                        {router.action_url ? (
                                                            <Link href={router.action_url} className="inline-flex items-center gap-1 text-sm text-primary hover:underline">
                                                                {t('Details')}
                                                                <ExternalLink className="h-3 w-3" />
                                                            </Link>
                                                        ) : (
                                                            <span className="text-muted-foreground">-</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 xl:grid-cols-3">
                    <div className="rounded-xl border bg-card shadow-sm">
                        <div className="flex items-center justify-between border-b p-4">
                            <h3 className="font-semibold">{t('Recent Customers')}</h3>

                            <Link href={route('isp.customers.index')} className="text-sm text-primary hover:underline">
                                {t('View all')}
                            </Link>
                        </div>

                        <div className="p-4">
                            {recentCustomers.length === 0 ? (
                                <EmptyState message={t('No customers added yet.')} />
                            ) : (
                                <div className="space-y-3">
                                    {recentCustomers.map((customer) => (
                                        <div key={customer.id} className="rounded-lg border p-3">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium">{customer.name}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {customer.package || t('No package')} · {customer.phone || t('No phone')}
                                                    </p>
                                                </div>

                                                <StatusBadge value={customer.connection_status || customer.billing_status} />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="rounded-xl border bg-card shadow-sm">
                        <div className="flex items-center justify-between border-b p-4">
                            <h3 className="font-semibold">{t('Expiring Today / Soon')}</h3>
                            <span className="text-xs text-muted-foreground">{t('Next 24 hours')}</span>
                        </div>

                        <div className="p-4">
                            {expiringSoon.length === 0 ? (
                                <EmptyState message={t('No expiring customers found.')} />
                            ) : (
                                <div className="space-y-3">
                                    {expiringSoon.map((customer) => (
                                        <div key={customer.id} className="rounded-lg border p-3">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium">{customer.name}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {customer.package || t('No package')} · {customer.phone || t('No phone')}
                                                    </p>
                                                </div>

                                                <span className="rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">
                                                    {customer.expiry || t('Due soon')}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="rounded-xl border bg-card shadow-sm">
                        <div className="flex items-center justify-between border-b p-4">
                            <h3 className="font-semibold">{t('Network Attention')}</h3>
                            <StatusBadge value={stats.provisioning_issues > 0 ? 'warning' : 'online'} />
                        </div>

                        <div className="space-y-3 p-4 text-sm text-muted-foreground">
                            <p>{t('Provisioning issues are counted from failed provisioning tokens, missing hotspot files, and routers waiting too long without heartbeat.')}</p>
                            <div className="rounded-lg border bg-background p-3">
                                <div className="font-medium text-foreground">{stats.provisioning_issues}</div>
                                <div>{t('Items need attention')}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
