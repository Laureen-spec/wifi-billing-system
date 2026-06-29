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
import { Edit3, Plus, Trash2 } from 'lucide-react';
import { IspOption, Option, Paginated, PlatformIspSelect, Tr069Status, titleCase } from '../components';

type Profile = {
    id: number;
    name: string;
    description?: string | null;
    wan_mode: string;
    pppoe_username?: string | null;
    vlan_id?: number | null;
    wifi_ssid?: string | null;
    wifi_enabled: boolean;
    status: string;
    isp?: { id: number; name: string } | null;
    edit_url: string;
    destroy_url: string;
};

type Props = {
    isp?: { id: number; name: string } | null;
    isPlatform: boolean;
    isps: IspOption[];
    filters: { status?: string; wan_mode?: string; isp_id?: number | null };
    profiles: Paginated<Profile>;
    statusOptions: Option[];
    wanModeOptions: Option[];
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function Tr069ProfilesIndex({ isp, isPlatform, isps, filters, profiles, statusOptions, wanModeOptions }: Props) {
    const currentFilters = () => ({
        ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
        ...((filters.status && filters.status !== 'all') ? { status: filters.status } : {}),
        ...((filters.wan_mode && filters.wan_mode !== 'all') ? { wan_mode: filters.wan_mode } : {}),
    });

    const visit = (params: Record<string, unknown>) => {
        router.get(route('tr069.profiles.index'), {
            ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
            status: filters.status || 'all',
            wan_mode: filters.wan_mode || 'all',
            ...params,
        }, { preserveState: true, replace: true });
    };

    const destroyProfile = (profile: Profile) => {
        if (!window.confirm(`Delete TR069 profile "${profile.name}"?`)) {
            return;
        }

        router.delete(profile.destroy_url, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'TR069', url: route('tr069.index') },
                { label: 'Provisioning Profiles' },
            ]}
            pageTitle="Provisioning Profiles"
        >
            <Head title="TR069 Provisioning Profiles" />

            <div className="space-y-5">
                <PageHeader
                    title="Provisioning Profiles"
                    description={isp ? `Provisioning templates for ${isp.name}.` : 'Provisioning templates across ISP accounts.'}
                    actions={
                        <>
                            <PlatformIspSelect isPlatform={isPlatform} isps={isps} value={filters.isp_id} routeName="tr069.profiles.index" extraFilters={currentFilters()} />
                            <Button asChild><Link href={route('tr069.profiles.create', filters.isp_id ? { isp_id: filters.isp_id } : {})}><Plus className="h-4 w-4" />New Profile</Link></Button>
                        </>
                    }
                />

                <Card>
                    <CardHeader className="border-b py-4"><CardTitle className="text-base">Filters</CardTitle></CardHeader>
                    <CardContent className="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Select value={filters.status || 'all'} onValueChange={(value) => visit({ status: value, page: 1 })}>
                            <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
                            <SelectContent>{statusOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent>
                        </Select>
                        <Select value={filters.wan_mode || 'all'} onValueChange={(value) => visit({ wan_mode: value, page: 1 })}>
                            <SelectTrigger><SelectValue placeholder="WAN mode" /></SelectTrigger>
                            <SelectContent>{wanModeOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent>
                        </Select>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {profiles.data.length === 0 ? (
                            <div className="p-4"><EmptyState title="No provisioning profiles" description="Create profiles for WAN, PPPoE, static IP, bridge, WiFi, VLAN, and TR069 parameter pushes." /></div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Profile</TableHead>
                                        {isPlatform && <TableHead>ISP</TableHead>}
                                        <TableHead>WAN</TableHead>
                                        <TableHead>WiFi</TableHead>
                                        <TableHead>VLAN</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {profiles.data.map((profile) => (
                                        <TableRow key={profile.id}>
                                            <TableCell>
                                                <div className="font-medium">{profile.name}</div>
                                                <div className="line-clamp-1 text-xs text-muted-foreground">{profile.description || 'No description'}</div>
                                            </TableCell>
                                            {isPlatform && <TableCell>{profile.isp?.name || 'Not set'}</TableCell>}
                                            <TableCell>
                                                <div>{titleCase(profile.wan_mode)}</div>
                                                <div className="text-xs text-muted-foreground">{profile.pppoe_username || 'No PPPoE user'}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div>{profile.wifi_ssid || 'No SSID'}</div>
                                                <div className="text-xs text-muted-foreground">{profile.wifi_enabled ? 'Enabled' : 'Disabled'}</div>
                                            </TableCell>
                                            <TableCell>{profile.vlan_id || 'None'}</TableCell>
                                            <TableCell><Tr069Status value={profile.status} /></TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button size="sm" variant="outline" asChild><Link href={profile.edit_url}><Edit3 className="h-4 w-4" /></Link></Button>
                                                    <Button size="sm" variant="outline" onClick={() => destroyProfile(profile)}><Trash2 className="h-4 w-4" /></Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {profiles.last_page > 1 && <CardContent className="border-t px-4 py-0"><Pagination data={profiles} routeName="tr069.profiles.index" filters={currentFilters()} /></CardContent>}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
