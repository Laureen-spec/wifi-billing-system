import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head, router, useForm } from '@inertiajs/react';
import { Download, Plus, Search } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { EmptyState, FilterPill, RowActions, StatusBadge, SummaryCard } from './components';

declare function route(name: string, params?: Record<string, unknown>): string;

type Summary = {
    key: string;
    title: string;
    value: string;
    description: string;
    tone?: string;
};

type FilterOption = {
    value: string;
    label: string;
};

type Transaction = {
    id: number | string;
    customer: string;
    phone?: string | null;
    account?: string | null;
    receipt?: string | null;
    method: string;
    source: string;
    package?: string | null;
    amount_formatted: string;
    currency: string;
    status: string;
    wallet: string;
    provisioning: string;
    date?: string | null;
    notes?: string | null;
    view_url?: string | null;
    download_receipt_url?: string | null;
};

type Pagination = {
    current_page: number;
    last_page: number;
    from?: number | null;
    to?: number | null;
    total: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
};

type Props = {
    pageTitle: string;
    subtitle: string;
    summary: Summary[];
    transactions: Transaction[];
    pagination: Pagination;
    filters: {
        filter: string;
        search: string;
        perPage: number;
    };
    filterOptions: FilterOption[];
    exportEnabled: boolean;
    recordPaymentEnabled: boolean;
};

export default function PaymentCenterIndex({
    pageTitle,
    subtitle,
    summary,
    transactions,
    pagination,
    filters,
    filterOptions,
    exportEnabled,
    recordPaymentEnabled,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [recordDialogOpen, setRecordDialogOpen] = useState(false);
    const manualPaymentForm = useForm({
        customer_name: '',
        phone: '',
        account: '',
        receipt: '',
        method: 'Cash',
        amount: '',
        currency: 'KES',
        status: 'confirmed',
        notes: '',
    });
    const exportHref = useMemo(() => {
        if (typeof window === 'undefined') {
            return route('isp-payment-center.export');
        }

        return `${route('isp-payment-center.export')}${window.location.search}`;
    }, []);

    const visit = (params: Record<string, string | number | undefined | null>) => {
        router.get(route('isp-payment-center.index'), {
            filter: filters.filter,
            search: filters.search,
            per_page: filters.perPage,
            ...params,
        }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        visit({ search, page: 1 });
    };

    const submitManualPayment = (event: FormEvent) => {
        event.preventDefault();
        manualPaymentForm.post(route('isp-payment-center.manual-payments.store'), {
            preserveScroll: true,
            onSuccess: () => {
                manualPaymentForm.reset();
                setRecordDialogOpen(false);
            },
        });
    };
    const manualPaymentError = (manualPaymentForm.errors as Record<string, string>).manual_payment;

    const pageActions = (
        <div className="flex flex-wrap items-center gap-2">
            <Button
                type="button"
                disabled={!recordPaymentEnabled}
                onClick={() => setRecordDialogOpen(true)}
                title={recordPaymentEnabled ? 'Record a manual payment' : 'Manual payment storage is not ready. Run migrations first.'}
            >
                <Plus className="h-4 w-4" />
                Record Payment
            </Button>
            <Button asChild={exportEnabled} type="button" variant="outline" disabled={!exportEnabled} title={exportEnabled ? 'Export CSV' : 'Export is not available'}>
                {exportEnabled ? (
                    <a href={exportHref}>
                        <Download className="h-4 w-4" />
                        Export
                    </a>
                ) : (
                    <span>
                        <Download className="h-4 w-4" />
                        Export
                    </span>
                )}
            </Button>
        </div>
    );

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Payment Center' },
            ]}
            pageTitle={pageTitle}
            pageActions={pageActions}
        >
            <Head title={pageTitle} />

            <div className="space-y-6">
                <Dialog open={recordDialogOpen} onOpenChange={setRecordDialogOpen}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Record Payment</DialogTitle>
                            <DialogDescription>
                                Save a cash, bank, or voucher collection in Payment Center without triggering M-Pesa, wallet posting, or provisioning.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={submitManualPayment} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="manual-customer">Customer</Label>
                                    <Input
                                        id="manual-customer"
                                        value={manualPaymentForm.data.customer_name}
                                        onChange={(event) => manualPaymentForm.setData('customer_name', event.target.value)}
                                        placeholder="Customer name"
                                    />
                                    {manualPaymentForm.errors.customer_name && <p className="text-sm text-destructive">{manualPaymentForm.errors.customer_name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="manual-phone">Phone</Label>
                                    <Input
                                        id="manual-phone"
                                        value={manualPaymentForm.data.phone}
                                        onChange={(event) => manualPaymentForm.setData('phone', event.target.value)}
                                        placeholder="Phone number"
                                    />
                                    {manualPaymentForm.errors.phone && <p className="text-sm text-destructive">{manualPaymentForm.errors.phone}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="manual-account">Account</Label>
                                    <Input
                                        id="manual-account"
                                        value={manualPaymentForm.data.account}
                                        onChange={(event) => manualPaymentForm.setData('account', event.target.value)}
                                        placeholder="Account or username"
                                    />
                                    {manualPaymentForm.errors.account && <p className="text-sm text-destructive">{manualPaymentForm.errors.account}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="manual-receipt">Receipt</Label>
                                    <Input
                                        id="manual-receipt"
                                        value={manualPaymentForm.data.receipt}
                                        onChange={(event) => manualPaymentForm.setData('receipt', event.target.value)}
                                        placeholder="Optional receipt reference"
                                    />
                                    {manualPaymentForm.errors.receipt && <p className="text-sm text-destructive">{manualPaymentForm.errors.receipt}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="manual-method">Method</Label>
                                    <select
                                        id="manual-method"
                                        value={manualPaymentForm.data.method}
                                        onChange={(event) => manualPaymentForm.setData('method', event.target.value)}
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    >
                                        <option value="Cash">Cash</option>
                                        <option value="Bank">Bank</option>
                                        <option value="Voucher">Voucher</option>
                                    </select>
                                    {manualPaymentForm.errors.method && <p className="text-sm text-destructive">{manualPaymentForm.errors.method}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="manual-amount">Amount</Label>
                                    <Input
                                        id="manual-amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={manualPaymentForm.data.amount}
                                        onChange={(event) => manualPaymentForm.setData('amount', event.target.value)}
                                        placeholder="0.00"
                                    />
                                    {manualPaymentForm.errors.amount && <p className="text-sm text-destructive">{manualPaymentForm.errors.amount}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="manual-currency">Currency</Label>
                                    <Input
                                        id="manual-currency"
                                        value={manualPaymentForm.data.currency}
                                        onChange={(event) => manualPaymentForm.setData('currency', event.target.value)}
                                        placeholder="KES"
                                    />
                                    {manualPaymentForm.errors.currency && <p className="text-sm text-destructive">{manualPaymentForm.errors.currency}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="manual-status">Status</Label>
                                    <select
                                        id="manual-status"
                                        value={manualPaymentForm.data.status}
                                        onChange={(event) => manualPaymentForm.setData('status', event.target.value)}
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    >
                                        <option value="confirmed">Confirmed</option>
                                        <option value="pending">Pending</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                    {manualPaymentForm.errors.status && <p className="text-sm text-destructive">{manualPaymentForm.errors.status}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="manual-notes">Notes</Label>
                                <Textarea
                                    id="manual-notes"
                                    value={manualPaymentForm.data.notes}
                                    onChange={(event) => manualPaymentForm.setData('notes', event.target.value)}
                                    placeholder="Internal notes"
                                    rows={3}
                                />
                                {manualPaymentForm.errors.notes && <p className="text-sm text-destructive">{manualPaymentForm.errors.notes}</p>}
                            </div>

                            {manualPaymentError && <p className="text-sm text-destructive">{manualPaymentError}</p>}

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setRecordDialogOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={manualPaymentForm.processing}>
                                    Save Payment
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                <div className="rounded-lg border bg-card p-4 shadow-sm">
                    <p className="max-w-3xl text-sm text-muted-foreground">{subtitle}</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    {summary.map(({ key, ...card }) => (
                        <SummaryCard key={key} {...card} />
                    ))}
                </div>

                <div className="rounded-lg border bg-card p-4 shadow-sm">
                    <form onSubmit={submitSearch} className="flex flex-col gap-3 lg:flex-row lg:items-center">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search receipt, phone, customer, username, account number..."
                                className="pl-9"
                            />
                        </div>
                        <Button type="submit">
                            Search
                        </Button>
                    </form>

                    <div className="mt-4 flex flex-wrap gap-2">
                        {filterOptions.map((option) => (
                            <FilterPill
                                key={option.value}
                                active={filters.filter === option.value}
                                label={option.label}
                                onClick={() => visit({ filter: option.value, page: 1 })}
                            />
                        ))}
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border bg-card shadow-sm">
                    <div className="border-b px-4 py-3">
                        <h2 className="text-base font-semibold text-foreground">Transactions</h2>
                        <p className="text-sm text-muted-foreground">Collections, confirmations, and settlement activity from existing payment records.</p>
                    </div>

                    {transactions.length === 0 ? (
                        <div className="p-4">
                            <EmptyState />
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-border text-sm">
                                <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3">Customer</th>
                                        <th className="px-4 py-3">Phone / Account</th>
                                        <th className="px-4 py-3">Receipt</th>
                                        <th className="px-4 py-3">Method</th>
                                        <th className="px-4 py-3">Source</th>
                                        <th className="px-4 py-3">Package</th>
                                        <th className="px-4 py-3 text-right">Amount</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Wallet</th>
                                        <th className="px-4 py-3">Provisioning</th>
                                        <th className="px-4 py-3">Date</th>
                                        <th className="px-4 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {transactions.map((transaction) => (
                                        <tr key={transaction.id} className="hover:bg-muted/40">
                                            <td className="px-4 py-3 font-medium text-foreground">{transaction.customer}</td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                <div>{transaction.phone || 'No phone'}</div>
                                                {transaction.account && <div className="text-xs">{transaction.account}</div>}
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{transaction.receipt || 'Pending'}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{transaction.method}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{transaction.source}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{transaction.package || 'No package'}</td>
                                            <td className="px-4 py-3 text-right font-semibold text-foreground">{transaction.amount_formatted}</td>
                                            <td className="px-4 py-3"><StatusBadge value={transaction.status} /></td>
                                            <td className="px-4 py-3"><StatusBadge value={transaction.wallet} /></td>
                                            <td className="px-4 py-3"><StatusBadge value={transaction.provisioning} /></td>
                                            <td className="px-4 py-3 text-muted-foreground">{transaction.date || 'Unknown'}</td>
                                            <td className="px-4 py-3">
                                                <RowActions
                                                    receipt={transaction.receipt}
                                                    viewUrl={transaction.view_url}
                                                    receiptUrl={transaction.download_receipt_url}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        Showing {pagination.from || 0} to {pagination.to || 0} of {pagination.total} transactions
                    </div>
                    <div className="flex gap-2">
                        <Button type="button" variant="outline" disabled={!pagination.prev_page_url} onClick={() => visit({ page: pagination.current_page - 1 })}>
                            Previous
                        </Button>
                        <Button type="button" variant="outline" disabled={!pagination.next_page_url} onClick={() => visit({ page: pagination.current_page + 1 })}>
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
