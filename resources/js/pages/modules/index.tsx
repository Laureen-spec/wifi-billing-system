import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import {
    ArrowRight,
    Boxes,
    CheckCircle2,
    ExternalLink,
    Eye,
    Filter,
    Layers3,
    Package,
    PlugZap,
    Plus,
    Power,
    PowerOff,
    Search,
    ServerCog,
    ShieldCheck,
    Sparkles,
    X,
} from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { getPackageAlias, getPackageFavicon } from '@/utils/helpers';
import { Module, ModulesIndexProps } from './types';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface AddOn {
    name: string;
    image: string;
    url: string;
}

interface Category {
    name: string;
    icon: string;
    description: string;
    add_ons: AddOn[];
}

type InstalledFilter = 'all' | 'active' | 'inactive';

const slugify = (name: string) => name.toLowerCase().replace(/[^a-z0-9]+/g, '-');

const getModuleTone = (moduleName: string) => {
    const name = moduleName.toLowerCase();

    if (name.includes('mpesa') || name.includes('payment') || name.includes('pay')) {
        return 'from-emerald-50 to-teal-50 border-emerald-200 text-emerald-700';
    }

    if (name.includes('wifi') || name.includes('billing')) {
        return 'from-cyan-50 to-emerald-50 border-cyan-200 text-cyan-700';
    }

    if (name.includes('crm') || name.includes('lead')) {
        return 'from-blue-50 to-slate-50 border-blue-200 text-blue-700';
    }

    if (name.includes('hrm') || name.includes('staff')) {
        return 'from-amber-50 to-orange-50 border-amber-200 text-amber-700';
    }

    return 'from-slate-50 to-white border-slate-200 text-slate-700';
};

export default function Index() {
    const { modules, auth, addOns = [], exploreUrl = '', systemVersion = '' } = usePage<ModulesIndexProps & { addOns: Category[]; exploreUrl: string; systemVersion: string }>().props;
    const { t } = useTranslation();

    const [searchTerm, setSearchTerm] = useState('');
    const [installedFilter, setInstalledFilter] = useState<InstalledFilter>('all');
    const [selectedModule, setSelectedModule] = useState<Module | null>(null);
    const [isDetailsOpen, setIsDetailsOpen] = useState(false);
    const [activeCategory, setActiveCategory] = useState<string>(addOns[0]?.name ?? '');
    const [exploreSearch, setExploreSearch] = useState('');
    const sidebarRef = useRef<HTMLDivElement>(null);
    const sectionRefs = useRef<Record<string, HTMLElement | null>>({});
    const isScrollingTo = useRef(false);
    const scrollContainerRef = useRef<HTMLDivElement>(null);

    const installedModules = useMemo(
        () => modules.filter(module => module.display !== false),
        [modules],
    );

    const installedStats = useMemo(() => {
        const active = installedModules.filter(module => module.is_enabled).length;
        const inactive = installedModules.length - active;

        return {
            total: installedModules.length,
            active,
            inactive,
            marketplace: addOns.reduce((total, category) => total + category.add_ons.length, 0),
        };
    }, [addOns, installedModules]);

    const filteredModules = useMemo(() => {
        const q = searchTerm.toLowerCase().trim();

        return installedModules.filter(module => {
            const matchesSearch = !q
                || module.alias.toLowerCase().includes(q)
                || module.name.toLowerCase().includes(q)
                || module.description.toLowerCase().includes(q);

            const matchesFilter = installedFilter === 'all'
                || (installedFilter === 'active' && module.is_enabled)
                || (installedFilter === 'inactive' && !module.is_enabled);

            return matchesSearch && matchesFilter;
        });
    }, [installedFilter, installedModules, searchTerm]);

    const filteredAddOns = useMemo(() => {
        if (!exploreSearch.trim()) return null;
        const q = exploreSearch.toLowerCase();
        return addOns
            .map(cat => ({ ...cat, add_ons: cat.add_ons.filter(a => a.name.toLowerCase().includes(q)) }))
            .filter(cat => cat.add_ons.length > 0);
    }, [exploreSearch, addOns]);

    const updateActive = useCallback(() => {
        if (isScrollingTo.current) return;
        const container = scrollContainerRef.current?.querySelector<HTMLDivElement>('[data-radix-scroll-area-viewport]');
        if (!container) return;
        const scrollTop = container.scrollTop + container.clientHeight * 0.3;
        let current = addOns[0]?.name ?? '';
        for (const category of addOns) {
            const el = sectionRefs.current[category.name];
            if (el && el.offsetTop <= scrollTop) {
                current = category.name;
            }
        }
        setActiveCategory(current);
    }, [addOns]);

    useEffect(() => {
        const container = scrollContainerRef.current?.querySelector<HTMLDivElement>('[data-radix-scroll-area-viewport]');
        if (!container) return;
        container.addEventListener('scroll', updateActive, { passive: true });
        updateActive();
        return () => container.removeEventListener('scroll', updateActive);
    }, [updateActive]);

    useEffect(() => {
        if (!sidebarRef.current) return;
        const activeBtn = sidebarRef.current.querySelector<HTMLElement>('[data-active="true"]');
        if (activeBtn) {
            activeBtn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }, [activeCategory]);

    const scrollToCategory = (name: string) => {
        const el = sectionRefs.current[name];
        const container = scrollContainerRef.current?.querySelector<HTMLDivElement>('[data-radix-scroll-area-viewport]');
        if (!el || !container) return;
        isScrollingTo.current = true;
        setActiveCategory(name);
        container.scrollTo({ top: el.offsetTop - 16, behavior: 'smooth' });
        setTimeout(() => { isScrollingTo.current = false; }, 800);
    };

    const displayCategories = filteredAddOns ?? addOns;

    const handleToggleModule = (moduleName: string, isEnabled: boolean) => {
        router.post(route('add-on.enable', moduleName), {}, {
            preserveState: true,
        });
    };

    const handleViewDetails = (module: Module) => {
        setSelectedModule(module);
        setIsDetailsOpen(true);
    };

    const filterChips: Array<{ key: InstalledFilter; label: string; count: number }> = [
        { key: 'all', label: t('All'), count: installedStats.total },
        { key: 'active', label: t('Active'), count: installedStats.active },
        { key: 'inactive', label: t('Inactive'), count: installedStats.inactive },
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Add-ons') }]}
            pageTitle={t('Add-ons Manager')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('manage-add-on') && (
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button size="sm" onClick={() => router.visit(route('add-on.upload'))}>
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Upload Add-ons')}</p>
                            </TooltipContent>
                        </Tooltip>
                    )}
                </TooltipProvider>
            }
        >
            <Head title={t('Add-ons')} />

            <div className="space-y-6">
                <section className="relative overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm">
                    <div className="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.16),transparent_55%)]" />
                    <div className="relative grid gap-6 p-6 lg:grid-cols-[1.3fr_1fr] lg:p-8">
                        <div className="space-y-4">
                            <div className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">
                                <PlugZap className="h-3.5 w-3.5" />
                                {t('Module control')}
                            </div>
                            <div>
                                <h2 className="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                                    {t('Add-ons Manager')}
                                </h2>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                                    {t('Install, activate, review, and explore platform modules from one clean control center.')}
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4">
                            <div className="rounded-2xl border border-slate-200 bg-white/85 p-4 shadow-sm backdrop-blur">
                                <Boxes className="mb-3 h-5 w-5 text-slate-500" />
                                <p className="text-2xl font-bold text-slate-950">{installedStats.total}</p>
                                <p className="text-xs font-medium text-slate-500">{t('Installed')}</p>
                            </div>
                            <div className="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 shadow-sm backdrop-blur">
                                <CheckCircle2 className="mb-3 h-5 w-5 text-emerald-600" />
                                <p className="text-2xl font-bold text-emerald-800">{installedStats.active}</p>
                                <p className="text-xs font-medium text-emerald-700">{t('Active')}</p>
                            </div>
                            <div className="rounded-2xl border border-slate-200 bg-slate-50/90 p-4 shadow-sm backdrop-blur">
                                <PowerOff className="mb-3 h-5 w-5 text-slate-500" />
                                <p className="text-2xl font-bold text-slate-950">{installedStats.inactive}</p>
                                <p className="text-xs font-medium text-slate-500">{t('Inactive')}</p>
                            </div>
                            <div className="rounded-2xl border border-cyan-200 bg-cyan-50/80 p-4 shadow-sm backdrop-blur">
                                <ServerCog className="mb-3 h-5 w-5 text-cyan-700" />
                                <p className="text-2xl font-bold text-cyan-900">{systemVersion ? `v${systemVersion}` : '-'}</p>
                                <p className="text-xs font-medium text-cyan-700">{t('System')}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <Card className="overflow-hidden rounded-[1.35rem] border-slate-200 shadow-sm">
                    <CardContent className="p-0">
                        <div className="border-b border-slate-200 bg-gradient-to-r from-white via-emerald-50/40 to-white p-5 sm:p-6">
                            <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                                <div>
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700">
                                            <Layers3 className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h3 className="text-xl font-bold text-slate-950">{t('Installed Add-ons')}</h3>
                                            <p className="text-sm text-slate-500">{t('Manage modules already available in this workspace.')}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
                                    <div className="relative min-w-0 lg:w-[360px]">
                                        <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                        <input
                                            value={searchTerm}
                                            onChange={event => setSearchTerm(event.target.value)}
                                            placeholder={t('Search installed add-ons...')}
                                            className="h-12 w-full rounded-2xl border border-slate-200 bg-white pl-11 pr-11 text-sm shadow-sm outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100"
                                        />
                                        {searchTerm && (
                                            <button
                                                type="button"
                                                onClick={() => setSearchTerm('')}
                                                className="absolute right-3 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                                aria-label={t('Clear search')}
                                            >
                                                <X className="h-4 w-4" />
                                            </button>
                                        )}
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2">
                                        {filterChips.map(chip => {
                                            const active = installedFilter === chip.key;
                                            return (
                                                <button
                                                    key={chip.key}
                                                    type="button"
                                                    onClick={() => setInstalledFilter(chip.key)}
                                                    className={`inline-flex h-10 items-center gap-2 rounded-full border px-4 text-sm font-semibold transition ${
                                                        active
                                                            ? 'border-emerald-300 bg-emerald-50 text-emerald-800 shadow-sm'
                                                            : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200 hover:bg-emerald-50/50 hover:text-slate-900'
                                                    }`}
                                                >
                                                    <Filter className="h-3.5 w-3.5" />
                                                    {chip.label}
                                                    <span className="rounded-full bg-white px-2 py-0.5 text-xs text-slate-500 shadow-sm">{chip.count}</span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="p-5 sm:p-6">
                            {filteredModules.length > 0 ? (
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                                    {filteredModules.map(module => {
                                        const tone = getModuleTone(module.name);

                                        return (
                                            <Card key={module.name} className="group relative overflow-hidden rounded-3xl border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg">
                                                <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400 opacity-0 transition group-hover:opacity-100" />
                                                <CardContent className="flex h-full flex-col p-5">
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className={`flex h-14 w-14 items-center justify-center rounded-2xl border bg-gradient-to-br ${tone}`}>
                                                            <img
                                                                src={getPackageFavicon(module.name)}
                                                                alt={getPackageAlias(module.name)}
                                                                className="h-9 w-9 rounded-xl object-contain"
                                                                onError={(event) => {
                                                                    const target = event.target as HTMLImageElement;
                                                                    target.style.display = 'none';
                                                                    target.nextElementSibling?.classList.remove('hidden');
                                                                }}
                                                            />
                                                            <Package className="hidden h-8 w-8" />
                                                        </div>

                                                        <div className="flex flex-col items-end gap-2">
                                                            <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ${
                                                                module.is_enabled
                                                                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                                                                    : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'
                                                            }`}>
                                                                {module.is_enabled ? t('Active') : t('Inactive')}
                                                            </span>
                                                            <span className="text-xs font-semibold text-slate-400">v{parseFloat(module.version).toFixed(1)}</span>
                                                        </div>
                                                    </div>

                                                    <div className="mt-5 min-h-[90px]">
                                                        <h3 className="line-clamp-1 text-base font-bold text-slate-950">{module.alias}</h3>
                                                        <p className="mt-2 line-clamp-3 text-sm leading-6 text-slate-500">{module.description}</p>
                                                    </div>

                                                    <div className="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                        <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 font-medium">
                                                            {module.package_name || module.name}
                                                        </span>
                                                    </div>

                                                    <div className="mt-5 flex gap-2 border-t border-slate-100 pt-4">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleViewDetails(module)}
                                                            className="h-10 flex-1 rounded-xl border-slate-200 font-semibold"
                                                        >
                                                            <Eye className="mr-2 h-4 w-4" />
                                                            {t('Details')}
                                                        </Button>
                                                        {auth.user?.permissions?.includes('manage-actions') && (
                                                            <TooltipProvider>
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="outline"
                                                                            size="sm"
                                                                            onClick={() => handleToggleModule(module.name, module.is_enabled)}
                                                                            className={`h-10 rounded-xl px-3 font-semibold ${
                                                                                module.is_enabled
                                                                                    ? 'border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700'
                                                                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800'
                                                                            }`}
                                                                        >
                                                                            {module.is_enabled ? <PowerOff className="h-4 w-4" /> : <Power className="h-4 w-4" />}
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{module.is_enabled ? t('Disable Module') : t('Enable Module')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            </TooltipProvider>
                                                        )}
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        );
                                    })}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={Package}
                                    title={t('No add-ons found')}
                                    description={searchTerm || installedFilter !== 'all' ? t('No add-ons match your search criteria.') : t('No add-ons are available.')}
                                    hasFilters={!!searchTerm || installedFilter !== 'all'}
                                    onClearFilters={() => {
                                        setSearchTerm('');
                                        setInstalledFilter('all');
                                    }}
                                />
                            )}
                        </div>
                    </CardContent>
                </Card>

                {addOns.length > 0 && (
                    <Card className="overflow-hidden rounded-[1.35rem] border-slate-200 shadow-sm">
                        <CardContent className="p-0">
                            <div className="flex flex-col gap-4 border-b border-slate-200 bg-slate-50/70 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-2xl border border-cyan-200 bg-cyan-50 text-cyan-700">
                                        <Sparkles className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-bold text-slate-950">{t('Explore Add-ons')}</h3>
                                        <p className="text-sm text-slate-500">{t('Discover compatible tools and modules for future growth.')}</p>
                                    </div>
                                </div>
                                <div className="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm">
                                    {installedStats.marketplace} {t('available')}
                                </div>
                            </div>

                            <div className="p-5 sm:p-6">
                                <div className="flex flex-col gap-5 lg:flex-row">
                                    <div className="lg:w-64 lg:shrink-0">
                                        <div className="sticky top-4 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                                            <div className="border-b border-slate-100 p-3">
                                                <div className="relative">
                                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                                    <input
                                                        type="text"
                                                        value={exploreSearch}
                                                        onChange={event => setExploreSearch(event.target.value)}
                                                        placeholder={t('Search marketplace...')}
                                                        className="h-10 w-full rounded-2xl border border-slate-200 bg-white pl-10 pr-9 text-sm outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100"
                                                    />
                                                    {exploreSearch && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setExploreSearch('')}
                                                            className="absolute right-2 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                                            aria-label={t('Clear search')}
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="max-h-[70vh] overflow-y-auto p-2" ref={sidebarRef}>
                                                <ul className="space-y-1">
                                                    {addOns.map(category => {
                                                        const isActive = activeCategory === category.name && !exploreSearch;
                                                        return (
                                                            <li key={category.name}>
                                                                <button
                                                                    data-active={isActive}
                                                                    onClick={() => { setExploreSearch(''); scrollToCategory(category.name); }}
                                                                    className={`flex w-full items-center justify-between gap-2 rounded-2xl px-3 py-2.5 text-left text-sm transition ${
                                                                        isActive
                                                                            ? 'border border-emerald-200 bg-emerald-50 text-emerald-800 shadow-sm'
                                                                            : 'border border-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-950'
                                                                    }`}
                                                                >
                                                                    <span className="flex min-w-0 items-center gap-2.5">
                                                                        <i className={`${category.icon} shrink-0 text-base`} />
                                                                        <span className="truncate font-semibold">{category.name}</span>
                                                                    </span>
                                                                    <span className={`shrink-0 rounded-full px-2 py-0.5 text-xs font-bold ${
                                                                        isActive ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500'
                                                                    }`}>
                                                                        {category.add_ons.length}
                                                                    </span>
                                                                </button>
                                                            </li>
                                                        );
                                                    })}
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <ScrollArea className="h-[calc(100vh-320px)] min-w-0 flex-1" ref={scrollContainerRef}>
                                        <div className="space-y-8 pr-4">
                                            {exploreSearch && filteredAddOns?.length === 0 && (
                                                <div className="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-200 bg-slate-50/70 py-16 text-center">
                                                    <Search className="mb-3 h-10 w-10 text-slate-300" />
                                                    <p className="font-semibold text-slate-600">{t('No add-ons match')} &quot;{exploreSearch}&quot;</p>
                                                    <div className="mt-4 flex flex-wrap items-center justify-center gap-2">
                                                        <button
                                                            onClick={() => setExploreSearch('')}
                                                            className="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                                                        >
                                                            <X className="h-3.5 w-3.5" />
                                                            {t('Clear Search')}
                                                        </button>
                                                        <a
                                                            href={exploreUrl}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                                        >
                                                            <ExternalLink className="h-3.5 w-3.5" />
                                                            {t('Explore All Add-ons')}
                                                        </a>
                                                    </div>
                                                </div>
                                            )}

                                            {displayCategories.map(category => (
                                                <section
                                                    key={category.name}
                                                    id={slugify(category.name)}
                                                    ref={el => { sectionRefs.current[category.name] = el; }}
                                                >
                                                    <div className="mb-4 flex items-center justify-between gap-3">
                                                        <div className="flex items-center gap-3">
                                                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700">
                                                                <i className={`${category.icon} text-xl`} />
                                                            </div>
                                                            <div>
                                                                <h3 className="text-base font-bold text-slate-950">{category.name}</h3>
                                                                <p className="text-xs text-slate-500">{category.description || `${category.add_ons.length} ${t('add-ons')}`}</p>
                                                            </div>
                                                        </div>
                                                        <span className="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold text-slate-500 shadow-sm">
                                                            {category.add_ons.length} {t('add-ons')}
                                                        </span>
                                                    </div>

                                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                                                        {category.add_ons.map(addon => (
                                                            <a
                                                                key={addon.name}
                                                                href={addon.url}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="group block"
                                                            >
                                                                <Card className="h-full rounded-3xl border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
                                                                    <CardContent className="flex h-full items-center gap-4 p-4">
                                                                        <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50">
                                                                            {addon.image ? (
                                                                                <img
                                                                                    src={addon.image}
                                                                                    alt={addon.name}
                                                                                    className="h-10 w-10 rounded-xl object-contain"
                                                                                    onError={(event) => {
                                                                                        const el = event.target as HTMLImageElement;
                                                                                        el.style.display = 'none';
                                                                                        el.nextElementSibling?.classList.remove('hidden');
                                                                                    }}
                                                                                />
                                                                            ) : null}
                                                                            <Package className={`h-8 w-8 text-emerald-500 ${addon.image ? 'hidden' : ''}`} />
                                                                        </div>
                                                                        <div className="min-w-0 flex-1">
                                                                            <p className="line-clamp-1 text-sm font-bold text-slate-950">{addon.name}</p>
                                                                            <span className="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 transition group-hover:text-emerald-800">
                                                                                {t('View Details')} <ExternalLink className="h-3 w-3" />
                                                                            </span>
                                                                        </div>
                                                                    </CardContent>
                                                                </Card>
                                                            </a>
                                                        ))}
                                                    </div>
                                                </section>
                                            ))}

                                            {!exploreSearch && (
                                                <div className="rounded-3xl border border-dashed border-emerald-200 bg-emerald-50/60 p-5 sm:flex sm:items-center sm:justify-between sm:gap-4">
                                                    <div>
                                                        <p className="font-bold text-slate-900">{t("Can't find what you need?")}</p>
                                                        <p className="mt-1 text-sm text-slate-500">{t('Browse the full marketplace for more tools and business modules.')}</p>
                                                    </div>
                                                    <a
                                                        href={exploreUrl}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="mt-4 inline-flex shrink-0 items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 sm:mt-0"
                                                    >
                                                        {t('View All')} <ArrowRight className="h-4 w-4" />
                                                    </a>
                                                </div>
                                            )}
                                        </div>
                                    </ScrollArea>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            <Dialog open={isDetailsOpen} onOpenChange={setIsDetailsOpen}>
                <DialogContent className="max-w-md rounded-3xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-3">
                            <span className="flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50">
                                <img
                                    src={selectedModule?.image}
                                    alt={selectedModule?.alias}
                                    className="h-8 w-8 rounded-xl object-contain"
                                    onError={(event) => {
                                        const target = event.target as HTMLImageElement;
                                        target.style.display = 'none';
                                        target.nextElementSibling?.classList.remove('hidden');
                                    }}
                                />
                                <Package className="hidden h-8 w-8 text-emerald-600" />
                            </span>
                            {selectedModule?.alias}
                        </DialogTitle>
                        <DialogDescription>
                            {selectedModule?.description}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="grid grid-cols-2 gap-3 text-sm">
                            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                <span className="font-medium text-slate-500">{t('Version')}</span>
                                <p className="mt-1 font-bold text-emerald-700">v{selectedModule?.version}</p>
                            </div>
                            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                <span className="font-medium text-slate-500">{t('Status')}</span>
                                <p className={`mt-1 font-bold ${selectedModule?.is_enabled ? 'text-emerald-700' : 'text-slate-500'}`}>
                                    {selectedModule?.is_enabled ? t('Active') : t('Inactive')}
                                </p>
                            </div>
                        </div>
                        {selectedModule?.package_name && (
                            <div className="rounded-2xl border border-slate-200 bg-white p-3">
                                <span className="font-medium text-slate-500">{t('Package')}</span>
                                <p className="mt-1 text-sm font-semibold text-slate-800">{selectedModule.package_name}</p>
                            </div>
                        )}
                        {auth.user?.permissions?.includes('manage-actions') && (
                            <div className="flex gap-2 border-t border-slate-100 pt-4">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        if (selectedModule) {
                                            handleToggleModule(selectedModule.name, selectedModule.is_enabled);
                                            setIsDetailsOpen(false);
                                        }
                                    }}
                                    className={`h-10 flex-1 rounded-xl font-semibold ${
                                        selectedModule?.is_enabled
                                            ? 'border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700'
                                            : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800'
                                    }`}
                                >
                                    {selectedModule?.is_enabled ? (
                                        <>
                                            <PowerOff className="mr-2 h-4 w-4" />
                                            {t('Disable')}
                                        </>
                                    ) : (
                                        <>
                                            <Power className="mr-2 h-4 w-4" />
                                            {t('Enable')}
                                        </>
                                    )}
                                </Button>
                            </div>
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
