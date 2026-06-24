import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Head } from '@inertiajs/react';
import { Router, Users, Wifi } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export default function Dashboard() {
    const { t } = useTranslation();

    const cards = [
        {
            title: t('ISP Billing'),
            value: t('Ready'),
            icon: Wifi,
        },
        {
            title: t('Customer Accounts'),
            value: t('Next phase'),
            icon: Users,
        },
        {
            title: t('MikroTik Provisioning'),
            value: t('Next phase'),
            icon: Router,
        },
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('WiFi Billing') }]}
            pageTitle={t('WiFi Billing')}
        >
            <Head title={t('WiFi Billing')} />

            <div className="grid gap-4 md:grid-cols-3">
                {cards.map((card) => {
                    const Icon = card.icon;

                    return (
                        <div key={card.title} className="rounded-lg border bg-card p-4 text-card-foreground shadow-sm">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <p className="text-sm text-muted-foreground">{card.title}</p>
                                    <p className="mt-2 text-2xl font-semibold">{card.value}</p>
                                </div>
                                <Icon className="h-5 w-5 text-primary" />
                            </div>
                        </div>
                    );
                })}
            </div>
        </AuthenticatedLayout>
    );
}
