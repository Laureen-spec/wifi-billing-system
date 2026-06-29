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
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Gift, Save } from 'lucide-react';
import { FormEvent } from 'react';
import { IspOption, Option } from '../components';

type RuleForm = {
    isp_id: string;
    name: string;
    trigger_type: string;
    points_value: string;
    amount_step: string;
    renewal_count: string;
    auto_voucher: boolean;
    voucher_threshold: string;
    voucher_package_name: string;
    voucher_duration_minutes: string;
    is_active: boolean;
};

type RulePayload = Partial<RuleForm> & {
    id?: number | null;
    isp_id?: number | string | null;
    points_value?: number | string;
    amount_step?: number | string | null;
    renewal_count?: number | string | null;
    voucher_threshold?: number | string | null;
    voucher_duration_minutes?: number | string | null;
};

type Props = {
    mode: 'create' | 'edit';
    rule: RulePayload;
    isPlatform: boolean;
    isps: IspOption[];
    triggerOptions: Option[];
    storeUrl?: string;
    updateUrl?: string;
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

const value = (input: unknown) => input === null || input === undefined ? '' : String(input);

export default function LoyaltyRuleForm({ mode, rule, isPlatform, isps, triggerOptions, storeUrl, updateUrl }: Props) {
    const isEdit = mode === 'edit';
    const { data, setData, post, put, processing, errors } = useForm<RuleForm>({
        isp_id: value(rule.isp_id || isps[0]?.id || ''),
        name: value(rule.name),
        trigger_type: value(rule.trigger_type || 'successful_payment'),
        points_value: value(rule.points_value ?? 10),
        amount_step: value(rule.amount_step),
        renewal_count: value(rule.renewal_count),
        auto_voucher: Boolean(rule.auto_voucher),
        voucher_threshold: value(rule.voucher_threshold),
        voucher_package_name: value(rule.voucher_package_name),
        voucher_duration_minutes: value(rule.voucher_duration_minutes),
        is_active: rule.is_active !== false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && updateUrl) {
            put(updateUrl, { preserveScroll: true });
            return;
        }

        post(storeUrl || route('loyalty.rules.store'), { preserveScroll: true });
    };

    const error = (key: keyof RuleForm) => errors[key] ? <p className="mt-1 text-xs text-destructive">{errors[key]}</p> : null;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Loyalty', url: route('loyalty.index') },
                { label: 'Reward Rules', url: route('loyalty.rules.index') },
                { label: isEdit ? 'Edit Rule' : 'New Rule' },
            ]}
            pageTitle={isEdit ? 'Edit Reward Rule' : 'New Reward Rule'}
        >
            <Head title={isEdit ? 'Edit Reward Rule' : 'New Reward Rule'} />

            <form onSubmit={submit} className="space-y-5">
                <div className="flex flex-col gap-3 rounded-lg border bg-card p-5 shadow-sm md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">{isEdit ? 'Edit Reward Rule' : 'New Reward Rule'}</h2>
                        <p className="mt-1 text-sm text-muted-foreground">Configure how customers earn points and vouchers.</p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={route('loyalty.rules.index')}>
                            <ArrowLeft className="h-4 w-4" />
                            Back
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Gift className="h-4 w-4" />
                            Rule Details
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        {isPlatform && (
                            <div>
                                <Label>ISP</Label>
                                <Select value={data.isp_id} onValueChange={(selected) => setData('isp_id', selected)}>
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Select ISP" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {isps.map((isp) => (
                                            <SelectItem key={isp.id} value={String(isp.id)}>{isp.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {error('isp_id')}
                            </div>
                        )}

                        <div className={isPlatform ? '' : 'md:col-span-2'}>
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                className="mt-1"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                            />
                            {error('name')}
                        </div>

                        <div>
                            <Label>Trigger</Label>
                            <Select value={data.trigger_type} onValueChange={(selected) => setData('trigger_type', selected)}>
                                <SelectTrigger className="mt-1">
                                    <SelectValue placeholder="Select trigger" />
                                </SelectTrigger>
                                <SelectContent>
                                    {triggerOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {error('trigger_type')}
                        </div>

                        <div>
                            <Label htmlFor="points-value">Points Value</Label>
                            <Input
                                id="points-value"
                                className="mt-1"
                                type="number"
                                min="0"
                                value={data.points_value}
                                onChange={(event) => setData('points_value', event.target.value)}
                            />
                            {error('points_value')}
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
                            <Label htmlFor="renewal-count">Renewal Count</Label>
                            <Input
                                id="renewal-count"
                                className="mt-1"
                                type="number"
                                min="1"
                                value={data.renewal_count}
                                onChange={(event) => setData('renewal_count', event.target.value)}
                            />
                            {error('renewal_count')}
                        </div>

                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <Label>Active</Label>
                                <p className="text-xs text-muted-foreground">Rule can award points.</p>
                            </div>
                            <Switch checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked)} />
                        </div>

                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <Label>Auto Voucher</Label>
                                <p className="text-xs text-muted-foreground">Create a voucher at the threshold.</p>
                            </div>
                            <Switch checked={data.auto_voucher} onCheckedChange={(checked) => setData('auto_voucher', checked)} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Voucher Settings</CardTitle>
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
                            <Label htmlFor="voucher-package">Package/Profile</Label>
                            <Input
                                id="voucher-package"
                                className="mt-1"
                                value={data.voucher_package_name}
                                onChange={(event) => setData('voucher_package_name', event.target.value)}
                            />
                            {error('voucher_package_name')}
                        </div>

                        <div>
                            <Label htmlFor="voucher-duration">Duration Minutes</Label>
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

                <div className="flex justify-end gap-2">
                    <Button variant="outline" asChild><Link href={route('loyalty.rules.index')}>Cancel</Link></Button>
                    <Button type="submit" disabled={processing}>
                        <Save className="h-4 w-4" />
                        {processing ? 'Saving...' : 'Save Rule'}
                    </Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
