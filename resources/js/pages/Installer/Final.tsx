import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, Copy, ShieldCheck, UserRound } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { InstallerShell } from './InstallerShell';

interface Credentials {
    admin: {
        email: string;
        password: string;
    };
    company: {
        email: string;
        password: string;
    };
}

interface Props {
    credentials: Credentials;
}

export default function Final({ credentials }: Props) {
    const { t } = useTranslation();

    const credentialCards = [
        { label: t('Super Admin'), email: credentials.admin.email, password: credentials.admin.password, note: t('Platform and system settings') },
        { label: t('Company / ISP Admin'), email: credentials.company.email, password: credentials.company.password, note: t('WiFi Billing operations') },
    ];

    return (
        <>
            <Head title={t('Installation Complete')} />
            <InstallerShell
                step="final"
                eyebrow={t('Ready to login')}
                title={t('Installation complete')}
                description={t('Your billing system is ready. Keep these credentials safe and change the passwords immediately after first login.')}
                wide
            >
                <div className="rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
                    <div className="flex items-start gap-3">
                        <div className="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-emerald-600 text-white">
                            <CheckCircle2 className="h-7 w-7" />
                        </div>
                        <div>
                            <h3 className="text-lg font-black text-emerald-950">{t('Setup successful')}</h3>
                            <p className="mt-1 text-sm leading-6 text-emerald-800">{t('Default accounts were created and enabled modules are now attached to the company account.')}</p>
                        </div>
                    </div>
                </div>

                <div className="mt-5 grid gap-4 md:grid-cols-2">
                    {credentialCards.map((card) => (
                        <div key={card.email} className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <div className="mb-4 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-950 text-cyan-300">
                                    <UserRound className="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 className="font-black text-slate-950">{card.label}</h3>
                                    <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{card.note}</p>
                                </div>
                            </div>
                            <div className="space-y-3">
                                <div className="rounded-2xl bg-white p-4">
                                    <p className="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">{t('Email')}</p>
                                    <p className="mt-1 flex items-center gap-2 font-bold text-slate-950"><Copy className="h-4 w-4 text-slate-400" />{card.email}</p>
                                </div>
                                <div className="rounded-2xl bg-white p-4">
                                    <p className="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">{t('Password')}</p>
                                    <p className="mt-1 flex items-center gap-2 font-bold text-slate-950"><Copy className="h-4 w-4 text-slate-400" />{card.password}</p>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="mt-5 rounded-3xl border border-amber-200 bg-amber-50 p-5">
                    <div className="flex items-start gap-3">
                        <ShieldCheck className="mt-1 h-5 w-5 flex-none text-amber-700" />
                        <div>
                            <h3 className="font-black text-amber-900">{t('Security reminder')}</h3>
                            <p className="mt-1 text-sm leading-6 text-amber-800">{t('Change default passwords immediately, keep APP_KEY safe, and block public installer access after setup.')}</p>
                        </div>
                    </div>
                </div>

                <div className="mt-8">
                    <Link
                        href={route('dashboard')}
                        className="inline-flex w-full min-h-12 items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-slate-300/60 transition hover:-translate-y-0.5 hover:bg-slate-800"
                    >
                        {t('Go to Dashboard')}
                    </Link>
                </div>
            </InstallerShell>
        </>
    );
}
