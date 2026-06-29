import { useForm } from '@inertiajs/react';
import { FormEventHandler, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Checkbox } from '@/components/ui/checkbox';
import { InputError } from '@/components/ui/input-error';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { SubscriptionInfo } from '@/components/ui/subscription-info';
import { Wifi, Users, RadioTower, Percent, Search, CheckCircle2, Save } from 'lucide-react';

interface Plan {
    id?: number;
    name: string;
    description: string;
    number_of_users: number;
    status: boolean;
    free_plan: boolean;
    modules: string[];
    package_price_yearly: number;
    package_price_monthly: number;
    storage_limit?: number;
    trial: boolean;
    trial_days: number;
    hotspot_revenue_fee_percent?: number;
    router_limit?: number | null;
}

interface Module {
    module: string;
    alias: string;
    image: string;
}

interface UserSubscriptionInfo {
    is_superadmin: boolean;
    active_plan_id?: number;
    available_modules_count: number;
}

interface CurrencySettings {
    code?: string;
    symbol?: string;
    symbolPosition?: 'before' | 'after' | string;
    symbolSpace?: boolean;
    decimalPlaces?: number;
    decimalSeparator?: string;
    thousandSeparator?: string;
}

interface Props {
    plan?: Plan;
    activeModules: Module[];
    isEdit?: boolean;
    userSubscriptionInfo?: UserSubscriptionInfo;
    currencySettings?: CurrencySettings;
}

function PlanForm({ plan, activeModules, isEdit = false, userSubscriptionInfo, currencySettings }: Props) {
    const { t } = useTranslation();
    const [moduleSearch, setModuleSearch] = useState('');

    const formatCurrency = (amount: number | string) => {
        const settings = currencySettings || {};
        const num = Number(amount || 0);
        const decimalPlaces = Number.isFinite(Number(settings.decimalPlaces)) ? Number(settings.decimalPlaces) : 0;
        const decimalSeparator = settings.decimalSeparator || '.';
        const thousandSeparator = settings.thousandSeparator ?? ',';
        const symbol = settings.symbol || settings.code || 'KES';
        const symbolPosition = settings.symbolPosition || 'before';
        const space = settings.symbolSpace === false ? '' : ' ';
        const fixed = num.toFixed(decimalPlaces);
        const parts = fixed.split('.');
        if (thousandSeparator) {
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
        }
        const formatted = parts.join(decimalSeparator);
        return symbolPosition === 'after' ? `${formatted}${space}${symbol}` : `${symbol}${space}${formatted}`;
    };

    const { data, setData, post, put, processing, errors } = useForm({
        name: plan?.name || '',
        description: plan?.description || '',
        number_of_users: plan?.number_of_users || 50,
        storage_limit: plan?.storage_limit || 0,
        status: plan?.status ?? true,
        free_plan: plan?.free_plan ?? false,
        modules: plan?.modules || [],
        package_price_yearly: plan?.package_price_yearly || 0,
        package_price_monthly: plan?.package_price_monthly || 300,
        trial: plan?.trial ?? false,
        trial_days: plan?.trial_days || 14,
        hotspot_revenue_fee_percent: plan?.hotspot_revenue_fee_percent ?? 2.5,
        router_limit: plan?.router_limit ?? null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (isEdit && plan) {
            put(route('plans.update', plan.id));
        } else {
            post(route('plans.store'));
        }
    };

    const filteredModules = useMemo(() => {
        const term = moduleSearch.toLowerCase();
        return activeModules.filter((module) => module.alias.toLowerCase().includes(term) || module.module.toLowerCase().includes(term));
    }, [activeModules, moduleSearch]);

    const handleModuleChange = (moduleName: string, checked: boolean) => {
        if (checked) {
            setData('modules', [...data.modules, moduleName]);
        } else {
            setData('modules', data.modules.filter((module) => module !== moduleName));
        }
    };

    const allFilteredSelected = filteredModules.length > 0 && filteredModules.every((module) => data.modules.includes(module.module));

    const toggleFilteredModules = () => {
        if (allFilteredSelected) {
            setData('modules', data.modules.filter((module) => !filteredModules.map((item) => item.module).includes(module)));
        } else {
            setData('modules', [...new Set([...data.modules, ...filteredModules.map((module) => module.module)])]);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid grid-cols-12 gap-6">
                <div className="col-span-12 space-y-4 lg:col-span-3">
                    {userSubscriptionInfo && (
                        <SubscriptionInfo userSubscriptionInfo={userSubscriptionInfo} totalModulesCount={activeModules.length} />
                    )}

                    <Card className="rounded-[24px]">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm">{t('Quick Settings')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <Label className="text-sm">{t('Active')}</Label>
                                <Switch checked={data.status} onCheckedChange={(checked) => setData('status', checked)} />
                            </div>
                            <div className="flex items-center justify-between">
                                <Label className="text-sm">{t('Trial')}</Label>
                                <Switch checked={data.trial} onCheckedChange={(checked) => setData('trial', checked)} />
                            </div>
                            <div className="flex items-center justify-between">
                                <Label className="text-sm">{t('Free')}</Label>
                                <Switch checked={data.free_plan} onCheckedChange={(checked) => setData('free_plan', checked)} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="rounded-[24px] border-emerald-100 bg-emerald-50/50">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-sm text-emerald-900"><Wifi className="h-4 w-4" />{t('ISP pricing rule')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm text-emerald-900">
                            <p>{t('PPPoE is a flat platform fee.')}</p>
                            <p>{t('Hotspot fee is charged only on successful hotspot payments.')}</p>
                            <p className="font-semibold">{t('Routers are displayed as Unlimited.')}</p>
                        </CardContent>
                    </Card>

                    {data.trial && (
                        <Card className="rounded-[24px]">
                            <CardHeader className="pb-3"><CardTitle className="text-sm">{t('Trial Settings')}</CardTitle></CardHeader>
                            <CardContent>
                                <Label>{t('Trial Days')}</Label>
                                <Input type="number" value={data.trial_days || 0} onChange={(e) => setData('trial_days', parseInt(e.target.value) || 0)} />
                                <InputError message={errors.trial_days} />
                            </CardContent>
                        </Card>
                    )}
                </div>

                <div className="col-span-12 space-y-6 lg:col-span-9">
                    <Card className="rounded-[28px]">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-2xl"><Wifi className="h-5 w-5 text-emerald-600" />{t('ISP Plan Information')}</CardTitle>
                            <p className="text-sm text-muted-foreground">{t('Build plans around PPPoE subscribers, unlimited routers, and hotspot revenue fee.')}</p>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="grid gap-4 lg:grid-cols-2">
                                <div>
                                    <Label required>{t('Plan Name')}</Label>
                                    <Input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder={t('Starter Plan')} />
                                    <InputError message={errors.name} />
                                </div>
                                <div>
                                    <Label>{t('PPPoE User Limit')}</Label>
                                    <Input type="number" value={data.number_of_users || ''} onChange={(e) => setData('number_of_users', parseInt(e.target.value) || 0)} placeholder={t('50')} />
                                    <p className="mt-1 text-xs text-muted-foreground">{t('Use -1 for unlimited PPPoE users.')}</p>
                                    <InputError message={errors.number_of_users} />
                                </div>
                            </div>

                            <div>
                                <Label>{t('Description')}</Label>
                                <Textarea value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} placeholder={t('For small ISP or WiFi operators.')} />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-4 lg:grid-cols-4">
                                <div className="rounded-2xl border p-4">
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground"><Users className="h-4 w-4 text-emerald-600" />{t('PPPoE users')}</div>
                                    <div className="mt-2 font-semibold">{Number(data.number_of_users || 0) === -1 ? t('Unlimited') : Number(data.number_of_users || 0).toLocaleString()}</div>
                                </div>
                                <div className="rounded-2xl border p-4">
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground"><RadioTower className="h-4 w-4 text-blue-600" />{t('Routers')}</div>
                                    <div className="mt-2 font-semibold">{t('Unlimited')}</div>
                                </div>
                                <div className="rounded-2xl border p-4">
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground"><Percent className="h-4 w-4 text-amber-600" />{t('Hotspot fee')}</div>
                                    <div className="mt-2 font-semibold">{data.hotspot_revenue_fee_percent || 0}%</div>
                                </div>
                                <div className="rounded-2xl border p-4">
                                    <div className="text-sm text-muted-foreground">{t('Selected add-ons')}</div>
                                    <div className="mt-2 font-semibold">{data.modules.length}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="rounded-[28px]">
                        <CardHeader>
                            <CardTitle>{t('Pricing')}</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 lg:grid-cols-3">
                            <div>
                                <Label>{t('Monthly Price')}</Label>
                                <Input type="number" step="0.01" value={data.package_price_monthly || 0} onChange={(e) => setData('package_price_monthly', parseFloat(e.target.value) || 0)} />
                                <p className="mt-1 text-xs text-muted-foreground">{formatCurrency(data.package_price_monthly || 0)} / {t('month')}</p>
                                <InputError message={errors.package_price_monthly} />
                            </div>
                            <div>
                                <Label>{t('Yearly Price')}</Label>
                                <Input type="number" step="0.01" value={data.package_price_yearly || 0} onChange={(e) => setData('package_price_yearly', parseFloat(e.target.value) || 0)} />
                                <p className="mt-1 text-xs text-muted-foreground">{formatCurrency(data.package_price_yearly || 0)} / {t('year')}</p>
                                <InputError message={errors.package_price_yearly} />
                            </div>
                            <div>
                                <Label>{t('Hotspot Revenue Fee %')}</Label>
                                <Input type="number" step="0.01" value={data.hotspot_revenue_fee_percent || 0} onChange={(e) => setData('hotspot_revenue_fee_percent', parseFloat(e.target.value) || 0)} />
                                <p className="mt-1 text-xs text-muted-foreground">{t('Default recommended fee is 2.5%.')}</p>
                                <InputError message={errors.hotspot_revenue_fee_percent} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="rounded-[28px]">
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                {t('Add-Ons')}
                                <Badge>{data.modules.length} {t('selected')}</Badge>
                            </CardTitle>
                            <div className="flex gap-2">
                                <div className="relative flex-1">
                                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input value={moduleSearch} onChange={(e) => setModuleSearch(e.target.value)} placeholder={t('Search add-ons...')} className="pl-9" />
                                </div>
                                <Button type="button" variant="outline" onClick={toggleFilteredModules}>{allFilteredSelected ? t('Uncheck All') : t('Check All')}</Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {filteredModules.map((module) => {
                                    const checked = data.modules.includes(module.module);
                                    return (
                                        <label key={module.module} className={`flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition ${checked ? 'border-emerald-300 bg-emerald-50' : 'hover:bg-muted/40'}`}>
                                            <Checkbox checked={checked} onCheckedChange={(value) => handleModuleChange(module.module, Boolean(value))} className="mt-1" />
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2 font-medium">
                                                    {checked && <CheckCircle2 className="h-4 w-4 text-emerald-600" />}
                                                    <span>{module.alias}</span>
                                                </div>
                                                <div className="text-xs text-muted-foreground">{module.module}</div>
                                            </div>
                                        </label>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? t('Saving...') : t('Save Plan')}
                        </Button>
                    </div>
                </div>
            </div>
        </form>
    );
}

export default PlanForm;
