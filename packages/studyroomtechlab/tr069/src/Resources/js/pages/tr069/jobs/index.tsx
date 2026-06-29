import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { EmptyState, PageHeader } from '@/pages/wifi-billing/components';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { DeviceLabel, DeviceSummary, IspOption, Option, Paginated, PlatformIspSelect, Tr069Status, dateText, titleCase } from '../components';

type Job = {
    id: number;
    job_type: string;
    status: string;
    result_message?: string | null;
    queued_at?: string | null;
    started_at?: string | null;
    completed_at?: string | null;
    failed_at?: string | null;
    device?: DeviceSummary | null;
    profile?: { id: number; name: string } | null;
    isp?: { id: number; name: string } | null;
    created_by?: string | null;
};

type Props = {
    isp?: { id: number; name: string } | null;
    isPlatform: boolean;
    isps: IspOption[];
    filters: { status?: string; job_type?: string; isp_id?: number | null };
    jobs: Paginated<Job>;
    statusOptions: Option[];
    jobTypeOptions: Option[];
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function Tr069JobsIndex({ isp, isPlatform, isps, filters, jobs, statusOptions, jobTypeOptions }: Props) {
    const currentFilters = () => ({
        ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
        ...((filters.status && filters.status !== 'all') ? { status: filters.status } : {}),
        ...((filters.job_type && filters.job_type !== 'all') ? { job_type: filters.job_type } : {}),
    });

    const visit = (params: Record<string, unknown>) => {
        router.get(route('tr069.jobs.index'), {
            ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
            status: filters.status || 'all',
            job_type: filters.job_type || 'all',
            ...params,
        }, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'TR069', url: route('tr069.index') },
                { label: 'Configuration Jobs' },
            ]}
            pageTitle="Configuration Jobs"
        >
            <Head title="TR069 Configuration Jobs" />

            <div className="space-y-5">
                <PageHeader
                    title="Configuration Jobs"
                    description={isp ? `Queued and completed CPE actions for ${isp.name}.` : 'Queued and completed CPE actions across ISP accounts.'}
                    actions={
                        <>
                            <PlatformIspSelect isPlatform={isPlatform} isps={isps} value={filters.isp_id} routeName="tr069.jobs.index" extraFilters={currentFilters()} />
                            <Button asChild><Link href={route('tr069.jobs.create', filters.isp_id ? { isp_id: filters.isp_id } : {})}><Plus className="h-4 w-4" />New Job</Link></Button>
                        </>
                    }
                />

                <Card>
                    <CardHeader className="border-b py-4"><CardTitle className="text-base">Filters</CardTitle></CardHeader>
                    <CardContent className="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Select value={filters.status || 'all'} onValueChange={(value) => visit({ status: value, page: 1 })}>
                            <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
                            <SelectContent>{statusOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent>
                        </Select>
                        <Select value={filters.job_type || 'all'} onValueChange={(value) => visit({ job_type: value, page: 1 })}>
                            <SelectTrigger><SelectValue placeholder="Type" /></SelectTrigger>
                            <SelectContent>{jobTypeOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent>
                        </Select>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {jobs.data.length === 0 ? (
                            <div className="p-4"><EmptyState title="No configuration jobs" description="Create provision, reboot, WiFi, firmware, parameter, or diagnostics jobs for CPE devices." /></div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Device</TableHead>
                                        {isPlatform && <TableHead>ISP</TableHead>}
                                        <TableHead>Type</TableHead>
                                        <TableHead>Profile</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Timeline</TableHead>
                                        <TableHead>Result</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {jobs.data.map((job) => (
                                        <TableRow key={job.id}>
                                            <TableCell><DeviceLabel device={job.device} /></TableCell>
                                            {isPlatform && <TableCell>{job.isp?.name || 'Not set'}</TableCell>}
                                            <TableCell>{titleCase(job.job_type)}</TableCell>
                                            <TableCell>{job.profile?.name || 'No profile'}</TableCell>
                                            <TableCell><Tr069Status value={job.status} /></TableCell>
                                            <TableCell className="text-xs text-muted-foreground">
                                                <div>Queued: {dateText(job.queued_at)}</div>
                                                <div>Done: {dateText(job.completed_at || job.failed_at)}</div>
                                            </TableCell>
                                            <TableCell className="max-w-xs text-sm text-muted-foreground">{job.result_message || 'Pending'}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {jobs.last_page > 1 && <CardContent className="border-t px-4 py-0"><Pagination data={jobs} routeName="tr069.jobs.index" filters={currentFilters()} /></CardContent>}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
