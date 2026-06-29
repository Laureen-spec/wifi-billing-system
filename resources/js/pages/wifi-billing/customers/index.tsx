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
import { EmptyState, MetricCard, PageHeader, Paginated, StatusBadge, money } from '../components';
import { Head, Link, router } from '@inertiajs/react';
import { CalendarClock, Plus, Search, ShieldCheck, UserCheck, Users, Wifi } from 'lucide-react';
import { FormEvent, useState } from 'react';

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

type Customer = {
    id: number;
    name: string;
    phone?: string | null;
    email?: string | null;
    location?: string | null;
    access_type?: string | null;
    username?: string | null;
    ip_address?: string | null;
    connection_status?: string | null;
    billing_status?: string | null;
    provisioning_status?: string | null;
    monthly_amount?: string | number | null;
    next_due_date?: string | null;
    package?: { id: number; name: string } | null;
    router?: { id: number; name: string } | null;
    show_url: string;
    edit_url: string;
};

type Option = {
    id: number;
    name: string;
};

type CustomerFilters = {
    q?: string;
    access_type?: string;
    connection_status?: string;
    billing_status?: string;
    provisioning_status?: string;
    internet_package_id?: string | number | null;
    mikrotik_router_id?: string | number | null;
    view?: string;
};

type Props = {
    customers: Paginated<Customer>;
    stats: {
        total: number;
        active: number;
        hotspot: number;
        pppoe: number;
        overdue: number;
        expiring_soon: number;
    };
    pageTitle: string;
    filters: CustomerFilters;
    createUrl?: string;
    options: {
        packages: Option[];
        routers: Option[];
    };
};

const toSelectValue = (value?: string | number | null) => value ? String(value) : 'all';

export default function CustomersIndex({ customers, stats, pageTitle, filters, options, createUrl }: Props) {
    const [localFilters, setLocalFilters] = useState({
        q: filters.q || '',
        access_type: filters.access_type || 'all',
        connection_status: filters.connection_status || 'all',
        billing_status: filters.billing_status || 'all',
        provisioning_status: filters.provisioning_status || 'all',
        internet_package_id: toSelectValue(filters.internet_package_id),
        mikrotik_router_id: toSelectValue(filters.mikrotik_router_id),
        view: filters.view || '',
    });

    const cleanFilters = () => Object.fromEntries(
        Object.entries(localFilters).filter(([, value]) => value && value !== 'all')
    );

    const applyFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('wifi-billing.customers.index'), cleanFilters(), {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        router.get(route('wifi-billing.customers.index'), {}, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: pageTitle },
            ]}
            pageTitle={pageTitle}
        >
            <Head title={pageTitle} />

            <div className="space-y-5">
                <PageHeader
                    title={pageTitle}
                    description="Search and monitor hotspot and PPPoE customers from the main admin workspace."
                    actions={
                        <Button asChild>
                            <Link href={createUrl || route('wifi-billing.customers.create')} preserveScroll={false}>
                                <Plus className="h-4 w-4" />
                                Add Customer
                            </Link>
                        </Button>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <MetricCard title="Total" value={stats.total} note="Registered customers" icon={Users} />
                    <MetricCard title="Active" value={stats.active} note="Currently connected" icon={ShieldCheck} tone="success" />
                    <MetricCard title="Hotspot" value={stats.hotspot} note="Hotspot access" icon={Wifi} />
                    <MetricCard title="PPPoE" value={stats.pppoe} note="PPPoE access" icon={UserCheck} />
                    <MetricCard title="Overdue" value={stats.overdue} note="Needs billing action" icon={CalendarClock} tone={stats.overdue > 0 ? 'danger' : 'default'} />
                    <MetricCard title="Expiring Soon" value={stats.expiring_soon} note="Next 7 days" icon={CalendarClock} tone={stats.expiring_soon > 0 ? 'warning' : 'default'} />
                </div>

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent className="p-4">
                        <form onSubmit={applyFilters} className="grid gap-3 lg:grid-cols-7">
                            <div className="relative lg:col-span-2">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={localFilters.q}
                                    onChange={(event) => setLocalFilters({ ...localFilters, q: event.target.value })}
                                    placeholder="Search name, phone, username, IP"
                                    className="pl-9"
                                />
                            </div>

                            <Select value={localFilters.access_type} onValueChange={(value) => setLocalFilters({ ...localFilters, access_type: value })}>
                                <SelectTrigger><SelectValue placeholder="Access" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All access</SelectItem>
                                    <SelectItem value="hotspot">Hotspot</SelectItem>
                                    <SelectItem value="pppoe">PPPoE</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select value={localFilters.connection_status} onValueChange={(value) => setLocalFilters({ ...localFilters, connection_status: value })}>
                                <SelectTrigger><SelectValue placeholder="Connection" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All connection</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="suspended">Suspended</SelectItem>
                                    <SelectItem value="disconnected">Disconnected</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select value={localFilters.billing_status} onValueChange={(value) => setLocalFilters({ ...localFilters, billing_status: value })}>
                                <SelectTrigger><SelectValue placeholder="Billing" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All billing</SelectItem>
                                    <SelectItem value="paid">Paid</SelectItem>
                                    <SelectItem value="unpaid">Unpaid</SelectItem>
                                    <SelectItem value="overdue">Overdue</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select value={localFilters.internet_package_id} onValueChange={(value) => setLocalFilters({ ...localFilters, internet_package_id: value })}>
                                <SelectTrigger><SelectValue placeholder="Plan" /></SelectTrigger>
                                <SelectContent searchable>
                                    <SelectItem value="all">All plans</SelectItem>
                                    {options.packages.map((option) => (
                                        <SelectItem key={option.id} value={String(option.id)}>{option.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={localFilters.mikrotik_router_id} onValueChange={(value) => setLocalFilters({ ...localFilters, mikrotik_router_id: value })}>
                                <SelectTrigger><SelectValue placeholder="Router" /></SelectTrigger>
                                <SelectContent searchable>
                                    <SelectItem value="all">All routers</SelectItem>
                                    {options.routers.map((option) => (
                                        <SelectItem key={option.id} value={String(option.id)}>{option.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <div className="flex gap-2 lg:col-span-7 lg:justify-end">
                                <Button type="button" variant="outline" onClick={resetFilters}>Reset</Button>
                                <Button type="submit">Apply Filters</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {customers.data.length === 0 ? (
                            <div className="p-4">
                                <EmptyState
                                    title="No customers found"
                                    description="Adjust the filters or add a new customer."
                                    action={
                                        <Button asChild>
                                            <Link href={createUrl || route('wifi-billing.customers.create')} preserveScroll={false}>
                                                <Plus className="h-4 w-4" />
                                                Add Customer
                                            </Link>
                                        </Button>
                                    }
                                />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Customer</TableHead>
                                        <TableHead>Access</TableHead>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Router</TableHead>
                                        <TableHead>Billing</TableHead>
                                        <TableHead>Provisioning</TableHead>
                                        <TableHead>Due Date</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {customers.data.map((customer) => (
                                        <TableRow key={customer.id}>
                                            <TableCell>
                                                <div className="font-medium">{customer.name}</div>
                                                <div className="text-xs text-muted-foreground">{customer.phone || customer.email || 'No contact'}</div>
                                                <div className="text-xs text-muted-foreground">{customer.username || customer.ip_address || customer.location || '-'}</div>
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge value={customer.access_type || 'hotspot'} />
                                                <div className="mt-1">
                                                    <StatusBadge value={customer.connection_status} />
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div>{customer.package?.name || 'No plan'}</div>
                                                <div className="text-xs text-muted-foreground">{money(customer.monthly_amount)}</div>
                                            </TableCell>
                                            <TableCell>{customer.router?.name || '-'}</TableCell>
                                            <TableCell><StatusBadge value={customer.billing_status} /></TableCell>
                                            <TableCell><StatusBadge value={customer.provisioning_status || 'pending'} /></TableCell>
                                            <TableCell>{customer.next_due_date || '-'}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <a href={customer.show_url}>View</a>
                                                    </Button>
                                                    <Button variant="outline" size="sm" asChild>
                                                        <a href={customer.edit_url}>Edit</a>
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {customers.last_page > 1 && (
                        <CardContent className="border-t px-4 py-0">
                            <Pagination data={customers} routeName="wifi-billing.customers.index" filters={cleanFilters()} />
                        </CardContent>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
