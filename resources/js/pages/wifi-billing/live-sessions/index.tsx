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
import { AlertTriangle, MonitorUp, Router as RouterIcon, Search, ShieldCheck, Users, Wifi } from 'lucide-react';
import { FormEvent, useState } from 'react';

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

type RouterSession = {
    id: number;
    name: string;
    host?: string | null;
    board_name?: string | null;
    routeros_version?: string | null;
    uptime?: string | null;
    cpu_load?: number | null;
    memory_free?: number | null;
    memory_total?: number | null;
    live_status?: string | null;
    hotspot_status?: string | null;
    hotspot_files_status?: string | null;
    pppoe_status?: string | null;
    last_seen_at?: string | null;
    packages_count?: number;
    live_url: string;
    setup_url: string;
};

type ActiveUser = {
    id: number;
    name: string;
    phone?: string | null;
    username?: string | null;
    access_type?: string | null;
    ip_address?: string | null;
    mac_address?: string | null;
    data_used_human?: string | null;
    connection_status?: string | null;
    billing_status?: string | null;
    next_due_date?: string | null;
    last_online_at?: string | null;
    package?: { id: number; name: string } | null;
    router?: { id: number; name: string } | null;
};

type Props = {
    activeUsers: ActiveUser[];
    routers: Paginated<RouterSession>;
    stats: {
        total: number;
        active: number;
        online: number;
        offline: number;
        waiting_for_link: number;
        hotspot_files_missing: number;
        active_users?: number;
        hotspot_users?: number;
        pppoe_users?: number;
    };
    filters: {
        q?: string;
        status?: string;
        connection_type?: string;
        hotspot_files_status?: string;
    };
};

export default function LiveSessionsIndex({ activeUsers, routers, stats, filters }: Props) {
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
        router.get(route('wifi-billing.live-sessions.index'), cleanFilters(), {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Live Sessions' },
            ]}
            pageTitle="Live Sessions"
        >
            <Head title="Live Sessions" />

            <div className="space-y-5">
                <PageHeader
                    title="Live Sessions"
                    description="Track router heartbeat freshness, resource state, and hotspot service readiness from the main dashboard."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href={route('wifi-billing.routers.index')}>
                                <RouterIcon className="h-4 w-4" />
                                Routers
                            </Link>
                        </Button>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <MetricCard title="Active Users" value={stats.active_users || activeUsers.length} note={`${stats.hotspot_users || 0} hotspot · ${stats.pppoe_users || 0} PPPoE`} icon={Users} tone="success" />
                    <MetricCard title="Online" value={stats.online} note="Fresh heartbeat" icon={ShieldCheck} tone="success" />
                    <MetricCard title="Offline" value={stats.offline} note="Stale heartbeat" icon={AlertTriangle} tone={stats.offline > 0 ? 'danger' : 'default'} />
                    <MetricCard title="Waiting" value={stats.waiting_for_link} note="No heartbeat yet" icon={MonitorUp} tone={stats.waiting_for_link > 0 ? 'warning' : 'default'} />
                    <MetricCard title="Hotspot Missing" value={stats.hotspot_files_missing} note="File sync needed" icon={Wifi} tone={stats.hotspot_files_missing > 0 ? 'danger' : 'default'} />
                </div>

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="text-base">Currently Active Users</CardTitle>
                        <p className="text-sm text-muted-foreground">Hotspot and PPPoE subscribers marked active by the billing system.</p>
                    </CardHeader>
                    <CardContent className="p-0">
                        {activeUsers.length === 0 ? (
                            <div className="p-4">
                                <EmptyState title="No active users found" description="Users will appear here when their account is active or recently reported online." />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Subscriber</TableHead>
                                        <TableHead>Device / IP</TableHead>
                                        <TableHead>Router</TableHead>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Expiry</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {activeUsers.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell>
                                                <div className="font-medium">{user.name}</div>
                                                <div className="text-xs text-muted-foreground">{user.username || user.phone || '-'}</div>
                                                <div className="text-xs text-muted-foreground">{user.access_type || 'hotspot'}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div>{user.mac_address || 'MAC not set'}</div>
                                                <div className="text-xs text-muted-foreground">{user.ip_address || 'IP not set'}</div>
                                                <div className="text-xs text-muted-foreground">{user.data_used_human || ''}</div>
                                            </TableCell>
                                            <TableCell>{user.router?.name || '-'}</TableCell>
                                            <TableCell>{user.package?.name || '-'}</TableCell>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <StatusBadge value={user.connection_status} />
                                                    <StatusBadge value={user.billing_status} />
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div>{user.next_due_date || '-'}</div>
                                                <div className="text-xs text-muted-foreground">{user.last_online_at || 'No recent online time'}</div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

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
                                </SelectContent>
                            </Select>
                            <Select value={localFilters.hotspot_files_status} onValueChange={(value) => setLocalFilters({ ...localFilters, hotspot_files_status: value })}>
                                <SelectTrigger><SelectValue placeholder="Hotspot files" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All hotspot files</SelectItem>
                                    <SelectItem value="present">Present</SelectItem>
                                    <SelectItem value="missing">Missing</SelectItem>
                                    <SelectItem value="sync_pending">Sync pending</SelectItem>
                                    <SelectItem value="unknown">Unknown</SelectItem>
                                </SelectContent>
                            </Select>
                            <div className="flex gap-2 lg:justify-end">
                                <Button type="button" variant="outline" onClick={() => router.get(route('wifi-billing.live-sessions.index'))}>Reset</Button>
                                <Button type="submit">Apply</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {routers.data.length === 0 ? (
                            <div className="p-4">
                                <EmptyState title="No live router data" description="Routers will appear here once linked and reporting heartbeat data." />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Router</TableHead>
                                        <TableHead>Live State</TableHead>
                                        <TableHead>Resources</TableHead>
                                        <TableHead>Services</TableHead>
                                        <TableHead>Plans</TableHead>
                                        <TableHead>Last Seen</TableHead>
                                        <TableHead className="text-right">Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {routers.data.map((session) => (
                                        <TableRow key={session.id}>
                                            <TableCell>
                                                <div className="font-medium">{session.name}</div>
                                                <div className="text-xs text-muted-foreground">{session.host || 'Pending link'}</div>
                                                <div className="text-xs text-muted-foreground">{session.routeros_version || session.board_name || '-'}</div>
                                            </TableCell>
                                            <TableCell><StatusBadge value={session.live_status} /></TableCell>
                                            <TableCell>
                                                <div>{session.cpu_load == null ? 'CPU unknown' : `${session.cpu_load}% CPU`}</div>
                                                <div className="text-xs text-muted-foreground">{memoryText(session.memory_free, session.memory_total)}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <StatusBadge value={session.hotspot_status || 'pending'} />
                                                    <StatusBadge value={session.hotspot_files_status || 'unknown'} />
                                                </div>
                                            </TableCell>
                                            <TableCell>{session.packages_count || 0}</TableCell>
                                            <TableCell>{session.last_seen_at || 'No heartbeat yet'}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button variant="outline" size="sm" asChild><Link href={session.live_url}>Open</Link></Button>
                                                    <Button variant="outline" size="sm" asChild><Link href={session.setup_url}>Setup</Link></Button>
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
                            <Pagination data={routers} routeName="wifi-billing.live-sessions.index" filters={cleanFilters()} />
                        </CardContent>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
