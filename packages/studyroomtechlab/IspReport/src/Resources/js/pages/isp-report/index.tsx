import { Link } from '@inertiajs/react';
import { ArrowRight, CreditCard, Lightbulb, ListChecks, RadioTower } from 'lucide-react';
import { EmptyRows, ReportCard, ReportLayout, StatsGrid, StatusBadge, TableShell } from './components';

declare function route(name: string): string;

type Stat = { label: string; value: string; description?: string };
type QuickLink = { label: string; description: string; href: string };
type Suggestion = { title: string; description: string };
type StaffLog = { id: string; event: string; target: string; changes: string; actor: string; source: string; when: string; tone?: string };
type ConnectionLog = { id: string; type: string; customer: string; phone?: string | null; router: string; package: string; status: string; usage: string; when: string };
type PaymentLog = { id: string; customer: string; phone?: string | null; receipt: string; method: string; package: string; amount: string; status: string; when: string };

type Props = {
    pageTitle: string;
    subtitle: string;
    summary: Stat[];
    quickLinks: QuickLink[];
    suggestions: Suggestion[];
    recentStaffLogs: StaffLog[];
    recentConnections: ConnectionLog[];
    recentPayments: PaymentLog[];
};

const iconFor = (label: string) => {
    if (label.toLowerCase().includes('staff')) return ListChecks;
    if (label.toLowerCase().includes('connection')) return RadioTower;
    return CreditCard;
};

export default function IspReportOverview({ pageTitle, subtitle, summary, quickLinks, suggestions, recentStaffLogs, recentConnections, recentPayments }: Props) {
    return (
        <ReportLayout title={pageTitle} subtitle={subtitle}>
            <StatsGrid stats={summary} />

            <div className="grid gap-4 lg:grid-cols-3">
                {quickLinks.map((item) => {
                    const Icon = iconFor(item.label);
                    return (
                        <Link key={item.label} href={item.href} className="group rounded-2xl border bg-card p-5 shadow-sm transition hover:border-primary/40 hover:shadow-md">
                            <div className="flex items-start justify-between gap-3">
                                <span className="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <Icon className="h-5 w-5" />
                                </span>
                                <ArrowRight className="h-4 w-4 text-muted-foreground transition group-hover:translate-x-1 group-hover:text-primary" />
                            </div>
                            <h3 className="mt-4 text-base font-semibold text-foreground">{item.label}</h3>
                            <p className="mt-1 text-sm leading-6 text-muted-foreground">{item.description}</p>
                        </Link>
                    );
                })}
            </div>

            <div className="grid gap-6 xl:grid-cols-2">
                <ReportCard title="Recent payment logs" description="Latest collections pulled from payment records." action={<Link href={route('isp-reports.payment-logs')} className="text-sm font-semibold text-primary">View all</Link>}>
                    {recentPayments.length ? (
                        <TableShell>
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr><th className="px-4 py-3">Customer</th><th className="px-4 py-3">Amount</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">When</th></tr>
                                </thead>
                                <tbody className="divide-y">
                                    {recentPayments.map((row) => (
                                        <tr key={row.id}>
                                            <td className="px-4 py-3"><div className="font-medium text-foreground">{row.customer}</div><div className="text-xs text-muted-foreground">{row.receipt}</div></td>
                                            <td className="px-4 py-3 font-semibold text-foreground">{row.amount}</td>
                                            <td className="px-4 py-3"><StatusBadge value={row.status} /></td>
                                            <td className="px-4 py-3 text-muted-foreground">{row.when}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </TableShell>
                    ) : <EmptyRows message="No payment logs yet." />}
                </ReportCard>

                <ReportCard title="Recent connection logs" description="Hotspot and PPPoE customer activity." action={<Link href={route('isp-reports.connection-logs')} className="text-sm font-semibold text-primary">View all</Link>}>
                    {recentConnections.length ? (
                        <TableShell>
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr><th className="px-4 py-3">Customer</th><th className="px-4 py-3">Type</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Usage</th></tr>
                                </thead>
                                <tbody className="divide-y">
                                    {recentConnections.map((row) => (
                                        <tr key={row.id}>
                                            <td className="px-4 py-3"><div className="font-medium text-foreground">{row.customer}</div><div className="text-xs text-muted-foreground">{row.router}</div></td>
                                            <td className="px-4 py-3 text-muted-foreground">{row.type}</td>
                                            <td className="px-4 py-3"><StatusBadge value={row.status} /></td>
                                            <td className="px-4 py-3 font-medium text-foreground">{row.usage}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </TableShell>
                    ) : <EmptyRows message="No connection logs yet." />}
                </ReportCard>
            </div>

            <div className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <ReportCard title="Recent staff logs" description="Sign-ins and workspace changes by staff." action={<Link href={route('isp-reports.staff-logs')} className="text-sm font-semibold text-primary">View all</Link>}>
                    {recentStaffLogs.length ? (
                        <TableShell>
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr><th className="px-4 py-3">Event</th><th className="px-4 py-3">Target</th><th className="px-4 py-3">Actor</th><th className="px-4 py-3">When</th></tr>
                                </thead>
                                <tbody className="divide-y">
                                    {recentStaffLogs.map((row) => (
                                        <tr key={row.id}>
                                            <td className="px-4 py-3"><StatusBadge value={row.event} /></td>
                                            <td className="px-4 py-3"><div className="font-medium text-foreground">{row.target}</div><div className="text-xs text-muted-foreground">{row.changes}</div></td>
                                            <td className="px-4 py-3 text-muted-foreground">{row.actor}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{row.when}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </TableShell>
                    ) : <EmptyRows message="No staff logs yet." />}
                </ReportCard>

                <ReportCard title="Suggested next reports" description="Useful additions for the ISP system.">
                    <div className="space-y-3">
                        {suggestions.map((item) => (
                            <div key={item.title} className="rounded-xl border bg-background p-4">
                                <div className="flex gap-3">
                                    <span className="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"><Lightbulb className="h-4 w-4" /></span>
                                    <div>
                                        <h3 className="text-sm font-semibold text-foreground">{item.title}</h3>
                                        <p className="mt-1 text-sm leading-6 text-muted-foreground">{item.description}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </ReportCard>
            </div>
        </ReportLayout>
    );
}
