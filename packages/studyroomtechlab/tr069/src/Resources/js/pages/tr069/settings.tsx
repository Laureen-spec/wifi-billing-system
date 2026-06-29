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
import { Head, useForm } from '@inertiajs/react';
import { Save, ServerCog } from 'lucide-react';
import { FormEvent } from 'react';
import { IspOption, PlatformIspSelect } from './components';

type Setting = {
    id: number;
    isp_id: number;
    enabled: boolean;
    acs_url?: string | null;
    api_token?: string | null;
    inform_interval?: number | null;
    connection_request_username?: string | null;
    connection_request_password?: string | null;
    default_profile_id?: number | null;
    allow_auto_register: boolean;
    require_known_serial: boolean;
};

type Props = {
    isp: { id: number; name: string };
    isPlatform: boolean;
    isps: IspOption[];
    setting: Setting;
    profileOptions: { id: number; name: string }[];
    saveUrl: string;
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

const value = (input: unknown) => input === null || input === undefined ? '' : String(input);

export default function Tr069Settings({ isp, isPlatform, isps, setting, profileOptions, saveUrl }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        isp_id: String(setting.isp_id || isp.id),
        enabled: Boolean(setting.enabled),
        acs_url: value(setting.acs_url),
        api_token: value(setting.api_token),
        inform_interval: value(setting.inform_interval || 3600),
        connection_request_username: value(setting.connection_request_username),
        connection_request_password: value(setting.connection_request_password),
        default_profile_id: setting.default_profile_id ? String(setting.default_profile_id) : '',
        allow_auto_register: Boolean(setting.allow_auto_register),
        require_known_serial: Boolean(setting.require_known_serial),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(saveUrl, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: 'WiFi Billing', url: route('wifi-billing.dashboard') }, { label: 'TR069', url: route('tr069.index') }, { label: 'Settings' }]} pageTitle="TR069 Settings">
            <Head title="TR069 Settings" />

            <form onSubmit={submit} className="space-y-5">
                <div className="flex flex-col gap-3 rounded-lg border bg-card p-5 shadow-sm md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">TR069 Settings</h2>
                        <p className="text-sm text-muted-foreground">{isp.name}</p>
                    </div>
                    <PlatformIspSelect isPlatform={isPlatform} isps={isps} value={setting.isp_id} routeName="tr069.settings" />
                </div>

                <Card>
                    <CardHeader><CardTitle className="flex items-center gap-2 text-base"><ServerCog className="h-4 w-4" />ACS Access</CardTitle></CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <input type="hidden" value={data.isp_id} readOnly />
                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <Label>Enable TR069</Label>
                                <p className="text-xs text-muted-foreground">Allow token-protected inform and job polling endpoints.</p>
                            </div>
                            <Switch checked={data.enabled} onCheckedChange={(checked) => setData('enabled', checked)} />
                        </div>
                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <Label>Auto Register Unknown CPE</Label>
                                <p className="text-xs text-muted-foreground">Create devices from valid inform payloads.</p>
                            </div>
                            <Switch checked={data.allow_auto_register} onCheckedChange={(checked) => setData('allow_auto_register', checked)} />
                        </div>
                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <Label>Require Known Serial</Label>
                                <p className="text-xs text-muted-foreground">Reject informs from unregistered serial numbers.</p>
                            </div>
                            <Switch checked={data.require_known_serial} onCheckedChange={(checked) => setData('require_known_serial', checked)} />
                        </div>
                        <Field label="ACS URL" value={data.acs_url} onChange={(next) => setData('acs_url', next)} error={errors.acs_url} />
                        <Field label="API Token" value={data.api_token} onChange={(next) => setData('api_token', next)} error={errors.api_token} />
                        <Field label="Inform Interval Seconds" type="number" value={data.inform_interval} onChange={(next) => setData('inform_interval', next)} error={errors.inform_interval} />
                        <Field label="Connection Request Username" value={data.connection_request_username} onChange={(next) => setData('connection_request_username', next)} error={errors.connection_request_username} />
                        <Field label="Connection Request Password" value={data.connection_request_password} onChange={(next) => setData('connection_request_password', next)} error={errors.connection_request_password} />
                        <div>
                            <Label>Default Provisioning Profile</Label>
                            <Select value={data.default_profile_id || 'none'} onValueChange={(next) => setData('default_profile_id', next === 'none' ? '' : next)}>
                                <SelectTrigger className="mt-1"><SelectValue placeholder="Default profile" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">No default profile</SelectItem>
                                    {profileOptions.map((profile) => <SelectItem key={profile.id} value={String(profile.id)}>{profile.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            {errors.default_profile_id && <p className="mt-1 text-xs text-destructive">{errors.default_profile_id}</p>}
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button type="submit" disabled={processing}><Save className="h-4 w-4" />{processing ? 'Saving...' : 'Save Settings'}</Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

function Field({ label, value, onChange, error, type = 'text' }: { label: string; value: string; onChange: (value: string) => void; error?: string; type?: string }) {
    return (
        <div>
            <Label>{label}</Label>
            <Input className="mt-1" type={type} value={value} onChange={(event) => onChange(event.target.value)} />
            {error && <p className="mt-1 text-xs text-destructive">{error}</p>}
        </div>
    );
}
