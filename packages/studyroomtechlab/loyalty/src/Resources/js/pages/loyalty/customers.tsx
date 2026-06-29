import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pagination } from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { EmptyState, PageHeader } from '@/pages/wifi-billing/components';
import { Head, router, useForm } from '@inertiajs/react';
import { Gift, PlusCircle, Search, Users } from 'lucide-react';
import { FormEvent, useState } from 'react';
import {
    CustomerLabel,
    CustomerSummary,
    IspOption,
    LoyaltyStatus,
    Paginated,
    PlatformIspSelect,
    points,
} from './components';

type CustomerPoint = {
    id: number;
    customer_id: number;
    current_points: number;
    lifetime_points: number;
    redeemed_points: number;
    last_awarded_at?: string | null;
    customer?: CustomerSummary | null;
    isp?: { id: number; name: string } | null;
    manual_points_url: string;
};

type ActivityRow = {
    id: number;
    type: string;
    points: number;
    description?: string | null;
    customer?: CustomerSummary | null;
    created_at?: string | null;
};

type Props = {
    isp?: { id: number; name: string } | null;
    isPlatform: boolean;
    isps: IspOption[];
    filters: { q?: string; isp_id?: number | null };
    customers: Paginated<CustomerPoint>;
    recentTransactions: ActivityRow[];
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function LoyaltyCustomers({ isp, isPlatform, isps, filters, customers, recentTransactions }: Props) {
    const [search, setSearch] = useState(filters.q || '');
    const [selectedPoint, setSelectedPoint] = useState<CustomerPoint | null>(null);
    const manualForm = useForm({
        points: '',
        description: '',
    });

    const currentFilters = () => ({
        ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
        ...(search ? { q: search } : {}),
    });

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('loyalty.customers'), currentFilters(), {
            preserveState: true,
            replace: true,
        });
    };

    const submitManualPoints = (event: FormEvent) => {
        event.preventDefault();
        if (!selectedPoint) {
            return;
        }

        manualForm.post(selectedPoint.manual_points_url, {
            preserveScroll: true,
            onSuccess: () => {
                manualForm.reset();
                setSelectedPoint(null);
            },
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Loyalty', url: route('loyalty.index') },
                { label: 'Customer Points' },
            ]}
            pageTitle="Customer Points"
        >
            <Head title="Customer Points" />

            <Dialog open={Boolean(selectedPoint)} onOpenChange={(open) => !open && setSelectedPoint(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Award manual points</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitManualPoints} className="space-y-4">
                        <div className="rounded-lg border bg-muted/30 p-3">
                            <CustomerLabel customer={selectedPoint?.customer} />
                            <div className="mt-2 text-sm text-muted-foreground">
                                Current balance: {points(selectedPoint?.current_points)}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="manual-points">Points</Label>
                            <Input
                                id="manual-points"
                                type="number"
                                min="1"
                                value={manualForm.data.points}
                                onChange={(event) => manualForm.setData('points', event.target.value)}
                            />
                            {manualForm.errors.points && <p className="text-sm text-destructive">{manualForm.errors.points}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="manual-description">Description</Label>
                            <Textarea
                                id="manual-description"
                                rows={3}
                                value={manualForm.data.description}
                                onChange={(event) => manualForm.setData('description', event.target.value)}
                            />
                            {manualForm.errors.description && <p className="text-sm text-destructive">{manualForm.errors.description}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setSelectedPoint(null)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={manualForm.processing}>
                                <Gift className="h-4 w-4" />
                                {manualForm.processing ? 'Saving...' : 'Award Points'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div className="space-y-5">
                <PageHeader
                    title="Customer Points"
                    description={isp ? `Point balances for ${isp.name}.` : 'Point balances across ISP accounts.'}
                    actions={
                        <PlatformIspSelect
                            isPlatform={isPlatform}
                            isps={isps}
                            value={filters.isp_id}
                            routeName="loyalty.customers"
                            extraFilters={search ? { q: search } : {}}
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
                    <CardContent className="p-4">
                        <form onSubmit={submitSearch} className="flex flex-col gap-3 sm:flex-row">
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search customer name, phone, username..."
                                className="sm:max-w-xl"
                            />
                            <div className="flex gap-2">
                                <Button type="submit">Search</Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setSearch('');
                                        router.get(route('loyalty.customers'), filters.isp_id ? { isp_id: filters.isp_id } : {}, {
                                            preserveState: true,
                                            replace: true,
                                        });
                                    }}
                                >
                                    Reset
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {customers.data.length === 0 ? (
                            <div className="p-4">
                                <EmptyState title="No loyalty balances" description="Customer balances appear after points are awarded." />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Customer</TableHead>
                                        {isPlatform && <TableHead>ISP</TableHead>}
                                        <TableHead className="text-right">Current</TableHead>
                                        <TableHead className="text-right">Lifetime</TableHead>
                                        <TableHead className="text-right">Redeemed</TableHead>
                                        <TableHead>Last Awarded</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {customers.data.map((point) => (
                                        <TableRow key={point.id}>
                                            <TableCell><CustomerLabel customer={point.customer} /></TableCell>
                                            {isPlatform && <TableCell>{point.isp?.name || 'Not set'}</TableCell>}
                                            <TableCell className="text-right font-semibold">{points(point.current_points)}</TableCell>
                                            <TableCell className="text-right">{points(point.lifetime_points)}</TableCell>
                                            <TableCell className="text-right">{points(point.redeemed_points)}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{point.last_awarded_at || 'Not set'}</TableCell>
                                            <TableCell className="text-right">
                                                <Button type="button" variant="outline" size="sm" onClick={() => setSelectedPoint(point)}>
                                                    <PlusCircle className="h-4 w-4" />
                                                    Adjust
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {customers.last_page > 1 && (
                        <CardContent className="border-t px-4 py-0">
                            <Pagination data={customers} routeName="loyalty.customers" filters={currentFilters()} />
                        </CardContent>
                    )}
                </Card>

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Users className="h-4 w-4" />
                            Recent Point History
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {recentTransactions.length === 0 ? (
                            <div className="p-4">
                                <EmptyState title="No point history" description="Point history appears after loyalty activity starts." />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Customer</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead className="text-right">Points</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Date</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentTransactions.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell><CustomerLabel customer={row.customer} /></TableCell>
                                            <TableCell><LoyaltyStatus value={row.type} /></TableCell>
                                            <TableCell className="text-right font-semibold">{points(row.points)}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{row.description || 'Not set'}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{row.created_at || 'Not set'}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
