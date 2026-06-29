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
import { ReactNode, useMemo, useState } from 'react';
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
                window.alert('System updated successfully.');
                setTimeout(() => {
                    window.location.href = route('dashboard');
                }, 2200);
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

            <main className="min-h-screen bg-background px-4 py-8 text-foreground sm:px-6 lg:px-10">
                <div className="mx-auto w-full max-w-7xl space-y-6">
                    <section className="overflow-hidden rounded-[2rem] border bg-card shadow-sm">
                        <div className="grid gap-0 lg:grid-cols-[0.92fr_1.08fr]">
                            <div className="border-b bg-muted/30 p-6 sm:p-8 lg:border-b-0 lg:border-r lg:p-10">
                                <div className="inline-flex items-center gap-2 rounded-full border bg-background px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-primary shadow-sm">
                                    <ShieldCheck className="h-4 w-4" />
                                    Maintenance check
                                </div>

                                <h1 className="mt-8 max-w-xl text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">
                                    StudyRoom Connect updater
                                </h1>
                                <p className="mt-4 max-w-2xl text-base leading-7 text-muted-foreground">
                                    Review pending database migrations, confirm the system state, then run updates safely before returning to the dashboard.
                                </p>

                                <div className="mt-8 grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
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

                            <div className="p-6 sm:p-8 lg:p-10">
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p className="text-xs font-semibold uppercase tracking-[0.22em] text-muted-foreground">
                                            System updater
                                        </p>
                                        <h2 className="mt-2 text-3xl font-semibold tracking-tight text-foreground">
                                            {updateState === 'completed'
                                                ? 'Update completed'
                                                : updateState === 'pending'
                                                    ? 'Updates are ready'
                                                    : 'System is current'}
                                        </h2>
                                        <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                                            {updateState === 'completed'
                                                ? 'The system was updated successfully. You can continue to the dashboard.'
                                                : updateState === 'pending'
                                                    ? 'Review the pending migrations below before running the update.'
                                                    : 'No pending migrations were found. Your installation is already up to date.'}
                                        </p>
                                    </div>

                                    <div className="rounded-2xl border bg-background px-4 py-3 text-right shadow-sm">
                                        <div className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</div>
                                        <div className="mt-1 flex items-center justify-end gap-2 text-sm font-semibold text-foreground">
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
                            </div>
                        </div>
                    </section>

                    <section className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                        <Card className="overflow-hidden rounded-[2rem] border bg-card shadow-sm">
                            <CardContent className="p-0">
                                <div className="flex items-center justify-between border-b bg-muted/30 px-5 py-4">
                                    <div>
                                        <h3 className="font-semibold text-foreground">Migration queue</h3>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Files waiting to be executed on the database.
                                        </p>
                                    </div>
                                    <div className="rounded-full border bg-background px-3 py-1 text-xs font-semibold text-muted-foreground shadow-sm">
                                        {pendingMigrations.length} item{pendingMigrations.length === 1 ? '' : 's'}
                                    </div>
                                </div>

                                {pendingMigrations.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center px-6 py-12 text-center">
                                        <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                            <CheckCircle2 className="h-7 w-7" />
                                        </div>
                                        <h3 className="mt-4 text-lg font-semibold text-foreground">System is up to date</h3>
                                        <p className="mt-2 max-w-md text-sm text-muted-foreground">
                                            No pending migrations found. Your system is running the latest available database structure.
                                        </p>
                                    </div>
                                ) : (
                                    <div className="divide-y">
                                        {visibleMigrations.map((migration, index) => (
                                            <div key={migration} className="flex items-center gap-3 px-5 py-3">
                                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-muted text-xs font-semibold text-muted-foreground">
                                                    {String(index + 1).padStart(2, '0')}
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <div className="truncate text-sm font-medium text-foreground">{migration}</div>
                                                    <div className="text-xs text-muted-foreground">Pending execution</div>
                                                </div>
                                                <TerminalSquare className="h-4 w-4 text-muted-foreground" />
                                            </div>
                                        ))}
                                        {hiddenMigrationCount > 0 && (
                                            <div className="px-5 py-3 text-sm text-muted-foreground">
                                                + {hiddenMigrationCount} more migration{hiddenMigrationCount === 1 ? '' : 's'} will run during update.
                                            </div>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <div className="space-y-6">
                            {hasUpdates && !completed && (
                                <div className="rounded-[2rem] border border-amber-200 bg-amber-50 p-5">
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

                            <Card className="rounded-[2rem] border bg-card shadow-sm">
                                <CardContent className="space-y-4 p-5">
                                    <div>
                                        <h3 className="font-semibold text-foreground">Update action</h3>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Run migrations only after confirming your backup is ready.
                                        </p>
                                    </div>

                                    <div className="flex flex-col gap-3">
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

                                        <Button variant="outline" asChild className="rounded-2xl">
                                            <Link href={route('dashboard')}>Back to dashboard</Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}

function StatusPill({ icon, label, value }: { icon: ReactNode; label: string; value: string }) {
    return (
        <div className="rounded-2xl border bg-background p-4 shadow-sm">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <span className="text-primary">{icon}</span>
                {label}
            </div>
            <div className="mt-2 text-lg font-semibold text-foreground">{value}</div>
        </div>
    );
}

function MetricCard({ icon, label, value }: { icon: ReactNode; label: string; value: string | number }) {
    return (
        <div className="rounded-2xl border bg-background p-4 shadow-sm">
            <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    {icon}
                </div>
                <div>
                    <div className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</div>
                    <div className="mt-1 text-xl font-semibold text-foreground">{value}</div>
                </div>
            </div>
        </div>
    );
}
