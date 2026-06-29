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
import { EmptyState, MetricCard, PageHeader, Paginated, StatusBadge, money, titleCase } from '../components';
import { Head, router } from '@inertiajs/react';
import { Clock, EyeOff, Package, Plus, Router as RouterIcon, Search, ShieldCheck, Wifi } from 'lucide-react';
import { FormEvent, useState } from 'react';

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

type Plan = {
    id: number;
    name: string;
    package_type?: string | null;
    access_type?: string | null;
    price?: string | number | null;
    download_speed_mbps?: string | number | null;
    upload_speed_mbps?: string | number | null;
    billing_cycle?: string | null;
    validity_days?: number | null;
    status?: string | null;
    shared_users?: number | null;
    enable_burst?: boolean;
    enable_schedule?: boolean;
    available_on_all_mikrotik?: boolean;
    hidden_from_client?: boolean;
    routers_count?: number;
    isp?: { id: number; name: string } | null;
    edit_url: string;
};

type Props = {
    packages: Paginated<Plan>;
    packageStats: {
        all: number;
        hotspot: number;
        pppoe: number;
        data_bundle: number;
        free_trial: number;
    };
    activeFilter: string;
    filters: {
        q?: string;
        type?: string;
        status?: string;
    };
};

export default function PlansIndex({ packages, packageStats, activeFilter, filters }: Props) {
    const [localFilters, setLocalFilters] = useState({
        q: filters.q || '',
        type: filters.type || activeFilter || 'all',
        status: filters.status || 'all',
    });

    const cleanFilters = () => Object.fromEntries(
        Object.entries(localFilters).filter(([, value]) => value && value !== 'all')
    );

    const applyFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('wifi-billing.packages.index'), cleanFilters(), {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Plans' },
            ]}
            pageTitle="Internet Plans"
        >
            <Head title="Internet Plans" />

            <div className="space-y-5">
                <PageHeader
                    title="Internet Plans"
                    description="Review hotspot, PPPoE, data bundle, and trial plans from the main admin workspace."
                    actions={
                        <Button asChild>
                            <a href={route('isp.packages.create')}>
                                <Plus className="h-4 w-4" />
                                Add Plan
                            </a>
                        </Button>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <MetricCard title="All Plans" value={packageStats.all} note="Total configured" icon={Package} />
                    <MetricCard title="Hotspot" value={packageStats.hotspot} note="Hotspot plans" icon={Wifi} />
                    <MetricCard title="PPPoE" value={packageStats.pppoe} note="PPPoE plans" icon={ShieldCheck} />
                    <MetricCard title="Data Bundles" value={packageStats.data_bundle} note="Usage packages" icon={Package} />
                    <MetricCard title="Free Trials" value={packageStats.free_trial} note="Trial access" icon={Clock} />
                </div>

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent className="p-4">
                        <form onSubmit={applyFilters} className="grid gap-3 lg:grid-cols-5">
                            <div className="relative lg:col-span-2">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={localFilters.q}
                                    onChange={(event) => setLocalFilters({ ...localFilters, q: event.target.value })}
                                    placeholder="Search plan, cycle, type"
                                    className="pl-9"
                                />
                            </div>

                            <Select value={localFilters.type} onValueChange={(value) => setLocalFilters({ ...localFilters, type: value })}>
                                <SelectTrigger><SelectValue placeholder="Type" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All types</SelectItem>
                                    <SelectItem value="hotspot">Hotspot</SelectItem>
                                    <SelectItem value="pppoe">PPPoE</SelectItem>
                                    <SelectItem value="data_bundle">Data bundle</SelectItem>
                                    <SelectItem value="free_trial">Free trial</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select value={localFilters.status} onValueChange={(value) => setLocalFilters({ ...localFilters, status: value })}>
                                <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>

                            <div className="flex gap-2 lg:justify-end">
                                <Button type="button" variant="outline" onClick={() => router.get(route('wifi-billing.packages.index'))}>Reset</Button>
                                <Button type="submit">Apply</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {packages.data.length === 0 ? (
                            <div className="p-4">
                                <EmptyState
                                    title="No plans found"
                                    description="Adjust filters or create a new internet plan."
                                    action={
                                        <Button asChild>
                                            <a href={route('isp.packages.create')}>
                                                <Plus className="h-4 w-4" />
                                                Add Plan
                                            </a>
                                        </Button>
                                    }
                                />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Speed</TableHead>
                                        <TableHead>Billing</TableHead>
                                        <TableHead>Routers</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {packages.data.map((plan) => (
                                        <TableRow key={plan.id}>
                                            <TableCell>
                                                <div className="font-medium">{plan.name}</div>
                                                <div className="text-xs text-muted-foreground">{plan.isp?.name || 'Current ISP'}</div>
                                                {plan.hidden_from_client && (
                                                    <div className="mt-1 inline-flex items-center gap-1 text-xs text-muted-foreground">
                                                        <EyeOff className="h-3 w-3" />
                                                        Hidden from client
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell><StatusBadge value={plan.package_type || plan.access_type} /></TableCell>
                                            <TableCell>
                                                <div>{plan.download_speed_mbps || 0} Mbps down</div>
                                                <div className="text-xs text-muted-foreground">{plan.upload_speed_mbps || 0} Mbps up</div>
                                            </TableCell>
                                            <TableCell>
                                                <div>{money(plan.price)}</div>
                                                <div className="text-xs text-muted-foreground">{titleCase(plan.billing_cycle)} / {plan.validity_days || 0} days</div>
                                            </TableCell>
                                            <TableCell>
                                                {plan.available_on_all_mikrotik ? (
                                                    <span>All routers</span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1">
                                                        <RouterIcon className="h-4 w-4 text-muted-foreground" />
                                                        {plan.routers_count || 0}
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell><StatusBadge value={plan.status} /></TableCell>
                                            <TableCell className="text-right">
                                                <Button variant="outline" size="sm" asChild>
                                                    <a href={plan.edit_url}>Edit</a>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {packages.last_page > 1 && (
                        <CardContent className="border-t px-4 py-0">
                            <Pagination data={packages} routeName="wifi-billing.packages.index" filters={cleanFilters()} />
                        </CardContent>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
