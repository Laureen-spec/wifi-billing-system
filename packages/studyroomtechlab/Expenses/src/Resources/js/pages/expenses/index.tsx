import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { EmptyState, MetricCard, PageHeader, Paginated, StatusBadge, money } from '@/pages/wifi-billing/components';
import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, Edit3, Filter, Plus, ReceiptText, Search, Trash2, TrendingDown } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface Option {
    value: string;
    label: string;
}

type Expense = {
    id: number;
    expense_number: string;
    category: string;
    category_label: string;
    description: string;
    amount: number;
    payment_method: string;
    payment_method_label: string;
    receipt_number?: string | null;
    expense_date: string;
    status: string;
    notes?: string | null;
    edit_url: string;
    destroy_url: string;
};

type Filters = {
    q?: string;
    category?: string;
    payment_method?: string;
    status?: string;
    from?: string;
    to?: string;
};

type Props = {
    isp?: { id: number; name: string } | null;
    expenses: Paginated<Expense>;
    stats: {
        this_month: number;
        last_7_days: number;
        today: number;
        all_time: number;
        records: number;
    };
    filters: Filters;
    options: {
        categories: Option[];
        paymentMethods: Option[];
        statuses: Option[];
    };
};

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

const toSelectValue = (value?: string | null) => value ? String(value) : 'all';

export default function IspExpensesIndex({ isp, expenses, stats, filters, options }: Props) {
    const [localFilters, setLocalFilters] = useState({
        q: filters.q || '',
        category: toSelectValue(filters.category),
        payment_method: toSelectValue(filters.payment_method),
        status: toSelectValue(filters.status),
        from: filters.from || '',
        to: filters.to || '',
    });

    const cleanFilters = () => Object.fromEntries(
        Object.entries(localFilters).filter(([, value]) => value && value !== 'all')
    );

    const applyFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('expenses.index'), cleanFilters(), {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        router.get(route('expenses.index'), {}, {
            preserveState: true,
            replace: true,
        });
    };

    const destroyExpense = (expense: Expense) => {
        if (! window.confirm(`Delete expense ${expense.expense_number}?`)) {
            return;
        }

        router.delete(expense.destroy_url, {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Expenses' },
            ]}
            pageTitle="Expenses"
        >
            <Head title="Expense Ledger" />

            <div className="space-y-5">
                <PageHeader
                    title="Expense Ledger"
                    description={`Track outgoing money for ${isp?.name || 'your ISP'} so revenue reports show a real bottom line.`}
                    actions={
                        <Button asChild>
                            <Link href={route('expenses.create')}>
                                <Plus className="h-4 w-4" />
                                Record Expense
                            </Link>
                        </Button>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <MetricCard title="This Month" value={money(stats.this_month)} note="Paid/reconciled expenses" icon={TrendingDown} tone={stats.this_month > 0 ? 'warning' : 'default'} />
                    <MetricCard title="Last 7 Days" value={money(stats.last_7_days)} note="Rolling expense window" icon={CalendarDays} />
                    <MetricCard title="Today" value={money(stats.today)} note="Recorded today" icon={CalendarDays} />
                    <MetricCard title="All Time" value={money(stats.all_time)} note={`${stats.records} records`} icon={ReceiptText} />
                    <MetricCard title="Dashboard Impact" value={`-${money(stats.this_month)}`} note="Subtracted from monthly revenue" icon={TrendingDown} tone={stats.this_month > 0 ? 'danger' : 'default'} />
                </div>

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Filter className="h-4 w-4" />
                            Expense Filters
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-4">
                        <form onSubmit={applyFilters} className="grid gap-3 lg:grid-cols-7">
                            <div className="relative lg:col-span-2">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={localFilters.q}
                                    onChange={(event) => setLocalFilters({ ...localFilters, q: event.target.value })}
                                    placeholder="Search description, receipt, notes..."
                                    className="pl-9"
                                />
                            </div>

                            <Select value={localFilters.category} onValueChange={(value) => setLocalFilters({ ...localFilters, category: value })}>
                                <SelectTrigger><SelectValue placeholder="Category" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All categories</SelectItem>
                                    {options.categories.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={localFilters.payment_method} onValueChange={(value) => setLocalFilters({ ...localFilters, payment_method: value })}>
                                <SelectTrigger><SelectValue placeholder="Method" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All methods</SelectItem>
                                    {options.paymentMethods.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={localFilters.status} onValueChange={(value) => setLocalFilters({ ...localFilters, status: value })}>
                                <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All statuses</SelectItem>
                                    {options.statuses.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Input
                                type="date"
                                value={localFilters.from}
                                onChange={(event) => setLocalFilters({ ...localFilters, from: event.target.value })}
                            />

                            <Input
                                type="date"
                                value={localFilters.to}
                                onChange={(event) => setLocalFilters({ ...localFilters, to: event.target.value })}
                            />

                            <div className="flex gap-2 lg:col-span-7 lg:justify-end">
                                <Button type="button" variant="outline" onClick={resetFilters}>Reset</Button>
                                <Button type="submit">Apply Filters</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {expenses.data.length === 0 ? (
                            <div className="p-4">
                                <EmptyState
                                    title="No expenses recorded"
                                    description="Record bandwidth, hardware, salaries, rent, utilities, and other ISP costs here."
                                    action={
                                        <Button asChild>
                                            <Link href={route('expenses.create')}>
                                                <Plus className="h-4 w-4" />
                                                Record Expense
                                            </Link>
                                        </Button>
                                    }
                                />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Category</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Method</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Amount</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {expenses.data.map((expense) => (
                                        <TableRow key={expense.id}>
                                            <TableCell>
                                                <div className="font-medium">{expense.expense_date}</div>
                                                <div className="text-xs text-muted-foreground">{expense.expense_number}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="font-medium">{expense.category_label}</div>
                                                <div className="text-xs text-muted-foreground">{expense.receipt_number || 'No receipt'}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div>{expense.description}</div>
                                                {expense.notes && <div className="mt-1 line-clamp-1 text-xs text-muted-foreground">{expense.notes}</div>}
                                            </TableCell>
                                            <TableCell>{expense.payment_method_label}</TableCell>
                                            <TableCell><StatusBadge value={expense.status} /></TableCell>
                                            <TableCell className="text-right font-semibold">{money(expense.amount)}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={expense.edit_url}>
                                                            <Edit3 className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button variant="outline" size="sm" onClick={() => destroyExpense(expense)}>
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {expenses.last_page > 1 && (
                        <CardContent className="border-t px-4 py-0">
                            <Pagination data={expenses} routeName="expenses.index" filters={cleanFilters()} />
                        </CardContent>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
