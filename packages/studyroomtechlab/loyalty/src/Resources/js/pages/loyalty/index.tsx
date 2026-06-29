import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { EmptyState, MetricCard, PageHeader } from '@/pages/wifi-billing/components';
import { Head, Link } from '@inertiajs/react';
import { Activity, Gift, Settings, Star, Trophy, Users } from 'lucide-react';
import {
    CustomerLabel,
    CustomerSummary,
    IspOption,
    LoyaltyStatus,
    PlatformIspSelect,
    points,
    titleCase,
} from './components';

type ActivityRow = {
    id: number;
    type: string;
    points: number;
    description?: string | null;
    customer?: CustomerSummary | null;
    created_at?: string | null;
};

type TopCustomer = {
    id: number;
    current_points: number;
    lifetime_points: number;
    redeemed_points: number;
    customer?: CustomerSummary | null;
};

type Props = {
    isp?: { id: number; name: string } | null;
    isPlatform: boolean;
    isps: IspOption[];
    filters: { isp_id?: number | null };
    stats: {
        total_points_issued: number;
        active_customers: number;
        vouchers_generated: number;
        redeemed_vouchers: number;
    };
    recentActivity: ActivityRow[];
    topCustomers: TopCustomer[];
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function LoyaltyDashboard({ isp, isPlatform, isps, filters, stats, recentActivity, topCustomers }: Props) {
    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Loyalty' },
            ]}
            pageTitle="Loyalty"
        >
            <Head title="Loyalty" />

            <div className="space-y-5">
                <PageHeader
                    title="Loyalty"
                    description={isp ? `Rewards workspace for ${isp.name}.` : 'Rewards workspace across ISP accounts.'}
                    actions={
                        <>
                            <PlatformIspSelect
                                isPlatform={isPlatform}
                                isps={isps}
                                value={filters.isp_id}
                                routeName="loyalty.index"
                            />
                            <Link
                                href={route('loyalty.settings', filters.isp_id ? { isp_id: filters.isp_id } : {})}
                                className="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground"
                            >
                                <Settings className="h-4 w-4" />
                                Settings
                            </Link>
                        </>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard title="Points Issued" value={points(stats.total_points_issued)} note="Earned and adjusted points" icon={Star} tone="success" />
                    <MetricCard title="Active Customers" value={points(stats.active_customers)} note="Customers with a balance" icon={Users} />
                    <MetricCard title="Vouchers Generated" value={points(stats.vouchers_generated)} note="All loyalty vouchers" icon={Gift} />
                    <MetricCard title="Redeemed Vouchers" value={points(stats.redeemed_vouchers)} note="Marked as redeemed" icon={Trophy} tone="warning" />
                </div>

                <div className="grid gap-5 xl:grid-cols-2">
                    <Card>
                        <CardHeader className="border-b py-4">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Activity className="h-4 w-4" />
                                Recent Activity
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {recentActivity.length === 0 ? (
                                <div className="p-4">
                                    <EmptyState title="No loyalty activity" description="Point transactions will appear after awards or redemptions." />
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Customer</TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead className="text-right">Points</TableHead>
                                            <TableHead>Date</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recentActivity.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell><CustomerLabel customer={row.customer} /></TableCell>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <LoyaltyStatus value={row.type} />
                                                        {row.description && <div className="line-clamp-1 text-xs text-muted-foreground">{row.description}</div>}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right font-semibold">{points(row.points)}</TableCell>
                                                <TableCell className="text-sm text-muted-foreground">{row.created_at || 'Not set'}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="border-b py-4">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Trophy className="h-4 w-4" />
                                Top Loyal Customers
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {topCustomers.length === 0 ? (
                                <div className="p-4">
                                    <EmptyState title="No point balances" description="Customers with earned points will be ranked here." />
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Customer</TableHead>
                                            <TableHead className="text-right">Current</TableHead>
                                            <TableHead className="text-right">Lifetime</TableHead>
                                            <TableHead className="text-right">Redeemed</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {topCustomers.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell><CustomerLabel customer={row.customer} /></TableCell>
                                                <TableCell className="text-right font-semibold">{points(row.current_points)}</TableCell>
                                                <TableCell className="text-right">{points(row.lifetime_points)}</TableCell>
                                                <TableCell className="text-right">{points(row.redeemed_points)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-3 md:grid-cols-5">
                    {[
                        ['Dashboard', 'loyalty.index'],
                        ['Customer Points', 'loyalty.customers'],
                        ['Reward Rules', 'loyalty.rules.index'],
                        ['Vouchers', 'loyalty.vouchers.index'],
                        ['Activity Logs', 'loyalty.logs.index'],
                    ].map(([label, routeName]) => (
                        <Link
                            key={routeName}
                            href={route(routeName, filters.isp_id ? { isp_id: filters.isp_id } : {})}
                            className="rounded-lg border bg-card px-4 py-3 text-sm font-medium shadow-sm hover:bg-muted"
                        >
                            {titleCase(label)}
                        </Link>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
