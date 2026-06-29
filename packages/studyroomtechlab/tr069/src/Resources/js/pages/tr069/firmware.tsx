import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Textarea } from '@/components/ui/textarea';
import { EmptyState, PageHeader } from '@/pages/wifi-billing/components';
import { Head, router, useForm } from '@inertiajs/react';
import { HardDriveUpload, Plus, Search } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { FilterChip, IspOption, Option, Paginated, PlatformIspSelect, Tr069Status } from './components';

type Firmware = {
    id: number;
    name: string;
    version: string;
    manufacturer?: string | null;
    model?: string | null;
    file_url?: string | null;
    checksum?: string | null;
    status: string;
    notes?: string | null;
    isp?: { id: number; name: string } | null;
    queue_url: string;
};

type Props = {
    isp?: { id: number; name: string } | null;
    isPlatform: boolean;
    isps: IspOption[];
    filters: { q?: string; status?: string; isp_id?: number | null };
    firmware: Paginated<Firmware>;
    statusOptions: Option[];
    deviceOptions: { id: number; label: string }[];
    storeUrl: string;
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function Tr069Firmware({ isp, isPlatform, isps, filters, firmware, statusOptions, deviceOptions, storeUrl }: Props) {
    const [search, setSearch] = useState(filters.q || '');
    const [queueTarget, setQueueTarget] = useState<Firmware | null>(null);
    const firmwareForm = useForm({
        isp_id: String(filters.isp_id || isps[0]?.id || ''),
        name: '',
        version: '',
        manufacturer: '',
        model: '',
        file_url: '',
        checksum: '',
        status: 'draft',
        notes: '',
    });
    const queueForm = useForm({ device_id: String(deviceOptions[0]?.id || '') });

    const params = () => ({
        ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
        ...(search ? { q: search } : {}),
        ...((filters.status && filters.status !== 'all') ? { status: filters.status } : {}),
    });

    const visit = (next: Record<string, unknown>) => {
        router.get(route('tr069.firmware.index'), {
            ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
            ...(search ? { q: search } : {}),
            status: filters.status || 'all',
            ...next,
        }, { preserveState: true, replace: true });
    };

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        visit({ q: search, page: 1 });
    };

    const submitFirmware = (event: FormEvent) => {
        event.preventDefault();
        firmwareForm.post(storeUrl, { preserveScroll: true, onSuccess: () => firmwareForm.reset() });
    };

    const submitQueue = (event: FormEvent) => {
        event.preventDefault();
        if (!queueTarget) {
            return;
        }
        queueForm.post(queueTarget.queue_url, { preserveScroll: true, onSuccess: () => setQueueTarget(null) });
    };

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: 'WiFi Billing', url: route('wifi-billing.dashboard') }, { label: 'TR069', url: route('tr069.index') }, { label: 'Firmware Updates' }]} pageTitle="Firmware Updates">
            <Head title="TR069 Firmware Updates" />

            <Dialog open={Boolean(queueTarget)} onOpenChange={(open) => !open && setQueueTarget(null)}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Queue firmware update</DialogTitle></DialogHeader>
                    <form onSubmit={submitQueue} className="space-y-4">
                        <div className="rounded-lg border bg-muted/30 p-3 text-sm">
                            <div className="font-medium">{queueTarget?.name}</div>
                            <div className="text-muted-foreground">Version {queueTarget?.version}</div>
                        </div>
                        <div>
                            <Label>CPE Device</Label>
                            <Select value={queueForm.data.device_id} onValueChange={(value) => queueForm.setData('device_id', value)}>
                                <SelectTrigger className="mt-1"><SelectValue placeholder="Select device" /></SelectTrigger>
                                <SelectContent>{deviceOptions.map((device) => <SelectItem key={device.id} value={String(device.id)}>{device.label}</SelectItem>)}</SelectContent>
                            </Select>
                            {queueForm.errors.device_id && <p className="mt-1 text-xs text-destructive">{queueForm.errors.device_id}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setQueueTarget(null)}>Cancel</Button>
                            <Button type="submit" disabled={queueForm.processing || !queueForm.data.device_id}>Queue Update</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div className="space-y-5">
                <PageHeader title="Firmware Updates" description={isp ? `Firmware metadata for ${isp.name}.` : 'Firmware metadata across ISP accounts.'} actions={<PlatformIspSelect isPlatform={isPlatform} isps={isps} value={filters.isp_id} routeName="tr069.firmware.index" extraFilters={params()} />} />

                <Card>
                    <CardHeader className="border-b py-4"><CardTitle className="flex items-center gap-2 text-base"><Plus className="h-4 w-4" />Register Firmware Metadata</CardTitle></CardHeader>
                    <CardContent className="p-4">
                        <form onSubmit={submitFirmware} className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            {isPlatform && (
                                <div>
                                    <Label>ISP</Label>
                                    <Select value={firmwareForm.data.isp_id} onValueChange={(value) => firmwareForm.setData('isp_id', value)}>
                                        <SelectTrigger className="mt-1"><SelectValue placeholder="Select ISP" /></SelectTrigger>
                                        <SelectContent>{isps.map((item) => <SelectItem key={item.id} value={String(item.id)}>{item.name}</SelectItem>)}</SelectContent>
                                    </Select>
                                </div>
                            )}
                            <Field label="Name" value={firmwareForm.data.name} onChange={(value) => firmwareForm.setData('name', value)} error={firmwareForm.errors.name} />
                            <Field label="Version" value={firmwareForm.data.version} onChange={(value) => firmwareForm.setData('version', value)} error={firmwareForm.errors.version} />
                            <Field label="Manufacturer" value={firmwareForm.data.manufacturer} onChange={(value) => firmwareForm.setData('manufacturer', value)} />
                            <Field label="Model" value={firmwareForm.data.model} onChange={(value) => firmwareForm.setData('model', value)} />
                            <Field label="File URL" value={firmwareForm.data.file_url} onChange={(value) => firmwareForm.setData('file_url', value)} error={firmwareForm.errors.file_url} />
                            <Field label="Checksum" value={firmwareForm.data.checksum} onChange={(value) => firmwareForm.setData('checksum', value)} />
                            <div>
                                <Label>Status</Label>
                                <Select value={firmwareForm.data.status} onValueChange={(value) => firmwareForm.setData('status', value)}>
                                    <SelectTrigger className="mt-1"><SelectValue placeholder="Status" /></SelectTrigger>
                                    <SelectContent>{statusOptions.filter((option) => option.value !== 'all').map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                            <div className="md:col-span-2 xl:col-span-4">
                                <Label>Notes</Label>
                                <Textarea className="mt-1" rows={2} value={firmwareForm.data.notes} onChange={(event) => firmwareForm.setData('notes', event.target.value)} />
                            </div>
                            <div className="md:col-span-2 xl:col-span-4">
                                <Button type="submit" disabled={firmwareForm.processing}><Plus className="h-4 w-4" />Register Firmware</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b py-4"><CardTitle className="flex items-center gap-2 text-base"><Search className="h-4 w-4" />Search</CardTitle></CardHeader>
                    <CardContent className="space-y-4 p-4">
                        <form onSubmit={submitSearch} className="flex flex-col gap-3 sm:flex-row">
                            <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search firmware name, version, manufacturer, model..." className="sm:max-w-xl" />
                            <Button type="submit">Search</Button>
                        </form>
                        <div className="flex flex-wrap gap-2">{statusOptions.map((option) => <FilterChip key={option.value} active={(filters.status || 'all') === option.value} onClick={() => visit({ status: option.value, page: 1 })}>{option.label}</FilterChip>)}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {firmware.data.length === 0 ? <div className="p-4"><EmptyState title="No firmware records" description="Register firmware metadata, then queue updates to selected CPE devices." /></div> : (
                            <Table>
                                <TableHeader><TableRow><TableHead>Firmware</TableHead>{isPlatform && <TableHead>ISP</TableHead>}<TableHead>Target</TableHead><TableHead>Status</TableHead><TableHead>File</TableHead><TableHead className="text-right">Actions</TableHead></TableRow></TableHeader>
                                <TableBody>
                                    {firmware.data.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell><div className="font-medium">{item.name}</div><div className="text-xs text-muted-foreground">Version {item.version}</div></TableCell>
                                            {isPlatform && <TableCell>{item.isp?.name || 'Not set'}</TableCell>}
                                            <TableCell>{[item.manufacturer, item.model].filter(Boolean).join(' ') || 'Any compatible CPE'}</TableCell>
                                            <TableCell><Tr069Status value={item.status} /></TableCell>
                                            <TableCell className="max-w-xs truncate text-sm text-muted-foreground">{item.file_url || 'No URL'}</TableCell>
                                            <TableCell className="text-right"><Button size="sm" variant="outline" onClick={() => setQueueTarget(item)}><HardDriveUpload className="h-4 w-4" />Queue</Button></TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {firmware.last_page > 1 && <CardContent className="border-t px-4 py-0"><Pagination data={firmware} routeName="tr069.firmware.index" filters={params()} /></CardContent>}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({ label, value, onChange, error }: { label: string; value: string; onChange: (value: string) => void; error?: string }) {
    return (
        <div>
            <Label>{label}</Label>
            <Input className="mt-1" value={value} onChange={(event) => onChange(event.target.value)} />
            {error && <p className="mt-1 text-xs text-destructive">{error}</p>}
        </div>
    );
}
