import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Head, useForm } from '@inertiajs/react';
import { Gift, Save, Settings } from 'lucide-react';
import { FormEvent } from 'react';
import { IspOption, Option, PlatformIspSelect } from './components';

type Setting = {
    id: number;
    isp_id: number;
    enabled: boolean;
    default_points_per_payment: number;
    points_per_amount: number;
    amount_step: number;
    voucher_threshold: number;
    voucher_package_name?: string | null;
    voucher_duration_minutes: number;
    points_expiry_days?: number | null;
    auto_generate_voucher: boolean;
};

type FormData = {
    isp_id: string;
    enabled: boolean;
    default_points_per_payment: string;
    points_per_amount: string;
    amount_step: string;
    voucher_threshold: string;
    voucher_package_name: string;
    voucher_duration_minutes: string;
    points_expiry_days: string;
    auto_generate_voucher: boolean;
};

type Props = {
    isp: { id: number; name: string };
    isPlatform: boolean;
    isps: IspOption[];
    setting: Setting;
    packageOptions: Option[];
    saveUrl: string;
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

const stringValue = (value: unknown) => value === null || value === undefined ? '' : String(value);

export default function LoyaltySettings({ isp, isPlatform, isps, setting, packageOptions, saveUrl }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        isp_id: String(setting.isp_id || isp.id),
        enabled: Boolean(setting.enabled),
        default_points_per_payment: stringValue(setting.default_points_per_payment),
        points_per_amount: stringValue(setting.points_per_amount),
        amount_step: stringValue(setting.amount_step),
        voucher_threshold: stringValue(setting.voucher_threshold),
        voucher_package_name: stringValue(setting.voucher_package_name),
        voucher_duration_minutes: stringValue(setting.voucher_duration_minutes),
        points_expiry_days: stringValue(setting.points_expiry_days),
        auto_generate_voucher: Boolean(setting.auto_generate_voucher),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(saveUrl, { preserveScroll: true });
    };

    const error = (key: keyof FormData) => errors[key] ? <p className="mt-1 text-xs text-destructive">{errors[key]}</p> : null;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Loyalty', url: route('loyalty.index') },
                { label: 'Settings' },
            ]}
            pageTitle="Loyalty Settings"
        >
            <Head title="Loyalty Settings" />

            <form onSubmit={submit} className="space-y-5">
                <div className="flex flex-col gap-3 rounded-lg border bg-card p-5 shadow-sm md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">Loyalty Settings</h2>
                        <p className="mt-1 text-sm text-muted-foreground">{isp.name}</p>
                    </div>
                    <PlatformIspSelect
                        isPlatform={isPlatform}
                        isps={isps}
                        value={setting.isp_id}
                        routeName="loyalty.settings"
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Settings className="h-4 w-4" />
                            Point Rules
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <input type="hidden" name="isp_id" value={data.isp_id} />

                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <Label>Enable Loyalty</Label>
                                <p className="text-xs text-muted-foreground">Allow automatic and manual point awards.</p>
                            </div>
                            <Switch checked={data.enabled} onCheckedChange={(checked) => setData('enabled', checked)} />
                        </div>

                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <Label>Auto Voucher</Label>
                                <p className="text-xs text-muted-foreground">Generate vouchers when customers reach the threshold.</p>
                            </div>
                            <Switch checked={data.auto_generate_voucher} onCheckedChange={(checked) => setData('auto_generate_voucher', checked)} />
                        </div>

                        <div>
                            <Label htmlFor="default-points">Default Points Per Payment</Label>
                            <Input
                                id="default-points"
                                className="mt-1"
                                type="number"
                                min="0"
                                value={data.default_points_per_payment}
                                onChange={(event) => setData('default_points_per_payment', event.target.value)}
                            />
                            {error('default_points_per_payment')}
                        </div>

                        <div>
                            <Label htmlFor="points-per-amount">Points Per Amount Step</Label>
                            <Input
                                id="points-per-amount"
                                className="mt-1"
                                type="number"
                                min="0"
                                value={data.points_per_amount}
                                onChange={(event) => setData('points_per_amount', event.target.value)}
                            />
                            {error('points_per_amount')}
                        </div>

                        <div>
                            <Label htmlFor="amount-step">Amount Step</Label>
                            <Input
                                id="amount-step"
                                className="mt-1"
                                type="number"
                                min="0.01"
                                step="0.01"
                                value={data.amount_step}
                                onChange={(event) => setData('amount_step', event.target.value)}
                            />
                            {error('amount_step')}
                        </div>

                        <div>
                            <Label htmlFor="points-expiry">Points Expiry Days</Label>
                            <Input
                                id="points-expiry"
                                className="mt-1"
                                type="number"
                                min="1"
                                value={data.points_expiry_days}
                                onChange={(event) => setData('points_expiry_days', event.target.value)}
                            />
                            {error('points_expiry_days')}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Gift className="h-4 w-4" />
                            Voucher Defaults
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-3">
                        <div>
                            <Label htmlFor="voucher-threshold">Voucher Threshold</Label>
                            <Input
                                id="voucher-threshold"
                                className="mt-1"
                                type="number"
                                min="1"
                                value={data.voucher_threshold}
                                onChange={(event) => setData('voucher_threshold', event.target.value)}
                            />
                            {error('voucher_threshold')}
                        </div>

                        <div>
                            <Label htmlFor="voucher-package">Voucher Package/Profile</Label>
                            <Input
                                id="voucher-package"
                                className="mt-1"
                                list="loyalty-package-options"
                                value={data.voucher_package_name}
                                onChange={(event) => setData('voucher_package_name', event.target.value)}
                            />
                            <datalist id="loyalty-package-options">
                                {packageOptions.map((option) => (
                                    <option key={option.value} value={option.value}>{option.label}</option>
                                ))}
                            </datalist>
                            {error('voucher_package_name')}
                        </div>

                        <div>
                            <Label htmlFor="voucher-duration">Voucher Duration Minutes</Label>
                            <Input
                                id="voucher-duration"
                                className="mt-1"
                                type="number"
                                min="1"
                                value={data.voucher_duration_minutes}
                                onChange={(event) => setData('voucher_duration_minutes', event.target.value)}
                            />
                            {error('voucher_duration_minutes')}
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button type="submit" disabled={processing}>
                        <Save className="h-4 w-4" />
                        {processing ? 'Saving...' : 'Save Settings'}
                    </Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
