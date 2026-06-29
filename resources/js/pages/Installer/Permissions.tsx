import { Head } from '@inertiajs/react';
import { FolderCheck } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { InstallerActions, InstallerShell, StatusPill } from './InstallerShell';

interface Permission {
    name: string;
    path: string;
    check: boolean;
}

interface Props {
    permissions: Record<string, Permission>;
}

export default function Permissions({ permissions }: Props) {
    const { t } = useTranslation();
    const allPassed = Object.values(permissions).every((perm) => perm.check);

    return (
        <>
            <Head title={t('Installation - Permissions')} />
            <InstallerShell
                step="permissions"
                eyebrow={t('Storage access')}
                title={t('File permissions')}
                description={t('These folders must be writable so Laravel can store logs, cache files, sessions, and generated views.')}
                footer={allPassed ? (
                    <InstallerActions backHref={route('installer.requirements')} nextHref={route('installer.environment')} nextLabel={t('Continue')} />
                ) : (
                    <InstallerActions backHref={route('installer.requirements')} nextLabel={t('Fix Permissions First')} disabled />
                )}
                wide
            >
                <div className="space-y-3">
                    {Object.entries(permissions).map(([key, perm]) => (
                        <div key={key} className="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-slate-950 text-cyan-300">
                                        <FolderCheck className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p className="font-black text-slate-950">{perm.name}</p>
                                        <p className="break-all text-xs leading-5 text-slate-500">{perm.path}</p>
                                    </div>
                                </div>
                                <StatusPill ok={perm.check} okText={t('Writable')} failText={t('Not Writable')} />
                            </div>
                        </div>
                    ))}
                </div>

                {!allPassed && (
                    <div className="mt-5 rounded-3xl border border-amber-200 bg-amber-50 p-5">
                        <h3 className="font-black text-amber-900">{t('Fix permissions')}</h3>
                        <p className="mt-2 text-sm leading-6 text-amber-800">{t('On Linux hosting, run these commands. On Windows/XAMPP, run XAMPP as Administrator and ensure the folder is not read-only.')}</p>
                        <code className="mt-3 block rounded-2xl bg-white p-4 text-xs text-slate-700">
                            chmod -R 755 storage/<br />
                            chmod -R 755 bootstrap/cache/
                        </code>
                    </div>
                )}
            </InstallerShell>
        </>
    );
}
