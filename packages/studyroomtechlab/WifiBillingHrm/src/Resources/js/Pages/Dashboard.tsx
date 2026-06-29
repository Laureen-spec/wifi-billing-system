import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Head } from '@inertiajs/react';
import { ClipboardList, Headphones, UserCheck } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export default function Dashboard() {
    const { t } = useTranslation();

    const cards = [
        {
            title: t('Installers'),
            value: t('Ready for setup'),
            icon: UserCheck,
        },
        {
            title: t('Field Jobs'),
            value: t('Next phase'),
            icon: ClipboardList,
        },
        {
            title: t('Support Workflow'),
            value: t('Next phase'),
            icon: Headphones,
        },
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('WiFi Billing'), url: route('wifi-billing.dashboard') },
                { label: t('HRM & Field Team') },
            ]}
            pageTitle={t('HRM & Field Team')}
        >
            <Head title={t('HRM & Field Team')} />

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
