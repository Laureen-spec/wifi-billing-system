import { useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { BookOpen, CheckCircle2, CreditCard, Eye, EyeOff, RefreshCw, Save, ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

interface PlatformSetting {
    active_gateway?: string | null;
    environment?: string | null;
    business_name?: string | null;
    shortcode?: string | null;
    account_reference?: string | null;
    callback_url?: string | null;
    commission_type?: string | null;
    commission_value?: string | number | null;
    allow_isp_direct?: boolean;
    is_active?: boolean;
    documentation_url?: string | null;
    updated_at?: string | null;
    has_consumer_key?: boolean;
    has_consumer_secret?: boolean;
    has_passkey?: boolean;
}

interface Props {
    auth?: any;
    mpesaPaymentSettings?: PlatformSetting | null;
}

type FormState = {
    active_gateway: string;
    environment: string;
    business_name: string;
    shortcode: string;
    account_reference: string;
    consumer_key: string;
    consumer_secret: string;
    passkey: string;
    callback_url: string;
    commission_type: string;
    commission_value: string;
    allow_isp_direct: boolean;
    is_active: boolean;
    documentation_url: string;
};

const defaultCallback = () => `${window.location.origin}/mpesa-payment/callback`;

const fieldClass = 'h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus:ring-2 focus:ring-ring';

export default function PaymentGatewaySettings({ auth, mpesaPaymentSettings }: Props) {
    const canEdit = auth?.user?.permissions?.includes('manage-settings') || auth?.user?.permissions?.includes('edit-system-settings');
    const [saving, setSaving] = useState(false);
    const [showSecrets, setShowSecrets] = useState(false);

    const initialForm = useMemo<FormState>(() => ({
        active_gateway: mpesaPaymentSettings?.active_gateway || 'mpesa',
        environment: mpesaPaymentSettings?.environment || 'sandbox',
        business_name: mpesaPaymentSettings?.business_name || '',
        shortcode: mpesaPaymentSettings?.shortcode || '',
        account_reference: mpesaPaymentSettings?.account_reference || 'StudyRoom WiFi',
        consumer_key: '',
        consumer_secret: '',
        passkey: '',
        callback_url: mpesaPaymentSettings?.callback_url || defaultCallback(),
        commission_type: mpesaPaymentSettings?.commission_type || 'percentage',
        commission_value: String(mpesaPaymentSettings?.commission_value ?? '2.5'),
        allow_isp_direct: Boolean(mpesaPaymentSettings?.allow_isp_direct),
        is_active: mpesaPaymentSettings?.is_active ?? true,
        documentation_url: mpesaPaymentSettings?.documentation_url || 'https://developer.safaricom.co.ke/',
    }), [mpesaPaymentSettings]);

    const [form, setForm] = useState<FormState>(initialForm);

    useEffect(() => {
        setForm(initialForm);
    }, [initialForm]);

    const update = (field: keyof FormState, value: string | boolean) => {
        setForm((current) => ({ ...current, [field]: value }));
    };

    const save = () => {
        setSaving(true);

        router.post(route('mpesa-payment.settings.platform.save'), form, {
            preserveScroll: true,
            onSuccess: (page) => {
                setSaving(false);
                const flash = (page.props as any)?.flash || {};
                toast.success(flash.success || 'Platform payment gateway saved.');
                setForm((current) => ({
                    ...current,
                    consumer_key: '',
                    consumer_secret: '',
                    passkey: '',
                }));
            },
            onError: (errors) => {
                setSaving(false);
                toast.error(Object.values(errors).join(', ') || 'Failed to save payment gateway.');
            },
            onFinish: () => setSaving(false),
        });
    };

    return (
        <Card className="overflow-hidden">
            <CardHeader className="flex flex-col gap-4 border-b md:flex-row md:items-center md:justify-between">
                <div>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <CreditCard className="h-5 w-5 text-primary" />
                        Payment Gateway
                    </CardTitle>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Configure the Super Admin M-Pesa gateway used for ISP subscription checkout and system payments.
                    </p>
                </div>
                {canEdit && (
                    <Button onClick={save} disabled={saving} size="sm">
                        {saving ? <RefreshCw className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                        {saving ? 'Saving...' : 'Save Changes'}
                    </Button>
                )}
            </CardHeader>

            <CardContent className="grid gap-6 p-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div className="space-y-5">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Active Gateway</Label>
                            <select className={fieldClass} value={form.active_gateway} onChange={(event) => update('active_gateway', event.target.value)} disabled={!canEdit}>
                                <option value="mpesa">M-Pesa Daraja</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Environment</Label>
                            <select className={fieldClass} value={form.environment} onChange={(event) => update('environment', event.target.value)} disabled={!canEdit}>
                                <option value="sandbox">Sandbox</option>
                                <option value="live">Live</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Business Name</Label>
                            <Input value={form.business_name} onChange={(event) => update('business_name', event.target.value)} disabled={!canEdit} placeholder="StudyRoom Connect" />
                        </div>
                        <div className="space-y-2">
                            <Label>Paybill / Business Shortcode</Label>
                            <Input value={form.shortcode} onChange={(event) => update('shortcode', event.target.value)} disabled={!canEdit} placeholder="174379" />
                        </div>
                        <div className="space-y-2">
                            <Label>Account Reference</Label>
                            <Input value={form.account_reference} onChange={(event) => update('account_reference', event.target.value)} disabled={!canEdit} placeholder="StudyRoom WiFi" />
                        </div>
                        <div className="space-y-2">
                            <Label>Callback URL</Label>
                            <Input value={form.callback_url} onChange={(event) => update('callback_url', event.target.value)} disabled={!canEdit} placeholder={defaultCallback()} />
                        </div>
                    </div>

                    <div className="rounded-xl border p-4">
                        <div className="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h4 className="text-sm font-semibold">Daraja Credentials</h4>
                                <p className="text-xs text-muted-foreground">Leave secret fields empty when you only want to keep existing saved credentials.</p>
                            </div>
                            <Button type="button" variant="outline" size="sm" onClick={() => setShowSecrets((value) => !value)}>
                                {showSecrets ? <EyeOff className="mr-2 h-4 w-4" /> : <Eye className="mr-2 h-4 w-4" />}
                                {showSecrets ? 'Hide' : 'Show'}
                            </Button>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Consumer Key</Label>
                                <Input type={showSecrets ? 'text' : 'password'} value={form.consumer_key} onChange={(event) => update('consumer_key', event.target.value)} disabled={!canEdit} placeholder={mpesaPaymentSettings?.has_consumer_key ? 'Saved — enter to replace' : 'Enter consumer key'} />
                            </div>
                            <div className="space-y-2">
                                <Label>Consumer Secret</Label>
                                <Input type={showSecrets ? 'text' : 'password'} value={form.consumer_secret} onChange={(event) => update('consumer_secret', event.target.value)} disabled={!canEdit} placeholder={mpesaPaymentSettings?.has_consumer_secret ? 'Saved — enter to replace' : 'Enter consumer secret'} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label>Passkey</Label>
                                <Input type={showSecrets ? 'text' : 'password'} value={form.passkey} onChange={(event) => update('passkey', event.target.value)} disabled={!canEdit} placeholder={mpesaPaymentSettings?.has_passkey ? 'Saved — enter to replace' : 'Enter STK passkey'} />
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Platform Commission Type</Label>
                            <select className={fieldClass} value={form.commission_type} onChange={(event) => update('commission_type', event.target.value)} disabled={!canEdit}>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                                <option value="none">No Commission</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Commission Value</Label>
                            <Input value={form.commission_value} onChange={(event) => update('commission_value', event.target.value)} disabled={!canEdit} placeholder="2.5" />
                        </div>
                    </div>

                    <div className="grid gap-3 md:grid-cols-2">
                        <div className="flex items-center justify-between rounded-xl border p-4">
                            <div>
                                <p className="text-sm font-medium">Gateway Status</p>
                                <p className="text-xs text-muted-foreground">Allow payment requests through this gateway.</p>
                            </div>
                            <Switch checked={form.is_active} onCheckedChange={(checked) => update('is_active', checked)} disabled={!canEdit} />
                        </div>
                        <div className="flex items-center justify-between rounded-xl border p-4">
                            <div>
                                <p className="text-sm font-medium">Allow ISP Own Gateway</p>
                                <p className="text-xs text-muted-foreground">Admins may add their own M-Pesa credentials.</p>
                            </div>
                            <Switch checked={form.allow_isp_direct} onCheckedChange={(checked) => update('allow_isp_direct', checked)} disabled={!canEdit} />
                        </div>
                    </div>
                </div>

                <aside className="space-y-4">
                    <div className="rounded-xl border bg-muted/30 p-4">
                        <div className="flex items-center gap-2">
                            <ShieldCheck className="h-5 w-5 text-primary" />
                            <h4 className="font-semibold">Gateway Overview</h4>
                        </div>
                        <div className="mt-4 space-y-3 text-sm">
                            <div className="flex justify-between gap-3"><span className="text-muted-foreground">Gateway</span><span className="font-medium">M-Pesa Daraja</span></div>
                            <div className="flex justify-between gap-3"><span className="text-muted-foreground">Environment</span><span className="font-medium capitalize">{form.environment}</span></div>
                            <div className="flex justify-between gap-3"><span className="text-muted-foreground">Status</span><span className={form.is_active ? 'font-medium text-emerald-600' : 'font-medium text-amber-600'}>{form.is_active ? 'Active' : 'Inactive'}</span></div>
                            <div className="flex justify-between gap-3"><span className="text-muted-foreground">Last updated</span><span className="font-medium text-right">{mpesaPaymentSettings?.updated_at || 'Not saved'}</span></div>
                        </div>
                    </div>

                    <div className="rounded-xl border p-4">
                        <div className="flex items-center gap-2">
                            <BookOpen className="h-5 w-5 text-primary" />
                            <h4 className="font-semibold">Documentation</h4>
                        </div>
                        <ul className="mt-4 space-y-3 text-sm text-muted-foreground">
                            <li className="flex gap-2"><CheckCircle2 className="mt-0.5 h-4 w-4 text-primary" />Create a Daraja app in Safaricom developer portal.</li>
                            <li className="flex gap-2"><CheckCircle2 className="mt-0.5 h-4 w-4 text-primary" />Copy Consumer Key, Consumer Secret, Shortcode and Passkey.</li>
                            <li className="flex gap-2"><CheckCircle2 className="mt-0.5 h-4 w-4 text-primary" />Set callback URL to the URL shown in this form.</li>
                        </ul>
                        <Input className="mt-4" value={form.documentation_url} onChange={(event) => update('documentation_url', event.target.value)} disabled={!canEdit} placeholder="Documentation URL" />
                    </div>
                </aside>
            </CardContent>
        </Card>
    );
}
