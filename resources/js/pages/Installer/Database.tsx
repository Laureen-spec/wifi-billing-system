import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { CheckCircle2, Database as DatabaseIcon, Loader2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { InstallerActions, InstallerShell } from './InstallerShell';

export default function Database() {
    const { t } = useTranslation();
    const { post, processing, errors } = useForm();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('installer.database.store'), {
            onSuccess: () => {
                window.location.href = route('installer.addons');
            },
        });
    };

    const actions = [
        t('Run fresh database migrations'),
        t('Create core roles and permissions'),
        t('Install required system settings'),
        t('Prepare the database for WiFi Billing modules'),
    ];

    return (
        <>
            <Head title={t('Installation - Database Setup')} />
            <InstallerShell
                step="database"
                eyebrow={t('Database setup')}
                title={t('Prepare fresh tables')}
                description={t('This will reset the selected database and build the required structure for a clean installation.')}
            >
                <form onSubmit={submit}>
                    {processing && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm">
                            <div className="rounded-3xl border border-white/10 bg-white p-8 text-center shadow-2xl">
                                <Loader2 className="mx-auto mb-4 h-10 w-10 animate-spin text-cyan-700" />
                                <p className="font-bold text-slate-950">{t('Setting up database...')}</p>
                            </div>
                        </div>
                    )}

                    {(errors as Record<string, string>).database && (
                        <div className="mb-5 rounded-3xl border border-rose-200 bg-rose-50 p-5 text-sm font-semibold text-rose-700">
                            {(errors as Record<string, string>).database}
                        </div>
                    )}

                    <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <div className="mb-5 flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-950 text-cyan-300">
                                <DatabaseIcon className="h-6 w-6" />
                            </div>
                            <div>
                                <h3 className="text-lg font-black text-slate-950">{t('Clean database install')}</h3>
                                <p className="text-sm text-slate-500">{t('No demo data will be added unless your own seeders add required defaults.')}</p>
                            </div>
                        </div>
                        <div className="space-y-3">
                            {actions.map((item) => (
                                <div key={item} className="flex items-center gap-3 rounded-2xl bg-white p-4">
                                    <CheckCircle2 className="h-5 w-5 flex-none text-emerald-600" />
                                    <span className="text-sm font-semibold text-slate-700">{item}</span>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="mt-8">
                        <InstallerActions backHref={route('installer.environment')} type="submit" nextLabel={processing ? t('Setting up...') : t('Setup Database')} disabled={processing} processing={processing} />
                    </div>
                </form>
            </InstallerShell>
        </>
    );
}
