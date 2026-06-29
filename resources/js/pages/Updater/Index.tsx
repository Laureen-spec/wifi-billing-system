import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import {
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    Clock3,
    Database,
    Gauge,
    HardDrive,
    Loader2,
    RefreshCw,
    ShieldCheck,
    TerminalSquare,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

interface Props {
    hasUpdates: boolean;
    pendingMigrations: string[];
}

export default function UpdaterIndex({ hasUpdates, pendingMigrations }: Props) {
    const { t } = useTranslation();
    const [updating, setUpdating] = useState(false);
    const [completed, setCompleted] = useState(false);
    const [error, setError] = useState('');

    const updateState = completed ? 'completed' : hasUpdates ? 'pending' : 'current';

    const visibleMigrations = useMemo(() => pendingMigrations.slice(0, 8), [pendingMigrations]);
    const hiddenMigrationCount = Math.max(pendingMigrations.length - visibleMigrations.length, 0);

    const runUpdate = async () => {
        setUpdating(true);
        setError('');

        try {
            const response = await axios.post(route('updater.update'));

            if (response.data.success) {
                setCompleted(true);
                setTimeout(() => {
                    window.location.href = route('dashboard');
                }, 1800);
            } else {
                setError(response.data.message || 'Update failed.');
            }
        } catch (err: any) {
            setError(err.response?.data?.message || 'Update failed. Please check the server logs and try again.');
        } finally {
            setUpdating(false);
        }
    };

    return (
        <>
            <Head title={t('System Updater')} />

            <main className="min-h-screen bg-[radial-gradient(circle_at_top_left,rgba(16,185,129,0.12),transparent_30%),linear-gradient(180deg,#f8fafc_0%,#eef2f7_100%)] px-4 py-8 text-slate-950 sm:px-6 lg:px-10">
                <div className="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-6xl items-center">
                    <div className="grid w-full overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.12)] lg:grid-cols-[0.85fr_1.15fr]">
                        <section className="relative overflow-hidden bg-slate-950 p-8 text-white sm:p-10">
                            <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-emerald-400/20 blur-3xl" />
                            <div className="absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-cyan-400/10 blur-3xl" />

                            <div className="relative flex h-full flex-col justify-between gap-10">
                                <div>
                                    <div className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">
                                        <ShieldCheck className="h-4 w-4" />
                                        Maintenance mode
                                    </div>

                                    <h1 className="mt-8 text-4xl font-semibold tracking-tight sm:text-5xl">
                                        StudyRoom Connect updater
                                    </h1>
                                    <p className="mt-4 max-w-md text-base leading-7 text-slate-300">
                                        Apply database migrations safely, review pending changes, and return to the dashboard once the system is ready.
                                    </p>
                                </div>

                                <div className="grid gap-3">
                                    <StatusPill
                                        icon={<Database className="h-4 w-4" />}
                                        label="Database migrations"
                                        value={`${pendingMigrations.length} pending`}
                                    />
                                    <StatusPill
                                        icon={<Clock3 className="h-4 w-4" />}
                                        label="Estimated duration"
                                        value={pendingMigrations.length > 0 ? 'A few minutes' : 'No action needed'}
                                    />
                                    <StatusPill
                                        icon={<HardDrive className="h-4 w-4" />}
                                        label="Safety reminder"
                                        value="Backup recommended"
                                    />
                                </div>
                            </div>
                        </section>

                        <section className="p-6 sm:p-8 lg:p-10">
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                                        System updater
                                    </p>
                                    <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                                        {updateState === 'completed'
                                            ? 'Update completed'
                                            : updateState === 'pending'
                                                ? 'Updates are ready'
                                                : 'System is current'}
                                    </h2>
                                    <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                        {updateState === 'completed'
                                            ? 'The system was updated successfully. You can continue to the dashboard.'
                                            : updateState === 'pending'
                                                ? 'Review the pending migrations below before running the update.'
                                                : 'No pending migrations were found. Your installation is already up to date.'}
                                    </p>
                                </div>

                                <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-right">
                                    <div className="text-xs font-medium uppercase tracking-wide text-slate-500">Status</div>
                                    <div className="mt-1 flex items-center justify-end gap-2 text-sm font-semibold text-slate-950">
                                        {updateState === 'completed' ? (
                                            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                        ) : updateState === 'pending' ? (
                                            <AlertTriangle className="h-4 w-4 text-amber-600" />
                                        ) : (
                                            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                        )}
                                        {updateState === 'pending' ? 'Action required' : updateState === 'completed' ? 'Updated' : 'Ready'}
                                    </div>
                                </div>
                            </div>

                            {error && (
                                <div className="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                    {error}
                                </div>
                            )}

                            {completed && (
                                <div className="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                    {t('System updated successfully!')} {t('Redirecting to dashboard...')}
                                </div>
                            )}

                            <div className="mt-8 grid gap-4 sm:grid-cols-3">
                                <MetricCard icon={<Database className="h-5 w-5" />} label="Pending migrations" value={pendingMigrations.length} />
                                <MetricCard icon={<Gauge className="h-5 w-5" />} label="Update state" value={updateState === 'pending' ? 'Pending' : 'Clear'} />
                                <MetricCard icon={<ShieldCheck className="h-5 w-5" />} label="Backup check" value="Recommended" />
                            </div>

                            <Card className="mt-6 overflow-hidden rounded-3xl border-slate-200 shadow-none">
                                <CardContent className="p-0">
                                    <div className="flex items-center justify-between border-b bg-slate-50 px-5 py-4">
                                        <div>
                                            <h3 className="font-semibold text-slate-950">Migration queue</h3>
                                            <p className="mt-1 text-xs text-slate-500">
                                                Files waiting to be executed on the database.
                                            </p>
                                        </div>
                                        <div className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm">
                                            {pendingMigrations.length} item{pendingMigrations.length === 1 ? '' : 's'}
                                        </div>
                                    </div>

                                    {pendingMigrations.length === 0 ? (
                                        <div className="flex flex-col items-center justify-center px-6 py-12 text-center">
                                            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                                <CheckCircle2 className="h-7 w-7" />
                                            </div>
                                            <h3 className="mt-4 text-lg font-semibold text-slate-950">System is up to date</h3>
                                            <p className="mt-2 max-w-md text-sm text-slate-500">
                                                No pending migrations found. Your system is running the latest available database structure.
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {visibleMigrations.map((migration, index) => (
                                                <div key={migration} className="flex items-center gap-3 px-5 py-3">
                                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold text-slate-600">
                                                        {String(index + 1).padStart(2, '0')}
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <div className="truncate text-sm font-medium text-slate-900">{migration}</div>
                                                        <div className="text-xs text-slate-500">Pending execution</div>
                                                    </div>
                                                    <TerminalSquare className="h-4 w-4 text-slate-400" />
                                                </div>
                                            ))}
                                            {hiddenMigrationCount > 0 && (
                                                <div className="px-5 py-3 text-sm text-slate-500">
                                                    + {hiddenMigrationCount} more migration{hiddenMigrationCount === 1 ? '' : 's'} will run during update.
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {hasUpdates && !completed && (
                                <div className="mt-6 rounded-3xl border border-amber-200 bg-amber-50 p-5">
                                    <div className="flex gap-3">
                                        <AlertTriangle className="mt-0.5 h-5 w-5 text-amber-700" />
                                        <div>
                                            <h4 className="font-semibold text-amber-950">Before you continue</h4>
                                            <ul className="mt-2 space-y-1 text-sm text-amber-800">
                                                <li>Back up the database before running production updates.</li>
                                                <li>Keep this page open until the update finishes.</li>
                                                <li>Do not refresh while migrations are running.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <Link href={route('dashboard')} className="text-sm font-medium text-slate-500 hover:text-slate-900">
                                    Back to dashboard
                                </Link>

                                <div className="flex gap-3">
                                    {hasUpdates && !completed && (
                                        <Button onClick={runUpdate} disabled={updating} size="lg" className="rounded-2xl px-6">
                                            {updating ? (
                                                <>
                                                    <Loader2 className="h-4 w-4 animate-spin" />
                                                    Updating system...
                                                </>
                                            ) : (
                                                <>
                                                    <RefreshCw className="h-4 w-4" />
                                                    Run system update
                                                </>
                                            )}
                                        </Button>
                                    )}

                                    {(completed || !hasUpdates) && (
                                        <Button asChild size="lg" className="rounded-2xl px-6">
                                            <Link href={route('dashboard')}>
                                                Continue to dashboard
                                                <ArrowRight className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </main>
        </>
    );
}

function StatusPill({ icon, label, value }: { icon: React.ReactNode; label: string; value: string }) {
    return (
        <div className="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur">
            <div className="flex items-center gap-2 text-sm text-slate-300">
                {icon}
                {label}
            </div>
            <div className="mt-2 text-lg font-semibold text-white">{value}</div>
        </div>
    );
}

function MetricCard({ icon, label, value }: { icon: React.ReactNode; label: string; value: string | number }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    {icon}
                </div>
                <div>
                    <div className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</div>
                    <div className="mt-1 text-xl font-semibold text-slate-950">{value}</div>
                </div>
            </div>
        </div>
    );
}
