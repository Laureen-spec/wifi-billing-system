import { Head } from '@inertiajs/react';
import { CheckCircle2, Loader2, PackageCheck, RadioTower, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { InstallerActions, InstallerShell } from './InstallerShell';

interface Module {
    name: string;
    alias: string;
    description: string;
    priority: number;
}

interface Props {
    modules: Module[];
}

export default function Addons({ modules }: Props) {
    const { t } = useTranslation();
    const [currentModuleIndex, setCurrentModuleIndex] = useState(0);
    const [installing, setInstalling] = useState(false);
    const [completed, setCompleted] = useState(false);
    const [error, setError] = useState('');
    const [installedModules, setInstalledModules] = useState<string[]>([]);

    const installNextModule = async () => {
        if (currentModuleIndex >= modules.length) {
            setCompleted(true);
            return;
        }

        const currentModule = modules[currentModuleIndex];
        setInstalling(true);
        setError('');

        try {
            const response = await axios.post(route('installer.addons.store'), {
                module: currentModule.name,
            });

            if (response.data.success) {
                setInstalledModules((prev) => [...prev, currentModule.name]);
                setCurrentModuleIndex((prev) => prev + 1);

                if (response.data.completed) {
                    setCompleted(true);
                }
            } else {
                setError(response.data.message);
            }
        } catch (err: any) {
            setError(err.response?.data?.message || 'Installation failed');
        } finally {
            setInstalling(false);
        }
    };

    const startInstallation = () => {
        if (modules.length === 0) {
            setCompleted(true);
            setError('');
            return;
        }

        setCurrentModuleIndex(0);
        setInstalledModules([]);
        setCompleted(false);
        setError('');
        installNextModule();
    };

    useEffect(() => {
        if (currentModuleIndex > 0 && currentModuleIndex < modules.length && !error) {
            const timer = setTimeout(() => {
                installNextModule();
            }, 800);
            return () => clearTimeout(timer);
        }
    }, [currentModuleIndex]);

    const progress = modules.length === 0 ? 100 : (installedModules.length / modules.length) * 100;

    return (
        <>
            <Head title={t('Installation - Add-ons')} />
            <InstallerShell
                step="addons"
                eyebrow={t('Module activation')}
                title={t('Install StudyRoom TechLab modules')}
                description={t('The installer will enable available modules and attach tenant modules like WiFi Billing to the company account.')}
                footer={completed ? (
                    <InstallerActions backHref={route('installer.database')} nextHref={route('installer.final')} nextLabel={t('Finish Setup')} />
                ) : (
                    <InstallerActions backHref={route('installer.database')} nextLabel={installing ? t('Installing...') : t('Start Module Install')} disabled={installing} processing={installing} />
                )}
                wide
            >
                {error && (
                    <div className="mb-5 flex items-start gap-3 rounded-3xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-700">
                        <XCircle className="mt-0.5 h-5 w-5 flex-none" />
                        <p>{error}</p>
                    </div>
                )}

                <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <div className="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-950 text-cyan-300">
                                <RadioTower className="h-6 w-6" />
                            </div>
                            <div>
                                <h3 className="font-black text-slate-950">{t('Add-ons Progress')}</h3>
                                <p className="text-sm text-slate-500">{installedModules.length}/{modules.length} {t('modules completed')}</p>
                            </div>
                        </div>
                        {installing && <Loader2 className="h-6 w-6 animate-spin text-cyan-700" />}
                    </div>

                    <div className="mb-5 overflow-hidden rounded-full bg-slate-200">
                        <div className="h-3 rounded-full bg-gradient-to-r from-cyan-500 to-emerald-500 transition-all duration-500" style={{ width: `${progress}%` }} />
                    </div>

                    {modules.length === 0 ? (
                        <div className="rounded-2xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700">
                            {t('No StudyRoom TechLab add-on modules are pending installation.')}
                        </div>
                    ) : (
                        <div className="grid max-h-[26rem] gap-3 overflow-y-auto pr-1">
                            {modules.map((module, index) => {
                                const installed = installedModules.includes(module.name);
                                const active = index === currentModuleIndex && installing;
                                return (
                                    <div key={module.name} className={`rounded-3xl border p-4 transition ${installed ? 'border-emerald-200 bg-emerald-50' : active ? 'border-cyan-200 bg-cyan-50' : 'border-slate-200 bg-white'}`}>
                                        <div className="flex items-center justify-between gap-4">
                                            <div className="flex items-start gap-3">
                                                <div className={`flex h-10 w-10 flex-none items-center justify-center rounded-2xl ${installed ? 'bg-emerald-600 text-white' : active ? 'bg-cyan-700 text-white' : 'bg-slate-100 text-slate-500'}`}>
                                                    {installed ? <CheckCircle2 className="h-5 w-5" /> : active ? <Loader2 className="h-5 w-5 animate-spin" /> : <PackageCheck className="h-5 w-5" />}
                                                </div>
                                                <div>
                                                    <h4 className="font-black text-slate-950">{module.alias}</h4>
                                                    <p className="text-sm leading-6 text-slate-600">{module.description}</p>
                                                </div>
                                            </div>
                                            <span className="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 sm:inline-flex">#{module.priority}</span>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </InstallerShell>
        </>
    );
}
