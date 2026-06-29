import { Head, Link, router, usePage } from '@inertiajs/react';
import Header from './components/Header';
import Footer from './components/Footer';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { getAdminSetting, getImagePath, formatAdminCurrency } from '@/utils/helpers';
import { useMemo, useState } from 'react';
import CookieConsent from '@/components/cookie-consent';
import { useTranslation } from 'react-i18next';
import { CheckCircle2, XCircle, Wifi, Users, HardDrive, PackageCheck, Edit, Plus, ShieldCheck } from 'lucide-react';

interface Plan {
    id: number;
    name: string;
    description?: string;
    package_price_monthly: number;
    package_price_yearly: number;
    number_of_users: number;
    storage_limit: number;
    modules: string[];
    free_plan: boolean;
    trial: boolean;
    trial_days: number;
    orders_count?: number;
}

interface Module {
    module: string;
    alias: string;
    image?: string;
    monthly_price?: number;
    yearly_price?: number;
}

interface PricingProps {
    plans?: Plan[];
    activeModules?: Module[];
    settings?: any;
}

export default function Pricing(props: PricingProps) {
    const { t } = useTranslation();
    const favicon = getAdminSetting('favicon');
    const faviconUrl = favicon ? getImagePath(favicon) : null;
    const { adminAllSetting, auth } = usePage().props as any;
    const plans = props.plans || [];
    const activeModules = props.activeModules || [];
    const settings = { ...props.settings, is_authenticated: Boolean(auth?.user?.id) };
    const colors = settings?.config_sections?.colors || { primary: '#0b63f6', secondary: '#09275c', accent: '#16a34a' };
    const pricingSettings = settings?.config_sections?.sections?.pricing || {};
    const [priceType, setPriceType] = useState<'monthly' | 'yearly'>('monthly');

    const isAdmin = Boolean(auth?.user?.roles?.includes('superadmin'));

    const mostPopularPlanId = useMemo(() => {
        if (!plans.length) return null;
        return plans.reduce((prev, current) => (current.orders_count || 0) > (prev.orders_count || 0) ? current : prev).id;
    }, [plans]);

    const priceForPlan = (plan: Plan) => {
        if (plan.free_plan) return 0;
        return priceType === 'monthly' ? Number(plan.package_price_monthly || 0) : Number(plan.package_price_yearly || 0);
    };

    const includedModules = (plan: Plan) => activeModules.filter((module) => plan.modules?.includes(module.module));

    const startPlan = (plan: Plan) => {
        if (!auth?.user?.id) {
            router.visit(route('register'));
            return;
        }
        router.visit(route('plans.subscribe', plan.id));
    };

    return (
        <>
            <Head title={t('Pricing')}>
                {faviconUrl && <link rel="icon" type="image/x-icon" href={faviconUrl} />}
            </Head>

            <Header settings={settings} />

            <main className="min-h-screen bg-slate-50">
                <section className="relative overflow-hidden bg-[#061b3f] py-16 text-white md:py-20">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(37,99,235,0.35),transparent_32rem)]" />
                    <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                            <div className="max-w-3xl">
                                <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-sm font-medium text-blue-100 ring-1 ring-white/15">
                                    <Wifi className="h-4 w-4" />
                                    {t('StudyRoom Connect WiFi Billing')}
                                </div>
                                <h1 className="text-4xl font-bold tracking-tight md:text-5xl">
                                    {pricingSettings.title || t('Pricing and packages')}
                                </h1>
                                <p className="mt-4 max-w-2xl text-base text-blue-100 md:text-lg">
                                    {pricingSettings.subtitle || t('Build subscription packages with included add-ons, user limits, storage limits, and clear monthly or yearly prices.')}
                                </p>
                            </div>

                            <div className="flex flex-wrap items-center gap-3">
                                <div className="rounded-2xl bg-white/10 p-1 ring-1 ring-white/15">
                                    <button
                                        type="button"
                                        onClick={() => setPriceType('monthly')}
                                        className={`rounded-xl px-4 py-2 text-sm font-semibold transition ${priceType === 'monthly' ? 'bg-white text-slate-950' : 'text-blue-100'}`}
                                    >
                                        {t('Monthly')}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setPriceType('yearly')}
                                        className={`rounded-xl px-4 py-2 text-sm font-semibold transition ${priceType === 'yearly' ? 'bg-white text-slate-950' : 'text-blue-100'}`}
                                    >
                                        {t('Yearly')}
                                    </button>
                                </div>

                                {isAdmin && (
                                    <Button asChild className="bg-white text-slate-950 hover:bg-blue-50">
                                        <Link href={route('plans.create')}>
                                            <Plus className="mr-2 h-4 w-4" />
                                            {t('New Plan')}
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    {plans.length > 0 ? (
                        <>
                            <div className="grid gap-6 lg:grid-cols-3">
                                {plans.map((plan) => {
                                    const price = priceForPlan(plan);
                                    const included = includedModules(plan);
                                    const isPopular = mostPopularPlanId === plan.id;

                                    return (
                                        <div key={plan.id} className={`relative rounded-[28px] border bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl ${isPopular ? 'border-blue-300 ring-4 ring-blue-50' : 'border-slate-200'}`}>
                                            {isPopular && (
                                                <div className="absolute right-6 top-6 rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white">
                                                    {t('Popular')}
                                                </div>
                                            )}

                                            <div className="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                                                <Wifi className="h-7 w-7" />
                                            </div>

                                            <h3 className="text-xl font-bold text-slate-950">{plan.name}</h3>
                                            <p className="mt-2 min-h-[48px] text-sm text-slate-600">{plan.description || t('Smart package for ISP billing users.')}</p>

                                            <div className="mt-6 flex items-end gap-2">
                                                <span className="text-4xl font-bold text-slate-950">{formatAdminCurrency(price)}</span>
                                                <span className="pb-2 text-sm text-slate-500">/ {priceType === 'monthly' ? t('month') : t('year')}</span>
                                            </div>

                                            <div className="mt-6 grid grid-cols-2 gap-3">
                                                <div className="rounded-2xl bg-slate-50 p-3">
                                                    <div className="flex items-center gap-2 text-xs text-slate-500"><Users className="h-4 w-4" />{t('Users')}</div>
                                                    <div className="mt-1 font-semibold text-slate-950">{Number(plan.number_of_users) === -1 ? t('Unlimited') : plan.number_of_users}</div>
                                                </div>
                                                <div className="rounded-2xl bg-slate-50 p-3">
                                                    <div className="flex items-center gap-2 text-xs text-slate-500"><HardDrive className="h-4 w-4" />{t('Storage')}</div>
                                                    <div className="mt-1 font-semibold text-slate-950">{Math.round(Number(plan.storage_limit || 0) / (1024 * 1024))} GB</div>
                                                </div>
                                            </div>

                                            <div className="mt-6">
                                                <div className="mb-3 flex items-center justify-between">
                                                    <span className="text-sm font-semibold text-slate-900">{t('Included add-ons')}</span>
                                                    <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">
                                                        {included.length}/{activeModules.length}
                                                    </Badge>
                                                </div>
                                                <div className="space-y-2">
                                                    {activeModules.slice(0, 6).map((module) => {
                                                        const enabled = plan.modules?.includes(module.module);
                                                        return (
                                                            <div key={module.module} className="flex items-center gap-2 text-sm">
                                                                {enabled ? <CheckCircle2 className="h-4 w-4 text-emerald-600" /> : <XCircle className="h-4 w-4 text-slate-300" />}
                                                                <span className={enabled ? 'text-slate-700' : 'text-slate-400'}>{module.alias}</span>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            </div>

                                            <div className="mt-6 flex gap-2">
                                                {isAdmin ? (
                                                    <Button asChild className="flex-1" style={{ backgroundColor: colors.primary }}>
                                                        <Link href={route('plans.edit', plan.id)}>
                                                            <Edit className="mr-2 h-4 w-4" />
                                                            {t('Edit Plan')}
                                                        </Link>
                                                    </Button>
                                                ) : (
                                                    <Button className="flex-1" style={{ backgroundColor: colors.primary }} onClick={() => startPlan(plan)}>
                                                        {t('Choose Package')}
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            <div className="mt-10 overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                                <div className="border-b border-slate-200 p-6">
                                    <div className="flex items-center gap-2 text-lg font-bold text-slate-950">
                                        <PackageCheck className="h-5 w-5 text-blue-600" />
                                        {t('Plan add-on comparison')}
                                    </div>
                                    <p className="mt-1 text-sm text-slate-500">{t('Admin can change these included add-ons from the Plans page.')}</p>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="w-full min-w-[760px] text-sm">
                                        <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                            <tr>
                                                <th className="px-6 py-4">{t('Add-on')}</th>
                                                {plans.map((plan) => <th key={plan.id} className="px-6 py-4 text-center">{plan.name}</th>)}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {activeModules.map((module) => (
                                                <tr key={module.module}>
                                                    <td className="px-6 py-4 font-medium text-slate-800">{module.alias}</td>
                                                    {plans.map((plan) => (
                                                        <td key={`${plan.id}-${module.module}`} className="px-6 py-4 text-center">
                                                            {plan.modules?.includes(module.module)
                                                                ? <CheckCircle2 className="mx-auto h-5 w-5 text-emerald-600" />
                                                                : <XCircle className="mx-auto h-5 w-5 text-slate-300" />}
                                                        </td>
                                                    ))}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="rounded-[28px] border border-slate-200 bg-white py-16 text-center shadow-sm">
                            <ShieldCheck className="mx-auto mb-4 h-12 w-12 text-slate-300" />
                            <h3 className="text-xl font-semibold text-slate-950">{t('No plans available')}</h3>
                            <p className="mt-2 text-sm text-slate-500">{t('Create your first plan from the admin panel.')}</p>
                            {isAdmin && (
                                <Button asChild className="mt-6" style={{ backgroundColor: colors.primary }}>
                                    <Link href={route('plans.create')}>{t('Create Plan')}</Link>
                                </Button>
                            )}
                        </div>
                    )}
                </section>
            </main>

            <Footer settings={settings} />
            <CookieConsent settings={adminAllSetting || {}} />
        </>
    );
}
