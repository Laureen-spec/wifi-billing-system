import { ChipFilters, EmptyRows, Option, Pagination, PaginationControls, ReportCard, ReportLayout, SearchBox, Stat, StatsGrid, StatusBadge, TableShell } from './components';

type StaffLog = {
    id: string;
    event: string;
    target: string;
    changes: string;
    actor: string;
    source: string;
    when: string;
    tone?: string;
};

type Props = {
    pageTitle: string;
    subtitle: string;
    filters: { search: string; event: string };
    eventOptions: Option[];
    logs: StaffLog[];
    pagination: Pagination;
    stats: Stat[];
};

export default function StaffLogs({ pageTitle, subtitle, filters, eventOptions, logs, pagination, stats }: Props) {
    return (
        <ReportLayout title={pageTitle} subtitle={subtitle}>
            <StatsGrid stats={stats} />

            <ReportCard title="Filters" description="Use chips instead of heavy dropdowns for faster review.">
                <div className="space-y-4">
                    <SearchBox routeName="isp-reports.staff-logs" placeholder="Search actor, IP, event, package, customer..." filters={filters} />
                    <ChipFilters label="Event" name="event" value={filters.event || 'all'} options={eventOptions} routeName="isp-reports.staff-logs" extra={{ search: filters.search }} />
                </div>
            </ReportCard>

            <ReportCard title="Staff activity" description="Audit trail for sign-ins and system changes.">
                {logs.length ? (
                    <TableShell>
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th className="px-5 py-3">Event</th>
                                    <th className="px-5 py-3">Target</th>
                                    <th className="px-5 py-3">Changes</th>
                                    <th className="px-5 py-3">Actor</th>
                                    <th className="px-5 py-3">Source</th>
                                    <th className="px-5 py-3">When</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {logs.map((row) => (
                                    <tr key={row.id} className="align-top">
                                        <td className="px-5 py-4"><StatusBadge value={row.event} /></td>
                                        <td className="px-5 py-4 font-semibold text-foreground">{row.target}</td>
                                        <td className="px-5 py-4 text-muted-foreground">{row.changes}</td>
                                        <td className="px-5 py-4 text-foreground">{row.actor}</td>
                                        <td className="px-5 py-4 font-mono text-xs text-muted-foreground">{row.source}</td>
                                        <td className="px-5 py-4 text-muted-foreground">{row.when}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <PaginationControls pagination={pagination} />
                    </TableShell>
                ) : <EmptyRows message="No staff activity found for the selected filters." />}
            </ReportCard>
        </ReportLayout>
    );
}
