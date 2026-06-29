import { Link, Head, usePage } from '@inertiajs/react';
import { type PropsWithChildren, useState } from 'react';
import { LanguageSwitcher } from '@/components/language-switcher';
import { useBrand } from '@/contexts/brand-context';
import { useFavicon } from '@/hooks/use-favicon';
import { getImagePath } from '@/utils/helpers';
import CookieConsent from '@/components/cookie-consent';
import { BadgeCheck, BarChart3, LockKeyhole, Router, ShieldCheck, Users, Wifi } from 'lucide-react';

interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: PropsWithChildren<AuthLayoutProps>) {
    const { settings, getPrimaryColor, getLogoSrc } = useBrand();
    const { adminAllSetting } = usePage().props as any;
    const [logoFailed, setLogoFailed] = useState(false);
    useFavicon();

    const logoSrc = getLogoSrc();
    const primaryColor = getPrimaryColor();
    const brandName = settings.titleText || 'StudyRoom Connect';
    const hasLogo = Boolean(logoSrc) && !logoFailed;

    return (
        <>
            <Head title={adminAllSetting?.metaTitle || title}>
                <meta name="keywords" content={adminAllSetting?.metaKeywords || ''} />
                <meta name="description" content={adminAllSetting?.metaDescription || ''} />
                <meta property="og:image" content={adminAllSetting?.metaImage ? getImagePath(adminAllSetting.metaImage) : ''} />
            </Head>

            <div className="relative min-h-svh overflow-hidden bg-[#f6f7f4] text-slate-950 dark:bg-slate-950 dark:text-white">
                <style>{`
                    .auth-primary-bg { background-color: ${primaryColor}; }
                    .auth-primary-text { color: ${primaryColor}; }
                    .auth-primary-border { border-color: ${primaryColor}; }
                    .dark .bg-primary { background-color: ${primaryColor} !important; color: white !important; }
                    .dark .bg-primary:hover { background-color: ${primaryColor}dd !important; }
                `}</style>

                <div
                    className="absolute inset-0 opacity-[0.42] dark:opacity-[0.16]"
                    style={{
                        backgroundImage: 'radial-gradient(circle at 1px 1px, rgba(15, 23, 42, 0.16) 1px, transparent 0)',
                        backgroundSize: '28px 28px',
                    }}
                />
                <div className="absolute -left-24 top-16 h-72 w-72 rounded-full bg-emerald-200/45 blur-3xl dark:bg-emerald-500/10" />
                <div className="absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-slate-300/60 blur-3xl dark:bg-slate-700/20" />
                <div className="absolute left-[38%] top-0 h-40 w-40 rounded-full bg-white/70 blur-3xl dark:bg-white/5" />

                <div className="absolute right-5 top-5 z-20">
                    <LanguageSwitcher variant="header" />
                </div>

                <main className="relative z-10 grid min-h-svh grid-cols-1 lg:grid-cols-[1.05fr_0.95fr]">
                    <section className="hidden border-r border-slate-200/80 bg-white/45 px-10 py-10 backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.03] lg:flex lg:flex-col lg:justify-between xl:px-16">
                        <div>
                            <Link href={route('dashboard')} className="inline-flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-100 bg-white shadow-sm dark:border-white/10 dark:bg-white/10">
                                    {hasLogo ? (
                                        <img
                                            src={getImagePath(logoSrc)}
                                            alt={brandName}
                                            onError={() => setLogoFailed(true)}
                                            className="max-h-8 max-w-8 object-contain"
                                        />
                                    ) : (
                                        <Wifi className="h-7 w-7 auth-primary-text" />
                                    )}
                                </div>
                                <div>
                                    <p className="text-lg font-bold tracking-tight text-slate-950 dark:text-white">{brandName}</p>
                                    <p className="text-sm text-slate-500 dark:text-slate-400">WiFi Billing System</p>
                                </div>
                            </Link>

                            <div className="mt-20 max-w-xl">
                                <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                    <ShieldCheck className="h-4 w-4" />
                                    Secure ISP operations portal
                                </div>
                                <h2 className="text-5xl font-black leading-[1.02] tracking-tight text-slate-950 dark:text-white">
                                    Manage subscribers, routers, and collections from one workspace.
                                </h2>
                                <p className="mt-6 max-w-lg text-lg leading-8 text-slate-600 dark:text-slate-300">
                                    Sign in to continue to your billing dashboard, payment center, hotspot revenue tools, and network operations.
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            {[
                                { label: 'Subscriber billing', icon: Users },
                                { label: 'Router control', icon: Router },
                                { label: 'Payment ledger', icon: BarChart3 },
                                { label: 'Protected access', icon: LockKeyhole },
                            ].map((item) => (
                                <div key={item.label} className="rounded-2xl border border-white/70 bg-white/70 p-4 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/[0.06]">
                                    <item.icon className="mb-3 h-5 w-5 auth-primary-text" />
                                    <p className="text-sm font-semibold text-slate-800 dark:text-slate-100">{item.label}</p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="flex min-h-svh items-center justify-center px-5 py-20 sm:px-8 lg:px-12">
                        <div className="w-full max-w-[480px]">
                            <div className="mb-8 flex items-center justify-center gap-3 lg:hidden">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-100 bg-white shadow-sm dark:border-white/10 dark:bg-white/10">
                                    {hasLogo ? (
                                        <img
                                            src={getImagePath(logoSrc)}
                                            alt={brandName}
                                            onError={() => setLogoFailed(true)}
                                            className="max-h-8 max-w-8 object-contain"
                                        />
                                    ) : (
                                        <Wifi className="h-7 w-7 auth-primary-text" />
                                    )}
                                </div>
                                <div>
                                    <p className="text-lg font-bold tracking-tight">{brandName}</p>
                                    <p className="text-sm text-slate-500 dark:text-slate-400">WiFi Billing System</p>
                                </div>
                            </div>

                            <div className="overflow-hidden rounded-[2rem] border border-white bg-white shadow-[0_24px_80px_rgba(15,23,42,0.12)] dark:border-white/10 dark:bg-slate-900/95">
                                <div className="border-b border-slate-100 bg-slate-50/70 px-7 py-6 dark:border-white/10 dark:bg-white/[0.03] sm:px-9">
                                    <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700 shadow-sm dark:border-emerald-500/20 dark:bg-white/5 dark:text-emerald-300">
                                        <BadgeCheck className="h-3.5 w-3.5" />
                                        Verified workspace
                                    </div>
                                    <h1 className="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">{title}</h1>
                                    <p className="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{description}</p>
                                </div>

                                <div className="px-7 py-7 sm:px-9 sm:py-8">
                                    {children}
                                </div>
                            </div>

                        </div>
                    </section>
                </main>
                <CookieConsent settings={adminAllSetting || {}} />
            </div>
        </>
    );
}
