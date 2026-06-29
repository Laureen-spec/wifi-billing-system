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
import { EmptyState, PageHeader } from '@/pages/wifi-billing/components';
import { Head, router } from '@inertiajs/react';
import { Filter } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { DeviceLabel, DeviceSummary, IspOption, Option, Paginated, PlatformIspSelect, Tr069Status, dateText, titleCase } from './components';

type LogRow = {
    id: number;
    event_type: string;
    level: string;
    message: string;
    created_at?: string | null;
    device?: DeviceSummary | null;
    isp?: { id: number; name: string } | null;
};

type Props = {
    isp?: { id: number; name: string } | null;
    isPlatform: boolean;
    isps: IspOption[];
    filters: { device_id?: number | null; event_type?: string; level?: string; from?: string; to?: string; isp_id?: number | null };
    logs: Paginated<LogRow>;
    deviceOptions: { id: number; label: string }[];
    eventOptions: Option[];
    levelOptions: Option[];
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function Tr069Logs({ isp, isPlatform, isps, filters, logs, deviceOptions, eventOptions, levelOptions }: Props) {
    const [local, setLocal] = useState({
        device_id: filters.device_id ? String(filters.device_id) : 'all',
        event_type: filters.event_type || 'all',
        level: filters.level || 'all',
        from: filters.from || '',
        to: filters.to || '',
    });

    const clean = () => ({
        ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
        ...(local.device_id !== 'all' ? { device_id: local.device_id } : {}),
        ...(local.event_type !== 'all' ? { event_type: local.event_type } : {}),
        ...(local.level !== 'all' ? { level: local.level } : {}),
        ...(local.from ? { from: local.from } : {}),
        ...(local.to ? { to: local.to } : {}),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('tr069.logs.index'), clean(), { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: 'WiFi Billing', url: route('wifi-billing.dashboard') }, { label: 'TR069', url: route('tr069.index') }, { label: 'Device Logs' }]} pageTitle="Device Logs">
            <Head title="TR069 Device Logs" />
            <div className="space-y-5">
                <PageHeader title="Device Logs" description={isp ? `CPE activity logs for ${isp.name}.` : 'CPE activity logs across ISP accounts.'} actions={<PlatformIspSelect isPlatform={isPlatform} isps={isps} value={filters.isp_id} routeName="tr069.logs.index" extraFilters={clean()} />} />

                <Card>
                    <CardHeader className="border-b py-4"><CardTitle className="flex items-center gap-2 text-base"><Filter className="h-4 w-4" />Filters</CardTitle></CardHeader>
                    <CardContent className="p-4">
                        <form onSubmit={submit} className="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                            <Select value={local.device_id} onValueChange={(value) => setLocal({ ...local, device_id: value })}>
                                <SelectTrigger><SelectValue placeholder="Device" /></SelectTrigger>
                                <SelectContent><SelectItem value="all">All devices</SelectItem>{deviceOptions.map((device) => <SelectItem key={device.id} value={String(device.id)}>{device.label}</SelectItem>)}</SelectContent>
                            </Select>
                            <Select value={local.event_type} onValueChange={(value) => setLocal({ ...local, event_type: value })}>
                                <SelectTrigger><SelectValue placeholder="Event" /></SelectTrigger>
                                <SelectContent>{eventOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent>
                            </Select>
                            <Select value={local.level} onValueChange={(value) => setLocal({ ...local, level: value })}>
                                <SelectTrigger><SelectValue placeholder="Level" /></SelectTrigger>
                                <SelectContent>{levelOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent>
                            </Select>
                            <Input type="date" value={local.from} onChange={(event) => setLocal({ ...local, from: event.target.value })} />
                            <Input type="date" value={local.to} onChange={(event) => setLocal({ ...local, to: event.target.value })} />
                            <Button type="submit">Apply</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {logs.data.length === 0 ? <div className="p-4"><EmptyState title="No device logs" description="Inform, provision, reboot, firmware, diagnostics, error, and status events will appear here." /></div> : (
                            <Table>
                                <TableHeader><TableRow><TableHead>Device</TableHead>{isPlatform && <TableHead>ISP</TableHead>}<TableHead>Event</TableHead><TableHead>Level</TableHead><TableHead>Message</TableHead><TableHead>Date</TableHead></TableRow></TableHeader>
                                <TableBody>
                                    {logs.data.map((log) => (
                                        <TableRow key={log.id}>
                                            <TableCell><DeviceLabel device={log.device} /></TableCell>
                                            {isPlatform && <TableCell>{log.isp?.name || 'Not set'}</TableCell>}
                                            <TableCell>{titleCase(log.event_type)}</TableCell>
                                            <TableCell><Tr069Status value={log.level} /></TableCell>
                                            <TableCell className="max-w-md text-sm text-muted-foreground">{log.message}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{dateText(log.created_at)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {logs.last_page > 1 && <CardContent className="border-t px-4 py-0"><Pagination data={logs} routeName="tr069.logs.index" filters={clean()} /></CardContent>}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
