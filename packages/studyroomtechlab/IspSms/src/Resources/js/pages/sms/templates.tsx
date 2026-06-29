import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, MessageSquare, Plus, Save, Settings } from 'lucide-react';
import { FormEvent } from 'react';

type Template = {
    id: number;
    name: string;
    key: string;
    body: string;
    enabled: boolean;
    scope: string;
    updated_at?: string | null;
};

type Paginated<T> = {
    data: T[];
    from?: number | null;
    to?: number | null;
    total: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
};

type Props = {
    pageTitle: string;
    subtitle: string;
    templates: Paginated<Template>;
    isPlatform: boolean;
    hasSmsTables: boolean;
    routes: {
        messages: string;
        newMessage: string;
        settings: string;
        store: string;
    };
};

type FormData = {
    scope: string;
    name: string;
    key: string;
    body: string;
    enabled: boolean;
};

const trimBody = (body: string) => body.length > 120 ? `${body.slice(0, 120)}...` : body;

export default function SmsTemplates({ pageTitle, subtitle, templates, isPlatform, hasSmsTables, routes }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        scope: isPlatform ? 'platform' : 'isp',
        name: '',
        key: '',
        body: '',
        enabled: true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(routes.store, {
            preserveScroll: true,
            onSuccess: () => reset('name', 'key', 'body'),
        });
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
                            <Link href={routes.settings}>
                                <Settings className="h-4 w-4" />
                                Settings
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={routes.newMessage}>
                                <Plus className="h-4 w-4" />
                                New Message
                            </Link>
                        </Button>
                    </div>
                </div>

                {!hasSmsTables && (
                    <div className="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        SMS template tables are not migrated yet.
                    </div>
                )}

                <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
                    <Card>
                        <CardHeader className="border-b py-4">
                            <CardTitle className="text-base">Templates</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {templates.data.length === 0 ? (
                                <div className="p-10 text-center">
                                    <MessageSquare className="mx-auto h-12 w-12 text-muted-foreground" />
                                    <h2 className="mt-4 text-lg font-semibold">No templates yet</h2>
                                    <p className="mt-1 text-sm text-muted-foreground">Create reusable messages for common customer updates.</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-border text-sm">
                                        <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                            <tr>
                                                <th className="px-4 py-3">Name</th>
                                                <th className="px-4 py-3">Key</th>
                                                <th className="px-4 py-3">Body</th>
                                                <th className="px-4 py-3">Status</th>
                                                <th className="px-4 py-3">Updated</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border">
                                            {templates.data.map((template) => (
                                                <tr key={template.id} className="hover:bg-muted/40">
                                                    <td className="px-4 py-3 font-medium">{template.name}</td>
                                                    <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{template.key}</td>
                                                    <td className="max-w-xl px-4 py-3 text-muted-foreground">{trimBody(template.body)}</td>
                                                    <td className="px-4 py-3">
                                                        <Badge variant={template.enabled ? 'default' : 'secondary'}>
                                                            {template.enabled ? 'Enabled' : 'Disabled'}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-3 text-muted-foreground">{template.updated_at || '-'}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                            <div className="flex flex-col gap-3 border-t p-4 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                                <div>Showing {templates.from || 0} to {templates.to || 0} of {templates.total}</div>
                                <div className="flex gap-2">
                                    <Button variant="outline" disabled={!templates.prev_page_url} asChild={Boolean(templates.prev_page_url)}>
                                        {templates.prev_page_url ? <Link href={templates.prev_page_url}>Previous</Link> : <span>Previous</span>}
                                    </Button>
                                    <Button variant="outline" disabled={!templates.next_page_url} asChild={Boolean(templates.next_page_url)}>
                                        {templates.next_page_url ? <Link href={templates.next_page_url}>Next</Link> : <span>Next</span>}
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="self-start">
                        <CardHeader className="border-b py-4">
                            <CardTitle className="text-base">New Template</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4">
                            <form onSubmit={submit} className="space-y-4">
                                {isPlatform && (
                                    <div className="space-y-2">
                                        <Label htmlFor="template-scope">Scope</Label>
                                        <select
                                            id="template-scope"
                                            value={data.scope}
                                            onChange={(event) => setData('scope', event.target.value)}
                                            className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="platform">Platform</option>
                                            <option value="isp">ISP</option>
                                        </select>
                                        <InputError message={errors.scope} />
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <Label htmlFor="template-name">Name</Label>
                                    <Input
                                        id="template-name"
                                        value={data.name}
                                        onChange={(event) => setData('name', event.target.value)}
                                        placeholder="Payment reminder"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="template-key">Key</Label>
                                    <Input
                                        id="template-key"
                                        value={data.key}
                                        onChange={(event) => setData('key', event.target.value)}
                                        placeholder="payment_reminder"
                                    />
                                    <InputError message={errors.key} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="template-body">Body</Label>
                                    <Textarea
                                        id="template-body"
                                        value={data.body}
                                        onChange={(event) => setData('body', event.target.value)}
                                        placeholder="Hi {{first_name}}, your package expires on {{expiry_date}}."
                                        rows={6}
                                    />
                                    <InputError message={errors.body} />
                                </div>

                                <Label className="flex items-center gap-3 rounded-md border bg-muted/40 p-3">
                                    <Checkbox
                                        checked={data.enabled}
                                        onCheckedChange={(checked) => setData('enabled', checked === true)}
                                    />
                                    <span className="text-sm">Template enabled</span>
                                </Label>

                                <Button type="submit" disabled={processing || !hasSmsTables} className="w-full">
                                    <Save className="h-4 w-4" />
                                    Save Template
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
