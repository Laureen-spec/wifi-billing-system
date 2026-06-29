import { Head } from '@inertiajs/react';
import { CheckCircle2, Database, PackageCheck, Server, ShieldCheck } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { InstallerActions, InstallerShell } from './InstallerShell';

export default function Welcome() {
    const { t } = useTranslation();
    const cards = [
        { icon: Server, title: t('Server scan'), text: t('Check PHP version and required extensions before installation starts.') },
        { icon: ShieldCheck, title: t('Safe setup'), text: t('Verify writable folders and generate required environment values.') },
        { icon: Database, title: t('Fresh database'), text: t('Create tables, roles, permissions, and core system settings.') },
        { icon: PackageCheck, title: t('ISP modules'), text: t('Enable WiFi Billing modules for your company account automatically.') },
    ];

    return (
        <>
            <Head title={t('Installation - Welcome')} />
            <InstallerShell
                step="welcome"
                eyebrow={t('Fresh installation')}
                title={t('Set up your WiFi billing system')}
                description={t('This guided installer prepares your local or production system with the database, admin accounts, and StudyRoom TechLab billing modules.')}
                footer={<InstallerActions nextHref={route('installer.requirements')} nextLabel={t('Start Installation')} />}
                wide
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    {cards.map((item) => {
                        const Icon = item.icon;
                        return (
                            <div key={item.title} className="rounded-3xl border border-slate-200 bg-slate-50 p-5 transition hover:-translate-y-1 hover:bg-white hover:shadow-xl hover:shadow-slate-200/70">
                                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-950 text-cyan-300">
                                    <Icon className="h-6 w-6" />
                                </div>
                                <h3 className="text-lg font-black text-slate-950">{item.title}</h3>
                                <p className="mt-2 text-sm leading-6 text-slate-600">{item.text}</p>
                            </div>
                        );
                    })}
                </div>

                <div className="mt-6 rounded-3xl border border-cyan-200 bg-cyan-50 p-5">
                    <div className="flex items-start gap-3">
                        <CheckCircle2 className="mt-1 h-5 w-5 flex-none text-cyan-700" />
                        <p className="text-sm leading-7 text-cyan-950">
                            {t('Recommended local database settings')}: <strong>127.0.0.1</strong>, <strong>3306</strong>, <strong>wifi_billing</strong>, <strong>root</strong>, {t('empty password')}.
                        </p>
                    </div>
                </div>
            </InstallerShell>
        </>
    );
}
