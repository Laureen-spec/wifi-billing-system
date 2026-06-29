import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { FormEvent } from 'react';
import { IspOption, Option } from '../components';

type ProfileForm = {
    isp_id: string;
    name: string;
    description: string;
    wan_mode: string;
    pppoe_username: string;
    pppoe_password: string;
    static_ip: string;
    static_gateway: string;
    static_dns: string;
    vlan_id: string;
    wifi_ssid: string;
    wifi_password: string;
    wifi_enabled: boolean;
    tr069_parameters_json: string;
    status: string;
};

type Props = {
    mode: 'create' | 'edit';
    profile: Partial<ProfileForm> & { id?: number | null };
    isPlatform: boolean;
    isps: IspOption[];
    wanModeOptions: Option[];
    statusOptions: Option[];
    storeUrl?: string;
    updateUrl?: string;
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

const value = (input: unknown) => input === null || input === undefined ? '' : String(input);

export default function Tr069ProfileForm({ mode, profile, isPlatform, isps, wanModeOptions, statusOptions, storeUrl, updateUrl }: Props) {
    const isEdit = mode === 'edit';
    const { data, setData, post, put, processing, errors } = useForm<ProfileForm>({
        isp_id: value(profile.isp_id || isps[0]?.id || ''),
        name: value(profile.name),
        description: value(profile.description),
        wan_mode: value(profile.wan_mode || 'dhcp'),
        pppoe_username: value(profile.pppoe_username),
        pppoe_password: value(profile.pppoe_password),
        static_ip: value(profile.static_ip),
        static_gateway: value(profile.static_gateway),
        static_dns: value(profile.static_dns),
        vlan_id: value(profile.vlan_id),
        wifi_ssid: value(profile.wifi_ssid),
        wifi_password: value(profile.wifi_password),
        wifi_enabled: profile.wifi_enabled !== false,
        tr069_parameters_json: value(profile.tr069_parameters_json),
        status: value(profile.status || 'active'),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (isEdit && updateUrl) {
            put(updateUrl, { preserveScroll: true });
            return;
        }
        post(storeUrl || route('tr069.profiles.store'), { preserveScroll: true });
    };

    const error = (key: keyof ProfileForm) => errors[key] ? <p className="mt-1 text-xs text-destructive">{errors[key]}</p> : null;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'TR069', url: route('tr069.index') },
                { label: 'Profiles', url: route('tr069.profiles.index') },
                { label: isEdit ? 'Edit Profile' : 'New Profile' },
            ]}
            pageTitle={isEdit ? 'Edit TR069 Profile' : 'New TR069 Profile'}
        >
            <Head title={isEdit ? 'Edit TR069 Profile' : 'New TR069 Profile'} />

            <form onSubmit={submit} className="space-y-5">
                <div className="flex items-center justify-between rounded-lg border bg-card p-5 shadow-sm">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">{isEdit ? 'Edit Profile' : 'New Profile'}</h2>
                        <p className="text-sm text-muted-foreground">Profiles queue safe ACS parameter jobs for future TR069 execution.</p>
                    </div>
                    <Button variant="outline" asChild><Link href={route('tr069.profiles.index')}><ArrowLeft className="h-4 w-4" />Back</Link></Button>
                </div>

                <Card>
                    <CardHeader><CardTitle className="text-base">Profile Details</CardTitle></CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        {isPlatform && (
                            <div>
                                <Label>ISP</Label>
                                <Select value={data.isp_id} onValueChange={(selected) => setData('isp_id', selected)}>
                                    <SelectTrigger className="mt-1"><SelectValue placeholder="Select ISP" /></SelectTrigger>
                                    <SelectContent>{isps.map((isp) => <SelectItem key={isp.id} value={String(isp.id)}>{isp.name}</SelectItem>)}</SelectContent>
                                </Select>
                                {error('isp_id')}
                            </div>
                        )}
                        <div className={isPlatform ? '' : 'md:col-span-2'}>
                            <Label>Name</Label>
                            <Input className="mt-1" value={data.name} onChange={(event) => setData('name', event.target.value)} />
                            {error('name')}
                        </div>
                        <div>
                            <Label>WAN Mode</Label>
                            <Select value={data.wan_mode} onValueChange={(selected) => setData('wan_mode', selected)}>
                                <SelectTrigger className="mt-1"><SelectValue placeholder="WAN mode" /></SelectTrigger>
                                <SelectContent>{wanModeOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent>
                            </Select>
                            {error('wan_mode')}
                        </div>
                        <div>
                            <Label>Status</Label>
                            <Select value={data.status} onValueChange={(selected) => setData('status', selected)}>
                                <SelectTrigger className="mt-1"><SelectValue placeholder="Status" /></SelectTrigger>
                                <SelectContent>{statusOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent>
                            </Select>
                            {error('status')}
                        </div>
                        <div className="md:col-span-2">
                            <Label>Description</Label>
                            <Textarea className="mt-1" rows={3} value={data.description} onChange={(event) => setData('description', event.target.value)} />
                            {error('description')}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">WAN and WiFi Parameters</CardTitle></CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-3">
                        <Field label="PPPoE Username" value={data.pppoe_username} onChange={(next) => setData('pppoe_username', next)} error={error('pppoe_username')} />
                        <Field label="PPPoE Password" value={data.pppoe_password} onChange={(next) => setData('pppoe_password', next)} error={error('pppoe_password')} />
                        <Field label="VLAN ID" type="number" value={data.vlan_id} onChange={(next) => setData('vlan_id', next)} error={error('vlan_id')} />
                        <Field label="Static IP" value={data.static_ip} onChange={(next) => setData('static_ip', next)} error={error('static_ip')} />
                        <Field label="Static Gateway" value={data.static_gateway} onChange={(next) => setData('static_gateway', next)} error={error('static_gateway')} />
                        <Field label="Static DNS" value={data.static_dns} onChange={(next) => setData('static_dns', next)} error={error('static_dns')} />
                        <Field label="WiFi SSID" value={data.wifi_ssid} onChange={(next) => setData('wifi_ssid', next)} error={error('wifi_ssid')} />
                        <Field label="WiFi Password" value={data.wifi_password} onChange={(next) => setData('wifi_password', next)} error={error('wifi_password')} />
                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <Label>WiFi Enabled</Label>
                                <p className="text-xs text-muted-foreground">Include WiFi settings in queued jobs.</p>
                            </div>
                            <Switch checked={data.wifi_enabled} onCheckedChange={(checked) => setData('wifi_enabled', checked)} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">TR069 Parameters JSON</CardTitle></CardHeader>
                    <CardContent>
                        <Textarea className="min-h-44 font-mono text-sm" value={data.tr069_parameters_json} onChange={(event) => setData('tr069_parameters_json', event.target.value)} placeholder={'{\"Device.ManagementServer.PeriodicInformEnable\": true}'} />
                        {error('tr069_parameters_json')}
                    </CardContent>
                </Card>

                <div className="flex justify-end gap-2">
                    <Button variant="outline" asChild><Link href={route('tr069.profiles.index')}>Cancel</Link></Button>
                    <Button type="submit" disabled={processing}><Save className="h-4 w-4" />{processing ? 'Saving...' : 'Save Profile'}</Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

function Field({ label, value, onChange, error, type = 'text' }: { label: string; value: string; onChange: (value: string) => void; error?: React.ReactNode; type?: string }) {
    return (
        <div>
            <Label>{label}</Label>
            <Input className="mt-1" type={type} value={value} onChange={(event) => onChange(event.target.value)} />
            {error}
        </div>
    );
}
