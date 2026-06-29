import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import { AlertTriangle, ArrowRight, CheckCircle2, CreditCard, XCircle, Plus, Edit, Trash2, Search, Package, Boxes, Users, HardDrive, Settings2, Wifi, ShieldCheck, Clock, RadioTower, Percent } from 'lucide-react';

interface Plan {
    id: number;
    name: string;
    description?: string;
    number_of_users: number;
    custom_plan: boolean;
    status: boolean;
    free_plan: boolean;
    modules: string[];
    package_price_yearly: number;
    package_price_monthly: number;
    storage_limit: number;
    trial: boolean;
    trial_days: number;
    orders_count?: number;
    creator?: { name: string };
}

interface Module {
    module: string;
    alias: string;
    image: string;
    monthly_price: number;
    yearly_price: number;
    is_enable?: boolean;
}

interface Props {
    plans: Plan[];
    canCreate: boolean;
    activeModules: Module[];
    createPackageEnabled: boolean;
    customDesignPackageEnabled: boolean;
    userTrialInfo?: { is_trial_done?: number } | null;
    planExpireDate?: string | null;
    activePlanId?: number | null;
    isSuperAdmin?: boolean;
    mode?: 'manage' | 'subscribe';
    planUsage?: {
        pppoeUsers: number;
        routersCount: number;
        hotspotRevenueThisMonth: number;
    };
    currencySettings?: {
        code?: string;
        symbol?: string;
        symbolPosition?: 'before' | 'after' | string;
        symbolSpace?: boolean;
        decimalPlaces?: number;
        decimalSeparator?: string;
        thousandSeparator?: string;
    };
}

export default function PlansIndex({ plans, canCreate, activeModules, createPackageEnabled, customDesignPackageEnabled, userTrialInfo, planExpireDate, activePlanId, isSuperAdmin: isSuperAdminProp, mode, planUsage, currencySettings }: Props) {
    const { t } = useTranslation();
    const { auth, flash } = usePage().props as any;
    const isSuperAdmin = Boolean(isSuperAdminProp ?? auth?.user?.roles?.includes('superadmin'));
    const subscriptionMode = mode === 'subscribe' || !isSuperAdmin;
    const [search, setSearch] = useState('');
    const [deletingPlan, setDeletingPlan] = useState<Plan | null>(null);
    const [localCreatePackage, setLocalCreatePackage] = useState(Boolean(createPackageEnabled));
    const [localCustomDesignPackage, setLocalCustomDesignPackage] = useState(Boolean(customDesignPackageEnabled));

    useFlashMessages();

    const enabledAddons = activeModules.filter((module) => module.is_enable !== false);
    const disabledAddons = activeModules.filter((module) => module.is_enable === false);

    const filteredPlans = useMemo(() => {
        const term = search.toLowerCase();
        return plans.filter((plan) =>
            plan.name.toLowerCase().includes(term) ||
            (plan.description || '').toLowerCase().includes(term)
        );
    }, [plans, search]);

    const stats = useMemo(() => ({
        total: plans.length,
        active: plans.filter((plan) => plan.status).length,
        disabled: plans.filter((plan) => !plan.status).length,
        addons: enabledAddons.length,
    }), [plans, enabledAddons.length]);

    const handlePackageSettingUpdate = (nextCreate: boolean, nextCustom: boolean) => {
        if (!nextCreate && !nextCustom) return;

        setLocalCreatePackage(nextCreate);
        setLocalCustomDesignPackage(nextCustom);

        router.post(route('plans.package-settings.update'), {
            create_package_enabled: nextCreate ? 'on' : 'off',
            custom_design_package_enabled: nextCustom ? 'on' : 'off',
        }, {
            preserveScroll: true,
        });
    };

    const confirmDelete = () => {
        if (!deletingPlan) return;
        router.delete(route('plans.destroy', deletingPlan.id), {
            preserveScroll: true,
            onFinish: () => setDeletingPlan(null),
        });
    };

    const storageGB = (plan: Plan) => Math.round(Number(plan.storage_limit || 0) / (1024 * 1024));
    const planModules = (plan: Plan) => enabledAddons.filter((module) => plan.modules?.includes(module.module));
    const trialAvailable = (plan: Plan) => Boolean(plan.trial) && ![1, 2].includes(Number(userTrialInfo?.is_trial_done ?? 0));
    const startTrial = (plan: Plan) => {
        router.post(route('plans.start-trial', plan.id), {}, { preserveScroll: true });
    };
    const formatPlanCurrency = (amount: number) => {
        const settings = currencySettings || {};
        const decimalPlaces = Number.isFinite(Number(settings.decimalPlaces)) ? Number(settings.decimalPlaces) : 0;
        const decimalSeparator = settings.decimalSeparator || '.';
        const thousandSeparator = settings.thousandSeparator ?? ',';
        const symbol = settings.symbol || settings.code || 'KES';
        const symbolPosition = settings.symbolPosition || 'before';
        const space = settings.symbolSpace === false ? '' : ' ';
        const fixed = Number(amount || 0).toFixed(decimalPlaces);
        const parts = fixed.split('.');
        if (thousandSeparator) {
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
        }
        const formatted = parts.join(decimalSeparator);
        return symbolPosition === 'after' ? `${formatted}${space}${symbol}` : `${symbol}${space}${formatted}`;
    };
    const pppoeLimitLabel = (plan: Plan) => {
        const limit = Number(plan.number_of_users || 0);
        if (limit === -1 || limit >= 1000) return t('Unlimited PPPoE users');
        if (limit <= 0) return t('Setup/testing only');
        if (limit <= 50) return t('Up to 50 PPPoE users');
        return `${limit.toLocaleString('en-KE')} ${t('PPPoE users')}`;
    };
    const routerLimitLabel = (plan: Plan) => {
        const limit = Number(plan.number_of_users || 0);
        if (limit === -1 || limit > 200) return t('Multiple routers');
        if (limit <= 50) return t('1 router included');
        if (limit <= 200) return t('Up to 3 routers');
        return t('Multiple routers');
    };
    const planBestFor = (plan: Plan) => {
        const name = `${plan.name || ''} ${plan.description || ''}`.toLowerCase();
        const limit = Number(plan.number_of_users || 0);
        if (plan.free_plan || name.includes('free')) return t('For testing and setup only.');
        if (limit <= 50 || name.includes('starter')) return t('For small ISP or WiFi operators.');
        if (limit <= 200 || name.includes('growth')) return t('For growing PPPoE and hotspot teams.');
        return t('For established ISPs with multiple routers.');
    };
    const activePlanName = plans.find((plan) => Number(plan.id) === Number(activePlanId || auth?.user?.active_plan || 0))?.name || t('No active plan');
    const usage = {
        pppoeUsers: Number(planUsage?.pppoeUsers || 0),
        routersCount: Number(planUsage?.routersCount || 0),
        hotspotRevenueThisMonth: Number(planUsage?.hotspotRevenueThisMonth || 0),
    };

    const planAccessNotice = String(flash?.error || '').toLowerCase().includes('plan has expired')
        ? {
            title: t('Subscription renewal required'),
            description: t('Your workspace plan has expired. Renew your subscription to restore full access to billing, customers, routers, and payment tools.'),
            action: t('Renew subscription'),
        }
        : null;

    if (subscriptionMode) {
        return (
            <AuthenticatedLayout
                breadcrumbs={[{ label: t('Subscription Plans') }]}
                pageTitle={t('Subscription Plans')}
            >
                <Head title={t('Subscription Plans')} />

                <div className="space-y-6">
                    {planAccessNotice && (
                        <div className="overflow-hidden rounded-[28px] border border-amber-200 bg-gradient-to-r from-amber-50 via-white to-emerald-50 shadow-sm">
                            <div className="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
                                <div className="flex gap-4">
                                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                                        <AlertTriangle className="h-6 w-6" />
                                    </div>
                                    <div>
                                        <div className="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-700">
                                            <CreditCard className="h-3.5 w-3.5" />
                                            {t('Account access')}
                                        </div>
                                        <h2 className="mt-2 text-xl font-bold text-slate-950">{planAccessNotice.title}</h2>
                                        <p className="mt-1 max-w-3xl text-sm leading-6 text-slate-600">{planAccessNotice.description}</p>
                                    </div>
                                </div>

                                <Button asChild className="shrink-0 rounded-2xl px-5">
                                    <a href="#available-packages">
                                        {planAccessNotice.action}
                                        <ArrowRight className="h-4 w-4" />
                                    </a>
                                </Button>
                            </div>
                        </div>
                    )}

                    <div className="overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-sm">
                        <div className="grid gap-6 p-6 lg:grid-cols-[1.35fr_0.65fr] lg:items-center">
                            <div>
                                <div className="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">
                                    <Wifi className="h-4 w-4" />
                                    {t('ISP Billing Plans')}
                                </div>
                                <h1 className="text-3xl font-bold tracking-tight text-slate-950 md:text-4xl">{t('Choose your ISP package')}</h1>
                                <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                                    {t('Pick a simple flat-rate PPPoE plan. Hotspot revenue is charged separately at 2.5% only when the system collects successful hotspot payments.')}
                                </p>
                            </div>
                            <div className="rounded-[26px] border border-emerald-100 bg-emerald-50/70 p-5">
                                <div className="text-sm font-semibold text-emerald-800">{t('Pricing rule')}</div>
                                <div className="mt-2 text-2xl font-bold text-emerald-950">{formatPlanCurrency(300)}</div>
                                <div className="text-sm text-emerald-800">{t('flat monthly fee for up to 50 PPPoE users')}</div>
                                <div className="mt-4 flex items-center gap-2 rounded-2xl bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm">
                                    <Percent className="h-4 w-4 text-emerald-600" />
                                    {t('2.5% hotspot revenue fee')}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div className="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-slate-500">{t('Active PPPoE users')}</div>
                                <Users className="h-5 w-5 text-emerald-600" />
                            </div>
                            <div className="mt-3 text-2xl font-bold text-slate-950">{usage.pppoeUsers.toLocaleString('en-KE')}</div>
                            <div className="mt-1 text-xs text-slate-500">{t('Used to recommend the right plan')}</div>
                        </div>
                        <div className="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-slate-500">{t('Routers')}</div>
                                <RadioTower className="h-5 w-5 text-blue-600" />
                            </div>
                            <div className="mt-3 text-2xl font-bold text-slate-950">{usage.routersCount.toLocaleString('en-KE')}</div>
                            <div className="mt-1 text-xs text-slate-500">{t('MikroTik routers connected')}</div>
                        </div>
                        <div className="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-slate-500">{t('Hotspot revenue')}</div>
                                <Package className="h-5 w-5 text-amber-600" />
                            </div>
                            <div className="mt-3 text-2xl font-bold text-slate-950">{formatPlanCurrency(usage.hotspotRevenueThisMonth)}</div>
                            <div className="mt-1 text-xs text-slate-500">{t('Successful hotspot payments this month')}</div>
                        </div>
                        <div className="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-slate-500">{t('Current plan')}</div>
                                <ShieldCheck className="h-5 w-5 text-purple-600" />
                            </div>
                            <div className="mt-3 truncate text-2xl font-bold text-slate-950">{activePlanName}</div>
                            <div className="mt-1 text-xs text-slate-500">{planExpireDate ? `${t('Expires')}: ${planExpireDate}` : t('No expiry date')}</div>
                        </div>
                    </div>

                    <div id="available-packages" className="scroll-mt-6 rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h2 className="text-lg font-bold text-slate-950">{t('Available packages')}</h2>
                                <p className="text-sm text-slate-500">{t('Choose a package based on subscribers, routers, and hotspot collections.')}</p>
                            </div>
                            <div className="relative w-full lg:w-80">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder={t('Search plans...')} className="pl-9" />
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        {filteredPlans.length > 0 ? filteredPlans.map((plan) => {
                            const included = planModules(plan);
                            const isCurrentPlan = Number(activePlanId || auth?.user?.active_plan || 0) === Number(plan.id);
                            const monthlyPrice = Number(plan.package_price_monthly || 0);
                            const yearlyPrice = Number(plan.package_price_yearly || 0);
                            return (
                                <div key={plan.id} className={`flex h-full flex-col overflow-hidden rounded-[30px] border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md ${isCurrentPlan ? 'border-emerald-300 ring-2 ring-emerald-100' : 'border-slate-200'}`}>
                                    <div className="p-6">
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <h2 className="text-xl font-bold text-slate-950">{plan.name}</h2>
                                                <p className="mt-2 text-sm leading-6 text-slate-500">{plan.description || planBestFor(plan)}</p>
                                            </div>
                                            {isCurrentPlan && <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">{t('Current')}</Badge>}
                                        </div>

                                        <div className="mt-6 rounded-[24px] bg-slate-50 p-5">
                                            <div className="flex items-end gap-2">
                                                <div className="text-3xl font-bold text-slate-950">{formatPlanCurrency(monthlyPrice)}</div>
                                                <div className="pb-1 text-sm text-slate-500">/ {t('month')}</div>
                                            </div>
                                            <div className="mt-1 text-sm text-slate-500">{yearlyPrice > 0 ? `${formatPlanCurrency(yearlyPrice)} / ${t('year')}` : t('Yearly billing not configured')}</div>
                                            <div className="mt-3 text-sm font-medium text-emerald-700">{planBestFor(plan)}</div>
                                        </div>

                                        <div className="mt-5 grid gap-3 text-sm">
                                            <div className="flex items-center justify-between rounded-2xl border border-slate-100 p-3">
                                                <span className="flex items-center gap-2 text-slate-600"><Users className="h-4 w-4 text-emerald-600" />{t('PPPoE users')}</span>
                                                <span className="font-semibold text-slate-950">{pppoeLimitLabel(plan)}</span>
                                            </div>
                                            <div className="flex items-center justify-between rounded-2xl border border-slate-100 p-3">
                                                <span className="flex items-center gap-2 text-slate-600"><RadioTower className="h-4 w-4 text-blue-600" />{t('Routers')}</span>
                                                <span className="font-semibold text-slate-950">{routerLimitLabel(plan)}</span>
                                            </div>
                                            <div className="flex items-center justify-between rounded-2xl border border-slate-100 p-3">
                                                <span className="flex items-center gap-2 text-slate-600"><Percent className="h-4 w-4 text-amber-600" />{t('Hotspot fee')}</span>
                                                <span className="font-semibold text-slate-950">2.5%</span>
                                            </div>
                                        </div>

                                        <div className="mt-6 border-t border-slate-100 pt-5">
                                            <h3 className="text-sm font-semibold text-slate-950">{t('Included modules')}</h3>
                                            <div className="mt-3 space-y-2">
                                                {included.length > 0 ? included.map((module) => (
                                                    <div key={module.module} className="flex items-center gap-2 text-sm text-slate-700">
                                                        <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                                        <span>{module.alias}</span>
                                                    </div>
                                                )) : (
                                                    <div className="text-sm text-slate-500">{t('No add-ons included')}</div>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-auto space-y-3 border-t border-slate-100 bg-slate-50/70 p-6">
                                        {isCurrentPlan ? (
                                            <Button className="w-full" disabled>{t('Current Plan')}</Button>
                                        ) : (
                                            <Button className="w-full" asChild>
                                                <Link href={route('plans.subscribe', plan.id)}>
                                                    {t('Subscribe to Plan')}
                                                </Link>
                                            </Button>
                                        )}
                                        {trialAvailable(plan) && (
                                            <Button variant="outline" className="w-full bg-white" onClick={() => startTrial(plan)}>
                                                <Clock className="mr-2 h-4 w-4" />
                                                {t('Start Trial')} ({plan.trial_days || 14} {t('days')})
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            );
                        }) : (
                            <div className="col-span-full rounded-[28px] border border-slate-200 bg-white py-16 text-center shadow-sm">
                                <ShieldCheck className="mx-auto mb-3 h-10 w-10 text-slate-300" />
                                <h3 className="text-lg font-semibold text-slate-950">{t('No plans found')}</h3>
                                <p className="text-sm text-slate-500">{t('Try a different search or contact support.')}</p>
                            </div>
                        )}
                    </div>

                    <div className="rounded-[26px] border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                        <div className="flex gap-3">
                            <Percent className="mt-0.5 h-5 w-5 flex-none" />
                            <div>
                                <div className="font-semibold">{t('Billing note')}</div>
                                <p className="mt-1 leading-6">
                                    {t('PPPoE billing is charged as a flat monthly platform fee. Hotspot billing uses a 2.5% revenue fee only on successful hotspot payments collected through the system. Manual cash payments and PPPoE flat billing are not charged this percentage.')}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Plans') }]}
            pageTitle={t('Plans')}
        >
            <Head title={t('Plans')} />

            <div className="space-y-6">
                <div className="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-3 inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
                                <Wifi className="h-4 w-4" />
                                {t('Plan Builder')}
                            </div>
                            <h1 className="text-3xl font-bold tracking-tight text-slate-950">{t('Plans')}</h1>
                            <p className="mt-2 max-w-3xl text-sm text-slate-600">
                                {t('Create packages, assign included add-ons, and control which plans are visible for customers.')}
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <Button variant="outline" asChild>
                                <Link href={route('addons.page')}>{t('Manage Add-ons')}</Link>
                            </Button>
                            {canCreate && (
                                <Button asChild>
                                    <Link href={route('plans.create')}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        {t('New Plan')}
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {isSuperAdmin && (
                    <div className="grid gap-4 lg:grid-cols-2">
                        <div className="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <div className="flex items-center gap-2 font-semibold text-slate-950">
                                        <Settings2 className="h-5 w-5 text-blue-600" />
                                        {t('Package assignment settings')}
                                    </div>
                                    <p className="mt-1 text-sm text-slate-500">{t('Choose what admins can assign when creating subscriptions.')}</p>
                                </div>
                            </div>
                            <div className="mt-5 space-y-4">
                                <div className="flex items-center justify-between rounded-2xl bg-slate-50 p-4">
                                    <div>
                                        <div className="font-medium text-slate-900">{t('Pre-built packages')}</div>
                                        <div className="text-xs text-slate-500">{t('Allow admins to assign ready-made plans.')}</div>
                                    </div>
                                    <Switch
                                        checked={localCreatePackage}
                                        onCheckedChange={(checked) => handlePackageSettingUpdate(checked, localCustomDesignPackage)}
                                    />
                                </div>
                                <div className="flex items-center justify-between rounded-2xl bg-slate-50 p-4">
                                    <div>
                                        <div className="font-medium text-slate-900">{t('Custom package design')}</div>
                                        <div className="text-xs text-slate-500">{t('Allow admins to build custom packages during assignment.')}</div>
                                    </div>
                                    <Switch
                                        checked={localCustomDesignPackage}
                                        onCheckedChange={(checked) => handlePackageSettingUpdate(localCreatePackage, checked)}
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div className="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                                <Package className="mb-3 h-6 w-6 text-blue-600" />
                                <div className="text-2xl font-bold text-slate-950">{stats.total}</div>
                                <div className="text-xs text-slate-500">{t('Total Plans')}</div>
                            </div>
                            <div className="rounded-[24px] border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                                <CheckCircle2 className="mb-3 h-6 w-6 text-emerald-600" />
                                <div className="text-2xl font-bold text-emerald-700">{stats.active}</div>
                                <div className="text-xs text-emerald-700">{t('Active')}</div>
                            </div>
                            <div className="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                                <XCircle className="mb-3 h-6 w-6 text-slate-500" />
                                <div className="text-2xl font-bold text-slate-950">{stats.disabled}</div>
                                <div className="text-xs text-slate-500">{t('Disabled')}</div>
                            </div>
                            <div className="rounded-[24px] border border-blue-200 bg-blue-50 p-5 shadow-sm">
                                <Boxes className="mb-3 h-6 w-6 text-blue-600" />
                                <div className="text-2xl font-bold text-blue-700">{stats.addons}</div>
                                <div className="text-xs text-blue-700">{t('Enabled Add-ons')}</div>
                            </div>
                        </div>
                    </div>
                )}

                <div className="grid gap-6 xl:grid-cols-[1.45fr_0.85fr]">
                    <div className="rounded-[28px] border border-slate-200 bg-white shadow-sm">
                        <div className="flex flex-col gap-4 border-b border-slate-200 p-5 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 className="text-lg font-bold text-slate-950">{t('Pricing packages')}</h2>
                                <p className="text-sm text-slate-500">{t('Enable, edit, or review included add-ons.')}</p>
                            </div>
                            <div className="relative w-full md:w-80">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder={t('Search plans...')} className="pl-9" />
                            </div>
                        </div>

                        <div className="divide-y divide-slate-100">
                            {filteredPlans.length > 0 ? filteredPlans.map((plan) => {
                                const included = planModules(plan);
                                return (
                                    <div key={plan.id} className="grid gap-4 p-5 lg:grid-cols-[1.3fr_0.8fr_0.8fr_auto] lg:items-center">
                                        <div className="flex gap-3">
                                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                                                <Wifi className="h-6 w-6" />
                                            </div>
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <h3 className="font-bold text-slate-950">{plan.name}</h3>
                                                    <Badge className={plan.status ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 hover:bg-slate-100'}>
                                                        {plan.status ? t('Active') : t('Disabled')}
                                                    </Badge>
                                                    {plan.free_plan && <Badge variant="outline">{t('Free')}</Badge>}
                                                </div>
                                                <p className="mt-1 line-clamp-2 text-sm text-slate-500">{plan.description || t('No description')}</p>
                                            </div>
                                        </div>

                                        <div>
                                            <div className="text-sm text-slate-500">{t('Monthly')}</div>
                                            <div className="font-bold text-slate-950">{formatPlanCurrency(Number(plan.package_price_monthly || 0))}</div>
                                            <div className="mt-1 text-xs text-slate-500">{formatPlanCurrency(Number(plan.package_price_yearly || 0))} / {t('year')}</div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-2 text-sm">
                                            <div className="rounded-xl bg-slate-50 p-3">
                                                <div className="flex items-center gap-1 text-xs text-slate-500"><Users className="h-3.5 w-3.5" />{t('Users')}</div>
                                                <div className="font-semibold text-slate-950">{Number(plan.number_of_users) === -1 ? t('Unlimited') : plan.number_of_users}</div>
                                            </div>
                                            <div className="rounded-xl bg-slate-50 p-3">
                                                <div className="flex items-center gap-1 text-xs text-slate-500"><HardDrive className="h-3.5 w-3.5" />{t('Storage')}</div>
                                                <div className="font-semibold text-slate-950">{storageGB(plan)} GB</div>
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap gap-2 lg:justify-end">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route('plans.edit', plan.id)}>
                                                    <Edit className="mr-2 h-4 w-4" />
                                                    {t('Edit')}
                                                </Link>
                                            </Button>
                                            {isSuperAdmin && (
                                                <Button variant="outline" size="sm" onClick={() => setDeletingPlan(plan)} className="text-red-600 hover:text-red-700">
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    {t('Delete')}
                                                </Button>
                                            )}
                                        </div>

                                        <div className="lg:col-span-4">
                                            <div className="flex flex-wrap gap-2">
                                                {included.length > 0 ? included.map((module) => (
                                                    <Badge key={module.module} variant="outline" className="bg-emerald-50 text-emerald-700">
                                                        <CheckCircle2 className="mr-1 h-3.5 w-3.5" />
                                                        {module.alias}
                                                    </Badge>
                                                )) : (
                                                    <span className="text-sm text-slate-500">{t('No add-ons included')}</span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                );
                            }) : (
                                <div className="py-16 text-center">
                                    <ShieldCheck className="mx-auto mb-3 h-10 w-10 text-slate-300" />
                                    <h3 className="text-lg font-semibold text-slate-950">{t('No plans found')}</h3>
                                    <p className="text-sm text-slate-500">{t('Try a different search.')}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="rounded-[28px] border border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-200 p-5">
                            <h2 className="text-lg font-bold text-slate-950">{t('Add-ons assignment')}</h2>
                            <p className="text-sm text-slate-500">{t('Only enabled add-ons can be added to new plans.')}</p>
                        </div>
                        <div className="space-y-3 p-5">
                            {enabledAddons.map((module) => (
                                <div key={module.module} className="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                    <div>
                                        <div className="font-semibold text-slate-950">{module.alias}</div>
                                        <div className="text-xs text-slate-500">{formatPlanCurrency(Number(module.monthly_price || 0))} / {t('month')}</div>
                                    </div>
                                    <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">{t('Enabled')}</Badge>
                                </div>
                            ))}

                            {disabledAddons.length > 0 && (
                                <div className="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800">
                                    {disabledAddons.length} {t('disabled add-ons are hidden from new plan assignment. Enable them from the Add-ons page.')}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <Dialog open={Boolean(deletingPlan)} onOpenChange={() => setDeletingPlan(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Delete plan')}</DialogTitle>
                        <DialogDescription>
                            {t('This action cannot be undone. Customers already subscribed to this plan may block deletion.')}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeletingPlan(null)}>{t('Cancel')}</Button>
                        <Button variant="destructive" onClick={confirmDelete}>{t('Delete')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
