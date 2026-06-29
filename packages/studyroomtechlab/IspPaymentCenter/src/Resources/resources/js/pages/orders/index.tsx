import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogBody,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import NoRecordsFound from '@/components/no-records-found';
import { formatAdminCurrency, formatDate } from '@/utils/helpers';
import {
    CheckCircle2,
    Clock3,
    Copy,
    Eye,
    Filter,
    PackageCheck,
    ReceiptText,
    Search,
    ShoppingCart,
    XCircle,
} from 'lucide-react';

interface TimelineItem {
    title: string;
    description: string;
    date?: string | null;
    status: 'completed' | 'pending' | 'failed' | string;
}

interface Order {
    id: number;
    order_id: string;
    customer_name: string;
    customer_email?: string | null;
    source: string;
    plan_name: string;
    method: string;
    amount: number;
    original_price?: number | string | null;
    discount_amount?: number;
    currency: string;
    payment_status: string;
    payment_status_raw: string;
    posting_status: string;
    provisioning_status: string;
    receipt?: string | null;
    txn_id?: string | null;
    created_at?: string | null;
    created_at_display?: string | null;
    coupon_code?: string | null;
    coupon_name?: string | null;
    timeline: TimelineItem[];
}

interface SummaryCard {
    key: string;
    title: string;
    value: string;
    description: string;
    tone?: string;
    isMoney?: boolean;
}

interface StatusTab {
    key: string;
    label: string;
    count: number;
}

interface PaginationPayload {
    data: Order[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links?: any;
    meta?: any;
}

interface Props {
    orders: PaginationPayload;
    summary: SummaryCard[];
    statusTabs: StatusTab[];
    filters: {
        search?: string;
        status?: string;
        per_page?: number | string;
        sort?: string;
        direction?: 'asc' | 'desc' | string;
    };
}

const toneClasses: Record<string, string> = {
    slate: 'border-slate-200 bg-slate-50 text-slate-700',
    amber: 'border-amber-200 bg-amber-50 text-amber-700',
    green: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    red: 'border-rose-200 bg-rose-50 text-rose-700',
    blue: 'border-sky-200 bg-sky-50 text-sky-700',
};

const orderRailClasses: Record<string, string> = {
    paid: 'from-emerald-500 to-emerald-300',
    confirmed: 'from-emerald-500 to-emerald-300',
    success: 'from-emerald-500 to-emerald-300',
    succeeded: 'from-emerald-500 to-emerald-300',
    completed: 'from-emerald-500 to-emerald-300',
    pending: 'from-amber-500 to-amber-300',
    waiting: 'from-amber-500 to-amber-300',
    failed: 'from-rose-500 to-rose-300',
    cancelled: 'from-rose-500 to-rose-300',
    canceled: 'from-rose-500 to-rose-300',
    expired: 'from-slate-500 to-slate-300',
    refunded: 'from-sky-500 to-sky-300',
};

function statusLabel(status?: string | null) {
    return String(status || 'Unknown')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function normalizedStatus(status?: string | null) {
    return String(status || '').toLowerCase().replace(/_/g, ' ');
}

function statusBadgeClass(status?: string | null) {
    const normalized = normalizedStatus(status);

    if (['paid', 'confirmed', 'success', 'succeeded', 'completed', 'posted', 'provisioned'].includes(normalized)) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    }

    if (['failed', 'cancelled', 'canceled', 'expired', 'refunded', 'reversed', 'chargeback', 'not provisioned', 'not posted'].includes(normalized)) {
        return 'border-rose-200 bg-rose-50 text-rose-700';
    }

    return 'border-amber-200 bg-amber-50 text-amber-700';
}

function StatusBadge({ value }: { value?: string | null }) {
    return (
        <Badge variant="outline" className={`rounded-full px-3 py-1 text-xs font-semibold ${statusBadgeClass(value)}`}>
            {statusLabel(value)}
        </Badge>
    );
}

function SummaryTile({ card, pageProps }: { card: SummaryCard; pageProps: any }) {
    const value = card.isMoney ? formatAdminCurrency(card.value, pageProps) : card.value;
    const tone = toneClasses[card.tone || 'slate'] || toneClasses.slate;

    return (
        <div className="rounded-2xl border bg-card p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">{card.title}</p>
                    <p className="mt-3 text-3xl font-black tracking-tight text-foreground">{value}</p>
                </div>
                <div className={`rounded-2xl border p-3 ${tone}`}>
                    <ShoppingCart className="h-5 w-5" />
                </div>
            </div>
            <p className="mt-3 text-sm text-muted-foreground">{card.description}</p>
        </div>
    );
}

function statusRailClass(order: Order) {
    const status = normalizedStatus(order.payment_status);

    return orderRailClasses[status] || 'from-slate-400 to-slate-200';
}

function OrderPipeline({ order }: { order: Order }) {
    const steps = [
        { label: 'Payment', value: order.payment_status },
        { label: 'Posting', value: order.posting_status },
        { label: 'Provisioning', value: order.provisioning_status },
    ];

    return (
        <div className="grid gap-2 md:grid-cols-3">
            {steps.map((step) => (
                <div key={step.label} className="rounded-xl border bg-muted/20 px-3 py-2">
                    <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{step.label}</p>
                    <div className="mt-2">
                        <StatusBadge value={step.value} />
                    </div>
                </div>
            ))}
        </div>
    );
}

function OrderCard({ order, pageProps, onView, onCopy }: { order: Order; pageProps: any; onView: () => void; onCopy: () => void }) {
    return (
        <article className="group relative overflow-hidden rounded-2xl border bg-card shadow-sm transition hover:border-primary/30 hover:shadow-md">
            <div className={`absolute inset-y-0 left-0 w-1.5 bg-gradient-to-b ${statusRailClass(order)}`} />

            <div className="grid gap-5 p-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1.4fr)_220px] lg:items-center">
                <div className="min-w-0 pl-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-full bg-muted px-3 py-1 font-mono text-xs font-bold text-foreground">
                            {order.order_id}
                        </span>
                        <span className="text-xs text-muted-foreground">
                            {order.created_at_display || (order.created_at ? formatDate(order.created_at, pageProps) : 'Unknown date')}
                        </span>
                    </div>
                    <p className="mt-3 truncate text-lg font-bold text-foreground">{order.customer_name}</p>
                    {order.customer_email && <p className="truncate text-sm text-muted-foreground">{order.customer_email}</p>}
                    <div className="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
                        <span className="rounded-full border px-3 py-1">{order.method || 'Unknown method'}</span>
                        <span className="rounded-full border px-3 py-1">Receipt: {order.receipt || '—'}</span>
                    </div>
                </div>

                <div className="space-y-4">
                    <div className="rounded-2xl border bg-muted/20 p-4">
                        <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Source / Package</p>
                        <p className="mt-2 font-semibold text-foreground">{order.plan_name || 'No package'}</p>
                        <p className="text-sm text-muted-foreground">{order.source || 'Unknown source'}</p>
                    </div>
                    <OrderPipeline order={order} />
                </div>

                <div className="flex flex-col gap-4 rounded-2xl border bg-background p-4 lg:items-end">
                    <div className="lg:text-right">
                        <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Amount</p>
                        <p className="mt-1 text-2xl font-black text-foreground">{formatAdminCurrency(order.amount, pageProps)}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button type="button" variant="outline" size="sm" onClick={onView}>
                            <Eye className="mr-2 h-4 w-4" />
                            View
                        </Button>
                        <Button type="button" size="icon" variant="ghost" title="Copy receipt" disabled={!order.receipt} onClick={onCopy}>
                            <Copy className="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </article>
    );
}

export default function OrdersIndex({ orders, summary, statusTabs, filters }: Props) {
    const { t } = useTranslation();
    const pageProps = usePage().props as any;
    const [search, setSearch] = useState(filters.search || '');
    const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

    const activeStatus = filters.status || 'all';
    const perPage = String(filters.per_page || orders.per_page || 10);

    const visit = (next: Record<string, any>) => {
        router.get(
            route('orders.index'),
            {
                search,
                status: activeStatus,
                per_page: perPage,
                sort: filters.sort || 'id',
                direction: filters.direction || 'desc',
                ...next,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const clearFilters = () => {
        setSearch('');
        router.get(route('orders.index'), { per_page: perPage }, { preserveState: true, replace: true });
    };

    const pageTitle = t('Order command center');

    const hasFilters = useMemo(() => Boolean(search || activeStatus !== 'all'), [search, activeStatus]);

    const copyReceipt = (value?: string | null) => {
        if (value && navigator?.clipboard) {
            navigator.clipboard.writeText(value);
        }
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Payment Center'), url: route('isp-payment-center.index') },
                { label: t('Orders') },
            ]}
            pageTitle={pageTitle}
        >
            <Head title={pageTitle} />

            <div className="space-y-6">
                <div className="overflow-hidden rounded-3xl border bg-card shadow-sm">
                    <div className="grid gap-6 p-6 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-center">
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.28em] text-primary">
                                {t('Payment Center')} / {t('Checkout operations')}
                            </p>
                            <h1 className="mt-3 text-3xl font-black tracking-tight text-foreground md:text-5xl">
                                {t('Order command center')}
                            </h1>
                            <p className="mt-3 max-w-3xl text-base text-muted-foreground">
                                {t('Monitor checkouts, gateway attempts, manual collections, and provisioning from one operational queue.')}
                            </p>
                        </div>

                        <div className="rounded-2xl border bg-muted/30 p-4">
                            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('Quick link')}</p>
                            <Button type="button" variant="outline" className="mt-3 w-full justify-start" onClick={() => router.visit(route('isp-payment-center.index'))}>
                                <ReceiptText className="mr-2 h-4 w-4" />
                                {t('Open payment ledger')}
                            </Button>
                            <p className="mt-3 text-xs text-muted-foreground">
                                {t('Use the ledger for raw transactions. Use Orders for business checkout tracking.')}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    {summary.map((card) => (
                        <SummaryTile key={card.key} card={card} pageProps={pageProps} />
                    ))}
                </div>

                <div className="grid gap-6 xl:grid-cols-[280px_minmax(0,1fr)]">
                    <aside className="space-y-4">
                        <Card className="rounded-2xl shadow-sm">
                            <CardContent className="p-4">
                                <div className="flex items-center gap-2">
                                    <Filter className="h-4 w-4 text-muted-foreground" />
                                    <p className="text-sm font-bold text-foreground">{t('Order queue')}</p>
                                </div>

                                <div className="mt-4 space-y-2">
                                    {statusTabs.map((tab) => (
                                        <button
                                            key={tab.key}
                                            type="button"
                                            onClick={() => visit({ status: tab.key, page: 1 })}
                                            className={`flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold transition ${
                                                activeStatus === tab.key
                                                    ? 'bg-foreground text-background shadow-sm'
                                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                            }`}
                                        >
                                            <span>{t(tab.label)}</span>
                                            <span className={`rounded-full px-2 py-0.5 text-xs ${activeStatus === tab.key ? 'bg-background/20' : 'bg-muted'}`}>
                                                {tab.count}
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="rounded-2xl shadow-sm">
                            <CardContent className="p-4">
                                <p className="text-sm font-bold text-foreground">{t('How to read orders')}</p>
                                <div className="mt-3 space-y-3 text-sm text-muted-foreground">
                                    <div className="flex gap-2">
                                        <CheckCircle2 className="mt-0.5 h-4 w-4 text-emerald-600" />
                                        <p>{t('Paid and provisioned means money was confirmed and service was delivered.')}</p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Clock3 className="mt-0.5 h-4 w-4 text-amber-600" />
                                        <p>{t('Pending means checkout started but confirmation is not complete.')}</p>
                                    </div>
                                    <div className="flex gap-2">
                                        <XCircle className="mt-0.5 h-4 w-4 text-rose-600" />
                                        <p>{t('Failed means payment or provisioning needs review.')}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </aside>

                    <section className="space-y-4">
                        <Card className="rounded-2xl shadow-sm">
                            <CardContent className="p-4">
                                <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
                                    <div className="relative flex-1">
                                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            value={search}
                                            onChange={(event) => setSearch(event.target.value)}
                                            onKeyDown={(event) => {
                                                if (event.key === 'Enter') {
                                                    visit({ page: 1 });
                                                }
                                            }}
                                            className="h-11 rounded-xl pl-10"
                                            placeholder={t('Search order number, customer, receipt, package, or phone...')}
                                        />
                                    </div>
                                    <Button type="button" onClick={() => visit({ page: 1 })}>
                                        {t('Search')}
                                    </Button>
                                    {hasFilters && (
                                        <Button type="button" variant="outline" onClick={clearFilters}>
                                            {t('Clear')}
                                        </Button>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {orders.data.length === 0 ? (
                            <Card className="rounded-2xl shadow-sm">
                                <CardContent className="p-8">
                                    <NoRecordsFound
                                        icon={ShoppingCart}
                                        title={t('No orders found')}
                                        description={t('Orders will appear here when customers make purchases or subscription checkouts.')}
                                        hasFilters={hasFilters}
                                        onClearFilters={clearFilters}
                                        className="h-auto"
                                    />
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="space-y-3">
                                {orders.data.map((order) => (
                                    <OrderCard
                                        key={order.id}
                                        order={order}
                                        pageProps={pageProps}
                                        onView={() => setSelectedOrder(order)}
                                        onCopy={() => copyReceipt(order.receipt)}
                                    />
                                ))}
                            </div>
                        )}

                        <Card className="rounded-2xl shadow-sm">
                            <CardContent className="flex flex-col gap-3 p-4 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    {t('Showing')} {orders.from || 0} {t('to')} {orders.to || 0} {t('of')} {orders.total} {t('orders')}
                                </div>
                                <div className="flex gap-2">
                                    <Button type="button" variant="outline" disabled={orders.current_page <= 1} onClick={() => visit({ page: orders.current_page - 1 })}>
                                        {t('Previous')}
                                    </Button>
                                    <Button type="button" variant="outline" disabled={orders.current_page >= orders.last_page} onClick={() => visit({ page: orders.current_page + 1 })}>
                                        {t('Next')}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </section>
                </div>
            </div>

            <Dialog open={Boolean(selectedOrder)} onOpenChange={(open) => !open && setSelectedOrder(null)}>
                <DialogContent className="max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>{selectedOrder?.order_id || t('Order details')}</DialogTitle>
                        <DialogDescription>
                            {t('Checkout, payment, posting, and provisioning breakdown for this order.')}
                        </DialogDescription>
                    </DialogHeader>

                    {selectedOrder && (
                        <DialogBody className="space-y-6">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <Detail label={t('Customer')} value={selectedOrder.customer_name} subValue={selectedOrder.customer_email} />
                                <Detail label={t('Package / Source')} value={selectedOrder.plan_name} subValue={selectedOrder.source} />
                                <Detail label={t('Amount')} value={formatAdminCurrency(selectedOrder.amount, pageProps)} subValue={selectedOrder.currency} />
                                <Detail label={t('Payment Method')} value={selectedOrder.method || 'Unknown'} />
                                <Detail label={t('M-Pesa / Gateway Receipt')} value={selectedOrder.receipt || '—'} />
                                <Detail label={t('Checkout / Transaction ID')} value={selectedOrder.txn_id || '—'} />
                            </div>

                            <div className="grid gap-3 sm:grid-cols-3">
                                <div className="rounded-xl border p-4">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('Payment')}</p>
                                    <div className="mt-3"><StatusBadge value={selectedOrder.payment_status} /></div>
                                </div>
                                <div className="rounded-xl border p-4">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('Posting')}</p>
                                    <div className="mt-3"><StatusBadge value={selectedOrder.posting_status} /></div>
                                </div>
                                <div className="rounded-xl border p-4">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('Provisioning')}</p>
                                    <div className="mt-3"><StatusBadge value={selectedOrder.provisioning_status} /></div>
                                </div>
                            </div>

                            <div className="rounded-xl border p-4">
                                <p className="text-sm font-semibold text-foreground">{t('Timeline')}</p>
                                <div className="mt-4 space-y-4">
                                    {selectedOrder.timeline.map((item, index) => (
                                        <div key={`${item.title}-${index}`} className="flex gap-3">
                                            <div className="mt-0.5">
                                                {item.status === 'completed' ? (
                                                    <CheckCircle2 className="h-5 w-5 text-emerald-600" />
                                                ) : item.status === 'failed' ? (
                                                    <XCircle className="h-5 w-5 text-rose-600" />
                                                ) : (
                                                    <Clock3 className="h-5 w-5 text-amber-600" />
                                                )}
                                            </div>
                                            <div>
                                                <p className="font-medium text-foreground">{item.title}</p>
                                                <p className="text-sm text-muted-foreground">{item.description}</p>
                                                {item.date && <p className="mt-1 text-xs text-muted-foreground">{item.date}</p>}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </DialogBody>
                    )}
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}

function Detail({ label, value, subValue }: { label: string; value?: string | null; subValue?: string | null }) {
    return (
        <div className="rounded-xl border bg-muted/20 p-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</p>
            <p className="mt-2 break-words font-semibold text-foreground">{value || '—'}</p>
            {subValue && <p className="mt-1 break-words text-sm text-muted-foreground">{subValue}</p>}
        </div>
    );
}
