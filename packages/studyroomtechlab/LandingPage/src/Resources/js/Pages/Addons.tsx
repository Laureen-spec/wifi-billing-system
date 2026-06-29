import { Head, router, usePage } from '@inertiajs/react';
import Header from './components/Header';
import Footer from './components/Footer';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { getAdminSetting, getImagePath, formatAdminCurrency } from '@/utils/helpers';
import { useEffect, useMemo, useState } from 'react';
import { Search, Filter, Boxes, CheckCircle2, XCircle, ShieldCheck, MoreHorizontal } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import CookieConsent from '@/components/cookie-consent';

interface Addon {
    id: number;
    module: string;
    name: string;
    image?: string;
    monthly_price?: number;
    yearly_price?: number;
    package_name?: string;
    is_enable: boolean;
    assigned_count?: number;
}

interface AddonsProps {
    addons?: {
        data: Addon[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    settings?: any;
    categories?: string[];
    filters?: {
        search?: string;
        category?: string;
        price?: string;
        price_type?: string;
        sort?: string;
        status?: string;
    };
    canManageAddons?: boolean;
    addonStats?: {
        total: number;
        enabled: number;
        disabled: number;
    };
}

export default function Addons(props: AddonsProps) {
    const { t } = useTranslation();
    const favicon = getAdminSetting('favicon');
    const faviconUrl = favicon ? getImagePath(favicon) : null;
    const { adminAllSetting, auth } = usePage().props as any;

    const addons = props.addons || { data: [], current_page: 1, last_page: 1, per_page: 20, total: 0 };
    const settings = { ...props.settings, is_authenticated: Boolean(auth?.user?.id) };
    const categories = props.categories || [];
    const filters = props.filters || {};
    const canManageAddons = Boolean(props.canManageAddons);
    const stats = props.addonStats || {
        total: addons.total,
        enabled: addons.data.filter((addon) => addon.is_enable).length,
        disabled: addons.data.filter((addon) => !addon.is_enable).length,
    };

    const colors = settings?.config_sections?.colors || { primary: '#0b63f6', secondary: '#09275c', accent: '#16a34a' };

    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category || 'all');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [priceType, setPriceType] = useState(filters.price_type || 'monthly');
    const [sortBy, setSortBy] = useState(filters.sort || 'name');

    useEffect(() => {
        setSearchTerm(filters.search || '');
        setSelectedCategory(filters.category || 'all');
        setStatusFilter(filters.status || 'all');
        setPriceType(filters.price_type || 'monthly');
        setSortBy(filters.sort || 'name');
    }, [filters.search, filters.category, filters.status, filters.price_type, filters.sort]);

    const applyFilters = (overrides: Record<string, string | number> = {}) => {
        const query = {
            search: searchTerm,
            category: selectedCategory === 'all' ? '' : selectedCategory,
            status: statusFilter === 'all' ? '' : statusFilter,
            price_type: priceType,
            sort: sortBy,
            ...overrides,
        };

        router.get(route('addons.page'), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const toggleAddon = (addon: Addon, checked: boolean) => {
        router.patch(route('addons.toggle', addon.id), {
            is_enable: checked,
        }, {
            preserveScroll: true,
        });
    };

    const handlePageChange = (page: number) => {
        applyFilters({ page });
    };

    const activePercent = useMemo(() => {
        if (!stats.total) return 0;
        return Math.round((stats.enabled / stats.total) * 100);
    }, [stats.enabled, stats.total]);

    return (
        <>
            <Head title={canManageAddons ? t('Add-ons Management') : t('Add-ons')}>
                {faviconUrl && <link rel="icon" type="image/x-icon" href={faviconUrl} />}
            </Head>

            <Header settings={settings} />

            <main className="min-h-screen bg-slate-50 py-10 md:py-16">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-8 rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div className="mb-3 inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
                                    <Boxes className="h-4 w-4" />
                                    {canManageAddons ? t('Admin add-on control') : t('Available add-ons')}
                                </div>
                                <h1 className="text-3xl font-bold tracking-tight text-slate-950 md:text-4xl">
                                    {canManageAddons ? t('Add-ons') : t('Extend your WiFi billing system')}
                                </h1>
                                <p className="mt-2 max-w-3xl text-sm text-slate-600 md:text-base">
                                    {canManageAddons
                                        ? t('Enable or disable modules. Disabled add-ons are hidden from customers and cannot be added to new plans.')
                                        : t('Choose extra modules that can be included in subscription packages.')}
                                </p>
                            </div>

                            {canManageAddons && (
                                <div className="grid grid-cols-3 gap-3 text-center">
                                    <div className="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                                        <div className="text-2xl font-bold text-slate-950">{stats.total}</div>
                                        <div className="text-xs text-slate-500">{t('Total')}</div>
                                    </div>
                                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4">
                                        <div className="text-2xl font-bold text-emerald-700">{stats.enabled}</div>
                                        <div className="text-xs text-emerald-700">{t('Enabled')}</div>
                                    </div>
                                    <div className="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                                        <div className="text-2xl font-bold text-slate-700">{stats.disabled}</div>
                                        <div className="text-xs text-slate-500">{t('Disabled')}</div>
                                    </div>
                                </div>
                            )}
                        </div>

                        {canManageAddons && (
                            <div className="mt-6 rounded-2xl bg-blue-50 p-4 text-sm text-blue-800">
                                <div className="flex items-center gap-2 font-medium">
                                    <ShieldCheck className="h-4 w-4" />
                                    {activePercent}% {t('of add-ons are enabled')}
                                </div>
                                <p className="mt-1 text-blue-700">
                                    {t('Plans only assign enabled add-ons. Turn off an add-on here when you do not want it available during package assignment.')}
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="grid gap-3 lg:grid-cols-[1.4fr_0.8fr_0.8fr_0.8fr_auto]">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    value={searchTerm}
                                    onChange={(event) => setSearchTerm(event.target.value)}
                                    onKeyDown={(event) => event.key === 'Enter' && applyFilters({ page: 1 })}
                                    placeholder={t('Search add-ons...')}
                                    className="pl-9"
                                />
                            </div>

                            <Select value={selectedCategory} onValueChange={setSelectedCategory}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Category')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('All Categories')}</SelectItem>
                                    {categories.map((category) => (
                                        <SelectItem key={category} value={category}>{category}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            {canManageAddons && (
                                <Select value={statusFilter} onValueChange={setStatusFilter}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Status')}</SelectItem>
                                        <SelectItem value="enabled">{t('Enabled')}</SelectItem>
                                        <SelectItem value="disabled">{t('Disabled')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            )}

                            <Select value={sortBy} onValueChange={setSortBy}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Sort')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="name">{t('Name')}</SelectItem>
                                    <SelectItem value="status">{t('Status')}</SelectItem>
                                    <SelectItem value="price_low">{t('Price: Low to High')}</SelectItem>
                                    <SelectItem value="price_high">{t('Price: High to Low')}</SelectItem>
                                    <SelectItem value="newest">{t('Newest')}</SelectItem>
                                </SelectContent>
                            </Select>

                            <Button onClick={() => applyFilters({ page: 1 })} style={{ backgroundColor: colors.primary }} className="text-white">
                                <Filter className="mr-2 h-4 w-4" />
                                {t('Apply')}
                            </Button>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm">
                        <div className="grid grid-cols-12 border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <div className="col-span-5">{t('Add-on')}</div>
                            <div className="col-span-2 hidden md:block">{t('Price')}</div>
                            <div className="col-span-2 hidden lg:block">{t('Assignment')}</div>
                            <div className="col-span-2">{t('Status')}</div>
                            <div className="col-span-1 text-right">{t('Action')}</div>
                        </div>

                        {addons.data.length > 0 ? addons.data.map((addon) => (
                            <div key={addon.id} className="grid grid-cols-12 items-center gap-3 border-b border-slate-100 px-5 py-5 last:border-0">
                                <div className="col-span-5 flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl bg-blue-50 ring-1 ring-blue-100">
                                        {addon.image ? (
                                            <img src={addon.image} alt="" className="h-8 w-8 object-contain" />
                                        ) : (
                                            <Boxes className="h-5 w-5 text-blue-600" />
                                        )}
                                    </div>
                                    <div className="min-w-0">
                                        <div className="truncate font-semibold text-slate-950">{addon.name}</div>
                                        <div className="truncate text-xs text-slate-500">{addon.module}</div>
                                    </div>
                                </div>

                                <div className="col-span-2 hidden md:block">
                                    <div className="font-semibold text-slate-900">{formatAdminCurrency(Number(addon.monthly_price || 0))}</div>
                                    <div className="text-xs text-slate-500">{t('monthly')}</div>
                                </div>

                                <div className="col-span-2 hidden lg:block">
                                    <div className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600" />
                                        {addon.assigned_count || 0} {t('plans')}
                                    </div>
                                </div>

                                <div className="col-span-2">
                                    <Badge className={addon.is_enable ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 hover:bg-slate-100'}>
                                        {addon.is_enable ? t('Enabled') : t('Disabled')}
                                    </Badge>
                                </div>

                                <div className="col-span-1 flex justify-end">
                                    {canManageAddons ? (
                                        <Switch checked={addon.is_enable} onCheckedChange={(checked) => toggleAddon(addon, checked)} />
                                    ) : (
                                        <MoreHorizontal className="h-5 w-5 text-slate-400" />
                                    )}
                                </div>
                            </div>
                        )) : (
                            <div className="py-16 text-center">
                                <XCircle className="mx-auto mb-3 h-10 w-10 text-slate-300" />
                                <h3 className="text-lg font-semibold text-slate-900">{t('No add-ons found')}</h3>
                                <p className="text-sm text-slate-500">{t('Try changing your search or filters.')}</p>
                            </div>
                        )}
                    </div>

                    {addons.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-center gap-2">
                            <Button variant="outline" disabled={addons.current_page === 1} onClick={() => handlePageChange(addons.current_page - 1)}>
                                {t('Previous')}
                            </Button>
                            <span className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm text-slate-600">
                                {addons.current_page} / {addons.last_page}
                            </span>
                            <Button variant="outline" disabled={addons.current_page === addons.last_page} onClick={() => handlePageChange(addons.current_page + 1)}>
                                {t('Next')}
                            </Button>
                        </div>
                    )}
                </div>
            </main>

            <Footer settings={settings} />
            <CookieConsent settings={adminAllSetting || {}} />
        </>
    );
}
