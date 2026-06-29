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
    slate: 'border-l-slate-400',
    amber: 'border-l-amber-500',
    green: 'border-l-emerald-500',
    red: 'border-l-rose-500',
    blue: 'border-l-sky-500',
};

function statusLabel(status?: string | null) {
    return String(status || 'Unknown')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function statusBadgeClass(status?: string | null) {
    const normalized = String(status || '').toLowerCase();

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

    return (
        <div className={`border-l-4 bg-card px-5 py-4 ${toneClasses[card.tone || 'slate'] || toneClasses.slate}`}>
            <p className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{card.title}</p>
            <p className="mt-2 text-2xl font-bold tracking-tight text-foreground">{value}</p>
            <p className="mt-1 text-sm text-muted-foreground">{card.description}</p>
        </div>
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

    const pageTitle = t('Order ledger');

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
                <div className="flex flex-col gap-4 border-b pb-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                            {t('Payment Center')} — {t('Orders')}
                        </p>
                        <h1 className="mt-2 text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                            {t('Order')} <span className="text-primary">{t('ledger')}.</span>
                        </h1>
                        <p className="mt-2 max-w-3xl text-base text-muted-foreground">
                            {t('Track customer checkouts, manual collections, gateway payments, and provisioning status.')}
                        </p>
                    </div>

                    <Button type="button" variant="outline" onClick={() => router.visit(route('isp-payment-center.index'))}>
                        <ReceiptText className="mr-2 h-4 w-4" />
                        {t('Payment ledger')}
                    </Button>
                </div>

                <div className="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div className="grid divide-y sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-5">
                        {summary.map((card) => (
                            <SummaryTile key={card.key} card={card} pageProps={pageProps} />
                        ))}
                    </div>
                </div>

                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex max-w-full gap-2 overflow-x-auto rounded-full border bg-card p-1 shadow-sm">
                        {statusTabs.map((tab) => (
                            <button
                                key={tab.key}
                                type="button"
                                onClick={() => visit({ status: tab.key, page: 1 })}
                                className={`inline-flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition ${
                                    activeStatus === tab.key
                                        ? 'bg-foreground text-background shadow-sm'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                }`}
                            >
                                {t(tab.label)}
                                <span className={`rounded-full px-2 py-0.5 text-xs ${activeStatus === tab.key ? 'bg-background/20' : 'bg-muted'}`}>
                                    {tab.count}
                                </span>
                            </button>
                        ))}
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <Button type="button" variant="outline" size="icon" title={t('Filters')}>
                            <Filter className="h-4 w-4" />
                        </Button>
                        <div className="relative w-full sm:w-96">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter') {
                                        visit({ page: 1 });
                                    }
                                }}
                                className="h-11 rounded-full pl-10"
                                placeholder={t('Search by order #, customer, receipt, package...')}
                            />
                        </div>
                        <Button type="button" onClick={() => visit({ page: 1 })}>
                            {t('Search')}
                        </Button>
                    </div>
                </div>

                <Card className="overflow-hidden rounded-2xl shadow-sm">
                    <CardContent className="p-0">
                        {orders.data.length === 0 ? (
                            <div className="p-8">
                                <NoRecordsFound
                                    icon={ShoppingCart}
                                    title={t('No orders found')}
                                    description={t('Orders will appear here when customers make purchases or subscription checkouts.')}
                                    hasFilters={hasFilters}
                                    onClearFilters={clearFilters}
                                    className="h-auto"
                                />
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-border text-sm">
                                    <thead className="bg-muted/50 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                        <tr>
                                            <th className="px-5 py-4">{t('Order')}</th>
                                            <th className="px-5 py-4">{t('Customer')}</th>
                                            <th className="px-5 py-4">{t('Source / Package')}</th>
                                            <th className="px-5 py-4">{t('Method')}</th>
                                            <th className="px-5 py-4 text-right">{t('Amount')}</th>
                                            <th className="px-5 py-4">{t('Payment')}</th>
                                            <th className="px-5 py-4">{t('Provisioning')}</th>
                                            <th className="px-5 py-4">{t('Date')}</th>
                                            <th className="px-5 py-4 text-right">{t('Actions')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border bg-card">
                                        {orders.data.map((order) => (
                                            <tr key={order.id} className="hover:bg-muted/40">
                                                <td className="px-5 py-4">
                                                    <div className="font-mono text-sm font-bold text-foreground">{order.order_id}</div>
                                                    <div className="mt-1 text-xs text-muted-foreground">
                                                        {order.receipt ? order.receipt : '—'}
                                                    </div>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <div className="font-medium text-foreground">{order.customer_name}</div>
                                                    {order.customer_email && <div className="text-xs text-muted-foreground">{order.customer_email}</div>}
                                                </td>
                                                <td className="px-5 py-4">
                                                    <div className="font-medium text-foreground">{order.plan_name}</div>
                                                    <div className="text-xs text-muted-foreground">{order.source}</div>
                                                </td>
                                                <td className="px-5 py-4 text-muted-foreground">{order.method || 'Unknown'}</td>
                                                <td className="px-5 py-4 text-right font-semibold text-foreground">
                                                    {formatAdminCurrency(order.amount, pageProps)}
                                                </td>
                                                <td className="px-5 py-4">
                                                    <StatusBadge value={order.payment_status} />
                                                    <div className="mt-2">
                                                        <StatusBadge value={order.posting_status} />
                                                    </div>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <StatusBadge value={order.provisioning_status} />
                                                </td>
                                                <td className="px-5 py-4 text-muted-foreground">
                                                    {order.created_at_display || (order.created_at ? formatDate(order.created_at, pageProps) : 'Unknown')}
                                                </td>
                                                <td className="px-5 py-4">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button type="button" size="icon" variant="ghost" title={t('View order')} onClick={() => setSelectedOrder(order)}>
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            size="icon"
                                                            variant="ghost"
                                                            title={t('Copy receipt')}
                                                            disabled={!order.receipt}
                                                            onClick={() => copyReceipt(order.receipt)}
                                                        >
                                                            <Copy className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
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
