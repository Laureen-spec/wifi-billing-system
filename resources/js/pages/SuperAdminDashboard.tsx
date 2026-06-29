import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head, Link } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { LineChart, PieChart } from '@/components/charts';
import {
    BarChart3,
    Building2,
    CheckCircle2,
    Clock3,
    CreditCard,
    Crown,
    ExternalLink,
    Headphones,
    LifeBuoy,
    ShoppingCart,
    TicketCheck,
    TrendingUp,
} from "lucide-react";
import { formatCurrency } from '@/utils/helpers';

interface SuperAdminDashboardProps {
    stats: {
        order_payments: number;
        total_orders: number;
        total_plans: number;
        total_companies: number;
    };
    chartData: Array<{
        month: string;
        orders: number;
        payments: number;
    }>;
    ticketChartData: Array<{
        month: string;
        created: number;
        resolved: number;
    }>;
    recentTickets: Array<{
        id: number;
        ticket_id: string;
        title: string;
        status: string;
        priority: string;
        category: string;
        category_color: string;
        creator: string;
        created_at: string;
    }>;
    weeklyPendingTickets: Array<{
        id: number;
        ticket_id: string;
        title: string;
        status: string;
        priority: string;
        category: string;
        category_color: string;
        creator: string;
        created_at: string;
        last_reply_at: string;
        days_pending: number;
    }>;
}

type Tone = 'emerald' | 'slate' | 'amber' | 'blue';

const toneStyles: Record<Tone, {
    card: string;
    iconWrap: string;
    icon: string;
    value: string;
    chip: string;
    glow: string;
}> = {
    emerald: {
        card: 'border-emerald-200/70 bg-emerald-50/50',
        iconWrap: 'bg-emerald-100/80 text-emerald-700 ring-emerald-200/70',
        icon: 'text-emerald-700',
        value: 'text-emerald-950',
        chip: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        glow: 'from-emerald-400/20',
    },
    slate: {
        card: 'border-slate-200 bg-slate-50/70',
        iconWrap: 'bg-slate-100 text-slate-700 ring-slate-200/80',
        icon: 'text-slate-700',
        value: 'text-slate-950',
        chip: 'bg-slate-100 text-slate-700 border-slate-200',
        glow: 'from-slate-400/20',
    },
    amber: {
        card: 'border-amber-200/80 bg-amber-50/55',
        iconWrap: 'bg-amber-100/90 text-amber-700 ring-amber-200/80',
        icon: 'text-amber-700',
        value: 'text-amber-950',
        chip: 'bg-amber-100 text-amber-800 border-amber-200',
        glow: 'from-amber-400/20',
    },
    blue: {
        card: 'border-sky-200/80 bg-sky-50/55',
        iconWrap: 'bg-sky-100/90 text-sky-700 ring-sky-200/80',
        icon: 'text-sky-700',
        value: 'text-sky-950',
        chip: 'bg-sky-100 text-sky-800 border-sky-200',
        glow: 'from-sky-400/20',
    },
};

export default function SuperAdminDashboard({ stats, chartData, ticketChartData, recentTickets, weeklyPendingTickets }: SuperAdminDashboardProps) {
    const { t } = useTranslation();

    const safeStats = {
        order_payments: Number(stats?.order_payments ?? 0),
        total_orders: Number(stats?.total_orders ?? 0),
        total_plans: Number(stats?.total_plans ?? 0),
        total_companies: Number(stats?.total_companies ?? 0),
    };

    const recentTicketCount = recentTickets?.length ?? 0;
    const pendingTicketCount = weeklyPendingTickets?.length ?? 0;
    const resolvedTickets = ticketChartData?.reduce((sum, item) => sum + Number(item.resolved ?? 0), 0) ?? 0;
    const createdTickets = ticketChartData?.reduce((sum, item) => sum + Number(item.created ?? 0), 0) ?? 0;
    const latestOrderMonth = [...(chartData ?? [])].reverse().find((item) => Number(item.orders ?? 0) > 0)?.month
        ?? chartData?.find((item) => Number(item.orders ?? 0) > 0)?.month
        ?? t('No activity yet');

    const orderSliceColors = ['#0f766e', '#0ea5e9', '#f59e0b', '#64748b', '#14b8a6', '#475569'];
    const orderPieData = (chartData ?? [])
        .map((item, index) => ({
            month: item.month,
            orders: Number(item.orders ?? 0),
            color: orderSliceColors[index % orderSliceColors.length],
        }))
        .filter((item) => item.orders > 0);

    const getStatusBadgeColor = (status: string) => {
        switch(status) {
            case 'open': return 'bg-sky-50 text-sky-700 border-sky-200';
            case 'in_progress': return 'bg-amber-50 text-amber-700 border-amber-200';
            case 'resolved': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            case 'closed': return 'bg-slate-100 text-slate-700 border-slate-200';
            default: return 'bg-violet-50 text-violet-700 border-violet-200';
        }
    };

    const getPriorityBadgeColor = (priority: string) => {
        switch(priority) {
            case 'low': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            case 'medium': return 'bg-amber-50 text-amber-700 border-amber-200';
            case 'high': return 'bg-orange-50 text-orange-700 border-orange-200';
            case 'urgent': return 'bg-red-50 text-red-700 border-red-200';
            default: return 'bg-slate-100 text-slate-700 border-slate-200';
        }
    };

    const formatWaitingTime = (daysPending: number) => {
        if (daysPending < 1) {
            return t('Today');
        }
        if (daysPending < 2) {
            return t('1 day ago');
        }
        if (daysPending < 30) {
            return `${Math.floor(daysPending)} ${t('days ago')}`;
        }
        if (daysPending < 60) {
            return t('1 month ago');
        }
        if (daysPending < 365) {
            return `${Math.floor(daysPending / 30)} ${t('months ago')}`;
        }

        const years = Math.floor(daysPending / 365);
        return `${years} ${years > 1 ? t('years ago') : t('year ago')}`;
    };

    const metricCards = [
        {
            label: t('Total Orders'),
            value: safeStats.total_orders,
            helper: t('All subscription orders'),
            icon: ShoppingCart,
            tone: 'emerald' as Tone,
        },
        {
            label: t('Order Payments'),
            value: formatCurrency(safeStats.order_payments),
            helper: t('Recorded payment value'),
            icon: CreditCard,
            tone: 'blue' as Tone,
        },
        {
            label: t('Total Plans'),
            value: safeStats.total_plans,
            helper: t('Available billing plans'),
            icon: Crown,
            tone: 'slate' as Tone,
        },
        {
            label: t('Total Companies'),
            value: safeStats.total_companies,
            helper: t('Registered workspaces'),
            icon: Building2,
            tone: 'amber' as Tone,
        },
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Dashboard') }]}
            pageTitle={t('Dashboard')}
        >
            <Head title={t('Dashboard')} />

            <div className="space-y-6 pb-8">
                <section className="relative overflow-hidden rounded-[28px] border border-slate-200/80 bg-[linear-gradient(135deg,#f8fafc_0%,#ffffff_45%,#ecfdf5_100%)] p-5 shadow-sm md:p-6">
                    <div className="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-emerald-200/35 blur-3xl" />
                    <div className="pointer-events-none absolute -bottom-24 left-1/3 h-72 w-72 rounded-full bg-sky-200/30 blur-3xl" />
                    <div className="relative">
                        <div className="space-y-5">
                            <div className="flex flex-wrap items-center gap-3">
                                <Badge className="border-emerald-200 bg-white/75 px-3 py-1 text-emerald-700 shadow-sm backdrop-blur">
                                    <span className="mr-2 h-2 w-2 rounded-full bg-emerald-500" />
                                    {t('Super Admin Command Center')}
                                </Badge>
                                <Badge variant="outline" className="bg-white/70 px-3 py-1 text-slate-600 shadow-sm backdrop-blur">
                                    {t('Latest order month')}: {latestOrderMonth}
                                </Badge>
                            </div>

                            <div className="max-w-3xl">
                                <h1 className="text-2xl font-semibold tracking-tight text-slate-950 md:text-3xl">
                                    {t('Platform operations at a glance')}
                                </h1>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 md:text-base">
                                    {t('Track company growth, subscription revenue, plan coverage, and helpdesk response flow from one clean workspace.')}
                                </p>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-3">
                                <div className="rounded-2xl border border-white/80 bg-white/70 p-4 shadow-sm backdrop-blur-md">
                                    <div className="flex items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-slate-500">
                                        <TrendingUp className="h-4 w-4 text-emerald-600" />
                                        {t('Revenue')}
                                    </div>
                                    <p className="mt-3 text-xl font-semibold text-slate-950">{formatCurrency(safeStats.order_payments)}</p>
                                    <p className="mt-1 text-xs text-slate-500">{t('Collected order payments')}</p>
                                </div>
                                <div className="rounded-2xl border border-white/80 bg-white/70 p-4 shadow-sm backdrop-blur-md">
                                    <div className="flex items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-slate-500">
                                        <TicketCheck className="h-4 w-4 text-amber-600" />
                                        {t('Tickets')}
                                    </div>
                                    <p className="mt-3 text-xl font-semibold text-slate-950">{pendingTicketCount}</p>
                                    <p className="mt-1 text-xs text-slate-500">{t('Awaiting response')}</p>
                                </div>
                                <div className="rounded-2xl border border-white/80 bg-white/70 p-4 shadow-sm backdrop-blur-md">
                                    <div className="flex items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-slate-500">
                                        <Building2 className="h-4 w-4 text-sky-600" />
                                        {t('Tenants')}
                                    </div>
                                    <p className="mt-3 text-xl font-semibold text-slate-950">{safeStats.total_companies}</p>
                                    <p className="mt-1 text-xs text-slate-500">{t('Registered companies')}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {metricCards.map((metric) => {
                        const Icon = metric.icon;
                        const tone = toneStyles[metric.tone];
                        return (
                            <Card key={metric.label} className={`relative h-full overflow-hidden rounded-3xl ${tone.card} shadow-sm`}>
                                <div className={`pointer-events-none absolute -right-16 -top-16 h-36 w-36 rounded-full bg-gradient-to-b ${tone.glow} to-transparent blur-2xl`} />
                                <CardContent className="relative p-5">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-sm font-medium text-slate-600">{metric.label}</p>
                                            <p className={`mt-3 text-3xl font-semibold tracking-tight ${tone.value}`}>{metric.value}</p>
                                        </div>
                                        <span className={`flex h-12 w-12 items-center justify-center rounded-2xl ring-1 ${tone.iconWrap}`}>
                                            <Icon className={`h-6 w-6 ${tone.icon}`} />
                                        </span>
                                    </div>
                                    <div className="mt-5">
                                        <p className="text-sm text-slate-600">{metric.helper}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.55fr_0.85fr]">
                    <Card className="overflow-hidden rounded-3xl border-slate-200/80 bg-white shadow-sm">
                        <CardHeader className="border-b border-slate-100 pb-4">
                            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-950 text-white">
                                            <BarChart3 className="h-5 w-5" />
                                        </span>
                                        <div>
                                            <CardTitle className="text-lg text-slate-950">{t('Recent Orders (Monthly)')}</CardTitle>
                                            <CardDescription>{t('Subscription activity across the year')}</CardDescription>
                                        </div>
                                    </div>
                                </div>
                                <Badge variant="outline" className="w-fit border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-700">
                                    {safeStats.total_orders} {t('orders')}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="p-5">
                            {orderPieData.length > 0 ? (
                                <div className="grid gap-5 rounded-3xl border border-slate-100 bg-gradient-to-br from-slate-50 to-white p-4 lg:grid-cols-[0.9fr_1.1fr]">
                                    <div className="relative rounded-3xl border border-slate-100 bg-white p-4 shadow-sm">
                                        <div className="absolute inset-x-0 top-4 mx-auto flex w-fit flex-col items-center rounded-2xl border border-slate-100 bg-white/90 px-3 py-2 text-center shadow-sm backdrop-blur">
                                            <span className="text-[11px] font-medium uppercase tracking-[0.2em] text-slate-400">{t('Total')}</span>
                                            <span className="text-xl font-semibold text-slate-950">{safeStats.total_orders}</span>
                                        </div>
                                        <PieChart
                                            data={orderPieData}
                                            dataKey="orders"
                                            nameKey="month"
                                            height={245}
                                            donut={true}
                                            innerRadius={64}
                                            outerRadius={92}
                                            separatorNone={false}
                                            showTooltip={true}
                                            showLegend={false}
                                        />
                                    </div>
                                    <div className="flex flex-col justify-center space-y-4">
                                        <div>
                                            <p className="text-sm font-semibold text-slate-950">{t('Orders distribution')}</p>
                                            <p className="mt-1 text-xs leading-5 text-slate-500">
                                                {t('A compact monthly split of subscription orders without taking over the dashboard.')}
                                            </p>
                                        </div>
                                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                                            {orderPieData.map((item) => {
                                                const percentage = safeStats.total_orders > 0
                                                    ? Math.round((item.orders / safeStats.total_orders) * 100)
                                                    : 0;

                                                return (
                                                    <div key={item.month} className="rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm">
                                                        <div className="flex items-center justify-between gap-3">
                                                            <span className="flex items-center gap-2 font-medium text-slate-700">
                                                                <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: item.color }} />
                                                                {item.month}
                                                            </span>
                                                            <span className="font-semibold text-slate-950">{item.orders}</span>
                                                        </div>
                                                        <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                                            <div className="h-full rounded-full" style={{ width: `${percentage}%`, backgroundColor: item.color }} />
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex h-[280px] items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-slate-500">
                                    {t('No order data available')}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden rounded-3xl border-slate-200/80 bg-white shadow-sm">
                        <CardHeader className="border-b border-slate-100 pb-4">
                            <div className="flex items-center gap-2">
                                <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                    <Headphones className="h-5 w-5" />
                                </span>
                                <div>
                                    <CardTitle className="text-lg text-slate-950">{t('Support pulse')}</CardTitle>
                                    <CardDescription>{t('Helpdesk load and response visibility')}</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-3 p-5">
                            <div className="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4">
                                <div className="flex items-center justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-emerald-800">{t('Recent activity')}</p>
                                        <p className="mt-1 text-2xl font-semibold text-slate-950">{recentTicketCount}</p>
                                    </div>
                                    <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-sm ring-1 ring-emerald-100">
                                        <Headphones className="h-6 w-6" />
                                    </span>
                                </div>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                                <div className="rounded-2xl border border-sky-100 bg-sky-50/60 p-4">
                                    <div className="flex items-center justify-between gap-4">
                                        <div>
                                            <p className="text-sm font-medium text-sky-800">{t('Created')}</p>
                                            <p className="mt-1 text-2xl font-semibold text-slate-950">{createdTickets}</p>
                                        </div>
                                        <CheckCircle2 className="h-6 w-6 text-sky-600" />
                                    </div>
                                </div>
                                <div className="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4">
                                    <div className="flex items-center justify-between gap-4">
                                        <div>
                                            <p className="text-sm font-medium text-emerald-800">{t('Resolved')}</p>
                                            <p className="mt-1 text-2xl font-semibold text-slate-950">{resolvedTickets}</p>
                                        </div>
                                        <TicketCheck className="h-6 w-6 text-emerald-600" />
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
                                <div className="flex items-center justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-amber-800">{t('Awaiting response')}</p>
                                        <p className="mt-1 text-2xl font-semibold text-slate-950">{pendingTicketCount}</p>
                                    </div>
                                    <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-amber-700 shadow-sm ring-1 ring-amber-100">
                                        <Clock3 className="h-6 w-6" />
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-6 xl:grid-cols-2">
                    <Card className="rounded-3xl border-slate-200/80 bg-white shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between gap-4 border-b border-slate-100 pb-4">
                            <div>
                                <CardTitle className="text-lg text-slate-950">{t('Recent Helpdesk Activity')}</CardTitle>
                                <CardDescription>{t('Newest support items from all companies')}</CardDescription>
                            </div>
                            {recentTicketCount > 0 && (
                                <Badge variant="outline" className="border-slate-200 bg-slate-50 text-slate-700">
                                    {recentTicketCount} {recentTicketCount === 1 ? t('ticket') : t('tickets')}
                                </Badge>
                            )}
                        </CardHeader>
                        <CardContent className="p-4">
                            {recentTicketCount > 0 ? (
                                <div className="space-y-3">
                                    {recentTickets.map((ticket) => (
                                        <Link
                                            key={ticket.id}
                                            href={route('helpdesk-tickets.show', ticket.id)}
                                            className="group block rounded-2xl border border-slate-200/80 bg-white p-4 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-50/30 hover:shadow-sm"
                                        >
                                            <div className="flex items-start gap-3">
                                                <span
                                                    className="mt-1 h-10 w-1.5 shrink-0 rounded-full bg-slate-300"
                                                    style={ticket.category_color ? { backgroundColor: ticket.category_color } : undefined}
                                                />
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className="rounded-full bg-slate-950 px-2.5 py-1 text-xs font-semibold text-white">#{ticket.ticket_id}</span>
                                                        <span className={`rounded-full border px-2.5 py-1 text-xs font-semibold ${getPriorityBadgeColor(ticket.priority)}`}>
                                                            {t(ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1))}
                                                        </span>
                                                        <span className={`rounded-full border px-2.5 py-1 text-xs font-semibold ${getStatusBadgeColor(ticket.status)}`}>
                                                            {t(ticket.status.replace('_', ' ').charAt(0).toUpperCase() + ticket.status.replace('_', ' ').slice(1))}
                                                        </span>
                                                    </div>
                                                    <p className="mt-3 line-clamp-2 text-sm font-semibold leading-6 text-slate-950">{ticket.title}</p>
                                                    <div className="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                                        {ticket.category && <span>{ticket.category}</span>}
                                                        <span>{ticket.creator}</span>
                                                        <span>{ticket.created_at}</span>
                                                    </div>
                                                </div>
                                                <ExternalLink className="mt-1 h-4 w-4 shrink-0 text-slate-400 transition group-hover:text-emerald-600" />
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50/70 px-6 py-12 text-center">
                                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm">
                                        <LifeBuoy className="h-6 w-6" />
                                    </div>
                                    <p className="font-semibold text-slate-950">{t('No recent activity')}</p>
                                    <p className="mt-1 text-sm text-slate-500">{t('No tickets have been created yet')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="rounded-3xl border-slate-200/80 bg-white shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between gap-4 border-b border-slate-100 pb-4">
                            <div>
                                <CardTitle className="text-lg text-slate-950">{t('Tickets Awaiting Your Response')}</CardTitle>
                                <CardDescription>{t('Pending conversations needing Super Admin attention')}</CardDescription>
                            </div>
                            {pendingTicketCount > 0 && (
                                <Badge variant="outline" className="border-amber-200 bg-amber-50 text-amber-700">
                                    {pendingTicketCount} {pendingTicketCount === 1 ? t('ticket') : t('tickets')}
                                </Badge>
                            )}
                        </CardHeader>
                        <CardContent className="p-4">
                            {pendingTicketCount > 0 ? (
                                <div className="space-y-3">
                                    {weeklyPendingTickets.map((ticket) => (
                                        <Link
                                            key={ticket.id}
                                            href={route('helpdesk-tickets.show', ticket.id)}
                                            className="group block rounded-2xl border border-amber-200/80 bg-amber-50/35 p-4 transition hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-sm"
                                        >
                                            <div className="flex items-start gap-3">
                                                <span
                                                    className="mt-1 h-10 w-1.5 shrink-0 rounded-full bg-amber-400"
                                                    style={ticket.category_color ? { backgroundColor: ticket.category_color } : undefined}
                                                />
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className="rounded-full bg-slate-950 px-2.5 py-1 text-xs font-semibold text-white">#{ticket.ticket_id}</span>
                                                        <span className={`rounded-full border px-2.5 py-1 text-xs font-semibold ${getPriorityBadgeColor(ticket.priority)}`}>
                                                            {t(ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1))}
                                                        </span>
                                                        <span className="rounded-full border border-amber-200 bg-white px-2.5 py-1 text-xs font-semibold text-amber-700">
                                                            {formatWaitingTime(ticket.days_pending)}
                                                        </span>
                                                    </div>
                                                    <p className="mt-3 line-clamp-2 text-sm font-semibold leading-6 text-slate-950">{ticket.title}</p>
                                                    <div className="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600">
                                                        {ticket.category && <span>{ticket.category}</span>}
                                                        <span>{ticket.creator}</span>
                                                        <span>{ticket.created_at}</span>
                                                    </div>
                                                </div>
                                                <ExternalLink className="mt-1 h-4 w-4 shrink-0 text-slate-400 transition group-hover:text-amber-600" />
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <div className="rounded-3xl border border-dashed border-emerald-200 bg-emerald-50/60 px-6 py-12 text-center">
                                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-emerald-600 shadow-sm">
                                        <CheckCircle2 className="h-6 w-6" />
                                    </div>
                                    <p className="font-semibold text-slate-950">{t('All caught up!')}</p>
                                    <p className="mt-1 text-sm text-slate-500">{t('No tickets awaiting response')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <Card className="overflow-hidden rounded-3xl border-slate-200/80 bg-white shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div className="flex items-center gap-2">
                                <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                    <TrendingUp className="h-5 w-5" />
                                </span>
                                <div>
                                    <CardTitle className="text-lg text-slate-950">{t('Monthly Ticket Trends')}</CardTitle>
                                    <CardDescription>{t('Created and resolved tickets by month')}</CardDescription>
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Badge variant="outline" className="border-sky-200 bg-sky-50 text-sky-700">{createdTickets} {t('created')}</Badge>
                                <Badge variant="outline" className="border-emerald-200 bg-emerald-50 text-emerald-700">{resolvedTickets} {t('resolved')}</Badge>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-5">
                        {ticketChartData && ticketChartData.length > 0 ? (
                            <div className="rounded-3xl border border-slate-100 bg-gradient-to-br from-slate-50 to-white p-4">
                                <LineChart
                                    data={ticketChartData}
                                    dataKey="created"
                                    xAxisKey="month"
                                    height={315}
                                    showTooltip={true}
                                    showGrid={true}
                                    showLegend={true}
                                    showDots={true}
                                    strokeWidth={3}
                                    lines={[
                                        { dataKey: 'created', color: '#2563eb', name: 'Created', type: 'monotone' },
                                        { dataKey: 'resolved', color: '#059669', name: 'Resolved', type: 'monotone' },
                                    ]}
                                />
                            </div>
                        ) : (
                            <div className="flex h-[300px] items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-slate-500">
                                {t('No ticket trend data available')}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
