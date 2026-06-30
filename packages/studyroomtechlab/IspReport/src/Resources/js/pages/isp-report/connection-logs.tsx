import { ChipFilters, EmptyRows, Option, Pagination, PaginationControls, ReportCard, ReportLayout, SearchBox, Stat, StatsGrid, StatusBadge, TableShell } from './components';

type ConnectionLog = {
    id: string;
    type: string;
    customer: string;
    phone?: string | null;
    username?: string | null;
    router: string;
    package: string;
    ip?: string | null;
    mac?: string | null;
    status: string;
    billingStatus: string;
    usage: string;
    lastSeen: string;
    expires: string;
    when: string;
};

type Props = {
    pageTitle: string;
    subtitle: string;
    filters: { search: string; type: string; status: string };
    typeOptions: Option[];
    statusOptions: Option[];
    logs: ConnectionLog[];
    pagination: Pagination;
    stats: Stat[];
};

export default function ConnectionLogs({ pageTitle, subtitle, filters, typeOptions, statusOptions, logs, pagination, stats }: Props) {
    return (
        <ReportLayout title={pageTitle} subtitle={subtitle}>
            <StatsGrid stats={stats} />

            <ReportCard title="Filters" description="Switch between Hotspot and PPPoE without a large form.">
                <div className="space-y-4">
                    <SearchBox routeName="isp-reports.connection-logs" placeholder="Search customer, phone, username, MAC, IP..." filters={filters} />
                    <ChipFilters label="Connection" name="type" value={filters.type || 'all'} options={typeOptions} routeName="isp-reports.connection-logs" extra={{ search: filters.search, status: filters.status }} />
                    <ChipFilters label="Status" name="status" value={filters.status || 'all'} options={statusOptions} routeName="isp-reports.connection-logs" extra={{ search: filters.search, type: filters.type }} />
                </div>
            </ReportCard>

            <ReportCard title="Connection logs" description="Hotspot, PPPoE, free access, package, router, and expiry status.">
                {logs.length ? (
                    <TableShell>
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th className="px-5 py-3">Customer</th>
                                    <th className="px-5 py-3">Type</th>
                                    <th className="px-5 py-3">Router / Package</th>
                                    <th className="px-5 py-3">Network</th>
                                    <th className="px-5 py-3">Usage</th>
                                    <th className="px-5 py-3">Status</th>
                                    <th className="px-5 py-3">Expiry</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {logs.map((row) => (
                                    <tr key={row.id} className="align-top">
                                        <td className="px-5 py-4">
                                            <div className="font-semibold text-foreground">{row.customer}</div>
                                            <div className="text-xs text-muted-foreground">{row.phone || row.username || 'No phone/username'}</div>
                                        </td>
                                        <td className="px-5 py-4"><StatusBadge value={row.type} /></td>
                                        <td className="px-5 py-4">
                                            <div className="font-medium text-foreground">{row.router}</div>
                                            <div className="text-xs text-muted-foreground">{row.package}</div>
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="font-mono text-xs text-muted-foreground">{row.ip || 'No IP'}</div>
                                            <div className="font-mono text-xs text-muted-foreground">{row.mac || 'No MAC'}</div>
                                        </td>
                                        <td className="px-5 py-4 font-medium text-foreground">{row.usage}</td>
                                        <td className="px-5 py-4"><StatusBadge value={row.status} /></td>
                                        <td className="px-5 py-4 text-muted-foreground">{row.expires}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <PaginationControls pagination={pagination} />
                    </TableShell>
                ) : <EmptyRows message="No connection logs found for the selected filters." />}
            </ReportCard>
        </ReportLayout>
    );
}
