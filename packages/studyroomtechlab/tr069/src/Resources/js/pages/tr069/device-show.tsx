import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { ArrowLeft, Play, RefreshCw, UploadCloud } from 'lucide-react';
import { useState } from 'react';
import { Tr069Status, dateText, safeJson, titleCase } from './components';

type Device = {
    id: number;
    serial_number: string;
    oui?: string | null;
    product_class?: string | null;
    manufacturer?: string | null;
    model?: string | null;
    firmware_version?: string | null;
    hardware_version?: string | null;
    ip_address?: string | null;
    mac_address?: string | null;
    connection_request_url?: string | null;
    connection_username?: string | null;
    connection_password?: string | null;
    last_inform_at?: string | null;
    last_seen_ip?: string | null;
    status: string;
    notes?: string | null;
    metadata?: unknown;
    customer?: { id: number; name?: string | null; phone?: string | null; username?: string | null } | null;
    isp?: { id: number; name: string } | null;
    provision_url: string;
    reboot_url: string;
    push_profile_url: string;
};

type Props = {
    device: Device;
    profileOptions: { id: number; name: string }[];
    recentJobs: { id: number; job_type: string; status: string; result_message?: string | null; queued_at?: string | null; completed_at?: string | null; profile?: { id: number; name: string } | null }[];
    recentLogs: { id: number; event_type: string; level: string; message: string; created_at?: string | null }[];
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function Tr069DeviceShow({ device, profileOptions, recentJobs, recentLogs }: Props) {
    const [profileId, setProfileId] = useState(profileOptions[0]?.id ? String(profileOptions[0].id) : '');
    const postAction = (url: string, payload: Record<string, string | number | boolean | null | undefined> = {}) => router.post(url, payload, { preserveScroll: true });

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'TR069', url: route('tr069.index') },
                { label: 'CPE Devices', url: route('tr069.devices.index') },
                { label: device.serial_number },
            ]}
            pageTitle="CPE Details"
        >
            <Head title={`CPE ${device.serial_number}`} />

            <div className="space-y-5">
                <PageHeader
                    title={device.serial_number}
                    description={[device.manufacturer, device.model, device.firmware_version].filter(Boolean).join(' - ') || 'TR069 CPE device'}
                    actions={
                        <>
                            <Button variant="outline" asChild><Link href={route('tr069.devices.index')}><ArrowLeft className="h-4 w-4" />Back</Link></Button>
                            <Button variant="outline" onClick={() => postAction(device.provision_url)}><Play className="h-4 w-4" />Provision</Button>
                            <Button variant="outline" onClick={() => postAction(device.reboot_url)}><RefreshCw className="h-4 w-4" />Reboot</Button>
                            <Select value={profileId} onValueChange={setProfileId}>
                                <SelectTrigger className="w-44"><SelectValue placeholder="Profile" /></SelectTrigger>
                                <SelectContent>
                                    {profileOptions.map((profile) => <SelectItem key={profile.id} value={String(profile.id)}>{profile.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            <Button disabled={!profileId} onClick={() => postAction(device.push_profile_url, { profile_id: profileId })}><UploadCloud className="h-4 w-4" />Push Profile</Button>
                        </>
                    }
                />

                <div className="grid gap-5 xl:grid-cols-3">
                    <Card>
                        <CardHeader><CardTitle className="text-base">Device Identity</CardTitle></CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <Info label="Status" value={<Tr069Status value={device.status} />} />
                            <Info label="OUI" value={device.oui || 'Not set'} />
                            <Info label="Product Class" value={device.product_class || 'Not set'} />
                            <Info label="Hardware" value={device.hardware_version || 'Not set'} />
                            <Info label="Firmware" value={device.firmware_version || 'Not set'} />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle className="text-base">Customer Assignment</CardTitle></CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <Info label="Customer" value={device.customer?.name || 'Unassigned'} />
                            <Info label="Phone" value={device.customer?.phone || 'Not set'} />
                            <Info label="Username" value={device.customer?.username || 'Not set'} />
                            <Info label="ISP" value={device.isp?.name || 'Not set'} />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle className="text-base">Network Info</CardTitle></CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <Info label="IP Address" value={device.ip_address || 'Not set'} />
                            <Info label="MAC Address" value={device.mac_address || 'Not set'} />
                            <Info label="Last Inform" value={dateText(device.last_inform_at)} />
                            <Info label="Last Seen IP" value={device.last_seen_ip || 'Not set'} />
                            <Info label="Connection Password" value={device.connection_password || 'Not set'} />
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader><CardTitle className="text-base">Recent Jobs</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        {recentJobs.length === 0 ? <div className="p-4"><EmptyState title="No recent jobs" description="Queued CPE jobs will appear here." /></div> : (
                            <Table>
                                <TableHeader><TableRow><TableHead>Type</TableHead><TableHead>Profile</TableHead><TableHead>Status</TableHead><TableHead>Queued</TableHead><TableHead>Result</TableHead></TableRow></TableHeader>
                                <TableBody>
                                    {recentJobs.map((job) => (
                                        <TableRow key={job.id}>
                                            <TableCell>{titleCase(job.job_type)}</TableCell>
                                            <TableCell>{job.profile?.name || 'No profile'}</TableCell>
                                            <TableCell><Tr069Status value={job.status} /></TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{dateText(job.queued_at)}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{job.result_message || 'Pending'}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Recent Logs</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        {recentLogs.length === 0 ? <div className="p-4"><EmptyState title="No device logs" description="CPE activity events will appear here." /></div> : (
                            <Table>
                                <TableHeader><TableRow><TableHead>Event</TableHead><TableHead>Level</TableHead><TableHead>Message</TableHead><TableHead>Date</TableHead></TableRow></TableHeader>
                                <TableBody>
                                    {recentLogs.map((log) => (
                                        <TableRow key={log.id}>
                                            <TableCell>{titleCase(log.event_type)}</TableCell>
                                            <TableCell><Tr069Status value={log.level} /></TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{log.message}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{dateText(log.created_at)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {Boolean(device.metadata) && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">Last Inform Details</CardTitle></CardHeader>
                        <CardContent><pre className="overflow-auto rounded-md bg-muted p-3 text-xs">{safeJson(device.metadata)}</pre></CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

function Info({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-3 border-b pb-2 last:border-0 last:pb-0">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}
