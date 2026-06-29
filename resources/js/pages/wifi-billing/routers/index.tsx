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
import { EmptyState, MetricCard, PageHeader, Paginated, StatusBadge, memoryText } from '../components';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, MonitorUp, Plus, Router as RouterIcon, Search, ShieldCheck, Wifi } from 'lucide-react';
import { FormEvent, useState } from 'react';

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

type RouterRow = {
    id: number;
    name: string;
    identity?: string | null;
    host?: string | null;
    api_port?: number | null;
    board_name?: string | null;
    routeros_version?: string | null;
    architecture?: string | null;
    uptime?: string | null;
    cpu_load?: number | null;
    memory_free?: number | null;
    memory_total?: number | null;
    connection_type?: string | null;
    status?: string | null;
    live_status?: string | null;
    provision_status?: string | null;
    hotspot_status?: string | null;
    hotspot_files_status?: string | null;
    sync_status?: string | null;
    last_seen_at?: string | null;
    packages_count?: number;
    isp?: { id: number; name: string } | null;
    show_url: string;
    edit_url: string;
    setup_url: string;
    live_url: string;
};

type Props = {
    routers: Paginated<RouterRow>;
    stats: {
        total: number;
        active: number;
        online: number;
        offline: number;
        waiting_for_link: number;
        hotspot_files_missing: number;
    };
    createUrl?: string;
    filters: {
        q?: string;
        status?: string;
        connection_type?: string;
        hotspot_files_status?: string;
    };
};

export default function RoutersIndex({ routers, stats, filters, createUrl }: Props) {
    const [localFilters, setLocalFilters] = useState({
        q: filters.q || '',
        status: filters.status || 'all',
        connection_type: filters.connection_type || 'all',
        hotspot_files_status: filters.hotspot_files_status || 'all',
    });

    const cleanFilters = () => Object.fromEntries(
        Object.entries(localFilters).filter(([, value]) => value && value !== 'all')
    );

    const applyFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('wifi-billing.routers.index'), cleanFilters(), {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        router.get(route('wifi-billing.routers.index'), {}, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Routers' },
            ]}
            pageTitle="MikroTik Routers"
        >
            <Head title="MikroTik Routers" />

            <div className="space-y-5">
                <PageHeader
                    title="MikroTik Routers"
                    description="Review linked routers, heartbeat status, RouterOS details, and hotspot file readiness."
                    actions={
                        <>
                            <Button variant="outline" asChild>
                                <Link href={route('wifi-billing.live-sessions.index')}>
                                    <MonitorUp className="h-4 w-4" />
                                    Live Sessions
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href={createUrl || route('wifi-billing.routers.create')} preserveScroll={false}>
                                    <Plus className="h-4 w-4" />
                                    Add Router
                                </Link>
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <MetricCard title="Total" value={stats.total} note="Routers registered" icon={RouterIcon} />
                    <MetricCard title="Active" value={stats.active} note="Enabled routers" icon={ShieldCheck} tone="success" />
                    <MetricCard title="Online" value={stats.online} note="Heartbeat under 60s" icon={MonitorUp} tone="success" />
                    <MetricCard title="Offline" value={stats.offline} note="Heartbeat stale" icon={AlertTriangle} tone={stats.offline > 0 ? 'danger' : 'default'} />
                    <MetricCard title="Waiting" value={stats.waiting_for_link} note="No heartbeat yet" icon={RouterIcon} tone={stats.waiting_for_link > 0 ? 'warning' : 'default'} />
                    <MetricCard title="Hotspot Missing" value={stats.hotspot_files_missing} note="File sync needed" icon={Wifi} tone={stats.hotspot_files_missing > 0 ? 'danger' : 'default'} />
                </div>

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent className="p-4">
                        <form onSubmit={applyFilters} className="grid gap-3 lg:grid-cols-6">
                            <div className="relative lg:col-span-2">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={localFilters.q}
                                    onChange={(event) => setLocalFilters({ ...localFilters, q: event.target.value })}
                                    placeholder="Search router, host, RouterOS"
                                    className="pl-9"
                                />
                            </div>

                            <Select value={localFilters.status} onValueChange={(value) => setLocalFilters({ ...localFilters, status: value })}>
                                <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="offline">Offline</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select value={localFilters.connection_type} onValueChange={(value) => setLocalFilters({ ...localFilters, connection_type: value })}>
                                <SelectTrigger><SelectValue placeholder="Connection" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All connection</SelectItem>
                                    <SelectItem value="agent">Agent</SelectItem>
                                    <SelectItem value="api">API</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select value={localFilters.hotspot_files_status} onValueChange={(value) => setLocalFilters({ ...localFilters, hotspot_files_status: value })}>
                                <SelectTrigger><SelectValue placeholder="Hotspot files" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All hotspot files</SelectItem>
                                    <SelectItem value="present">Present</SelectItem>
                                    <SelectItem value="missing">Missing</SelectItem>
                                    <SelectItem value="provision_pending">Provision pending</SelectItem>
                                    <SelectItem value="sync_pending">Sync pending</SelectItem>
                                    <SelectItem value="unknown">Unknown</SelectItem>
                                </SelectContent>
                            </Select>

                            <div className="flex gap-2 lg:justify-end">
                                <Button type="button" variant="outline" onClick={resetFilters}>Reset</Button>
                                <Button type="submit">Apply</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {routers.data.length === 0 ? (
                            <div className="p-4">
                                <EmptyState
                                    title="No routers found"
                                    description="Adjust filters or add a MikroTik router."
                                    action={
                                        <Button asChild>
                                            <Link href={createUrl || route('wifi-billing.routers.create')} preserveScroll={false}>
                                                <Plus className="h-4 w-4" />
                                                Add Router
                                            </Link>
                                        </Button>
                                    }
                                />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Router</TableHead>
                                        <TableHead>RouterOS</TableHead>
                                        <TableHead>Resources</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Services</TableHead>
                                        <TableHead>Last Seen</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {routers.data.map((routerRow) => (
                                        <TableRow key={routerRow.id}>
                                            <TableCell>
                                                <div className="font-medium">{routerRow.name}</div>
                                                <div className="text-xs text-muted-foreground">{routerRow.host || 'Pending link'}{routerRow.api_port ? `:${routerRow.api_port}` : ''}</div>
                                                <div className="text-xs text-muted-foreground">{routerRow.isp?.name || '-'}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div>{routerRow.routeros_version || '-'}</div>
                                                <div className="text-xs text-muted-foreground">{routerRow.board_name || routerRow.architecture || '-'}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div>{routerRow.cpu_load == null ? 'CPU unknown' : `${routerRow.cpu_load}% CPU`}</div>
                                                <div className="text-xs text-muted-foreground">{memoryText(routerRow.memory_free, routerRow.memory_total)}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <StatusBadge value={routerRow.status} />
                                                    <StatusBadge value={routerRow.live_status} />
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <StatusBadge value={routerRow.hotspot_files_status || 'unknown'} />
                                                    <StatusBadge value={routerRow.sync_status || 'pending'} />
                                                </div>
                                            </TableCell>
                                            <TableCell>{routerRow.last_seen_at || 'No heartbeat yet'}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button variant="outline" size="sm" asChild><Link href={routerRow.live_url}>Live</Link></Button>
                                                    <Button variant="outline" size="sm" asChild><Link href={routerRow.setup_url}>Setup</Link></Button>
                                                    <Button variant="outline" size="sm" asChild><a href={routerRow.edit_url}>Edit</a></Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {routers.last_page > 1 && (
                        <CardContent className="border-t px-4 py-0">
                            <Pagination data={routers} routeName="wifi-billing.routers.index" filters={cleanFilters()} />
                        </CardContent>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
