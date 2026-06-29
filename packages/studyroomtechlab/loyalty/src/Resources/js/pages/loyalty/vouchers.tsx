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
import { CheckCircle2, Search, Ticket } from 'lucide-react';
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
    minutes,
    points,
} from './components';

type Voucher = {
    id: number;
    voucher_code: string;
    points_used: number;
    package_name?: string | null;
    duration_minutes?: number | null;
    status: string;
    expires_at?: string | null;
    redeemed_at?: string | null;
    created_at?: string | null;
    customer?: CustomerSummary | null;
    isp?: { id: number; name: string } | null;
    redeem_url: string;
};

type Props = {
    isp?: { id: number; name: string } | null;
    isPlatform: boolean;
    isps: IspOption[];
    filters: { q?: string; status?: string; isp_id?: number | null };
    vouchers: Paginated<Voucher>;
    statusOptions: Option[];
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function LoyaltyVouchers({ isp, isPlatform, isps, filters, vouchers, statusOptions }: Props) {
    const [search, setSearch] = useState(filters.q || '');

    const cleanFilters = () => ({
        ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
        ...(search ? { q: search } : {}),
        ...((filters.status && filters.status !== 'all') ? { status: filters.status } : {}),
    });

    const visit = (params: Record<string, unknown>) => {
        router.get(route('loyalty.vouchers.index'), {
            ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
            ...(search ? { q: search } : {}),
            status: filters.status || 'all',
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

    const redeemVoucher = (voucher: Voucher) => {
        if (!window.confirm(`Mark voucher ${voucher.voucher_code} as redeemed?`)) {
            return;
        }

        router.post(voucher.redeem_url, {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Loyalty', url: route('loyalty.index') },
                { label: 'Vouchers' },
            ]}
            pageTitle="Loyalty Vouchers"
        >
            <Head title="Loyalty Vouchers" />

            <div className="space-y-5">
                <PageHeader
                    title="Vouchers"
                    description={isp ? `Loyalty vouchers for ${isp.name}.` : 'Loyalty vouchers across ISP accounts.'}
                    actions={
                        <PlatformIspSelect
                            isPlatform={isPlatform}
                            isps={isps}
                            value={filters.isp_id}
                            routeName="loyalty.vouchers.index"
                            extraFilters={cleanFilters()}
                        />
                    }
                />

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Search className="h-4 w-4" />
                            Search
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 p-4">
                        <form onSubmit={submitSearch} className="flex flex-col gap-3 sm:flex-row">
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search voucher code, customer, phone..."
                                className="sm:max-w-xl"
                            />
                            <Button type="submit">Search</Button>
                        </form>
                        <div className="flex flex-wrap gap-2">
                            {statusOptions.map((option) => (
                                <FilterChip
                                    key={option.value}
                                    active={(filters.status || 'all') === option.value}
                                    onClick={() => visit({ status: option.value, page: 1 })}
                                >
                                    {option.label}
                                </FilterChip>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {vouchers.data.length === 0 ? (
                            <div className="p-4">
                                <EmptyState title="No vouchers found" description="Generated loyalty vouchers will appear here." />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Voucher</TableHead>
                                        <TableHead>Customer</TableHead>
                                        {isPlatform && <TableHead>ISP</TableHead>}
                                        <TableHead>Package</TableHead>
                                        <TableHead className="text-right">Points</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Expiry</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {vouchers.data.map((voucher) => (
                                        <TableRow key={voucher.id}>
                                            <TableCell>
                                                <div className="font-mono text-sm font-semibold">{voucher.voucher_code}</div>
                                                <div className="text-xs text-muted-foreground">{voucher.created_at || 'Not set'}</div>
                                            </TableCell>
                                            <TableCell><CustomerLabel customer={voucher.customer} /></TableCell>
                                            {isPlatform && <TableCell>{voucher.isp?.name || 'Not set'}</TableCell>}
                                            <TableCell>
                                                <div>{voucher.package_name || 'Not set'}</div>
                                                <div className="text-xs text-muted-foreground">{minutes(voucher.duration_minutes)}</div>
                                            </TableCell>
                                            <TableCell className="text-right font-semibold">{points(voucher.points_used)}</TableCell>
                                            <TableCell><LoyaltyStatus value={voucher.status} /></TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{voucher.expires_at || 'No expiry'}</TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={voucher.status !== 'unused'}
                                                    onClick={() => redeemVoucher(voucher)}
                                                >
                                                    {voucher.status === 'unused' ? <CheckCircle2 className="h-4 w-4" /> : <Ticket className="h-4 w-4" />}
                                                    Redeem
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {vouchers.last_page > 1 && (
                        <CardContent className="border-t px-4 py-0">
                            <Pagination data={vouchers} routeName="loyalty.vouchers.index" filters={cleanFilters()} />
                        </CardContent>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
