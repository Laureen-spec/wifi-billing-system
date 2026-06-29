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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Head, router, useForm } from '@inertiajs/react';
import { Download, Plus, Search, Users } from 'lucide-react';
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

type CustomerOption = {
    id: number | string;
    name: string;
    label: string;
    phone?: string | null;
    account?: string | null;
    package?: string | null;
    amount?: number | null;
    amount_formatted?: string | null;
    connection_status?: string | null;
    billing_status?: string | null;
};

type Transaction = {
    id: number | string;
    customer: string;
    customer_id?: number | string | null;
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
    customers: CustomerOption[];
};

const formatAmountForInput = (amount?: number | null) => {
    if (amount === null || amount === undefined || Number.isNaN(Number(amount)) || Number(amount) <= 0) {
        return '';
    }

    return Number(amount).toFixed(2);
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
    customers,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [recordDialogOpen, setRecordDialogOpen] = useState(false);
    const manualPaymentForm = useForm({
        customer_id: '',
        receipt: '',
        method: 'Cash',
        amount: '',
        currency: 'KES',
        status: 'confirmed',
        notes: '',
    });

    const selectedCustomer = useMemo(
        () => customers.find((customer) => String(customer.id) === String(manualPaymentForm.data.customer_id)),
        [customers, manualPaymentForm.data.customer_id],
    );

    const canRecordPayment = recordPaymentEnabled && customers.length > 0;

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

    const setSelectedCustomer = (customerId: string) => {
        const customer = customers.find((item) => String(item.id) === customerId);

        manualPaymentForm.setData({
            ...manualPaymentForm.data,
            customer_id: customerId,
            amount: manualPaymentForm.data.amount || formatAmountForInput(customer?.amount),
        });
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
                disabled={!canRecordPayment}
                onClick={() => setRecordDialogOpen(true)}
                title={canRecordPayment ? 'Record a customer payment' : 'Create customers first, then record payments against them.'}
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
                    <DialogContent className="max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>Record customer payment</DialogTitle>
                            <DialogDescription>
                                Select an existing customer from the customer table. This saves the collection only and does not create a subscriber, post wallet balance, or trigger router provisioning.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={submitManualPayment} className="space-y-5">
                            <div className="rounded-lg border bg-muted/25 p-4">
                                <div className="space-y-2">
                                    <Label htmlFor="manual-customer">Customer</Label>
                                    <Select
                                        value={manualPaymentForm.data.customer_id}
                                        onValueChange={setSelectedCustomer}
                                        disabled={customers.length === 0}
                                    >
                                        <SelectTrigger id="manual-customer" className="bg-background">
                                            <SelectValue placeholder="Search and select a customer" />
                                        </SelectTrigger>
                                        <SelectContent searchable className="max-h-80">
                                            {customers.map((customer) => (
                                                <SelectItem key={customer.id} value={String(customer.id)}>
                                                    {customer.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {manualPaymentForm.errors.customer_id && <p className="text-sm text-destructive">{manualPaymentForm.errors.customer_id}</p>}
                                    {customers.length === 0 && (
                                        <p className="text-sm text-muted-foreground">
                                            No customers were found for this account. Add the subscriber in WiFi Billing customers first, then come back to record the payment.
                                        </p>
                                    )}
                                </div>

                                {selectedCustomer && (
                                    <div className="mt-4 grid gap-3 rounded-md border bg-background p-3 text-sm md:grid-cols-4">
                                        <div>
                                            <div className="text-xs uppercase tracking-wide text-muted-foreground">Name</div>
                                            <div className="mt-1 font-medium text-foreground">{selectedCustomer.name}</div>
                                        </div>
                                        <div>
                                            <div className="text-xs uppercase tracking-wide text-muted-foreground">Phone</div>
                                            <div className="mt-1 text-foreground">{selectedCustomer.phone || 'Not set'}</div>
                                        </div>
                                        <div>
                                            <div className="text-xs uppercase tracking-wide text-muted-foreground">Account</div>
                                            <div className="mt-1 text-foreground">{selectedCustomer.account || 'Not set'}</div>
                                        </div>
                                        <div>
                                            <div className="text-xs uppercase tracking-wide text-muted-foreground">Plan / amount</div>
                                            <div className="mt-1 text-foreground">{selectedCustomer.package || 'No plan'} {selectedCustomer.amount_formatted ? `· ${selectedCustomer.amount_formatted}` : ''}</div>
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="manual-method">Collection method</Label>
                                    <Select value={manualPaymentForm.data.method} onValueChange={(value) => manualPaymentForm.setData('method', value)}>
                                        <SelectTrigger id="manual-method">
                                            <SelectValue placeholder="Select method" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Cash">Cash</SelectItem>
                                            <SelectItem value="Bank">Bank</SelectItem>
                                            <SelectItem value="Voucher">Voucher</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {manualPaymentForm.errors.method && <p className="text-sm text-destructive">{manualPaymentForm.errors.method}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="manual-status">Status</Label>
                                    <Select value={manualPaymentForm.data.status} onValueChange={(value) => manualPaymentForm.setData('status', value)}>
                                        <SelectTrigger id="manual-status">
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="confirmed">Confirmed</SelectItem>
                                            <SelectItem value="pending">Pending review</SelectItem>
                                            <SelectItem value="failed">Failed</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {manualPaymentForm.errors.status && <p className="text-sm text-destructive">{manualPaymentForm.errors.status}</p>}
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

                                <div className="grid grid-cols-3 gap-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="manual-currency">Currency</Label>
                                        <Input
                                            id="manual-currency"
                                            value={manualPaymentForm.data.currency}
                                            onChange={(event) => manualPaymentForm.setData('currency', event.target.value.toUpperCase())}
                                            placeholder="KES"
                                        />
                                        {manualPaymentForm.errors.currency && <p className="text-sm text-destructive">{manualPaymentForm.errors.currency}</p>}
                                    </div>
                                    <div className="col-span-2 space-y-2">
                                        <Label htmlFor="manual-receipt">Receipt reference</Label>
                                        <Input
                                            id="manual-receipt"
                                            value={manualPaymentForm.data.receipt}
                                            onChange={(event) => manualPaymentForm.setData('receipt', event.target.value)}
                                            placeholder="Optional"
                                        />
                                        {manualPaymentForm.errors.receipt && <p className="text-sm text-destructive">{manualPaymentForm.errors.receipt}</p>}
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="manual-notes">Internal notes</Label>
                                <Textarea
                                    id="manual-notes"
                                    value={manualPaymentForm.data.notes}
                                    onChange={(event) => manualPaymentForm.setData('notes', event.target.value)}
                                    placeholder="Example: bank slip confirmed by cashier"
                                    rows={3}
                                />
                                {manualPaymentForm.errors.notes && <p className="text-sm text-destructive">{manualPaymentForm.errors.notes}</p>}
                            </div>

                            {manualPaymentError && <p className="text-sm text-destructive">{manualPaymentError}</p>}

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setRecordDialogOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={manualPaymentForm.processing || !canRecordPayment}>
                                    Save Payment
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                <div className="rounded-xl border bg-card shadow-sm">
                    <div className="flex flex-col gap-4 border-b p-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">Billing operations</div>
                            <h2 className="mt-2 text-xl font-semibold tracking-tight text-foreground">Payment workspace</h2>
                            <p className="mt-1 max-w-3xl text-sm text-muted-foreground">{subtitle}</p>
                        </div>
                        <div className="flex items-center gap-3 rounded-lg border bg-background px-4 py-3 text-sm">
                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                <Users className="h-4 w-4" />
                            </div>
                            <div>
                                <div className="font-medium text-foreground">Customer-linked records</div>
                                <div className="text-xs text-muted-foreground">Manual payments must use existing customers.</div>
                            </div>
                        </div>
                    </div>
                    <div className="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        {summary.map(({ key, ...card }) => (
                            <SummaryCard key={key} {...card} />
                        ))}
                    </div>
                </div>

                <div className="rounded-xl border bg-card p-4 shadow-sm">
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <form onSubmit={submitSearch} className="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Search receipt, phone, customer, username, account number..."
                                    className="pl-9"
                                />
                            </div>
                            <Button type="submit">Search</Button>
                        </form>
                    </div>

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

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <div className="flex flex-col gap-1 border-b px-4 py-4 sm:px-5">
                        <h2 className="text-base font-semibold text-foreground">Payment ledger</h2>
                        <p className="text-sm text-muted-foreground">Confirmed, pending, manual, and gateway collections from Payment Center.</p>
                    </div>

                    {transactions.length === 0 ? (
                        <div className="p-4">
                            <EmptyState />
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-border text-sm">
                                <thead className="bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3">Customer</th>
                                        <th className="px-4 py-3">Receipt</th>
                                        <th className="px-4 py-3">Channel</th>
                                        <th className="px-4 py-3">Plan / source</th>
                                        <th className="px-4 py-3 text-right">Amount</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Posting</th>
                                        <th className="px-4 py-3">Date</th>
                                        <th className="px-4 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {transactions.map((transaction) => (
                                        <tr key={transaction.id} className="hover:bg-muted/35">
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-foreground">{transaction.customer}</div>
                                                <div className="mt-1 text-xs text-muted-foreground">
                                                    {transaction.phone || 'No phone'}{transaction.account ? ` · ${transaction.account}` : ''}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{transaction.receipt || 'Pending'}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-foreground">{transaction.method}</div>
                                                <div className="text-xs text-muted-foreground">{transaction.source}</div>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">{transaction.package || 'No package'}</td>
                                            <td className="px-4 py-3 text-right font-semibold text-foreground">{transaction.amount_formatted}</td>
                                            <td className="px-4 py-3"><StatusBadge value={transaction.status} /></td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-col gap-1">
                                                    <StatusBadge value={transaction.wallet} compact />
                                                    <StatusBadge value={transaction.provisioning} compact />
                                                </div>
                                            </td>
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
