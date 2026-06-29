import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, UserPlus } from 'lucide-react';
import { FormEvent } from 'react';

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

type Option = { id: number; name: string };

type Props = {
    defaults: {
        access_type?: string;
        connection_status?: string;
        billing_status?: string;
        shared_users?: number;
    };
    isps: Option[];
    routers: Option[];
    packages: Option[];
    storeUrl: string;
};

export default function CustomerCreate({ defaults, isps, routers, packages, storeUrl }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        isp_id: isps.length === 1 ? String(isps[0].id) : '',
        internet_package_id: '',
        mikrotik_router_id: '',
        access_type: defaults.access_type || 'hotspot',
        username: '',
        password: '',
        mac_address: '',
        ip_address: '',
        shared_users: String(defaults.shared_users || 1),
        name: '',
        phone: '',
        email: '',
        location: '',
        address: '',
        connection_status: defaults.connection_status || 'active',
        billing_status: defaults.billing_status || 'paid',
        monthly_amount: '0',
        installation_date: '',
        next_due_date: '',
        notes: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(storeUrl, { preserveScroll: true });
    };

    const error = (key: keyof typeof data) => errors[key] ? <p className="mt-1 text-xs text-red-600">{errors[key]}</p> : null;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Customers', url: route('wifi-billing.customers.index') },
                { label: 'Create Customer' },
            ]}
            pageTitle="Create Customer"
        >
            <Head title="Create Customer" />

            <div className="space-y-5">
                <div className="flex flex-col gap-3 rounded-lg border bg-card p-5 shadow-sm md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">Create Customer</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Add a hotspot or PPPoE subscriber. Existing provisioning logic will queue the MikroTik command after saving.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={route('wifi-billing.customers.index')}>
                            <ArrowLeft className="h-4 w-4" />
                            Back to Customers
                        </Link>
                    </Button>
                </div>

                <form onSubmit={submit} className="space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base"><UserPlus className="h-4 w-4" /> Customer Details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            {isps.length > 1 && (
                                <div>
                                    <label className="text-sm font-medium">ISP</label>
                                    <select className="mt-1 w-full rounded-md border bg-background px-3 py-2" value={data.isp_id} onChange={(e) => setData('isp_id', e.target.value)}>
                                        <option value="">Select ISP</option>
                                        {isps.map((isp) => <option key={isp.id} value={isp.id}>{isp.name}</option>)}
                                    </select>
                                    {error('isp_id')}
                                </div>
                            )}

                            <div>
                                <label className="text-sm font-medium">Name</label>
                                <Input className="mt-1" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Customer name" />
                                {error('name')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Phone</label>
                                <Input className="mt-1" value={data.phone} onChange={(e) => setData('phone', e.target.value)} placeholder="07... or 254..." />
                                {error('phone')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Email</label>
                                <Input className="mt-1" value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="Optional" />
                                {error('email')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Location</label>
                                <Input className="mt-1" value={data.location} onChange={(e) => setData('location', e.target.value)} placeholder="Area / estate" />
                                {error('location')}
                            </div>
                            <div className="md:col-span-2">
                                <label className="text-sm font-medium">Address</label>
                                <Input className="mt-1" value={data.address} onChange={(e) => setData('address', e.target.value)} placeholder="Optional address" />
                                {error('address')}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle className="text-base">Internet Access</CardTitle></CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="text-sm font-medium">Access Type</label>
                                <select className="mt-1 w-full rounded-md border bg-background px-3 py-2" value={data.access_type} onChange={(e) => setData('access_type', e.target.value)}>
                                    <option value="hotspot">Hotspot</option>
                                    <option value="pppoe">PPPoE</option>
                                </select>
                                {error('access_type')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Package</label>
                                <select className="mt-1 w-full rounded-md border bg-background px-3 py-2" value={data.internet_package_id} onChange={(e) => setData('internet_package_id', e.target.value)}>
                                    <option value="">No package</option>
                                    {packages.map((pkg) => <option key={pkg.id} value={pkg.id}>{pkg.name}</option>)}
                                </select>
                                {error('internet_package_id')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">MikroTik Router</label>
                                <select className="mt-1 w-full rounded-md border bg-background px-3 py-2" value={data.mikrotik_router_id} onChange={(e) => setData('mikrotik_router_id', e.target.value)}>
                                    <option value="">All linked routers / auto</option>
                                    {routers.map((router) => <option key={router.id} value={router.id}>{router.name}</option>)}
                                </select>
                                <p className="mt-1 text-xs text-muted-foreground">Leave blank to queue provisioning to all linked routers for this ISP.</p>
                                {error('mikrotik_router_id')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Shared Users / Devices</label>
                                <Input className="mt-1" type="number" min="1" value={data.shared_users} onChange={(e) => setData('shared_users', e.target.value)} />
                                {error('shared_users')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Username</label>
                                <Input className="mt-1" value={data.username} onChange={(e) => setData('username', e.target.value)} placeholder="Auto if blank" />
                                {error('username')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Password</label>
                                <Input className="mt-1" value={data.password} onChange={(e) => setData('password', e.target.value)} placeholder="Auto if blank" />
                                {error('password')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">MAC Address</label>
                                <Input className="mt-1" value={data.mac_address} onChange={(e) => setData('mac_address', e.target.value)} placeholder="Optional" />
                                {error('mac_address')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">IP Address</label>
                                <Input className="mt-1" value={data.ip_address} onChange={(e) => setData('ip_address', e.target.value)} placeholder="Optional" />
                                {error('ip_address')}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle className="text-base">Billing</CardTitle></CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="text-sm font-medium">Connection Status</label>
                                <select className="mt-1 w-full rounded-md border bg-background px-3 py-2" value={data.connection_status} onChange={(e) => setData('connection_status', e.target.value)}>
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="disconnected">Disconnected</option>
                                </select>
                                {error('connection_status')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Billing Status</label>
                                <select className="mt-1 w-full rounded-md border bg-background px-3 py-2" value={data.billing_status} onChange={(e) => setData('billing_status', e.target.value)}>
                                    <option value="paid">Paid</option>
                                    <option value="unpaid">Unpaid</option>
                                    <option value="overdue">Overdue</option>
                                </select>
                                {error('billing_status')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Monthly Amount</label>
                                <Input className="mt-1" type="number" min="0" step="0.01" value={data.monthly_amount} onChange={(e) => setData('monthly_amount', e.target.value)} />
                                {error('monthly_amount')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Installation Date</label>
                                <Input className="mt-1" type="date" value={data.installation_date} onChange={(e) => setData('installation_date', e.target.value)} />
                                {error('installation_date')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Next Due Date</label>
                                <Input className="mt-1" type="date" value={data.next_due_date} onChange={(e) => setData('next_due_date', e.target.value)} />
                                {error('next_due_date')}
                            </div>
                            <div className="md:col-span-2">
                                <label className="text-sm font-medium">Notes</label>
                                <textarea className="mt-1 min-h-24 w-full rounded-md border bg-background px-3 py-2" value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Optional notes" />
                                {error('notes')}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-2">
                        <Button variant="outline" asChild><Link href={route('wifi-billing.customers.index')}>Cancel</Link></Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="h-4 w-4" />
                            {processing ? 'Saving...' : 'Save Customer'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
