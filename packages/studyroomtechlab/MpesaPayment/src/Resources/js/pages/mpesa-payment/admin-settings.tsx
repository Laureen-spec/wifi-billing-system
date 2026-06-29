import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, BookOpen, CheckCircle2, CreditCard, KeyRound, Save, ShieldCheck } from 'lucide-react';
import { FormEvent } from 'react';

type AdminSetting = {
    payment_mode?: 'system' | 'own';
    gateway?: string;
    environment?: string;
    business_name?: string | null;
    shortcode?: string | null;
    account_reference?: string | null;
    callback_url?: string | null;
    system_payment_channel?: 'till' | 'phone' | 'paybill';
    system_till_number?: string | null;
    system_paybill_number?: string | null;
    system_account_number?: string | null;
    system_phone_number?: string | null;
    documentation_url?: string | null;
    is_active?: boolean;
    updated_at?: string | null;
    has_consumer_key?: boolean;
    has_consumer_secret?: boolean;
    has_passkey?: boolean;
};

type PlatformSetting = {
    is_active?: boolean;
    environment?: string | null;
    shortcode?: string | null;
    business_name?: string | null;
    allow_isp_direct?: boolean;
    updated_at?: string | null;
};

type Props = {
    pageTitle: string;
    subtitle: string;
    setting?: AdminSetting | null;
    platformSetting?: PlatformSetting | null;
    isp: { id: number; name: string; email?: string | null; phone?: string | null };
    availableGateways: Array<{ value: string; label: string }>;
    routes: { save: string; dashboard: string };
};

type FormData = {
    payment_mode: 'system' | 'own';
    gateway: string;
    is_active: boolean;
    system_payment_channel: 'till' | 'phone' | 'paybill';
    system_till_number: string;
    system_paybill_number: string;
    system_account_number: string;
    system_phone_number: string;
    environment: string;
    business_name: string;
    shortcode: string;
    account_reference: string;
    consumer_key: string;
    consumer_secret: string;
    passkey: string;
    callback_url: string;
    documentation_url: string;
};

const fieldClass = 'h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus:ring-2 focus:ring-ring';
const callbackUrl = () => `${window.location.origin}/mpesa-payment/callback`;

export default function AdminPaymentAddonSettings({ pageTitle, subtitle, setting, platformSetting, isp, availableGateways, routes }: Props) {
    const currentMode = setting?.payment_mode || 'system';
    const { data, setData, post, processing, errors } = useForm<FormData>({
        payment_mode: currentMode,
        gateway: setting?.gateway || 'mpesa',
        is_active: setting?.is_active ?? true,
        system_payment_channel: setting?.system_payment_channel || 'till',
        system_till_number: setting?.system_till_number || '',
        system_paybill_number: setting?.system_paybill_number || '',
        system_account_number: setting?.system_account_number || '',
        system_phone_number: setting?.system_phone_number || '',
        environment: setting?.environment || platformSetting?.environment || 'sandbox',
        business_name: setting?.business_name || isp.name || '',
        shortcode: setting?.shortcode || '',
        account_reference: setting?.account_reference || isp.name || '',
        consumer_key: '',
        consumer_secret: '',
        passkey: '',
        callback_url: setting?.callback_url || callbackUrl(),
        documentation_url: setting?.documentation_url || 'https://developer.safaricom.co.ke/',
    });

    const isOwnGateway = data.payment_mode === 'own';
    const platformReady = Boolean(platformSetting?.is_active && platformSetting?.shortcode);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(routes.save, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: 'Dashboard', url: routes.dashboard }, { label: pageTitle }]}
            pageTitle={pageTitle}
        >
            <Head title={pageTitle} />

            <div className="space-y-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-foreground">{pageTitle}</h1>
                        <p className="mt-1 max-w-3xl text-sm text-muted-foreground">{subtitle}</p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={routes.dashboard}>
                            <ArrowLeft className="h-4 w-4" />
                            Dashboard
                        </Link>
                    </Button>
                </div>

                <form onSubmit={submit} className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="space-y-5">
                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <CreditCard className="h-5 w-5 text-primary" />
                                    Payment Mode
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 p-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <button
                                        type="button"
                                        onClick={() => setData('payment_mode', 'system')}
                                        className={`rounded-xl border p-4 text-left transition ${data.payment_mode === 'system' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'hover:bg-muted/40'}`}
                                    >
                                        <div className="flex items-center gap-2 font-semibold">
                                            <ShieldCheck className="h-5 w-5 text-primary" />
                                            System Payment
                                        </div>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            Use the Super Admin M-Pesa gateway. The admin only defines the Till, Phone, or Paybill reference used for reporting and settlement.
                                        </p>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setData('payment_mode', 'own')}
                                        className={`rounded-xl border p-4 text-left transition ${data.payment_mode === 'own' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'hover:bg-muted/40'}`}
                                    >
                                        <div className="flex items-center gap-2 font-semibold">
                                            <KeyRound className="h-5 w-5 text-primary" />
                                            Own Payment Gateway
                                        </div>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            Use this ISP&apos;s own M-Pesa Daraja credentials for direct collection into the ISP shortcode.
                                        </p>
                                    </button>
                                </div>
                                <InputError message={errors.payment_mode} />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Gateway Details</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 p-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Available Payment Gateway</Label>
                                    <select className={fieldClass} value={data.gateway} onChange={(event) => setData('gateway', event.target.value)}>
                                        {availableGateways.map((gateway) => (
                                            <option key={gateway.value} value={gateway.value}>{gateway.label}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.gateway} />
                                </div>

                                <div className="flex items-center justify-between rounded-xl border px-4 py-3">
                                    <div>
                                        <p className="text-sm font-medium">Enable Payment Addon</p>
                                        <p className="text-xs text-muted-foreground">Allow this ISP to collect payments through this setup.</p>
                                    </div>
                                    <Switch checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked)} />
                                </div>

                                {!isOwnGateway && (
                                    <>
                                        <div className="space-y-2">
                                            <Label>System Payment Type</Label>
                                            <select
                                                className={fieldClass}
                                                value={data.system_payment_channel}
                                                onChange={(event) => setData('system_payment_channel', event.target.value as FormData['system_payment_channel'])}
                                            >
                                                <option value="till">M-Pesa Till</option>
                                                <option value="phone">M-Pesa Phone</option>
                                                <option value="paybill">M-Pesa Paybill</option>
                                            </select>
                                            <InputError message={errors.system_payment_channel} />
                                        </div>

                                        {data.system_payment_channel === 'till' && (
                                            <div className="space-y-2">
                                                <Label>Admin Till Number</Label>
                                                <Input value={data.system_till_number} onChange={(event) => setData('system_till_number', event.target.value)} placeholder="Enter admin till number" />
                                                <InputError message={errors.system_till_number} />
                                            </div>
                                        )}

                                        {data.system_payment_channel === 'phone' && (
                                            <div className="space-y-2">
                                                <Label>Admin M-Pesa Phone</Label>
                                                <Input value={data.system_phone_number} onChange={(event) => setData('system_phone_number', event.target.value)} placeholder="0712345678" />
                                                <InputError message={errors.system_phone_number} />
                                            </div>
                                        )}

                                        {data.system_payment_channel === 'paybill' && (
                                            <>
                                                <div className="space-y-2">
                                                    <Label>Admin Paybill Number</Label>
                                                    <Input value={data.system_paybill_number} onChange={(event) => setData('system_paybill_number', event.target.value)} placeholder="Enter paybill number" />
                                                    <InputError message={errors.system_paybill_number} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label>Account Number</Label>
                                                    <Input value={data.system_account_number} onChange={(event) => setData('system_account_number', event.target.value)} placeholder="Enter account number" />
                                                    <InputError message={errors.system_account_number} />
                                                </div>
                                            </>
                                        )}
                                    </>
                                )}

                                {isOwnGateway && (
                                    <>
                                        <div className="space-y-2">
                                            <Label>Environment</Label>
                                            <select className={fieldClass} value={data.environment} onChange={(event) => setData('environment', event.target.value)}>
                                                <option value="sandbox">Sandbox</option>
                                                <option value="live">Live</option>
                                            </select>
                                            <InputError message={errors.environment} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Business Name</Label>
                                            <Input value={data.business_name} onChange={(event) => setData('business_name', event.target.value)} placeholder="ISP business name" />
                                            <InputError message={errors.business_name} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>M-Pesa Shortcode</Label>
                                            <Input value={data.shortcode} onChange={(event) => setData('shortcode', event.target.value)} placeholder="Paybill / shortcode" />
                                            <InputError message={errors.shortcode} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Account Reference</Label>
                                            <Input value={data.account_reference} onChange={(event) => setData('account_reference', event.target.value)} placeholder={isp.name} />
                                            <InputError message={errors.account_reference} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Consumer Key</Label>
                                            <Input type="password" value={data.consumer_key} onChange={(event) => setData('consumer_key', event.target.value)} placeholder={setting?.has_consumer_key ? 'Saved — enter to replace' : 'Enter consumer key'} />
                                            <InputError message={errors.consumer_key} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Consumer Secret</Label>
                                            <Input type="password" value={data.consumer_secret} onChange={(event) => setData('consumer_secret', event.target.value)} placeholder={setting?.has_consumer_secret ? 'Saved — enter to replace' : 'Enter consumer secret'} />
                                            <InputError message={errors.consumer_secret} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Passkey</Label>
                                            <Input type="password" value={data.passkey} onChange={(event) => setData('passkey', event.target.value)} placeholder={setting?.has_passkey ? 'Saved — enter to replace' : 'Enter STK passkey'} />
                                            <InputError message={errors.passkey} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Callback URL</Label>
                                            <Input value={data.callback_url} onChange={(event) => setData('callback_url', event.target.value)} placeholder={callbackUrl()} />
                                            <InputError message={errors.callback_url} />
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <BookOpen className="h-5 w-5 text-primary" />
                                    Documentation
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 p-4 md:grid-cols-[minmax(0,1fr)_240px]">
                                <div className="space-y-2">
                                    <Label>Documentation URL</Label>
                                    <Input value={data.documentation_url} onChange={(event) => setData('documentation_url', event.target.value)} placeholder="https://developer.safaricom.co.ke/" />
                                    <InputError message={errors.documentation_url} />
                                </div>
                                <div className="rounded-xl bg-muted/40 p-3 text-sm text-muted-foreground">
                                    {isOwnGateway
                                        ? 'Own gateway requires Consumer Key, Consumer Secret, Passkey, Shortcode, and callback URL from Daraja.'
                                        : 'System payment uses the Super Admin credentials. Admin till, phone, or paybill details are used as the ISP collection reference.'}
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing}>
                                <Save className="mr-2 h-4 w-4" />
                                {processing ? 'Saving...' : 'Save Payment Addon'}
                            </Button>
                        </div>
                    </div>

                    <aside className="space-y-4">
                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Gateway Overview</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 p-4 text-sm">
                                <div className="flex justify-between gap-3"><span className="text-muted-foreground">ISP</span><span className="font-medium text-right">{isp.name}</span></div>
                                <div className="flex justify-between gap-3"><span className="text-muted-foreground">Mode</span><span className="font-medium">{isOwnGateway ? 'Own gateway' : 'System payment'}</span></div>
                                <div className="flex justify-between gap-3"><span className="text-muted-foreground">Gateway</span><span className="font-medium">M-Pesa Daraja</span></div>
                                <div className="flex justify-between gap-3"><span className="text-muted-foreground">Status</span><span className={data.is_active ? 'font-medium text-emerald-600' : 'font-medium text-amber-600'}>{data.is_active ? 'Active' : 'Inactive'}</span></div>
                                <div className="flex justify-between gap-3"><span className="text-muted-foreground">Platform Gateway</span><span className={platformReady ? 'font-medium text-emerald-600' : 'font-medium text-red-600'}>{platformReady ? 'Ready' : 'Not configured'}</span></div>
                                <div className="flex justify-between gap-3"><span className="text-muted-foreground">Updated</span><span className="font-medium text-right">{setting?.updated_at || 'Not saved'}</span></div>
                            </CardContent>
                        </Card>

                        {!platformReady && !isOwnGateway && (
                            <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                Super Admin M-Pesa gateway is not configured yet. System payment will not work until the platform gateway is active.
                            </div>
                        )}

                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Setup checklist</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 p-4 text-sm text-muted-foreground">
                                <p className="flex gap-2"><CheckCircle2 className="mt-0.5 h-4 w-4 text-primary" />Choose System Payment for platform-managed M-Pesa.</p>
                                <p className="flex gap-2"><CheckCircle2 className="mt-0.5 h-4 w-4 text-primary" />Choose Own Gateway only when the ISP has a Daraja app.</p>
                                <p className="flex gap-2"><CheckCircle2 className="mt-0.5 h-4 w-4 text-primary" />Use Paybill + Account Number when the ISP wants clear payment references.</p>
                            </CardContent>
                        </Card>
                    </aside>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
