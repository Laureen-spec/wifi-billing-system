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
import { Activity, AlertTriangle, CheckCircle2, Clock3, RadioTower, Router, ServerCog } from 'lucide-react';
import { DeviceLabel, DeviceSummary, IspOption, PlatformIspSelect, Tr069Status, dateText, numberText, titleCase } from './components';

type Inform = {
    id: number;
    message: string;
    level: string;
    created_at?: string | null;
    device?: DeviceSummary | null;
};

type Job = {
    id: number;
    job_type: string;
    status: string;
    queued_at?: string | null;
    device?: DeviceSummary | null;
    profile?: { id: number; name: string } | null;
};

type Props = {
    isp?: { id: number; name: string } | null;
    isPlatform: boolean;
    isps: IspOption[];
    filters: { isp_id?: number | null };
    stats: {
        total_cpes: number;
        online_cpes: number;
        offline_cpes: number;
        pending_jobs: number;
        failed_jobs: number;
    };
    statusSummary: { status: string; label: string; count: number }[];
    recentInforms: Inform[];
    recentJobs: Job[];
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function Tr069Dashboard({ isp, isPlatform, isps, filters, stats, statusSummary, recentInforms, recentJobs }: Props) {
    const scope = filters.isp_id ? { isp_id: filters.isp_id } : {};

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'TR069' },
            ]}
            pageTitle="TR069"
        >
            <Head title="TR069 ACS Dashboard" />

            <div className="space-y-5">
                <PageHeader
                    title="TR069 ACS Dashboard"
                    description={isp ? `CPE provisioning and monitoring for ${isp.name}.` : 'CPE provisioning and monitoring across ISP accounts.'}
                    actions={
                        <>
                            <PlatformIspSelect isPlatform={isPlatform} isps={isps} value={filters.isp_id} routeName="tr069.index" />
                            <Link href={route('tr069.settings', scope)} className="inline-flex h-10 items-center gap-2 rounded-md border px-4 text-sm font-medium hover:bg-accent">
                                <ServerCog className="h-4 w-4" />
                                Settings
                            </Link>
                        </>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <MetricCard title="Total CPEs" value={numberText(stats.total_cpes)} note="Registered devices" icon={Router} />
                    <MetricCard title="Online CPEs" value={numberText(stats.online_cpes)} note="Recently informed" icon={RadioTower} tone="success" />
                    <MetricCard title="Offline CPEs" value={numberText(stats.offline_cpes)} note="Marked offline" icon={AlertTriangle} tone="warning" />
                    <MetricCard title="Pending Jobs" value={numberText(stats.pending_jobs)} note="Queued actions" icon={Clock3} />
                    <MetricCard title="Failed Jobs" value={numberText(stats.failed_jobs)} note="Needs attention" icon={AlertTriangle} tone="danger" />
                </div>

                <div className="grid gap-5 xl:grid-cols-3">
                    <Card>
                        <CardHeader className="border-b py-4">
                            <CardTitle className="text-base">Device Status Summary</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 p-4">
                            {statusSummary.map((item) => (
                                <div key={item.status} className="flex items-center justify-between rounded-lg border p-3">
                                    <Tr069Status value={item.status} />
                                    <span className="font-semibold">{numberText(item.count)}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="xl:col-span-2">
                        <CardHeader className="border-b py-4">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Activity className="h-4 w-4" />
                                Recent Informs
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {recentInforms.length === 0 ? (
                                <div className="p-4">
                                    <EmptyState title="No CPE informs yet" description="Inform events will appear after CPE devices contact the ACS endpoint." />
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Device</TableHead>
                                            <TableHead>Level</TableHead>
                                            <TableHead>Message</TableHead>
                                            <TableHead>Date</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recentInforms.map((inform) => (
                                            <TableRow key={inform.id}>
                                                <TableCell><DeviceLabel device={inform.device} /></TableCell>
                                                <TableCell><Tr069Status value={inform.level} /></TableCell>
                                                <TableCell className="text-sm text-muted-foreground">{inform.message}</TableCell>
                                                <TableCell className="text-sm text-muted-foreground">{dateText(inform.created_at)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <CheckCircle2 className="h-4 w-4" />
                            Recent Jobs
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {recentJobs.length === 0 ? (
                            <div className="p-4">
                                <EmptyState title="No configuration jobs" description="Provisioning, reboot, WiFi, firmware, and diagnostics jobs will appear here." />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Device</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Profile</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Queued</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentJobs.map((job) => (
                                        <TableRow key={job.id}>
                                            <TableCell><DeviceLabel device={job.device} /></TableCell>
                                            <TableCell>{titleCase(job.job_type)}</TableCell>
                                            <TableCell>{job.profile?.name || 'No profile'}</TableCell>
                                            <TableCell><Tr069Status value={job.status} /></TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{dateText(job.queued_at)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
