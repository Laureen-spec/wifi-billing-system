import { Head } from '@inertiajs/react';
import { Server } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { InstallerActions, InstallerShell, StatusPill } from './InstallerShell';

interface Requirement {
    name: string;
    check: boolean;
    current?: string;
}

interface Requirements {
    php: Requirement;
    extensions: Record<string, Requirement>;
}

interface Props {
    requirements: Requirements;
}

export default function Requirements({ requirements }: Props) {
    const { t } = useTranslation();
    const allPassed = requirements.php.check && Object.values(requirements.extensions).every((ext) => ext.check);

    return (
        <>
            <Head title={t('Installation - Requirements')} />
            <InstallerShell
                step="requirements"
                eyebrow={t('Compatibility check')}
                title={t('Server requirements')}
                description={t('The installer checks your PHP version and required extensions before touching your database.')}
                footer={allPassed ? (
                    <InstallerActions backHref={route('installer.welcome')} nextHref={route('installer.permissions')} nextLabel={t('Continue')} />
                ) : (
                    <InstallerActions backHref={route('installer.welcome')} nextLabel={t('Fix Requirements First')} disabled />
                )}
                wide
            >
                <div className="space-y-3">
                    <div className="flex items-center justify-between rounded-3xl border border-slate-200 bg-slate-50 p-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-950 text-cyan-300">
                                <Server className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="font-black text-slate-950">{requirements.php.name}</p>
                                <p className="text-sm text-slate-500">{t('Current')}: {requirements.php.current}</p>
                            </div>
                        </div>
                        <StatusPill ok={requirements.php.check} okText={t('Passed')} failText={t('Failed')} />
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        {Object.entries(requirements.extensions).map(([key, ext]) => (
                            <div key={key} className="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4">
                                <span className="font-semibold text-slate-800">{ext.name}</span>
                                <StatusPill ok={ext.check} okText={t('Loaded')} failText={t('Missing')} />
                            </div>
                        ))}
                    </div>
                </div>
            </InstallerShell>
        </>
    );
}
