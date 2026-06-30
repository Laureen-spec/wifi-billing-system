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

const settingDescription = (component?: string, t?: (key: string) => string) => {
  if (!t) return '';

  if (component === 'payment-gateway-settings') {
    return t('Configure platform payment methods, M-Pesa credentials, and collection rules.');
  }

  if (component === 'admin-module-settings') {
    return t('Open the payment, SMS, hotspot portal, and template settings available to your ISP workspace.');
  }

  if (component === 'currency-settings') {
    return t('Manage default currency and invoice formatting.');
  }

  if (component === 'brand-settings') {
    return t('Update logo, colors, branding, and product identity.');
  }

  if (component === 'system-settings') {
    return t('Manage system preferences and platform behavior.');
  }

  if (component === 'menu-preferences') {
    return t('Rename sidebar menu labels for your own account and reset them any time.');
  }

  return t('Update the selected system configuration page.');
};

export default function Settings() {
  const { t } = useTranslation();
  const { auth, globalSettings = {}, emailProviders = {}, cacheSize = '0.00', mpesaPaymentSettings = null } = usePage().props as any;
  const [activeSection, setActiveSection] = useState(() => getHashSection());

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
      const firstSection = visibleSections[0].href.replace('#', '');
      setActiveSection(firstSection);

      if (typeof window !== 'undefined' && firstSection) {
        window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${firstSection}`);
      }
    }
  }, [visibleSections, activeSection]);

  const ActiveComponent = activeItem ? getSettingsComponent(activeItem.component, activatedPackages) : null;

  return (
    <AuthenticatedLayout
      breadcrumbs={[{ label: t('Settings') }, ...(activeItem ? [{ label: activeItem.title }] : [])]}
    >
      <Head title={activeItem?.title || t('Settings')} />

      <div className="space-y-6">
        <div className="rounded-3xl border bg-card px-6 py-5 shadow-sm">
          <p className="text-sm text-muted-foreground">{t('Settings')}</p>
          <h1 className="mt-1 text-2xl font-semibold tracking-tight text-foreground">
            {activeItem?.title || t('Settings')}
          </h1>
          <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
            {settingDescription(activeItem?.component, t)}
          </p>
        </div>

        <div className="grid gap-6 xl:grid-cols-[280px_minmax(0,1fr)]">
          <aside className="min-w-0">
            <div className="sticky top-20 rounded-3xl border bg-card p-3 shadow-sm">
              <div className="px-3 py-2">
                <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  {t('Settings Menu')}
                </p>
              </div>

              <ScrollArea className="h-[calc(100vh-15rem)] pr-2">
                <div className="space-y-1">
                  {visibleSections.map((item) => {
                    const itemId = item.href.replace('#', '');
                    const isActive = activeSection === itemId;

                    return (
                      <Button
                        key={`${item.href}-${item.component}`}
                        variant="ghost"
                        className={cn(
                          'h-auto w-full justify-start gap-3 rounded-2xl px-3 py-3 text-left text-sm font-medium',
                          isActive && 'bg-primary/10 text-primary shadow-none hover:bg-primary/10 hover:text-primary'
                        )}
                        onClick={() => handleNavClick(item.href)}
                      >
                        <span className={cn(
                          'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border bg-background text-muted-foreground',
                          isActive && 'border-primary/20 bg-primary/10 text-primary'
                        )}>
                          <item.icon className="h-4 w-4" />
                        </span>
                        <span className="min-w-0">
                          <span className="block truncate">{item.title}</span>
                          <span className="block truncate text-xs font-normal text-muted-foreground">
                            {item.component === 'payment-gateway-settings'
                              ? t('Manage payment gateways')
                              : item.component === 'admin-module-settings'
                                ? t('SMS, payment and hotspot')
                                : item.component === 'brand-settings'
                                  ? t('Logo, colors and branding')
                                  : item.component === 'system-settings'
                                    ? t('General system preferences')
                                    : item.component === 'currency-settings'
                                      ? t('Manage currencies')
                                      : t('Configure settings')}
                          </span>
                        </span>
                      </Button>
                    );
                  })}
                </div>
              </ScrollArea>
            </div>
          </aside>

          <main className="min-w-0">
            <div id={activeItem?.href.replace('#', '')} className="min-w-0">
              <Suspense fallback={<div className="rounded-3xl border bg-card p-6 shadow-sm">{t('Loading...')}</div>}>
                {ActiveComponent ? (
                  <ActiveComponent
                    userSettings={globalSettings}
                    auth={auth}
                    emailProviders={emailProviders}
                    cacheSize={cacheSize}
                    mpesaPaymentSettings={mpesaPaymentSettings}
                  />
                ) : (
                  <div className="rounded-3xl border bg-card p-6 text-sm text-muted-foreground shadow-sm">
                    {t('This settings page is not available.')}
                  </div>
                )}
              </Suspense>
            </div>
          </main>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
