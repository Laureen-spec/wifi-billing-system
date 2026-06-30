import { PropsWithChildren, ReactNode, Fragment } from "react";
import {AppSidebar} from "@/components/app-sidebar";
import {SidebarInset, SidebarProvider, SidebarTrigger} from "@/components/ui/sidebar";
import {Separator} from "@/components/ui/separator";
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbLink,
    BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import { NavUser } from "@/components/nav-user";
import { TopbarActions } from "@/components/topbar-actions";
import { usePage, Head, Link, router } from "@inertiajs/react";
import { PageProps } from "@/types";
import { BrandProvider, useBrand } from "@/contexts/brand-context";
import CookieConsent from "@/components/cookie-consent";
import { useFavicon } from "@/hooks/use-favicon";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { UserX, Bot } from "lucide-react";
import { useFormFields } from '@/hooks/useFormFields';
import { getImagePath } from '@/utils/helpers';

function AuthenticatedLayoutContent({
    header,
    children,
    breadcrumbs,
    pageTitle,
    pageActions
}: PropsWithChildren<{
    header?: ReactNode;
    breadcrumbs?: Array<{label: string, url?: string}>;
    pageTitle?: string;
    pageActions?: ReactNode;
    className?: string;
}>) {
    const { t } = useTranslation();
    const { auth, companyAllSetting, adminAllSetting } = usePage<PageProps>().props as any;
    const { settings } = useBrand();
    useFavicon();

    const generalAlerts = useFormFields('generalAlert', {}, () => {}, {});

    // Check if current page is AI Agent chat page
    const isAIAgentPage = window.location.pathname.includes('/ai-agent/chat');
    const hiddenMenuKeys = ((auth as any)?.menuVisibility?.hidden || []) as string[];
    const aiAgentHidden = hiddenMenuKeys.includes('ai-agent');


    return (
        <>
        <Head title={adminAllSetting?.metaTitle}>
            {adminAllSetting?.metaKeywords && (
                <meta name="keywords" content={adminAllSetting.metaKeywords} />
            )}
            {adminAllSetting?.metaDescription && (
                <meta name="description" content={adminAllSetting.metaDescription} />
            )}
            {adminAllSetting?.metaImage && (
                <meta property="og:image" content={getImagePath(adminAllSetting.metaImage)} />
            )}
        </Head>
        <div
            className={settings.layoutDirection === 'rtl' ? 'rtl' : 'ltr'}
            data-theme={settings.themeMode}
            dir={settings.layoutDirection === 'rtl' ? 'rtl' : 'ltr'}
            style={{ direction: settings.layoutDirection === 'rtl' ? 'rtl' : 'ltr' }}
        >
        <SidebarProvider defaultOpen={true}>
            <AppSidebar />

            <SidebarInset className="overflow-visible"
                style={{ direction: settings.layoutDirection === 'rtl' ? 'rtl' : 'ltr' }}
                dir={settings.layoutDirection === 'rtl' ? 'rtl' : 'ltr'}
            >
                <header
                    className="sticky top-0 z-30 flex min-h-[64px] shrink-0 items-center justify-between gap-3 border-b border-slate-200/80 bg-white/90 px-5 py-2.5 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/85"
                >
                    {/* Sidebar + Breadcrumb */}
                    <div className={`flex min-w-0 items-center gap-3 ${ settings.layoutDirection === "rtl" ? "order-2 flex-row-reverse" : "order-1" }`}>
                        <SidebarTrigger className={`h-9 w-9 rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 ${ settings.layoutDirection === "rtl" ? "order-3" : "order-1" }`} />

                        <Separator orientation="vertical" className="order-2 hidden h-6 bg-slate-200 md:block dark:bg-slate-800" />

                        <Breadcrumb className={`${ settings.layoutDirection === "rtl" ? "order-1" : "order-3" } hidden min-w-0 md:block`}>
                            <BreadcrumbList className={`flex text-sm font-medium text-slate-500 ${ settings.layoutDirection === "rtl" ? "justify-end" : "justify-start" }`}>
                            <BreadcrumbItem>
                                <BreadcrumbLink asChild>
                                    <Link className="text-slate-500 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100" href={route("dashboard")}>{t('Dashboard')}</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            {breadcrumbs?.map((crumb, index) => (
                                <Fragment key={index}>
                                <BreadcrumbSeparator className={settings.layoutDirection === 'rtl' ? 'rotate-180 text-slate-300' : 'text-slate-300'} />
                                <BreadcrumbItem>
                                    {crumb.url ? (
                                    <BreadcrumbLink asChild>
                                        <Link className="text-slate-500 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100" href={crumb.url}>{crumb.label}</Link>
                                    </BreadcrumbLink>
                                    ) : (
                                    <BreadcrumbPage className="font-semibold text-slate-900 dark:text-slate-100">{crumb.label}</BreadcrumbPage>
                                    )}
                                </BreadcrumbItem>
                                </Fragment>
                            ))}
                            </BreadcrumbList>
                        </Breadcrumb>
                    </div>

                    <div
                        className={`flex min-w-0 shrink-0 items-center justify-end gap-2 ${
                        settings.layoutDirection === "rtl" ? "order-1 flex-row-reverse" : "order-2"
                        }`}
                    >
                        {auth.impersonating && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.post(route('users.leave-impersonation'))}
                                className="h-9 rounded-xl border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100 hover:text-orange-800"
                            >
                                <UserX className="h-4 w-4 mr-2" />
                                {t('Leave Login As User')}
                            </Button>
                        )}
                        <TopbarActions />
                        <NavUser user={auth.user} inHeader={true} />
                    </div>
                </header>

                <main className="h-full p-4">
                    {pageTitle && (
                        <div className="flex items-center mb-6" dir={settings.layoutDirection}>
                            <h1 className="text-xl font-semibold text-gray-900 dark:text-white flex-1">{pageTitle}</h1>
                            <div className="flex-shrink-0">{pageActions}</div>
                        </div>
                    )}
                    {children}
                </main>
            </SidebarInset>
        </SidebarProvider>
        <CookieConsent settings={adminAllSetting || {}} />
        {generalAlerts.map((alert) => (
            <div key={alert.id}>{alert.component}</div>
        ))}
        
        {/* Floating AI Agent Button */}
        {auth.user?.permissions?.includes('manage-ai-agent') && !isAIAgentPage && !aiAgentHidden && (
            <div className="fixed bottom-8 right-8 z-50 animate-bounce" style={{ animationDuration: '2s' }}>
                <Button
                    onClick={() => router.visit(route('ai-agent.chat.page'))}
                    className="h-14 w-14 rounded-full shadow-lg hover:shadow-xl transition-shadow duration-200 bg-primary hover:bg-primary/90 p-0 [&_svg]:!size-7"
                >
                    <Bot className="text-primary-foreground" strokeWidth={2} />
                </Button>
            </div>
        )}
        </div>
        </>
    );
}

export default function AuthenticatedLayout(props: PropsWithChildren<{
    header?: ReactNode;
    breadcrumbs?: Array<{label: string, url?: string}>;
    pageTitle?: string;
    pageActions?: ReactNode;
    className?: string;
}>) {
    return (
        <BrandProvider>
            <AuthenticatedLayoutContent {...props} />
        </BrandProvider>
    );
}
