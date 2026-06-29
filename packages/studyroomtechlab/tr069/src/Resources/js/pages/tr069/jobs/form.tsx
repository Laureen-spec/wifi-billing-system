import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { FormEvent } from 'react';
import { IspOption, Option } from '../components';

type Props = {
    isPlatform: boolean;
    isps: IspOption[];
    devices: { id: number; label: string }[];
    profiles: { id: number; name: string }[];
    jobTypeOptions: Option[];
    storeUrl: string;
    defaults: {
        isp_id?: string | number;
        cpe_device_id?: string | number;
        tr069_profile_id?: string | number;
        job_type: string;
        payload_json?: string;
    };
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function Tr069JobForm({ isPlatform, isps, devices, profiles, jobTypeOptions, storeUrl, defaults }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        isp_id: String(defaults.isp_id || isps[0]?.id || ''),
        cpe_device_id: String(defaults.cpe_device_id || devices[0]?.id || ''),
        tr069_profile_id: String(defaults.tr069_profile_id || 'none'),
        job_type: defaults.job_type || 'provision',
        payload_json: defaults.payload_json || '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(storeUrl, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'TR069', url: route('tr069.index') },
                { label: 'Configuration Jobs', url: route('tr069.jobs.index') },
                { label: 'Create Job' },
            ]}
            pageTitle="Create Configuration Job"
        >
            <Head title="Create TR069 Job" />

            <form onSubmit={submit} className="space-y-5">
                <div className="flex items-center justify-between rounded-lg border bg-card p-5 shadow-sm">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">Create Configuration Job</h2>
                        <p className="text-sm text-muted-foreground">Jobs are queued for ACS execution; no direct router commands are run here.</p>
                    </div>
                    <Button variant="outline" asChild><Link href={route('tr069.jobs.index')}><ArrowLeft className="h-4 w-4" />Back</Link></Button>
                </div>

                <Card>
                    <CardHeader><CardTitle className="text-base">Job Details</CardTitle></CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        {isPlatform && (
                            <div>
                                <Label>ISP</Label>
                                <Select value={data.isp_id} onValueChange={(selected) => setData('isp_id', selected)}>
                                    <SelectTrigger className="mt-1"><SelectValue placeholder="Select ISP" /></SelectTrigger>
                                    <SelectContent>{isps.map((isp) => <SelectItem key={isp.id} value={String(isp.id)}>{isp.name}</SelectItem>)}</SelectContent>
                                </Select>
                                {errors.isp_id && <p className="mt-1 text-xs text-destructive">{errors.isp_id}</p>}
                            </div>
                        )}
                        <div>
                            <Label>CPE Device</Label>
                            <Select value={data.cpe_device_id} onValueChange={(selected) => setData('cpe_device_id', selected)}>
                                <SelectTrigger className="mt-1"><SelectValue placeholder="Select device" /></SelectTrigger>
                                <SelectContent>{devices.map((device) => <SelectItem key={device.id} value={String(device.id)}>{device.label}</SelectItem>)}</SelectContent>
                            </Select>
                            {errors.cpe_device_id && <p className="mt-1 text-xs text-destructive">{errors.cpe_device_id}</p>}
                        </div>
                        <div>
                            <Label>Job Type</Label>
                            <Select value={data.job_type} onValueChange={(selected) => setData('job_type', selected)}>
                                <SelectTrigger className="mt-1"><SelectValue placeholder="Select type" /></SelectTrigger>
                                <SelectContent>{jobTypeOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent>
                            </Select>
                            {errors.job_type && <p className="mt-1 text-xs text-destructive">{errors.job_type}</p>}
                        </div>
                        <div>
                            <Label>Profile</Label>
                            <Select value={data.tr069_profile_id} onValueChange={(selected) => setData('tr069_profile_id', selected === 'none' ? '' : selected)}>
                                <SelectTrigger className="mt-1"><SelectValue placeholder="Optional profile" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">No profile</SelectItem>
                                    {profiles.map((profile) => <SelectItem key={profile.id} value={String(profile.id)}>{profile.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            {errors.tr069_profile_id && <p className="mt-1 text-xs text-destructive">{errors.tr069_profile_id}</p>}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Payload JSON</CardTitle></CardHeader>
                    <CardContent>
                        <Textarea className="min-h-44 font-mono text-sm" value={data.payload_json} onChange={(event) => setData('payload_json', event.target.value)} placeholder={'{\"Device.WiFi.SSID.1.SSID\": \"CustomerWiFi\"}'} />
                        {errors.payload_json && <p className="mt-1 text-xs text-destructive">{errors.payload_json}</p>}
                    </CardContent>
                </Card>

                <div className="flex justify-end gap-2">
                    <Button variant="outline" asChild><Link href={route('tr069.jobs.index')}>Cancel</Link></Button>
                    <Button type="submit" disabled={processing}><Save className="h-4 w-4" />{processing ? 'Queueing...' : 'Queue Job'}</Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
