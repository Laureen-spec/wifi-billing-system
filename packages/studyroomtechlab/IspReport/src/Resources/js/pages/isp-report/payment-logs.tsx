import { ChipFilters, EmptyRows, Option, Pagination, PaginationControls, ReportCard, ReportLayout, SearchBox, Stat, StatsGrid, StatusBadge, TableShell } from './components';

type PaymentLog = {
    id: string;
    customer: string;
    phone?: string | null;
    receipt: string;
    method: string;
    source: string;
    package: string;
    amount: string;
    status: string;
    wallet: string;
    provisioning: string;
    when: string;
};

type Props = {
    pageTitle: string;
    subtitle: string;
    filters: { search: string; status: string; method: string };
    statusOptions: Option[];
    methodOptions: Option[];
    logs: PaymentLog[];
    pagination: Pagination;
    stats: Stat[];
};

export default function PaymentLogs({ pageTitle, subtitle, filters, statusOptions, methodOptions, logs, pagination, stats }: Props) {
    return (
        <ReportLayout title={pageTitle} subtitle={subtitle}>
            <StatsGrid stats={stats} />

            <ReportCard title="Filters" description="Review payment logs using simple chips instead of bulky forms.">
                <div className="space-y-4">
                    <SearchBox routeName="isp-reports.payment-logs" placeholder="Search customer, phone, receipt, checkout ID..." filters={filters} />
                    <ChipFilters label="Status" name="status" value={filters.status || 'all'} options={statusOptions} routeName="isp-reports.payment-logs" extra={{ search: filters.search, method: filters.method }} />
                    <ChipFilters label="Method" name="method" value={filters.method || 'all'} options={methodOptions} routeName="isp-reports.payment-logs" extra={{ search: filters.search, status: filters.status }} />
                </div>
            </ReportCard>

            <ReportCard title="Payment logs" description="Collections, wallet posting, and provisioning traceability.">
                {logs.length ? (
                    <TableShell>
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th className="px-5 py-3">Customer</th>
                                    <th className="px-5 py-3">Receipt</th>
                                    <th className="px-5 py-3">Method</th>
                                    <th className="px-5 py-3">Package</th>
                                    <th className="px-5 py-3">Amount</th>
                                    <th className="px-5 py-3">Status</th>
                                    <th className="px-5 py-3">Wallet / Provisioning</th>
                                    <th className="px-5 py-3">When</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {logs.map((row) => (
                                    <tr key={row.id} className="align-top">
                                        <td className="px-5 py-4">
                                            <div className="font-semibold text-foreground">{row.customer}</div>
                                            <div className="text-xs text-muted-foreground">{row.phone || 'No phone'}</div>
                                        </td>
                                        <td className="px-5 py-4 font-mono text-xs text-muted-foreground">{row.receipt}</td>
                                        <td className="px-5 py-4"><StatusBadge value={row.method} /></td>
                                        <td className="px-5 py-4 text-muted-foreground">{row.package}</td>
                                        <td className="px-5 py-4 font-semibold text-foreground">{row.amount}</td>
                                        <td className="px-5 py-4"><StatusBadge value={row.status} /></td>
                                        <td className="px-5 py-4">
                                            <div><StatusBadge value={row.wallet} /></div>
                                            <div className="mt-1"><StatusBadge value={row.provisioning} /></div>
                                        </td>
                                        <td className="px-5 py-4 text-muted-foreground">{row.when}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <PaginationControls pagination={pagination} />
                    </TableShell>
                ) : <EmptyRows message="No payment logs found for the selected filters." />}
            </ReportCard>
        </ReportLayout>
    );
}
