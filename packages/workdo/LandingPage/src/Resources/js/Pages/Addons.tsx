import { Head, router, usePage } from '@inertiajs/react';
import Header from './components/Header';
import Footer from './components/Footer';
import AddonCard from './components/AddonCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { getAdminSetting, getImagePath, formatAdminCurrency } from '@/utils/helpers';
import { useState, useEffect } from 'react';
import { Search, Filter } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import CookieConsent from "@/components/cookie-consent";

interface Addon {
    id: number;
    name: string;
    description?: string;
    image?: string;
    monthly_price?: number;
    yearly_price?: number;
    package_name: string;
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
    };
}

export default function Addons(props: AddonsProps) {
    const { t } = useTranslation();
    const favicon = getAdminSetting('favicon');
    const faviconUrl = favicon ? getImagePath(favicon) : null;
    const { adminAllSetting, auth } = usePage().props as any;
    const addons = props.addons || { data: [], current_page: 1, last_page: 1, per_page: 20, total: 0 };
    const settings = { ...props.settings, is_authenticated: (auth?.user?.id !== undefined && auth?.user?.id !== null) };
    const categories = props.categories || [];
    const filters = props.filters || {};

    const colors = settings?.config_sections?.colors || { primary: '#10b981', secondary: '#059669', accent: '#f59e0b' };
    const addonSettings = settings?.config_sections?.sections?.addons || {};

    const [searchTerm, setSearchTerm] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('');
    const [priceFilter, setPriceFilter] = useState('');
    const [priceType, setPriceType] = useState(addonSettings.default_price_type || 'monthly');
    const [sortBy, setSortBy] = useState('name');

    useEffect(() => {
        if (filters.search) setSearchTerm(filters.search);
        if (filters.category) setSelectedCategory(filters.category);
        if (filters.price) setPriceFilter(filters.price);
        if (filters.price_type) setPriceType(filters.price_type);
        if (filters.sort && typeof filters.sort === 'string') setSortBy(filters.sort);
    }, [filters]);

    const handleFilter = () => {
        const params = {
            search: searchTerm || undefined,
            category: selectedCategory === 'all' ? undefined : selectedCategory,
            price: priceFilter === 'all' ? undefined : priceFilter,
            price_type: priceType,
            sort: sortBy,
            page: 1
        };

        router.get(route('addons.page'), params, { preserveState: true });
    };

    const handlePageChange = (page: number) => {
        const params = {
            search: searchTerm || undefined,
            category: selectedCategory === 'all' ? undefined : selectedCategory,
            price: priceFilter === 'all' ? undefined : priceFilter,
            price_type: priceType,
            sort: sortBy,
            page
        };

        router.get(route('addons.page'), params, { preserveState: true });
    };

    return (
        <>
            <Head title="Addons" >
                {faviconUrl && <link rel="icon" type="image/x-icon" href={faviconUrl} />}
            </Head>

            <Header settings={settings} />

            <main className="min-h-screen bg-gray-50 py-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-12">
                        <h1 className="text-4xl md:text-5xl font-bold text-slate-900 mb-6">
                            {addonSettings.title || 'Premium Addons'}
                        </h1>
                        <p className="text-xl text-slate-700 max-w-3xl mx-auto mb-8">
                            {addonSettings.subtitle || 'Extend your WorkDo Dash with powerful premium modules designed to enhance your business operations'}
                        </p>
                        <div className="text-sm text-slate-500">
                            {t('Showing')} {addons.data.length} {t('of')} {addons.total} {t('addons')}
                        </div>
                    </div>

                    {/* Filters */}
                    {(addonSettings.show_search !== false || addonSettings.show_category !== false || addonSettings.show_price !== false || addonSettings.show_sort !== false) && (
                        <div className="bg-white rounded-lg shadow-sm border p-6 mb-8">
                            <div className="grid grid-cols-1 md:grid-cols-5 lg:grid-cols-5 gap-4 items-end">
                                {addonSettings.show_search !== false && (
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-2">{t('Search')}</label>
                                        <div className="relative">
                                            <Search className="absolute start-3 top-1/2 transform -translate-y-1/2 text-slate-400 h-4 w-4" />
                                            <Input
                                                placeholder={t("Search addons...")}
                                                value={searchTerm}
                                                onChange={(e) => setSearchTerm(e.target.value)}
                                                className="ps-10"
                                                onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                            />
                                        </div>
                                    </div>
                                )}

                                {addonSettings.show_price !== false && (
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-2">{t("Price")}</label>
                                        <Select value={priceFilter} onValueChange={setPriceFilter}>
                                            <SelectTrigger>
                                                <SelectValue placeholder={t("All Prices")} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">{t("All Prices")}</SelectItem>
                                                <SelectItem value="free">{t("Free")}</SelectItem>
                                                <SelectItem value="0-50">{formatAdminCurrency(0)} - {formatAdminCurrency(50)}</SelectItem>
                                                <SelectItem value="50-100">{formatAdminCurrency(50)} - {formatAdminCurrency(100)}</SelectItem>
                                                <SelectItem value="100+">{formatAdminCurrency(100)}+</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">{t('Price Type')}</label>
                                    <Select value={priceType} onValueChange={setPriceType}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="monthly">{t('Monthly')}</SelectItem>
                                            <SelectItem value="yearly">{t('Yearly')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {addonSettings.show_sort !== false && (
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-2">{t('Sort By')}</label>
                                        <Select value={sortBy} onValueChange={setSortBy}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="name">{t('Name')}</SelectItem>
                                                <SelectItem value="price_low">{t('Price: Low to High')}</SelectItem>
                                                <SelectItem value="price_high">{t('Price: High to Low')}</SelectItem>
                                                <SelectItem value="newest">{t('Newest')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}

                                <div>
                                    <Button onClick={handleFilter} style={{ backgroundColor: colors.primary }} className="w-full text-white">
                                        <Filter className="h-4 w-4 me-2" />
                                        {t('Filter')}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                        {addons.data.map((addon) => (
                            <AddonCard
                                key={addon.id}
                                addon={addon}
                                colors={colors}
                                priceType={priceType as 'monthly' | 'yearly'}
                                variant={addonSettings.card_variant as 'card1' | 'card2' | 'card3' | 'card4' | 'card5'}
                                onViewDetails={() => {
                                    router.visit(route('marketplace', { slug: addon.package_name }));
                                }}
                            />
                        ))}
                    </div>

                    {/* Pagination */}
                    {addons.last_page > 1 && (
                        <div className="flex justify-center items-center gap-2 mt-12">
                            <Button
                                variant="outline"
                                disabled={addons.current_page === 1}
                                onClick={() => handlePageChange(addons.current_page - 1)}
                            >
                                {t('Previous')}
                            </Button>

                            {Array.from({ length: Math.min(5, addons.last_page) }, (_, i) => {
                                const page = addons.current_page <= 3
                                    ? i + 1
                                    : addons.current_page + i - 2;
                                if (page > addons.last_page) return null;

                                return (
                                    <Button
                                        key={page}
                                        variant={page === addons.current_page ? 'default' : 'outline'}
                                        onClick={() => handlePageChange(page)}
                                        style={page === addons.current_page ? { backgroundColor: colors.primary } : {}}
                                        className={page === addons.current_page ? 'text-white' : ''}
                                    >
                                        {page}
                                    </Button>
                                );
                            })}

                            <Button
                                variant="outline"
                                disabled={addons.current_page === addons.last_page}
                                onClick={() => handlePageChange(addons.current_page + 1)}
                            >
                                {t('Next')}
                            </Button>
                        </div>
                    )}

                    {addons.data.length === 0 && (
                        <div className="text-center py-16">
                            <div className="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg className="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <h3 className="text-xl font-semibold text-slate-900 mb-2">{t('No Addons Available')}</h3>
                            <p className="text-slate-700">{addonSettings.empty_message || t('Check back later for new premium addons and modules.')}</p>
                        </div>
                    )}
                </div>
            </main>

            <Footer settings={settings} />

            <CookieConsent settings={adminAllSetting || {}} />
        </>
    );
}
