import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { CreditCard, MessageSquareText, FileText, Wifi, ArrowUpRight, ShieldCheck } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const routeExists = (name: string): boolean => {
  try {
    const ziggy = (route as any)();
    return typeof ziggy?.has === 'function' ? ziggy.has(name) : true;
  } catch (error) {
    return false;
  }
};

const safeRoute = (name: string, fallback: string): string => {
  try {
    return routeExists(name) ? route(name) : fallback;
  } catch (error) {
    return fallback;
  }
};

const adminSettings = [
  {
    title: 'Payment Settings',
    description: 'Choose system payment collection or connect your own gateway for customer payments.',
    helper: 'Admin collection rule, M-Pesa mode, phone, till, or paybill setup.',
    routeName: 'mpesa-payment.settings.admin',
    fallback: '/mpesa-payment/admin-settings',
    icon: CreditCard,
  },
  {
    title: 'SMS Settings',
    description: 'Manage your SMS gateway rule, balance usage, and own API connection preference.',
    helper: 'System SMS balance or own SMS API settings.',
    routeName: 'isp.sms.settings',
    fallback: '/isp/sms/settings',
    icon: MessageSquareText,
  },
  {
    title: 'SMS Template Settings',
    description: 'Create and manage reusable SMS templates for customer communication.',
    helper: 'Payment reminders, expiry alerts, and support messages.',
    routeName: 'isp.sms.templates.index',
    fallback: '/isp/sms/templates',
    icon: FileText,
  },
  {
    title: 'Hotspot Template Settings',
    description: 'Customize the customer hotspot portal, package display, colors, and access flow.',
    helper: 'Portal branding, free access section, and customer payment page.',
    routeName: 'wifi-billing.settings.hotspot-template.edit',
    fallback: '/wifi-billing/settings/hotspot-template',
    icon: Wifi,
  },
];

export default function AdminModuleSettings() {
  const { t } = useTranslation();

  return (
    <div className="space-y-6">
      <Card className="rounded-3xl border bg-card shadow-sm">
        <CardHeader className="border-b">
          <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
              <CardTitle className="text-xl font-semibold text-foreground">{t('Admin Settings')}</CardTitle>
              <CardDescription className="mt-1 max-w-2xl text-sm text-muted-foreground">
                {t('Manage the settings available to your ISP workspace. Platform-only settings remain hidden and controlled by Super Admin.')}
              </CardDescription>
            </div>
            <div className="inline-flex items-center gap-2 rounded-2xl border bg-background px-3 py-2 text-xs font-medium text-muted-foreground">
              <ShieldCheck className="h-4 w-4 text-primary" />
              {t('Workspace scoped')}
            </div>
          </div>
        </CardHeader>
        <CardContent className="p-4 sm:p-6">
          <div className="grid gap-4 lg:grid-cols-2">
            {adminSettings.map((item) => {
              const Icon = item.icon;
              const href = safeRoute(item.routeName, item.fallback);

              return (
                <div
                  key={item.routeName}
                  className="group rounded-3xl border bg-background p-5 shadow-sm transition hover:border-primary/30 hover:bg-primary/[0.03]"
                >
                  <div className="flex items-start gap-4">
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border bg-card text-primary shadow-sm">
                      <Icon className="h-5 w-5" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <h3 className="text-base font-semibold text-foreground">{t(item.title)}</h3>
                      <p className="mt-1 text-sm leading-6 text-muted-foreground">{t(item.description)}</p>
                      <p className="mt-3 rounded-2xl bg-muted/50 px-3 py-2 text-xs text-muted-foreground">
                        {t(item.helper)}
                      </p>
                    </div>
                  </div>

                  <div className="mt-5 flex justify-end">
                    <Button asChild className="rounded-2xl">
                      <Link href={href}>
                        {t('Open settings')}
                        <ArrowUpRight className="ml-2 h-4 w-4" />
                      </Link>
                    </Button>
                  </div>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
