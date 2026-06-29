import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, MessageSquare, Save, Tags } from 'lucide-react';
import { FormEvent } from 'react';

type Setting = {
    scope: string;
    mode: string;
    provider?: string | null;
    sender_id?: string | null;
    username?: string | null;
    callback_url?: string | null;
    is_active: boolean;
    updated_at?: string | null;
};

type Props = {
    pageTitle: string;
    subtitle: string;
    setting?: Setting | null;
    platformSetting?: Setting | null;
    isPlatform: boolean;
    hasSmsTables: boolean;
    dryRun: boolean;
    routes: {
        messages: string;
        newMessage: string;
        save: string;
        templates: string;
    };
};

type FormData = {
    scope: string;
    mode: string;
    provider: string;
    sender_id: string;
    username: string;
    callback_url: string;
    api_key: string;
    api_secret: string;
    is_active: boolean;
};

export default function SmsSettings({ pageTitle, subtitle, setting, platformSetting, isPlatform, hasSmsTables, dryRun, routes }: Props) {
    const current = setting || platformSetting;
    const { data, setData, post, processing, errors } = useForm<FormData>({
        scope: isPlatform ? (current?.scope || 'platform') : 'isp',
        mode: current?.mode || 'platform',
        provider: current?.provider || 'platform',
        sender_id: current?.sender_id || '',
        username: current?.username || '',
        callback_url: current?.callback_url || '',
        api_key: '',
        api_secret: '',
        is_active: current?.is_active ?? true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(routes.save, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: 'ISP SMS', url: routes.messages }, { label: pageTitle }]}
            pageTitle={pageTitle}
        >
            <Head title={pageTitle} />

            <div className="space-y-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-normal text-foreground">{pageTitle}</h1>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">{subtitle}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={routes.messages}>
                                <ArrowLeft className="h-4 w-4" />
                                Messages
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={routes.templates}>
                                <Tags className="h-4 w-4" />
                                Templates
                            </Link>
                        </Button>
                    </div>
                </div>

                {dryRun && (
                    <div className="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Local safe mode is enabled. Messages move from queued to sent without calling a live SMS gateway.
                    </div>
                )}

                {!hasSmsTables && (
                    <div className="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        SMS settings tables are not migrated yet.
                    </div>
                )}

                <form onSubmit={submit} className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <Card>
                        <CardHeader className="border-b py-4">
                            <CardTitle className="text-base">Gateway</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 p-4 md:grid-cols-2">
                            {isPlatform && (
                                <div className="space-y-2">
                                    <Label htmlFor="sms-scope">Scope</Label>
                                    <select
                                        id="sms-scope"
                                        value={data.scope}
                                        onChange={(event) => setData('scope', event.target.value)}
                                        className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="platform">Platform setting</option>
                                        <option value="isp">ISP setting</option>
                                    </select>
                                    <InputError message={errors.scope} />
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="sms-mode">Mode</Label>
                                <select
                                    id="sms-mode"
                                    value={data.mode}
                                    onChange={(event) => {
                                        const mode = event.target.value;
                                        setData('mode', mode);
                                        if (mode === 'platform') {
                                            setData('provider', 'platform');
                                        }
                                    }}
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="platform">Use platform gateway</option>
                                    <option value="own">Use own gateway</option>
                                </select>
                                <InputError message={errors.mode} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="sms-provider">Provider</Label>
                                <select
                                    id="sms-provider"
                                    value={data.provider}
                                    onChange={(event) => setData('provider', event.target.value)}
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="platform">Platform</option>
                                    <option value="custom_http">Custom HTTP</option>
                                    <option value="africastalking">AfricasTalking</option>
                                    <option value="twilio">Twilio</option>
                                    <option value="other">Other</option>
                                </select>
                                <InputError message={errors.provider} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="sms-sender">Sender ID</Label>
                                <Input
                                    id="sms-sender"
                                    value={data.sender_id}
                                    onChange={(event) => setData('sender_id', event.target.value)}
                                    placeholder="StudyRoom"
                                />
                                <InputError message={errors.sender_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="sms-username">Username</Label>
                                <Input
                                    id="sms-username"
                                    value={data.username}
                                    onChange={(event) => setData('username', event.target.value)}
                                    placeholder="Gateway username"
                                />
                                <InputError message={errors.username} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="sms-url">Custom HTTP URL</Label>
                                <Input
                                    id="sms-url"
                                    value={data.callback_url}
                                    onChange={(event) => setData('callback_url', event.target.value)}
                                    placeholder="https://gateway.example/send"
                                />
                                <InputError message={errors.callback_url} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="sms-key">API key</Label>
                                <Input
                                    id="sms-key"
                                    value={data.api_key}
                                    onChange={(event) => setData('api_key', event.target.value)}
                                    placeholder={current ? 'Leave blank to keep existing key' : 'API key'}
                                />
                                <InputError message={errors.api_key} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="sms-secret">API secret</Label>
                                <Input
                                    id="sms-secret"
                                    type="password"
                                    value={data.api_secret}
                                    onChange={(event) => setData('api_secret', event.target.value)}
                                    placeholder={current ? 'Leave blank to keep existing secret' : 'API secret'}
                                />
                                <InputError message={errors.api_secret} />
                            </div>

                            <Label className="flex items-center gap-3 rounded-md border bg-muted/40 p-3 md:col-span-2">
                                <Checkbox
                                    checked={data.is_active}
                                    onCheckedChange={(checked) => setData('is_active', checked === true)}
                                />
                                <span className="text-sm">Gateway setting is active</span>
                            </Label>
                        </CardContent>
                    </Card>

                    <Card className="self-start">
                        <CardHeader className="border-b py-4">
                            <CardTitle className="text-base">Status</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 p-4">
                            <div className="flex items-center gap-3 rounded-md border bg-muted/40 p-3">
                                <MessageSquare className="h-5 w-5 text-primary" />
                                <div>
                                    <p className="font-medium">{current ? 'Configured' : 'Not configured'}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {current?.updated_at ? `Updated ${current.updated_at}` : 'No saved setting yet.'}
                                    </p>
                                </div>
                            </div>
                            <Button type="submit" disabled={processing || !hasSmsTables} className="w-full">
                                <Save className="h-4 w-4" />
                                Save Settings
                            </Button>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
