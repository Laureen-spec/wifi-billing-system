import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Clipboard, Router } from 'lucide-react';

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

type Props = {
    router: {
        id: number;
        name: string;
        host?: string | null;
        api_port?: number | null;
        connection_type?: string | null;
        status?: string | null;
        last_seen_at?: string | null;
    };
    command: string;
    provisionUrl: string;
    ros: string;
    rosVersions: Record<string, string>;
};

export default function RouterSetupScript({ router: routerRow, command, provisionUrl, ros, rosVersions }: Props) {
    const copy = async (value: string) => {
        await navigator.clipboard?.writeText(value);
    };

    const changeRos = (version: string) => {
        router.get(route('wifi-billing.routers.setup-script', routerRow.id), { ros: version }, { preserveState: false });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Routers', url: route('wifi-billing.routers.index') },
                { label: `${routerRow.name} setup` },
            ]}
            pageTitle="MikroTik Setup Script"
        >
            <Head title="MikroTik Setup Script" />

            <div className="space-y-5">
                <div className="flex flex-col gap-3 rounded-lg border bg-card p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">MikroTik Setup Script</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Copy this command into MikroTik Terminal. It keeps the existing router-agent provisioning flow unchanged.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={route('wifi-billing.routers.index')}>
                            <ArrowLeft className="h-4 w-4" />
                            Back to Routers
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-sm text-muted-foreground">Router</p>
                            <div className="mt-2 flex items-center gap-2 text-lg font-semibold"><Router className="h-4 w-4" /> {routerRow.name}</div>
                            <p className="mt-1 text-xs text-muted-foreground">{routerRow.host || 'Pending link'} · {routerRow.connection_type || 'agent'}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-sm text-muted-foreground">RouterOS Version</p>
                            <select className="mt-2 w-full rounded-md border bg-background px-3 py-2" value={ros} onChange={(event) => changeRos(event.target.value)}>
                                {Object.entries(rosVersions).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                            </select>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-sm text-muted-foreground">Last Seen</p>
                            <p className="mt-2 text-lg font-semibold">{routerRow.last_seen_at || 'No heartbeat yet'}</p>
                            <p className="mt-1 text-xs text-muted-foreground">Status: {routerRow.status || 'pending'}</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between border-b py-4">
                        <CardTitle className="text-base">Terminal Command</CardTitle>
                        <Button type="button" variant="outline" size="sm" onClick={() => copy(command)}>
                            <Clipboard className="h-4 w-4" />
                            Copy Command
                        </Button>
                    </CardHeader>
                    <CardContent className="p-4">
                        <pre className="whitespace-pre-wrap rounded-lg bg-slate-950 p-4 text-sm text-slate-50">{command}</pre>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between border-b py-4">
                        <CardTitle className="text-base">Provision URL</CardTitle>
                        <Button type="button" variant="outline" size="sm" onClick={() => copy(provisionUrl)}>
                            <Clipboard className="h-4 w-4" />
                            Copy URL
                        </Button>
                    </CardHeader>
                    <CardContent className="p-4">
                        <code className="break-all text-sm">{provisionUrl}</code>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
