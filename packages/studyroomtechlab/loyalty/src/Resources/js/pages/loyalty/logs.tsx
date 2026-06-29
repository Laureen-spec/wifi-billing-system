import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { EmptyState, PageHeader } from '@/pages/wifi-billing/components';
import { Head, router } from '@inertiajs/react';
import { Clock3, Search } from 'lucide-react';
import { FormEvent, useState } from 'react';
import {
    CustomerLabel,
    CustomerSummary,
    FilterChip,
    IspOption,
    LoyaltyStatus,
    Option,
    Paginated,
    PlatformIspSelect,
    points,
} from './components';

type LogRow = {
    id: number;
    type: string;
    points: number;
    source_type?: string | null;
    source_id?: string | null;
    description?: string | null;
    expires_at?: string | null;
    expired_at?: string | null;
    created_at?: string | null;
    customer?: CustomerSummary | null;
    isp?: { id: number; name: string } | null;
    created_by?: string | null;
};

type Props = {
    isp?: { id: number; name: string } | null;
    isPlatform: boolean;
    isps: IspOption[];
    filters: { type?: string; q?: string; isp_id?: number | null };
    logs: Paginated<LogRow>;
    typeOptions: Option[];
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function LoyaltyLogs({ isp, isPlatform, isps, filters, logs, typeOptions }: Props) {
    const [search, setSearch] = useState(filters.q || '');

    const cleanFilters = () => ({
        ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
        ...(search ? { q: search } : {}),
        ...((filters.type && filters.type !== 'all') ? { type: filters.type } : {}),
    });

    const visit = (params: Record<string, unknown>) => {
        router.get(route('loyalty.logs.index'), {
            ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
            ...(search ? { q: search } : {}),
            type: filters.type || 'all',
            ...params,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        visit({ q: search, page: 1 });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Loyalty', url: route('loyalty.index') },
                { label: 'Activity Logs' },
            ]}
            pageTitle="Activity Logs"
        >
            <Head title="Loyalty Activity Logs" />

            <div className="space-y-5">
                <PageHeader
                    title="Activity Logs"
                    description={isp ? `Point transactions for ${isp.name}.` : 'Point transactions across ISP accounts.'}
                    actions={
                        <PlatformIspSelect
                            isPlatform={isPlatform}
                            isps={isps}
                            value={filters.isp_id}
                            routeName="loyalty.logs.index"
                            extraFilters={cleanFilters()}
                        />
                    }
                />

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Search className="h-4 w-4" />
                            Filters
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 p-4">
                        <form onSubmit={submitSearch} className="flex flex-col gap-3 sm:flex-row">
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search customer, source, description..."
                                className="sm:max-w-xl"
                            />
                            <Button type="submit">Search</Button>
                        </form>
                        <div className="flex flex-wrap gap-2">
                            {typeOptions.map((option) => (
                                <FilterChip
                                    key={option.value}
                                    active={(filters.type || 'all') === option.value}
                                    onClick={() => visit({ type: option.value, page: 1 })}
                                >
                                    {option.label}
                                </FilterChip>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {logs.data.length === 0 ? (
                            <div className="p-4">
                                <EmptyState title="No activity logs" description="Loyalty transactions will appear here." />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Customer</TableHead>
                                        {isPlatform && <TableHead>ISP</TableHead>}
                                        <TableHead>Type</TableHead>
                                        <TableHead className="text-right">Points</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Source</TableHead>
                                        <TableHead>Date</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {logs.data.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell><CustomerLabel customer={row.customer} /></TableCell>
                                            {isPlatform && <TableCell>{row.isp?.name || 'Not set'}</TableCell>}
                                            <TableCell><LoyaltyStatus value={row.type} /></TableCell>
                                            <TableCell className="text-right font-semibold">{points(row.points)}</TableCell>
                                            <TableCell className="max-w-sm text-sm text-muted-foreground">
                                                <div className="line-clamp-2">{row.description || 'Not set'}</div>
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">
                                                {row.source_type ? `${row.source_type.split('\\').pop()} #${row.source_id || '-'}` : 'Not set'}
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                <div className="flex items-center gap-2">
                                                    <Clock3 className="h-4 w-4" />
                                                    {row.created_at || 'Not set'}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {logs.last_page > 1 && (
                        <CardContent className="border-t px-4 py-0">
                            <Pagination data={logs} routeName="loyalty.logs.index" filters={cleanFilters()} />
                        </CardContent>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
