import { useState, Suspense, useEffect, useMemo } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ScrollArea } from '@/components/ui/scroll-area';
import { allSettingsItems } from '@/utils/settings';
import { getSettingsComponent } from '@/utils/settings-components';

const getHashSection = () => {
  if (typeof window === 'undefined') {
    return '';
  }

  return window.location.hash.replace('#', '');
};

export default function Settings() {
  const { t } = useTranslation();
  const { auth, globalSettings = {}, emailProviders = {}, cacheSize = '0.00', mpesaPaymentSettings = null } = usePage().props as any;
  const [activeSection, setActiveSection] = useState(() => getHashSection() || 'brand-settings');

  const userPermissions = auth?.user?.permissions || [];
  const userRoles = auth?.user?.roles || [];
  const activatedPackages = auth?.user?.activatedPackages || [];

  const sidebarNavItems = useMemo(
    () => allSettingsItems(userPermissions, userRoles, activatedPackages, t),
    [userPermissions, userRoles, activatedPackages, t]
  );

  const visibleSections = sidebarNavItems;

  const activeItem = useMemo(() => {
    return visibleSections.find((item) => item.href.replace('#', '') === activeSection) || visibleSections[0] || null;
  }, [activeSection, visibleSections]);

  const handleNavClick = (href: string) => {
    const id = href.replace('#', '');
    setActiveSection(id);

    if (typeof window !== 'undefined') {
      window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${id}`);
    }
  };

  useEffect(() => {
    if (!visibleSections.length) {
      return;
    }

    const hashSection = getHashSection();
    const hashItem = visibleSections.find((item) => item.href.replace('#', '') === hashSection);

    if (hashItem) {
      setActiveSection(hashSection);
      return;
    }

    const stillVisible = visibleSections.some((item) => item.href.replace('#', '') === activeSection);

    if (!stillVisible) {
      setActiveSection(visibleSections[0].href.replace('#', ''));
    }
  }, [visibleSections, activeSection]);

  const ActiveComponent = activeItem ? getSettingsComponent(activeItem.component, activatedPackages) : null;

  return (
    <AuthenticatedLayout
      breadcrumbs={[{ label: t('Settings') }]}
      pageTitle={t('Settings')}
    >
      <Head title={t('Settings')} />

      <div className="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
        {/* Sidebar Navigation */}
        <aside className="min-w-0">
          <div className="sticky top-4 rounded-2xl border bg-card p-3 shadow-sm">
            <div className="px-3 py-2">
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {t('Settings Menu')}
              </p>
            </div>

            <ScrollArea className="h-[calc(100vh-12rem)] pr-2">
              <div className="space-y-1">
                {visibleSections.map((item) => {
                  const itemId = item.href.replace('#', '');
                  const isActive = activeSection === itemId;

                  return (
                    <Button
                      key={`${item.href}-${item.component}`}
                      variant="ghost"
                      className={cn(
                        'w-full justify-start gap-3 rounded-xl px-3 py-5 text-left text-sm font-medium',
                        isActive && 'bg-primary/10 text-primary shadow-none hover:bg-primary/10 hover:text-primary'
                      )}
                      onClick={() => handleNavClick(item.href)}
                    >
                      <item.icon className="h-4 w-4 shrink-0" />
                      <span className="truncate">{item.title}</span>
                    </Button>
                  );
                })}
              </div>
            </ScrollArea>
          </div>
        </aside>

        {/* Single active settings page */}
        <main className="min-w-0">
          <div className="mb-5 rounded-2xl border bg-card px-6 py-5 shadow-sm">
            <p className="text-sm text-muted-foreground">{t('Settings')}</p>
            <h1 className="text-2xl font-semibold tracking-tight">
              {activeItem?.title || t('Settings')}
            </h1>
            <p className="mt-1 text-sm text-muted-foreground">
              {activeItem?.component === 'payment-gateway-settings'
                ? t('Configure platform payment methods and M-Pesa collection rules from one clean page.')
                : t('Update the selected system configuration page.')}
            </p>
          </div>

          <div id={activeItem?.href.replace('#', '')} className="min-w-0">
            <Suspense fallback={<div className="rounded-2xl border bg-card p-6 shadow-sm">{t('Loading...')}</div>}>
              {ActiveComponent ? (
                <ActiveComponent
                  userSettings={globalSettings}
                  auth={auth}
                  emailProviders={emailProviders}
                  cacheSize={cacheSize}
                  mpesaPaymentSettings={mpesaPaymentSettings}
                />
              ) : (
                <div className="rounded-2xl border bg-card p-6 text-sm text-muted-foreground shadow-sm">
                  {t('This settings page is not available.')}
                </div>
              )}
            </Suspense>
          </div>
        </main>
      </div>
    </AuthenticatedLayout>
  );
}
