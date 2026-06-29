import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Pagination } from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { EmptyState, PageHeader } from '@/pages/wifi-billing/components';
import { Head, Link, router } from '@inertiajs/react';
import { Edit3, Filter, Plus, Trash2 } from 'lucide-react';
import {
    IspOption,
    LoyaltyStatus,
    Option,
    Paginated,
    PlatformIspSelect,
    minutes,
    points,
    titleCase,
} from '../components';

type Rule = {
    id: number;
    isp_id: number;
    name: string;
    trigger_type: string;
    trigger_label: string;
    points_value: number;
    amount_step?: number | null;
    renewal_count?: number | null;
    auto_voucher: boolean;
    voucher_threshold?: number | null;
    voucher_package_name?: string | null;
    voucher_duration_minutes?: number | null;
    is_active: boolean;
    isp?: { id: number; name: string } | null;
    edit_url: string;
    destroy_url: string;
};

type Props = {
    isp?: { id: number; name: string } | null;
    isPlatform: boolean;
    isps: IspOption[];
    rules: Paginated<Rule>;
    filters: { status?: string; trigger_type?: string; isp_id?: number | null };
    triggerOptions: Option[];
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function LoyaltyRulesIndex({ isp, isPlatform, isps, rules, filters, triggerOptions }: Props) {
    const visit = (params: Record<string, unknown>) => {
        router.get(route('loyalty.rules.index'), {
            ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
            status: filters.status || 'all',
            trigger_type: filters.trigger_type || 'all',
            ...params,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const cleanFilters = () => ({
        ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
        ...((filters.status && filters.status !== 'all') ? { status: filters.status } : {}),
        ...((filters.trigger_type && filters.trigger_type !== 'all') ? { trigger_type: filters.trigger_type } : {}),
    });

    const destroyRule = (rule: Rule) => {
        if (!window.confirm(`Delete reward rule "${rule.name}"?`)) {
            return;
        }

        router.delete(rule.destroy_url, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Loyalty', url: route('loyalty.index') },
                { label: 'Reward Rules' },
            ]}
            pageTitle="Reward Rules"
        >
            <Head title="Reward Rules" />

            <div className="space-y-5">
                <PageHeader
                    title="Reward Rules"
                    description={isp ? `Rules for ${isp.name}.` : 'Rules across ISP accounts.'}
                    actions={
                        <>
                            <PlatformIspSelect
                                isPlatform={isPlatform}
                                isps={isps}
                                value={filters.isp_id}
                                routeName="loyalty.rules.index"
                                extraFilters={cleanFilters()}
                            />
                            <Button asChild>
                                <Link href={route('loyalty.rules.create', filters.isp_id ? { isp_id: filters.isp_id } : {})}>
                                    <Plus className="h-4 w-4" />
                                    New Rule
                                </Link>
                            </Button>
                        </>
                    }
                />

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Filter className="h-4 w-4" />
                            Filters
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Select value={filters.status || 'all'} onValueChange={(value) => visit({ status: value, page: 1 })}>
                            <SelectTrigger>
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All statuses</SelectItem>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive">Inactive</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select value={filters.trigger_type || 'all'} onValueChange={(value) => visit({ trigger_type: value, page: 1 })}>
                            <SelectTrigger>
                                <SelectValue placeholder="Trigger" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All triggers</SelectItem>
                                {triggerOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {rules.data.length === 0 ? (
                            <div className="p-4">
                                <EmptyState
                                    title="No reward rules"
                                    description="Create rules for successful payments, amount spent, renewals, and bonuses."
                                    action={
                                        <Button asChild>
                                            <Link href={route('loyalty.rules.create', filters.isp_id ? { isp_id: filters.isp_id } : {})}>
                                                <Plus className="h-4 w-4" />
                                                New Rule
                                            </Link>
                                        </Button>
                                    }
                                />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Rule</TableHead>
                                        {isPlatform && <TableHead>ISP</TableHead>}
                                        <TableHead>Trigger</TableHead>
                                        <TableHead className="text-right">Points</TableHead>
                                        <TableHead>Voucher</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rules.data.map((rule) => (
                                        <TableRow key={rule.id}>
                                            <TableCell>
                                                <div className="font-medium">{rule.name}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {rule.amount_step ? `Every KES ${rule.amount_step}` : rule.renewal_count ? `${rule.renewal_count} renewals` : 'Standard award'}
                                                </div>
                                            </TableCell>
                                            {isPlatform && <TableCell>{rule.isp?.name || 'Not set'}</TableCell>}
                                            <TableCell>{rule.trigger_label}</TableCell>
                                            <TableCell className="text-right font-semibold">{points(rule.points_value)}</TableCell>
                                            <TableCell>
                                                <div className="text-sm">{rule.auto_voucher ? 'Auto voucher' : 'Points only'}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {rule.voucher_threshold ? `${points(rule.voucher_threshold)} pts` : 'No threshold'}
                                                    {rule.voucher_duration_minutes ? ` - ${minutes(rule.voucher_duration_minutes)}` : ''}
                                                </div>
                                            </TableCell>
                                            <TableCell><LoyaltyStatus value={rule.is_active ? 'active' : 'inactive'} /></TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={rule.edit_url}>
                                                            <Edit3 className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button variant="outline" size="sm" onClick={() => destroyRule(rule)}>
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {rules.last_page > 1 && (
                        <CardContent className="border-t px-4 py-0">
                            <Pagination data={rules} routeName="loyalty.rules.index" filters={cleanFilters()} />
                        </CardContent>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
