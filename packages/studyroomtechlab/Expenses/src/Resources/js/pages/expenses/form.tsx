import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ReceiptText, Save, TrendingDown } from 'lucide-react';
import { FormEvent } from 'react';

interface Option {
    value: string;
    label: string;
}

type ExpenseFormData = {
    category: string;
    description: string;
    amount: string;
    payment_method: string;
    receipt_number: string;
    expense_date: string;
    status: string;
    notes: string;
};

type ExpensePayload = Partial<ExpenseFormData> & {
    id?: number | null;
    expense_number?: string | null;
    amount?: string | number;
};

type Props = {
    mode: 'create' | 'edit';
    isp?: { id: number; name: string } | null;
    expense: ExpensePayload;
    options: {
        categories: Option[];
        paymentMethods: Option[];
        statuses: Option[];
    };
    storeUrl?: string;
    updateUrl?: string;
};

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

export default function IspExpenseForm({ mode, isp, expense, options, storeUrl, updateUrl }: Props) {
    const isEdit = mode === 'edit';
    const { data, setData, post, put, processing, errors } = useForm<ExpenseFormData>({
        category: expense.category || 'other',
        description: expense.description || '',
        amount: String(expense.amount ?? ''),
        payment_method: expense.payment_method || 'cash',
        receipt_number: expense.receipt_number || '',
        expense_date: expense.expense_date || new Date().toISOString().slice(0, 10),
        status: expense.status || 'paid',
        notes: expense.notes || '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && updateUrl) {
            put(updateUrl, { preserveScroll: true });
            return;
        }

        post(storeUrl || route('expenses.store'), { preserveScroll: true });
    };

    const error = (key: keyof ExpenseFormData) => errors[key] ? <p className="mt-1 text-xs text-red-600">{errors[key]}</p> : null;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Expenses', url: route('expenses.index') },
                { label: isEdit ? 'Edit Expense' : 'Record Expense' },
            ]}
            pageTitle={isEdit ? 'Edit Expense' : 'Record Expense'}
        >
            <Head title={isEdit ? 'Edit Expense' : 'Record Expense'} />

            <div className="space-y-5">
                <div className="flex flex-col gap-3 rounded-lg border bg-card p-5 shadow-sm md:flex-row md:items-center md:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-sm font-medium uppercase tracking-wide text-muted-foreground">
                            <TrendingDown className="h-4 w-4" />
                            Expenses — {isEdit ? expense.expense_number : 'New'}
                        </div>
                        <h2 className="mt-2 text-2xl font-semibold tracking-tight">
                            {isEdit ? 'Edit Expense' : 'Record an Expense'}
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Outgoing money for {isp?.name || 'your ISP'}. Paid and reconciled expenses are subtracted from dashboard revenue.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={route('expenses.index')}>
                            <ArrowLeft className="h-4 w-4" />
                            Back to Ledger
                        </Link>
                    </Button>
                </div>

                <form onSubmit={submit} className="space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ReceiptText className="h-4 w-4" />
                                Expense Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="text-sm font-medium">Category <span className="text-red-600">*</span></label>
                                <select
                                    className="mt-1 w-full rounded-md border bg-background px-3 py-2"
                                    value={data.category}
                                    onChange={(event) => setData('category', event.target.value)}
                                >
                                    {options.categories.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                                {error('category')}
                            </div>

                            <div>
                                <label className="text-sm font-medium">Payment Method <span className="text-red-600">*</span></label>
                                <select
                                    className="mt-1 w-full rounded-md border bg-background px-3 py-2"
                                    value={data.payment_method}
                                    onChange={(event) => setData('payment_method', event.target.value)}
                                >
                                    {options.paymentMethods.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                                {error('payment_method')}
                            </div>

                            <div className="md:col-span-2">
                                <label className="text-sm font-medium">Description <span className="text-red-600">*</span></label>
                                <Input
                                    className="mt-1"
                                    value={data.description}
                                    onChange={(event) => setData('description', event.target.value)}
                                    placeholder="Example: bandwidth renewal, router repair, technician transport"
                                />
                                {error('description')}
                            </div>

                            <div>
                                <label className="text-sm font-medium">Amount <span className="text-red-600">*</span></label>
                                <Input
                                    className="mt-1"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    value={data.amount}
                                    onChange={(event) => setData('amount', event.target.value)}
                                    placeholder="0.00"
                                />
                                {error('amount')}
                            </div>

                            <div>
                                <label className="text-sm font-medium">Expense Date <span className="text-red-600">*</span></label>
                                <Input
                                    className="mt-1"
                                    type="date"
                                    value={data.expense_date}
                                    onChange={(event) => setData('expense_date', event.target.value)}
                                />
                                {error('expense_date')}
                            </div>

                            <div>
                                <label className="text-sm font-medium">Receipt / Reference Number</label>
                                <Input
                                    className="mt-1"
                                    value={data.receipt_number}
                                    onChange={(event) => setData('receipt_number', event.target.value)}
                                    placeholder="Optional, but must be unique per ISP"
                                />
                                <p className="mt-1 text-xs text-muted-foreground">Used for reconciliation. Duplicate references are blocked.</p>
                                {error('receipt_number')}
                            </div>

                            <div>
                                <label className="text-sm font-medium">Status <span className="text-red-600">*</span></label>
                                <select
                                    className="mt-1 w-full rounded-md border bg-background px-3 py-2"
                                    value={data.status}
                                    onChange={(event) => setData('status', event.target.value)}
                                >
                                    {options.statuses.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                                {error('status')}
                            </div>

                            <div className="md:col-span-2">
                                <label className="text-sm font-medium">Notes</label>
                                <Textarea
                                    className="mt-1 min-h-28"
                                    value={data.notes}
                                    onChange={(event) => setData('notes', event.target.value)}
                                    placeholder="Optional internal notes"
                                />
                                {error('notes')}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-2">
                        <Button variant="outline" asChild><Link href={route('expenses.index')}>Cancel</Link></Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="h-4 w-4" />
                            {processing ? 'Saving...' : isEdit ? 'Save Changes' : 'Record Expense'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
