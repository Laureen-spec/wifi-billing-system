import { Menu, X } from 'lucide-react';
import { type MouseEvent, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { getAdminSetting, getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';
import { LanguageSwitcher } from '@/components/language-switcher';

interface HeaderProps {
    settings?: any;
}

export default function Header({ settings }: HeaderProps) {
    const sectionData = settings?.config_sections?.sections?.header || {};
    const colors = settings?.config_sections?.colors || { primary: '#0f766e', secondary: '#0ea5e9', accent: '#f59e0b' };
    const { t } = useTranslation();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const companyName = sectionData.company_name || settings?.company_name || 'StudyRoomTechLab WiFi Billing';
    const isAuthenticated = settings?.is_authenticated;
    const ctaText = isAuthenticated ? t('Dashboard') : (sectionData.cta_text || t('Get Started'));
    const enableRegistration = settings?.enable_registration !== false;

    const logoPath = getAdminSetting('logo_dark');
    const logoUrl = logoPath ? getImagePath(logoPath) : null;

    const getDefaultHrefForText = (text?: string) => {
        const cleanText = String(text || '').toLowerCase().replace(/[^a-z0-9]/g, '');

        if (cleanText === 'home') return '#home';
        if (cleanText === 'features') return '#features';
        if (cleanText === 'modules') return '#modules';
        if (cleanText === 'pricing') return route('pricing.page');
        if (cleanText === 'addons') return route('addons.page');
        if (cleanText === 'addon') return route('addons.page');

        return '#';
    };

    const normalizeHref = (href?: string, text?: string) => {
        if (!href || href === '#') return getDefaultHrefForText(text);
        return href.startsWith('/page/') ? route('custom-page.show', href.replace('/page/', '')) : href;
    };

    const defaultNavigationItems = [
        { text: t('Home'), href: '#home' },
        { text: t('Features'), href: '#features' },
        { text: t('Modules'), href: '#modules' },
        { text: t('Pricing'), href: route('pricing.page') },
        { text: t('Add-ons'), href: route('addons.page') },
    ];

    const baseNavigationItems = Array.isArray(sectionData.navigation_items) && sectionData.navigation_items.length > 0
        ? sectionData.navigation_items
        : defaultNavigationItems;

    const navigationItems = [...baseNavigationItems];
    const hasHref = (needle: string) => navigationItems.some((item: any) => String(item?.href || '').toLowerCase().includes(needle));
    const hasText = (needle: string) => navigationItems.some((item: any) => String(item?.text || '').toLowerCase().replace(/[^a-z0-9]/g, '').includes(needle));

    if (sectionData?.enable_addon_link !== false && !hasHref('/addons') && !hasText('addons')) {
        navigationItems.push({ text: t('Add-ons'), href: route('addons.page') });
    }

    if (sectionData?.enable_pricing_link !== false && !hasHref('/pricing') && !hasText('pricing')) {
        navigationItems.push({ text: t('Pricing'), href: route('pricing.page') });
    }

    const handleHashNavigation = (event: MouseEvent<HTMLAnchorElement>, href: string, text?: string) => {
        if (!href.startsWith('#')) return;

        const element = document.querySelector(href);

        if (element) {
            event.preventDefault();
            setMobileMenuOpen(false);
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            window.history.replaceState(null, '', href);
            return;
        }

        const fallbackHref = getDefaultHrefForText(text);
        if (fallbackHref && !fallbackHref.startsWith('#')) {
            event.preventDefault();
            setMobileMenuOpen(false);
            router.visit(fallbackHref);
        }
    };

    const renderNavItem = (item: any, isMobile = false) => {
        const href = normalizeHref(item.href, item.text);
        const className = isMobile
            ? 'block rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50'
            : 'rounded-full px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white hover:shadow-sm';

        const content = item.text;

        if (href.startsWith('#')) {
            return (
                <a
                    key={`${item.text}-${href}`}
                    href={href}
                    className={className}
                    onClick={(event) => handleHashNavigation(event, href, item.text)}
                    onMouseEnter={(e) => e.currentTarget.style.color = colors.primary}
                    onMouseLeave={(e) => e.currentTarget.style.color = ''}
                >
                    {content}
                </a>
            );
        }

        if (item.target === '_blank') {
            return (
                <a key={`${item.text}-${href}`} href={href} target="_blank" rel="noopener noreferrer" className={className}
                    onMouseEnter={(e) => e.currentTarget.style.color = colors.primary}
                    onMouseLeave={(e) => e.currentTarget.style.color = ''}
                >
                    {content}
                </a>
            );
        }

        return (
            <Link key={`${item.text}-${href}`} href={href} className={className}
                onMouseEnter={(e) => e.currentTarget.style.color = colors.primary}
                onMouseLeave={(e) => e.currentTarget.style.color = ''}
            >
                {content}
            </Link>
        );
    };

    const renderActions = (isMobile = false) => {
        if (isAuthenticated) {
            return (
                <button
                    type="button"
                    onClick={() => router.visit(route('dashboard'))}
                    className={isMobile ? 'w-full rounded-xl px-4 py-3 text-sm font-bold text-white shadow-sm transition' : 'rounded-full px-5 py-2.5 text-sm font-bold text-white shadow-sm transition'}
                    style={{ backgroundColor: colors.primary }}
                    onMouseEnter={(e) => e.currentTarget.style.backgroundColor = colors.secondary}
                    onMouseLeave={(e) => e.currentTarget.style.backgroundColor = colors.primary}
                >
                    {ctaText}
                </button>
            );
        }

        if (!enableRegistration) {
            return (
                <button
                    type="button"
                    onClick={() => router.visit(route('login'))}
                    className={isMobile ? 'w-full rounded-xl px-4 py-3 text-sm font-bold text-white shadow-sm transition' : 'rounded-full px-5 py-2.5 text-sm font-bold text-white shadow-sm transition'}
                    style={{ backgroundColor: colors.primary }}
                >
                    {t('Sign In')}
                </button>
            );
        }

        return (
            <div className={isMobile ? 'grid gap-2' : 'flex items-center gap-2'}>
                <button
                    type="button"
                    onClick={() => router.visit(route('login'))}
                    className={isMobile ? 'w-full rounded-xl border px-4 py-3 text-sm font-bold transition' : 'rounded-xl border px-4 py-2.5 text-sm font-bold transition'}
                    style={{ borderColor: colors.primary, color: colors.primary }}
                    onMouseEnter={(e) => {
                        e.currentTarget.style.backgroundColor = colors.primary;
                        e.currentTarget.style.color = '#fff';
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.backgroundColor = 'transparent';
                        e.currentTarget.style.color = colors.primary;
                    }}
                >
                    {t('Sign In')}
                </button>
                <button
                    type="button"
                    onClick={() => router.visit(route('register'))}
                    className={isMobile ? 'w-full rounded-xl px-4 py-3 text-sm font-bold text-white shadow-sm transition' : 'rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm transition'}
                    style={{ backgroundColor: colors.primary }}
                    onMouseEnter={(e) => e.currentTarget.style.backgroundColor = colors.secondary}
                    onMouseLeave={(e) => e.currentTarget.style.backgroundColor = colors.primary}
                >
                    {sectionData.cta_text || t('Get Started')}
                </button>
            </div>
        );
    };

    return (
        <header className="sticky top-0 z-50 border-b border-slate-200/70 bg-white/90 backdrop-blur-xl">
            <div className="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between gap-4">
                    <Link href={route('landing.page')} className="flex min-w-0 items-center gap-3">
                        {logoUrl ? (
                            <img src={logoUrl} alt={companyName} className="h-10 w-auto max-w-[180px] object-contain" />
                        ) : (
                            <>
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border bg-white shadow-sm" style={{ borderColor: `${colors.primary}30`, color: colors.primary }}>
                                    <span className="h-3 w-3 rounded-full" style={{ backgroundColor: colors.primary }} />
                                </span>
                                <span className="truncate text-lg font-black tracking-tight text-slate-950">{companyName}</span>
                            </>
                        )}
                    </Link>

                    <nav className="hidden items-center gap-1 rounded-full border border-slate-200 bg-slate-50/80 p-1.5 shadow-sm lg:flex">
                        {navigationItems.map((item: any) => renderNavItem(item))}
                    </nav>

                    <div className="hidden items-center gap-3 lg:flex">
                        {renderActions()}
                        <div className="h-8 w-px bg-slate-200" />
                        <LanguageSwitcher />
                    </div>

                    <button
                        type="button"
                        aria-label={mobileMenuOpen ? 'Close menu' : 'Open menu'}
                        className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 lg:hidden"
                        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                    >
                        {mobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                    </button>
                </div>

                {mobileMenuOpen && (
                    <div className="mt-3 rounded-3xl border border-slate-200 bg-white p-3 shadow-xl lg:hidden">
                        <div className="grid gap-1">
                            {navigationItems.map((item: any) => renderNavItem(item, true))}
                        </div>
                        <div className="mt-3 border-t border-slate-100 pt-3">
                            {renderActions(true)}
                            <div className="mt-3">
                                <LanguageSwitcher />
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </header>
    );
}
