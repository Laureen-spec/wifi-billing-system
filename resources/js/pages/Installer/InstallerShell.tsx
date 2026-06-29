import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CheckCircle2, Circle, Database, PackageCheck, Rocket, Server, Settings, ShieldCheck, Wifi } from 'lucide-react';
import { ReactNode } from 'react';

export type InstallerStep = 'welcome' | 'requirements' | 'permissions' | 'environment' | 'database' | 'addons' | 'final';

type ShellProps = {
    step: InstallerStep;
    title: string;
    eyebrow: string;
    description: string;
    children: ReactNode;
    footer?: ReactNode;
    wide?: boolean;
};

const steps: { key: InstallerStep; label: string; icon: typeof Circle }[] = [
    { key: 'welcome', label: 'Welcome', icon: Rocket },
    { key: 'requirements', label: 'Server', icon: Server },
    { key: 'permissions', label: 'Files', icon: ShieldCheck },
    { key: 'environment', label: 'Config', icon: Settings },
    { key: 'database', label: 'Database', icon: Database },
    { key: 'addons', label: 'Modules', icon: PackageCheck },
    { key: 'final', label: 'Finish', icon: CheckCircle2 },
];

export function InstallerShell({ step, title, eyebrow, description, children, footer, wide = false }: ShellProps) {
    const currentIndex = steps.findIndex((item) => item.key === step);
    const progress = ((currentIndex + 1) / steps.length) * 100;

    return (
        <div className="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(20,184,166,0.28),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(249,115,22,0.22),transparent_30%),linear-gradient(135deg,#020617_0%,#0f172a_46%,#111827_100%)]" />
            <div className="absolute left-[-12rem] top-[-12rem] h-96 w-96 rounded-full border border-cyan-300/10" />
            <div className="absolute left-[-8rem] top-[-8rem] h-72 w-72 rounded-full border border-cyan-300/10" />
            <div className="absolute bottom-8 right-8 hidden h-72 w-72 rounded-full border border-orange-300/10 lg:block" />

            <main className="relative mx-auto grid min-h-screen w-full max-w-7xl grid-cols-1 gap-8 px-5 py-6 lg:grid-cols-[0.82fr_1.18fr] lg:px-10 lg:py-10">
                <aside className="flex flex-col justify-between rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 shadow-2xl shadow-cyan-950/30 backdrop-blur-xl lg:p-8">
                    <div>
                        <div className="mb-10 flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-400 text-slate-950 shadow-lg shadow-cyan-500/30">
                                <Wifi className="h-7 w-7" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold uppercase tracking-[0.35em] text-cyan-200">StudyRoom</p>
                                <h1 className="text-2xl font-black tracking-tight text-white">WiFi Billing</h1>
                            </div>
                        </div>

                        <div className="mb-8 inline-flex rounded-full border border-cyan-300/20 bg-cyan-300/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.28em] text-cyan-100">
                            Installer console
                        </div>

                        <h2 className="max-w-md text-4xl font-black leading-tight text-white lg:text-5xl">
                            Launch your ISP billing panel cleanly.
                        </h2>
                        <p className="mt-5 max-w-md text-sm leading-7 text-slate-300">
                            Configure server checks, database connection, tenant modules, and admin accounts in one guided setup flow.
                        </p>
                    </div>

                    <div className="mt-10 space-y-3">
                        {steps.map((item, index) => {
                            const Icon = item.icon;
                            const active = item.key === step;
                            const done = index < currentIndex;

                            return (
                                <div
                                    key={item.key}
                                    className={`flex items-center gap-3 rounded-2xl border px-4 py-3 transition ${
                                        active
                                            ? 'border-cyan-300/50 bg-cyan-300/15 text-white shadow-lg shadow-cyan-950/40'
                                            : done
                                              ? 'border-emerald-300/20 bg-emerald-300/10 text-emerald-100'
                                              : 'border-white/10 bg-white/[0.03] text-slate-400'
                                    }`}
                                >
                                    <span className={`flex h-9 w-9 items-center justify-center rounded-xl ${active ? 'bg-cyan-300 text-slate-950' : done ? 'bg-emerald-300 text-slate-950' : 'bg-white/10'}`}>
                                        {done ? <CheckCircle2 className="h-5 w-5" /> : <Icon className="h-5 w-5" />}
                                    </span>
                                    <div className="flex-1">
                                        <p className="text-xs uppercase tracking-[0.22em] opacity-70">Step {index + 1}</p>
                                        <p className="font-semibold">{item.label}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </aside>

                <section className="flex items-center justify-center">
                    <div className={`w-full ${wide ? 'max-w-4xl' : 'max-w-2xl'}`}>
                        <div className="mb-5 overflow-hidden rounded-full border border-white/10 bg-white/10">
                            <div className="h-2 rounded-full bg-gradient-to-r from-cyan-300 via-emerald-300 to-orange-300 transition-all duration-500" style={{ width: `${progress}%` }} />
                        </div>

                        <div className="rounded-[2rem] border border-white/10 bg-white/[0.96] p-6 text-slate-950 shadow-2xl shadow-black/40 backdrop-blur-xl sm:p-8 lg:p-10">
                            <div className="mb-8">
                                <p className="mb-3 text-xs font-black uppercase tracking-[0.35em] text-cyan-700">{eyebrow}</p>
                                <h2 className="text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{title}</h2>
                                <p className="mt-3 max-w-2xl text-sm leading-7 text-slate-600">{description}</p>
                            </div>
                            {children}
                            {footer && <div className="mt-8 border-t border-slate-200 pt-6">{footer}</div>}
                        </div>
                    </div>
                </section>
            </main>
        </div>
    );
}

export function InstallerActions({ backHref, nextHref, nextLabel = 'Next', backLabel = 'Back', disabled = false, processing = false, type = 'button' }: {
    backHref?: string;
    nextHref?: string;
    nextLabel?: string;
    backLabel?: string;
    disabled?: boolean;
    processing?: boolean;
    type?: 'button' | 'submit';
}) {
    const primaryClasses = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-slate-300/60 transition hover:-translate-y-0.5 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60';
    const secondaryClasses = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50';

    return (
        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            {backHref ? (
                <Link href={backHref} className={secondaryClasses}>
                    <ArrowLeft className="h-4 w-4" />
                    {backLabel}
                </Link>
            ) : <span />}

            {nextHref ? (
                <Link href={nextHref} className={primaryClasses}>
                    {nextLabel}
                    <ArrowRight className="h-4 w-4" />
                </Link>
            ) : (
                <button type={type} disabled={disabled || processing} className={primaryClasses}>
                    {processing ? 'Processing...' : nextLabel}
                    <ArrowRight className="h-4 w-4" />
                </button>
            )}
        </div>
    );
}

export function StatusPill({ ok, okText = 'Ready', failText = 'Needs attention' }: { ok: boolean; okText?: string; failText?: string }) {
    return (
        <span className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold ${ok ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
            {ok ? <CheckCircle2 className="h-4 w-4" /> : <Circle className="h-4 w-4" />}
            {ok ? okText : failText}
        </span>
    );
}
