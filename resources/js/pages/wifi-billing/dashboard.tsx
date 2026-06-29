import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { EmptyState, MetricCard, PageHeader, StatusBadge, memoryText, money } from './components';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarClock,
    CreditCard,
    ExternalLink,
    MonitorUp,
    Package,
    Plus,
    Router as RouterIcon,
    Settings,
    ShieldCheck,
    UserPlus,
    Users,
} from 'lucide-react';

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

type DashboardStats = {
    total_customers: number;
    active_customers: number;
    expired_customers: number;
    today_payments: number;
    monthly_revenue: number;
    monthly_billing_value: number;
    total_packages: number;
    total_routers: number;
    online_routers: number;
    offline_routers: number;
    waiting_for_link_routers: number;
    hotspot_files_missing_count: number;
    provisioning_issues: number;
};

type CustomerRow = {
    id: number;
    name: string;
    phone?: string | null;
    package?: string | null;
    connection_status?: string | null;
    billing_status?: string | null;
};

type ExpiringRow = {
    id: number;
    name: string;
    phone?: string | null;
    package?: string | null;
    expiry?: string | null;
};

type RouterRow = {
    id: number;
    name: string;
    host?: string | null;
    board_name?: string | null;
    routeros_version?: string | null;
    cpu_load?: number | null;
    memory_free?: number | null;
    memory_total?: number | null;
    hotspot_files_status?: string | null;
    status?: string | null;
    last_seen_at?: string | null;
    action_url?: string | null;
};

type DashboardProps = {
    isp?: {
        id: number;
        name: string;
        status?: string | null;
    } | null;
    stats?: Partial<DashboardStats>;
    recentCustomers?: CustomerRow[];
    expiringSoon?: ExpiringRow[];
    routers?: RouterRow[];
};

const defaultStats: DashboardStats = {
    total_customers: 0,
    active_customers: 0,
    expired_customers: 0,
    today_payments: 0,
    monthly_revenue: 0,
    monthly_billing_value: 0,
    total_packages: 0,
    total_routers: 0,
    online_routers: 0,
    offline_routers: 0,
    waiting_for_link_routers: 0,
    hotspot_files_missing_count: 0,
    provisioning_issues: 0,
};

export default function Dashboard({
    isp = null,
    stats: incomingStats = {},
    recentCustomers = [],
    expiringSoon = [],
    routers = [],
}: DashboardProps) {
    const stats = { ...defaultStats, ...incomingStats };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: 'WiFi Billing' }]}
            pageTitle="WiFi Billing Dashboard"
        >
            <Head title="WiFi Billing Dashboard" />

            <div className="space-y-5">
                <PageHeader
                    title="WiFi Billing Dashboard"
                    description={`Monitor ${isp?.name || 'your ISP'} customers, plans, collections, router health, and hotspot readiness.`}
                    actions={
                        <>
                            <Button asChild>
                                <Link href={route('wifi-billing.customers.create')}>
                                    <UserPlus className="h-4 w-4" />
                                    Add Customer
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route('wifi-billing.settings.hotspot-template.edit')}>
                                    <Settings className="h-4 w-4" />
                                    Hotspot Template
                                </Link>
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard title="Today Payments" value={money(stats.today_payments)} note="Collected today" icon={CreditCard} />
                    <MetricCard title="Monthly Revenue" value={money(stats.monthly_revenue || stats.monthly_billing_value)} note={stats.monthly_revenue > 0 ? 'Collected this month' : 'Expected monthly billing'} icon={CreditCard} />
                    <MetricCard title="Active Customers" value={stats.active_customers} note={`${stats.total_customers} total customers`} icon={Users} tone="success" />
                    <MetricCard title="Online Routers" value={stats.online_routers} note={`${stats.total_routers} routers registered`} icon={ShieldCheck} tone="success" />
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard title="Expired Customers" value={stats.expired_customers} note="Past due or overdue" icon={CalendarClock} tone={stats.expired_customers > 0 ? 'danger' : 'default'} />
                    <MetricCard title="Internet Plans" value={stats.total_packages} note="Packages configured" icon={Package} />
                    <MetricCard title="Waiting for Link" value={stats.waiting_for_link_routers} note="Routers without heartbeat" icon={RouterIcon} tone={stats.waiting_for_link_routers > 0 ? 'warning' : 'default'} />
                    <MetricCard title="Provisioning Issues" value={stats.provisioning_issues} note="Items needing attention" icon={AlertTriangle} tone={stats.provisioning_issues > 0 ? 'danger' : 'default'} />
                </div>

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card className="xl:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between border-b py-4">
                            <div>
                                <CardTitle className="text-base">Router Live Status</CardTitle>
                                <p className="text-sm text-muted-foreground">Heartbeat, resources, and hotspot file status.</p>
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('wifi-billing.live-sessions.index')}>
                                    <MonitorUp className="h-4 w-4" />
                                    Live Sessions
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="p-0">
                            {routers.length === 0 ? (
                                <div className="p-4">
                                    <EmptyState
                                        title="No routers added"
                                        description="Linked MikroTik routers will appear here after setup."
                                        action={
                                            <Button asChild>
                                                <Link href={route('wifi-billing.routers.create')}>
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
                                            <TableHead>CPU</TableHead>
                                            <TableHead>Memory</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Hotspot Files</TableHead>
                                            <TableHead className="text-right">Action</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {routers.map((router) => (
                                            <TableRow key={router.id}>
                                                <TableCell>
                                                    <div className="font-medium">{router.name}</div>
                                                    <div className="text-xs text-muted-foreground">{router.host || 'Pending link'}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <div>{router.routeros_version || '-'}</div>
                                                    <div className="text-xs text-muted-foreground">{router.board_name || '-'}</div>
                                                </TableCell>
                                                <TableCell>{router.cpu_load == null ? 'Unknown' : `${router.cpu_load}%`}</TableCell>
                                                <TableCell>{memoryText(router.memory_free, router.memory_total)}</TableCell>
                                                <TableCell><StatusBadge value={router.status} /></TableCell>
                                                <TableCell><StatusBadge value={router.hotspot_files_status || 'unknown'} /></TableCell>
                                                <TableCell className="text-right">
                                                    {router.action_url && (
                                                        <Button variant="ghost" size="sm" asChild>
                                                            <Link href={router.action_url}>
                                                                <ExternalLink className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    )}
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
                            <CardTitle className="text-base">Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-2 p-4">
                            <Button variant="outline" className="justify-start" asChild>
                                <Link href={route('wifi-billing.customers.index')}>
                                    <Users className="h-4 w-4" />
                                    Customers
                                </Link>
                            </Button>
                            <Button variant="outline" className="justify-start" asChild>
                                <Link href={route('wifi-billing.packages.index')}>
                                    <Package className="h-4 w-4" />
                                    Plans
                                </Link>
                            </Button>
                            <Button variant="outline" className="justify-start" asChild>
                                <Link href={route('wifi-billing.routers.index')}>
                                    <RouterIcon className="h-4 w-4" />
                                    Routers
                                </Link>
                            </Button>
                            <Button variant="outline" className="justify-start" asChild>
                                <Link href={route('wifi-billing.settings.index')}>
                                    <Settings className="h-4 w-4" />
                                    Settings
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between border-b py-4">
                            <CardTitle className="text-base">Recent Customers</CardTitle>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={route('wifi-billing.customers.index')}>View all</Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="p-4">
                            {recentCustomers.length === 0 ? (
                                <EmptyState title="No customers yet" description="New ISP customers will appear here." />
                            ) : (
                                <div className="space-y-3">
                                    {recentCustomers.map((customer) => (
                                        <div key={customer.id} className="rounded-lg border p-3">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium">{customer.name}</p>
                                                    <p className="text-xs text-muted-foreground">{customer.package || 'No package'} / {customer.phone || 'No phone'}</p>
                                                </div>
                                                <StatusBadge value={customer.connection_status || customer.billing_status} />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="border-b py-4">
                            <CardTitle className="text-base">Expiring Today or Soon</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4">
                            {expiringSoon.length === 0 ? (
                                <EmptyState title="Nothing expiring" description="Customers due soon will be listed here." />
                            ) : (
                                <div className="space-y-3">
                                    {expiringSoon.map((customer) => (
                                        <div key={customer.id} className="rounded-lg border p-3">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium">{customer.name}</p>
                                                    <p className="text-xs text-muted-foreground">{customer.package || 'No package'} / {customer.phone || 'No phone'}</p>
                                                </div>
                                                <StatusBadge value={customer.expiry || 'due soon'} />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
