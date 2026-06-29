import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Router, Save } from 'lucide-react';
import { FormEvent } from 'react';

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

type Option = { id: number; name: string };

type Props = {
    defaults: {
        api_port?: number;
        status?: string;
        connection_type?: string;
        host?: string;
    };
    isps: Option[];
    storeUrl: string;
};

export default function RouterCreate({ defaults, isps, storeUrl }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        isp_id: isps.length === 1 ? String(isps[0].id) : '',
        name: '',
        host: defaults.host || 'pending-link',
        api_port: String(defaults.api_port || 8728),
        username: '',
        password: '',
        connection_type: defaults.connection_type || 'agent',
        status: defaults.status || 'active',
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
                { label: 'Routers', url: route('wifi-billing.routers.index') },
                { label: 'Add MikroTik' },
            ]}
            pageTitle="Add MikroTik"
        >
            <Head title="Add MikroTik" />

            <div className="space-y-5">
                <div className="flex flex-col gap-3 rounded-lg border bg-card p-5 shadow-sm md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">Add MikroTik</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Link a router in agent mode or API mode. Provisioning scripts and RouterOS commands remain unchanged.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={route('wifi-billing.routers.index')}>
                            <ArrowLeft className="h-4 w-4" />
                            Back to Routers
                        </Link>
                    </Button>
                </div>

                <form onSubmit={submit} className="space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base"><Router className="h-4 w-4" /> Router Details</CardTitle>
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
                                <label className="text-sm font-medium">MikroTik Name</label>
                                <Input className="mt-1" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="RB951Ui / Njoro Main" />
                                {error('name')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Host/IP</label>
                                <Input className="mt-1" value={data.host} onChange={(e) => setData('host', e.target.value)} placeholder="192.168.1.186 or pending-link" />
                                <p className="mt-1 text-xs text-muted-foreground">Agent mode can work without API host. API mode needs a reachable IP.</p>
                                {error('host')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">API Port</label>
                                <Input className="mt-1" type="number" value={data.api_port} onChange={(e) => setData('api_port', e.target.value)} />
                                {error('api_port')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">API Username</label>
                                <Input className="mt-1" value={data.username} onChange={(e) => setData('username', e.target.value)} placeholder="Optional for agent mode" />
                                {error('username')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">API Password</label>
                                <Input className="mt-1" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} placeholder="Optional for agent mode" />
                                {error('password')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Connection Type</label>
                                <select className="mt-1 w-full rounded-md border bg-background px-3 py-2" value={data.connection_type} onChange={(e) => setData('connection_type', e.target.value)}>
                                    <option value="agent">Agent mode</option>
                                    <option value="api">Direct API</option>
                                    <option value="pending">Pending</option>
                                </select>
                                {error('connection_type')}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Status</label>
                                <select className="mt-1 w-full rounded-md border bg-background px-3 py-2" value={data.status} onChange={(e) => setData('status', e.target.value)}>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="pending">Pending</option>
                                </select>
                                {error('status')}
                            </div>
                            <div className="md:col-span-2">
                                <label className="text-sm font-medium">Notes</label>
                                <textarea className="mt-1 min-h-24 w-full rounded-md border bg-background px-3 py-2" value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Optional notes" />
                                {error('notes')}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-2">
                        <Button variant="outline" asChild><Link href={route('wifi-billing.routers.index')}>Cancel</Link></Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="h-4 w-4" />
                            {processing ? 'Saving...' : 'Save Router'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
