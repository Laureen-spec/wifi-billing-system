import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
import { Eye, Play, RefreshCw, Search, UploadCloud } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { FilterChip, IspOption, Option, Paginated, PlatformIspSelect, Tr069Status, dateText } from './components';

type Device = {
    id: number;
    serial_number: string;
    manufacturer?: string | null;
    model?: string | null;
    firmware_version?: string | null;
    ip_address?: string | null;
    mac_address?: string | null;
    last_inform_at?: string | null;
    status: string;
    customer?: { id: number; name?: string | null; phone?: string | null; username?: string | null } | null;
    isp?: { id: number; name: string } | null;
    show_url: string;
    provision_url: string;
    reboot_url: string;
    push_profile_url: string;
};

type ProfileOption = {
    id: number;
    name: string;
};

type Props = {
    isp?: { id: number; name: string } | null;
    isPlatform: boolean;
    isps: IspOption[];
    filters: { q?: string; status?: string; isp_id?: number | null };
    devices: Paginated<Device>;
    statusOptions: Option[];
    profileOptions: ProfileOption[];
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export default function Tr069Devices({ isp, isPlatform, isps, filters, devices, statusOptions, profileOptions }: Props) {
    const [search, setSearch] = useState(filters.q || '');
    const [profileId, setProfileId] = useState(profileOptions[0]?.id ? String(profileOptions[0].id) : '');

    const params = () => ({
        ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
        ...(search ? { q: search } : {}),
        ...((filters.status && filters.status !== 'all') ? { status: filters.status } : {}),
    });

    const visit = (next: Record<string, unknown>) => {
        router.get(route('tr069.devices.index'), {
            ...(filters.isp_id ? { isp_id: filters.isp_id } : {}),
            ...(search ? { q: search } : {}),
            status: filters.status || 'all',
            ...next,
        }, { preserveState: true, replace: true });
    };

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        visit({ q: search, page: 1 });
    };

    const postAction = (url: string, payload: Record<string, string | number | boolean | null | undefined> = {}) => {
        router.post(url, payload, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'TR069', url: route('tr069.index') },
                { label: 'CPE Devices' },
            ]}
            pageTitle="CPE Devices"
        >
            <Head title="TR069 CPE Devices" />

            <div className="space-y-5">
                <PageHeader
                    title="CPE Devices"
                    description={isp ? `TR069 CPE inventory for ${isp.name}.` : 'TR069 CPE inventory across ISP accounts.'}
                    actions={<PlatformIspSelect isPlatform={isPlatform} isps={isps} value={filters.isp_id} routeName="tr069.devices.index" extraFilters={params()} />}
                />

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Search className="h-4 w-4" />
                            Search and Filters
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 p-4">
                        <form onSubmit={submitSearch} className="flex flex-col gap-3 sm:flex-row">
                            <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search serial, customer, model, MAC, IP..." className="sm:max-w-xl" />
                            <Button type="submit">Search</Button>
                        </form>
                        <div className="flex flex-wrap gap-2">
                            {statusOptions.map((option) => (
                                <FilterChip key={option.value} active={(filters.status || 'all') === option.value} onClick={() => visit({ status: option.value, page: 1 })}>
                                    {option.label}
                                </FilterChip>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {devices.data.length === 0 ? (
                            <div className="p-4">
                                <EmptyState title="No CPE devices found" description="Devices will appear after they inform the TR069 endpoint or are seeded by future ACS integration." />
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Device</TableHead>
                                        <TableHead>Customer</TableHead>
                                        {isPlatform && <TableHead>ISP</TableHead>}
                                        <TableHead>Firmware</TableHead>
                                        <TableHead>IP</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Last Inform</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {devices.data.map((device) => (
                                        <TableRow key={device.id}>
                                            <TableCell>
                                                <div className="font-mono text-sm font-semibold">{device.serial_number}</div>
                                                <div className="text-xs text-muted-foreground">{[device.manufacturer, device.model].filter(Boolean).join(' ') || 'Unknown model'}</div>
                                                <div className="text-xs text-muted-foreground">{device.mac_address || 'No MAC'}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="font-medium">{device.customer?.name || 'Unassigned'}</div>
                                                <div className="text-xs text-muted-foreground">{device.customer?.phone || device.customer?.username || 'No customer link'}</div>
                                            </TableCell>
                                            {isPlatform && <TableCell>{device.isp?.name || 'Not set'}</TableCell>}
                                            <TableCell>{device.firmware_version || 'Not set'}</TableCell>
                                            <TableCell>{device.ip_address || 'Not set'}</TableCell>
                                            <TableCell><Tr069Status value={device.status} /></TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{dateText(device.last_inform_at)}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex flex-wrap justify-end gap-2">
                                                    <Button size="sm" variant="outline" asChild>
                                                        <Link href={device.show_url}><Eye className="h-4 w-4" /></Link>
                                                    </Button>
                                                    <Button size="sm" variant="outline" onClick={() => postAction(device.provision_url)}>
                                                        <Play className="h-4 w-4" />
                                                    </Button>
                                                    <Button size="sm" variant="outline" onClick={() => postAction(device.reboot_url)}>
                                                        <RefreshCw className="h-4 w-4" />
                                                    </Button>
                                                    <Select value={profileId} onValueChange={setProfileId}>
                                                        <SelectTrigger className="h-9 w-36"><SelectValue placeholder="Profile" /></SelectTrigger>
                                                        <SelectContent>
                                                            {profileOptions.map((profile) => (
                                                                <SelectItem key={profile.id} value={String(profile.id)}>{profile.name}</SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <Button size="sm" variant="outline" disabled={!profileId} onClick={() => postAction(device.push_profile_url, { profile_id: profileId })}>
                                                        <UploadCloud className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {devices.last_page > 1 && (
                        <CardContent className="border-t px-4 py-0">
                            <Pagination data={devices} routeName="tr069.devices.index" filters={params()} />
                        </CardContent>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
