import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, BellRing, Coins, MessageSquare, Save, Tags, Wallet } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';

type Setting = {
    scope: string;
    mode: string;
    provider?: string | null;
    sender_id?: string | null;
    username?: string | null;
    callback_url?: string | null;
    allow_system_sms?: boolean;
    allow_own_sms?: boolean;
    free_sms_remaining?: number;
    sms_balance?: number;
    estimated_cost_per_sms?: number;
    low_balance_alert_enabled?: boolean;
    low_balance_alert_threshold?: number;
    low_balance_alert_phone?: string | null;
    low_balance_alerted_at?: string | null;
    is_active: boolean;
    updated_at?: string | null;
};

type Props = {
    pageTitle: string;
    subtitle: string;
    setting?: Setting | null;
    platformSetting?: Setting | null;
    isPlatform: boolean;
    hasSmsTables: boolean;
    dryRun: boolean;
    routes: {
        messages: string;
        newMessage: string;
        save: string;
        templates: string;
        topUp?: string;
    };
};

type FormData = {
    scope: string;
    mode: string;
    provider: string;
    sender_id: string;
    username: string;
    callback_url: string;
    api_key: string;
    api_secret: string;
    is_active: boolean;
    allow_system_sms: boolean;
    allow_own_sms: boolean;
    free_sms_remaining: number;
    sms_balance: string;
    estimated_cost_per_sms: string;
    low_balance_alert_enabled: boolean;
    low_balance_alert_threshold: string;
    low_balance_alert_phone: string;
};

const providers = [
    { value: 'africastalking', label: 'AfricasTalking', hint: 'Recommended for Kenya SMS delivery.' },
    { value: 'custom_http', label: 'Custom HTTP Gateway', hint: 'Use your own HTTP endpoint.' },
    { value: 'twilio', label: 'Twilio', hint: 'Connect Twilio credentials.' },
    { value: 'other', label: 'Other provider', hint: 'Store provider details for manual integration.' },
];

export default function SmsSettings({ pageTitle, subtitle, setting, platformSetting, isPlatform, hasSmsTables, dryRun, routes }: Props) {
    const current = setting || platformSetting;
    const { data, setData, post, processing, errors } = useForm<FormData>({
        scope: isPlatform ? (current?.scope || 'platform') : 'isp',
        mode: current?.mode || 'platform',
        provider: current?.provider && current.provider !== 'platform' ? current.provider : 'africastalking',
        sender_id: current?.sender_id || '',
        username: current?.username || '',
        callback_url: current?.callback_url || '',
        api_key: '',
        api_secret: '',
        is_active: current?.is_active ?? true,
        allow_system_sms: current?.allow_system_sms ?? true,
        allow_own_sms: current?.allow_own_sms ?? true,
        free_sms_remaining: current?.free_sms_remaining ?? 5,
        sms_balance: String(current?.sms_balance ?? 0),
        estimated_cost_per_sms: String(current?.estimated_cost_per_sms ?? 1),
        low_balance_alert_enabled: current?.low_balance_alert_enabled ?? true,
        low_balance_alert_threshold: String(current?.low_balance_alert_threshold ?? 10),
        low_balance_alert_phone: current?.low_balance_alert_phone || '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(routes.save, { preserveScroll: true });
    };

    const usingSystemSms = data.mode === 'platform';
    const balance = Number(data.sms_balance || 0);
    const threshold = Number(data.low_balance_alert_threshold || 10);
    const lowBalance = !isPlatform && usingSystemSms && balance <= threshold && data.free_sms_remaining <= 0;
    const selectedProvider = providers.find((provider) => provider.value === data.provider);

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: 'ISP SMS', url: routes.messages }, { label: pageTitle }]} pageTitle={pageTitle}>
            <Head title={pageTitle} />

            <div className="space-y-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-muted-foreground">Communication — SMS gateway</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-foreground">SMS settings</h1>
                        <p className="mt-2 max-w-3xl text-sm text-muted-foreground">{subtitle}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild><Link href={routes.messages}><ArrowLeft className="h-4 w-4" />Email / SMS Log</Link></Button>
                        <Button variant="outline" asChild><Link href={routes.templates}><Tags className="h-4 w-4" />Templates</Link></Button>
                        {routes.topUp && <Button asChild><Link href={routes.topUp}><Wallet className="h-4 w-4" />Top up SMS</Link></Button>}
                    </div>
                </div>

                {dryRun && <div className="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">Safe mode is enabled. Messages are recorded and marked sent without calling a live gateway.</div>}
                {!hasSmsTables && <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">SMS settings tables are not migrated yet.</div>}
                {lowBalance && <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"><strong>System alert:</strong> SMS balance is below {threshold}. Top up before sending more system SMS. {data.low_balance_alert_phone ? `Low-balance alerts will be sent to ${data.low_balance_alert_phone}.` : 'Configure an alert phone number below.'}</div>}

                {!isPlatform && (
                    <div className="grid gap-4 lg:grid-cols-4">
                        <MetricCard icon={<Wallet className="h-5 w-5" />} label="System balance" value={balance.toFixed(2)} hint="charged after starter SMS" />
                        <MetricCard icon={<MessageSquare className="h-5 w-5" />} label="Free SMS" value={String(data.free_sms_remaining)} hint="starter credits" />
                        <MetricCard icon={<Coins className="h-5 w-5" />} label="Cost / SMS" value={Number(data.estimated_cost_per_sms || 0).toFixed(2)} hint="system SMS estimate" />
                        <MetricCard icon={<BellRing className="h-5 w-5" />} label="Low balance alert" value={data.low_balance_alert_enabled ? 'On' : 'Off'} hint={`threshold ${threshold}`} />
                    </div>
                )}

                <form onSubmit={submit} className="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_390px]">
                    <div className="space-y-5">
                        <Card>
                            <CardHeader className="border-b py-4"><CardTitle className="text-base">SMS gateway rule</CardTitle></CardHeader>
                            <CardContent className="space-y-4 p-5">
                                <div className="grid gap-4 md:grid-cols-2">
                                    {isPlatform && (
                                        <div className="space-y-2">
                                            <Label htmlFor="sms-scope">Setting scope</Label>
                                            <select id="sms-scope" value={data.scope} onChange={(event) => setData('scope', event.target.value)} className="h-11 w-full rounded-xl border border-input bg-background px-3 text-sm">
                                                <option value="platform">Super admin platform SMS</option>
                                                <option value="isp">ISP SMS default</option>
                                            </select>
                                            <InputError message={errors.scope} />
                                        </div>
                                    )}

                                    <div className="space-y-2">
                                        <Label htmlFor="sms-mode">Gateway rule</Label>
                                        <select
                                            id="sms-mode"
                                            value={data.mode}
                                            onChange={(event) => {
                                                const mode = event.target.value;
                                                setData('mode', mode);
                                                setData('allow_system_sms', mode === 'platform');
                                                setData('allow_own_sms', mode === 'own');
                                            }}
                                            className="h-11 w-full rounded-xl border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="platform">Use system SMS balance</option>
                                            <option value="own">Use own SMS API</option>
                                        </select>
                                        <InputError message={errors.mode} />
                                    </div>
                                </div>

                                {!isPlatform ? (
                                    <div className="flex flex-col gap-3 rounded-2xl border bg-muted/20 p-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm font-semibold text-foreground">
                                                {usingSystemSms ? 'System SMS selected' : 'Own SMS API selected'}
                                            </p>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {usingSystemSms
                                                    ? 'Admin will use the platform SMS gateway and pay from the SMS balance.'
                                                    : 'Admin will use the configured provider credentials below.'}
                                            </p>
                                        </div>
                                        <Button type="submit" size="sm" disabled={processing}>
                                            <Save className="h-4 w-4" />
                                            Save rule
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="flex items-center gap-3 rounded-xl border p-4">
                                            <Checkbox checked={data.allow_system_sms} onCheckedChange={(value) => setData('allow_system_sms', Boolean(value))} />
                                            <div><p className="font-medium">Allow system SMS</p><p className="text-xs text-muted-foreground">Admins can use platform SMS balance.</p></div>
                                        </div>
                                        <div className="flex items-center gap-3 rounded-xl border p-4">
                                            <Checkbox checked={data.allow_own_sms} onCheckedChange={(value) => setData('allow_own_sms', Boolean(value))} />
                                            <div><p className="font-medium">Allow own API</p><p className="text-xs text-muted-foreground">Admins can connect AfricasTalking or another provider.</p></div>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {usingSystemSms && !isPlatform ? (
                            <Card>
                                <CardHeader className="border-b py-4">
                                    <CardTitle className="text-base">System SMS balance and alerts</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-5 p-5">
                                    <div className="rounded-2xl border bg-muted/20 p-4">
                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p className="font-medium text-foreground">Platform-managed SMS wallet</p>
                                                <p className="mt-1 text-sm text-muted-foreground">Managed by the platform. Contact support or top up SMS balance to increase usage.</p>
                                            </div>
                                            <Badge variant="outline" className="w-fit">Read only</Badge>
                                        </div>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-3">
                                        <ReadOnlyBalanceCard label="Free SMS remaining" value={String(data.free_sms_remaining)} hint="Starter SMS credits controlled by Super Admin." />
                                        <ReadOnlyBalanceCard label="SMS balance" value={balance.toFixed(2)} hint="Top-up wallet for system SMS charges." />
                                        <ReadOnlyBalanceCard label="Cost per SMS" value={Number(data.estimated_cost_per_sms || 0).toFixed(2)} hint="Platform SMS rate set by Super Admin." />
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-3">
                                        <div className="flex items-center gap-3 rounded-xl border p-4 md:col-span-3"><Checkbox checked={data.low_balance_alert_enabled} onCheckedChange={(value) => setData('low_balance_alert_enabled', Boolean(value))} /><div><p className="font-medium">Send low-balance SMS alert</p><p className="text-xs text-muted-foreground">When balance drops below your threshold, notify the configured phone number.</p></div></div>
                                        <div className="space-y-2"><Label htmlFor="alert-phone">Alert phone number</Label><Input id="alert-phone" value={data.low_balance_alert_phone} onChange={(e) => setData('low_balance_alert_phone', e.target.value)} placeholder="0712345678" /><InputError message={errors.low_balance_alert_phone} /></div>
                                        <div className="space-y-2"><Label htmlFor="alert-threshold">Alert below balance</Label><Input id="alert-threshold" type="number" min="0" step="0.01" value={data.low_balance_alert_threshold} onChange={(e) => setData('low_balance_alert_threshold', e.target.value)} /><InputError message={errors.low_balance_alert_threshold} /></div>
                                        <div className="rounded-xl border bg-muted/30 p-4 text-sm text-muted-foreground"><p className="font-medium text-foreground">Top-up rule</p><p className="mt-1">After free SMS are depleted, every system SMS is deducted from this balance. Use the top-up button when balance is low.</p></div>
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardHeader className="border-b py-4"><CardTitle className="text-base">{isPlatform ? 'Platform SMS gateway configuration' : 'Own SMS API configuration'}</CardTitle></CardHeader>
                                <CardContent className="grid gap-4 p-5 md:grid-cols-2">
                                    <div className="space-y-2 md:col-span-2">
                                        <Label htmlFor="sms-provider">Available gateway</Label>
                                        <select id="sms-provider" value={data.provider} onChange={(event) => setData('provider', event.target.value)} className="h-11 w-full rounded-xl border border-input bg-background px-3 text-sm">
                                            {providers.map((provider) => <option key={provider.value} value={provider.value}>{provider.label}</option>)}
                                        </select>
                                        {selectedProvider && <p className="text-xs text-muted-foreground">{selectedProvider.hint}</p>}
                                        <InputError message={errors.provider} />
                                    </div>
                                    <div className="space-y-2"><Label htmlFor="sms-sender">Sender ID</Label><Input id="sms-sender" value={data.sender_id} onChange={(event) => setData('sender_id', event.target.value)} placeholder="StudyRoom" /><InputError message={errors.sender_id} /></div>
                                    <div className="space-y-2"><Label htmlFor="sms-username">Username</Label><Input id="sms-username" value={data.username} onChange={(event) => setData('username', event.target.value)} placeholder="Gateway username" /><InputError message={errors.username} /></div>
                                    <div className="space-y-2"><Label htmlFor="sms-key">API key</Label><Input id="sms-key" value={data.api_key} onChange={(event) => setData('api_key', event.target.value)} placeholder={current ? 'Leave blank to keep existing key' : 'API key'} /><InputError message={errors.api_key} /></div>
                                    <div className="space-y-2"><Label htmlFor="sms-secret">API secret</Label><Input id="sms-secret" type="password" value={data.api_secret} onChange={(event) => setData('api_secret', event.target.value)} placeholder={current ? 'Leave blank to keep existing secret' : 'API secret'} /><InputError message={errors.api_secret} /></div>
                                    {data.provider === 'custom_http' && <div className="space-y-2 md:col-span-2"><Label htmlFor="sms-url">Custom HTTP send URL</Label><Input id="sms-url" value={data.callback_url} onChange={(event) => setData('callback_url', event.target.value)} placeholder="https://gateway.example/send" /><InputError message={errors.callback_url} /></div>}
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader className="border-b py-4"><CardTitle className="text-base">Gateway status</CardTitle></CardHeader>
                            <CardContent className="flex items-center justify-between gap-4 p-5">
                                <div className="flex items-center gap-3"><Checkbox checked={data.is_active} onCheckedChange={(value) => setData('is_active', Boolean(value))} /><div><p className="font-medium">Gateway active</p><p className="text-xs text-muted-foreground">Disable this to pause new SMS sending.</p></div></div>
                                <Button type="submit" disabled={processing}><Save className="h-4 w-4" />Save settings</Button>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-5">
                        {!isPlatform ? (
                            <Card>
                                <CardHeader className="border-b bg-muted/20 py-4"><CardTitle className="text-base">System alert</CardTitle></CardHeader>
                                <CardContent className="space-y-4 p-5 text-sm">
                                    <div className={`rounded-2xl border p-4 ${lowBalance ? 'border-amber-200 bg-amber-50 text-amber-900' : 'bg-muted/20 text-muted-foreground'}`}>
                                        <div className="flex items-start gap-3"><BellRing className="mt-0.5 h-5 w-5" /><div><p className="font-medium text-foreground">Low balance monitor</p><p className="mt-1">Alert phone: {data.low_balance_alert_phone || 'Not configured'}</p><p>Threshold: {data.low_balance_alert_threshold || '10'}</p>{current?.low_balance_alerted_at && <p>Last alert: {current.low_balance_alerted_at}</p>}</div></div>
                                    </div>
                                    {routes.topUp && <Button className="w-full" asChild><Link href={routes.topUp}><Wallet className="h-4 w-4" />Go to SMS checkout</Link></Button>}
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardHeader className="border-b bg-muted/20 py-4"><CardTitle className="text-base">Super admin SMS gateway</CardTitle></CardHeader>
                                <CardContent className="space-y-3 p-5 text-sm text-muted-foreground">
                                    <div className="rounded-2xl border bg-muted/20 p-4">
                                        Configure the platform SMS provider here. Admins who pick system SMS will use this gateway and pay from their SMS balance.
                                    </div>
                                    <div className="rounded-2xl border bg-muted/20 p-4">
                                        Admin wallet balance, starter SMS, and low-balance alerts stay on the admin side only.
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        <Card className="overflow-hidden">
                            <CardHeader className="border-b bg-muted/20 py-4">
                                <CardTitle className="text-base">Gateway summary</CardTitle>
                                <p className="text-xs text-muted-foreground">Current SMS configuration snapshot.</p>
                            </CardHeader>
                            <CardContent className="space-y-3 p-5 text-sm">
                                <SummaryRow label="Rule" value={usingSystemSms ? 'System SMS' : 'Own API'} />
                                <SummaryRow label="Provider" value={usingSystemSms ? 'Platform gateway' : selectedProvider?.label || 'Not selected'} />
                                <SummaryRow label="Status" value={data.is_active ? 'Active' : 'Inactive'} />
                                <SummaryRow label="Last updated" value={current?.updated_at || 'Not saved yet'} />
                            </CardContent>
                        </Card>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}


function ReadOnlyBalanceCard({ label, value, hint }: { label: string; value: string; hint: string }) {
    return (
        <div className="rounded-2xl border bg-card p-4 shadow-sm">
            <p className="text-sm font-medium text-muted-foreground">{label}</p>
            <p className="mt-2 text-2xl font-semibold text-foreground">{value}</p>
            <p className="mt-1 text-xs leading-5 text-muted-foreground">{hint}</p>
        </div>
    );
}

function MetricCard({ icon, label, value, hint }: { icon: ReactNode; label: string; value: string; hint: string }) {
    return <Card><CardContent className="flex items-center gap-3 p-4"><div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary">{icon}</div><div><p className="text-sm text-muted-foreground">{label}</p><p className="text-2xl font-semibold">{value}</p><p className="text-xs text-muted-foreground">{hint}</p></div></CardContent></Card>;
}

function SummaryRow({ label, value }: { label: string; value: string }) {
    return <div className="flex items-center justify-between gap-4 rounded-xl border bg-background px-3 py-2.5"><span className="text-muted-foreground">{label}</span><span className="text-right font-medium text-foreground">{value}</span></div>;
}
