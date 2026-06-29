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
import { ArrowLeft, MessageCircleMore, Router as RouterIcon, Search, Send, Settings, Tags, Users, Wand2, X } from 'lucide-react';
import { FormEvent, ReactNode, useMemo, useRef, useState } from 'react';

type Audience = 'specific' | 'segment' | 'mikrotik' | 'everyone';

type Customer = {
    id: number;
    name: string;
    phone?: string | null;
    normalized_phone?: string | null;
    username?: string | null;
    email?: string | null;
    connection_status?: string | null;
    billing_status?: string | null;
    provisioning_status?: string | null;
    next_due_date?: string | null;
    has_valid_phone: boolean;
    package?: { id: number; name: string } | null;
    router?: { id: number; name: string } | null;
};

type Segment = { key: string; label: string; description: string; count: number };
type Router = { id: number; name: string; host?: string | null; status?: string | null; customer_count: number };
type Template = { id: number; name: string; body: string };

type Props = {
    pageTitle: string;
    subtitle: string;
    customers: Customer[];
    segments: Segment[];
    routers: Router[];
    templates: Template[];
    routes: { index: string; send: string; settings: string; templates: string };
    isPlatform: boolean;
};

type FormData = {
    audience: Audience;
    customer_ids: number[];
    segment: string;
    router_id: string;
    message: string;
    confirm_everyone: boolean;
};

const audienceTabs: Array<{ key: Audience; label: string }> = [
    { key: 'specific', label: 'Specific users' },
    { key: 'segment', label: 'Segments' },
    { key: 'mikrotik', label: 'MikroTik' },
    { key: 'everyone', label: 'Everyone' },
];

const variableTokens = ['@first_name', '@last_name', '@email', '@phone', '@package_name', '@company_name', '@expiry_date', '@due_date'];

export default function NewSmsMessage({ pageTitle, subtitle, customers, segments, routers, templates, routes }: Props) {
    const [search, setSearch] = useState('');
    const [selectedTemplate, setSelectedTemplate] = useState('');
    const textareaRef = useRef<HTMLTextAreaElement | null>(null);

    const { data, setData, post, processing, errors } = useForm<FormData>({
        audience: 'specific',
        customer_ids: [],
        segment: segments[0]?.key || '',
        router_id: routers[0]?.id ? String(routers[0].id) : '',
        message: 'Hi @first_name, your package expires on @expiry_date.',
        confirm_everyone: false,
    });

    const validCustomers = useMemo(() => customers.filter((c) => c.has_valid_phone), [customers]);
    const selectedCustomers = useMemo(() => customers.filter((c) => data.customer_ids.includes(c.id)), [customers, data.customer_ids]);

    const filteredCustomers = useMemo(() => {
        const term = search.trim().toLowerCase();
        const source = customers.filter((c) => c.has_valid_phone);
        if (!term) return source.slice(0, 12);
        return source.filter((customer) => [customer.name, customer.phone, customer.normalized_phone, customer.username, customer.email, customer.package?.name, customer.router?.name].filter(Boolean).join(' ').toLowerCase().includes(term)).slice(0, 18);
    }, [customers, search]);

    const audienceCount = useMemo(() => {
        if (data.audience === 'specific') return selectedCustomers.length;
        if (data.audience === 'segment') return segments.find((s) => s.key === data.segment)?.count || 0;
        if (data.audience === 'mikrotik') return routers.find((r) => String(r.id) === data.router_id)?.customer_count || 0;
        return validCustomers.length;
    }, [data.audience, data.customer_ids, data.segment, data.router_id, selectedCustomers.length, segments, routers, validCustomers.length]);

    const previewCustomer = selectedCustomers[0] || validCustomers[0] || ({
        name: 'Dickson',
        phone: '0712345678',
        email: 'dickson@example.com',
        next_due_date: '2026-06-30',
        package: { id: 0, name: '1-Day Unlimited' },
    } as Customer);

    const previewText = useMemo(() => personalize(data.message, previewCustomer), [data.message, previewCustomer]);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(routes.send, { preserveScroll: true });
    };

    const toggleCustomer = (customer: Customer) => {
        const exists = data.customer_ids.includes(customer.id);
        setData('customer_ids', exists ? data.customer_ids.filter((id) => id !== customer.id) : [...data.customer_ids, customer.id]);
    };

    const insertVariable = (token: string) => {
        const next = `${data.message}${data.message && !data.message.endsWith(' ') ? ' ' : ''}${token}`;
        setData('message', next);
        requestAnimationFrame(() => textareaRef.current?.focus());
    };

    const pickTemplate = (templateId: string) => {
        setSelectedTemplate(templateId);
        const template = templates.find((item) => String(item.id) === templateId);
        if (template) setData('message', template.body);
    };

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: 'ISP SMS', url: routes.index }, { label: pageTitle }]} pageTitle={pageTitle}>
            <Head title={pageTitle} />

            <form onSubmit={submit} className="space-y-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-muted-foreground">Communication — compose SMS</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">SMS composer</h1>
                        <p className="mt-2 max-w-3xl text-sm text-muted-foreground">{subtitle}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={routes.index}><ArrowLeft className="h-4 w-4" />SMS log</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={routes.settings}><Settings className="h-4 w-4" />Settings</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={routes.templates}><Tags className="h-4 w-4" />Templates</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <StatCard icon={<Users className="h-5 w-5" />} label="Reachable subscribers" value={String(validCustomers.length)} hint="customers with valid phone" />
                    <StatCard icon={<MessageCircleMore className="h-5 w-5" />} label="Current audience" value={String(audienceCount)} hint="messages will be personalized" />
                    <StatCard icon={<Wand2 className="h-5 w-5" />} label="Message length" value={String(data.message.length)} hint={`${Math.ceil(Math.max(data.message.length, 1) / 160)} segment`} />
                </div>

                <div className="grid gap-5 xl:grid-cols-[minmax(0,1.25fr)_420px]">
                    <div className="space-y-5">
                        <Card>
                            <CardHeader className="border-b py-4"><CardTitle className="text-base">Recipients</CardTitle></CardHeader>
                            <CardContent className="space-y-4 p-5">
                                <div className="inline-flex flex-wrap gap-2 rounded-2xl border bg-muted/20 p-1">
                                    {audienceTabs.map((tab) => (
                                        <button key={tab.key} type="button" onClick={() => setData('audience', tab.key)} className={`rounded-xl px-4 py-2 text-sm font-medium transition ${data.audience === tab.key ? 'bg-foreground text-background shadow-sm' : 'text-muted-foreground hover:bg-background'}`}>{tab.label}</button>
                                    ))}
                                </div>

                                {data.audience === 'specific' && (
                                    <div className="space-y-4">
                                        <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_260px]">
                                            <div className="relative">
                                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                                <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search by name, phone, package, router..." className="pl-9" />
                                            </div>
                                            <select
                                                value=""
                                                onChange={(event) => {
                                                    const customer = customers.find((item) => String(item.id) === event.target.value);
                                                    if (customer) toggleCustomer(customer);
                                                }}
                                                className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                            >
                                                <option value="">Select subscriber from dropdown</option>
                                                {filteredCustomers.map((customer) => (
                                                    <option key={customer.id} value={customer.id}>
                                                        {customer.name} · {customer.phone || customer.normalized_phone || 'No phone'} · {customer.package?.name || 'No package'}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        {selectedCustomers.length > 0 ? (
                                            <div className="rounded-2xl border bg-muted/20 p-3">
                                                <div className="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Selected recipients</div>
                                                <div className="flex flex-wrap gap-2">
                                                    {selectedCustomers.map((customer) => (
                                                        <button type="button" key={customer.id} onClick={() => toggleCustomer(customer)} className="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1.5 text-sm text-primary">
                                                            {customer.name} ({customer.phone}) <X className="h-3.5 w-3.5" />
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="rounded-2xl border border-dashed p-5 text-sm text-muted-foreground">
                                                Search, then pick a subscriber from the dropdown list. This keeps SMS selection compact even when you have many hotspot customers.
                                            </div>
                                        )}
                                    </div>
                                )}

                                {data.audience === 'segment' && (
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {segments.map((segment) => (
                                            <button type="button" key={segment.key} onClick={() => setData('segment', segment.key)} className={`rounded-2xl border p-4 text-left ${data.segment === segment.key ? 'border-primary bg-primary/5' : 'hover:bg-muted/30'}`}>
                                                <div className="flex items-center justify-between">
                                                    <div className="font-medium">{segment.label}</div>
                                                    <Badge variant="outline">{segment.count}</Badge>
                                                </div>
                                                <div className="mt-2 text-sm text-muted-foreground">{segment.description}</div>
                                            </button>
                                        ))}
                                    </div>
                                )}

                                {data.audience === 'mikrotik' && (
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {routers.map((router) => (
                                            <button type="button" key={router.id} onClick={() => setData('router_id', String(router.id))} className={`rounded-2xl border p-4 text-left ${data.router_id === String(router.id) ? 'border-primary bg-primary/5' : 'hover:bg-muted/30'}`}>
                                                <div className="flex items-center justify-between gap-3">
                                                    <div className="flex items-center gap-2 font-medium"><RouterIcon className="h-4 w-4" />{router.name}</div>
                                                    <Badge variant="outline">{router.customer_count}</Badge>
                                                </div>
                                                <div className="mt-2 text-sm text-muted-foreground">{router.host || 'No host'} · {router.status || 'Unknown status'}</div>
                                            </button>
                                        ))}
                                    </div>
                                )}

                                {data.audience === 'everyone' && (
                                    <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                        <div className="font-medium text-amber-900">Send to everyone</div>
                                        <p className="mt-1 text-sm text-amber-800">This will target every customer with a valid phone number.</p>
                                        <div className="mt-3 flex items-center gap-3">
                                            <Checkbox checked={data.confirm_everyone} onCheckedChange={(value) => setData('confirm_everyone', Boolean(value))} />
                                            <span className="text-sm">I confirm that I want to send this to everyone.</span>
                                        </div>
                                        <InputError message={errors.confirm_everyone} />
                                    </div>
                                )}
                                <InputError message={errors.audience} />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b py-4"><CardTitle className="text-base">Message</CardTitle></CardHeader>
                            <CardContent className="space-y-4 p-5">
                                <div className="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
                                    <div className="space-y-2">
                                        <Label htmlFor="sms-message">Compose</Label>
                                        <Textarea id="sms-message" ref={textareaRef} value={data.message} onChange={(e) => setData('message', e.target.value)} className="min-h-[180px]" placeholder="Type your SMS here..." />
                                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                                            <span>{data.message.length} / 480</span>
                                            <span>{Math.ceil(Math.max(data.message.length, 1) / 160)} segment</span>
                                        </div>
                                        <InputError message={errors.message} />
                                    </div>
                                    <div className="space-y-3">
                                        <Label>Quick template</Label>
                                        <select value={selectedTemplate} onChange={(e) => pickTemplate(e.target.value)} className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            <option value="">Choose template</option>
                                            {templates.map((template) => <option key={template.id} value={String(template.id)}>{template.name}</option>)}
                                        </select>
                                        <div className="rounded-2xl border p-3">
                                            <div className="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Variable tools</div>
                                            <div className="flex flex-wrap gap-2">
                                                {variableTokens.map((token) => (
                                                    <button key={token} type="button" onClick={() => insertVariable(token)} className="rounded-full border bg-background px-2.5 py-1 text-xs font-medium hover:bg-muted/40">{token}</button>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-5">
                        <Card>
                            <CardHeader className="border-b py-4"><CardTitle className="text-base">Live preview</CardTitle></CardHeader>
                            <CardContent className="space-y-4 p-5">
                                <div className="rounded-[24px] border bg-muted/20 p-4">
                                    <div className="mb-3 flex items-center justify-between">
                                        <div>
                                            <div className="font-medium">{previewCustomer.name || 'Dickson'}</div>
                                            <div className="text-xs text-muted-foreground">{previewCustomer.phone || '0712345678'}</div>
                                        </div>
                                        <Badge variant="outline">Preview</Badge>
                                    </div>
                                    <div className="rounded-2xl bg-background p-4 text-sm leading-7 text-foreground shadow-sm">
                                        {previewText || 'Hi Dickson, your package expires on 2026-06-30.'}
                                    </div>
                                </div>
                                <div className="rounded-2xl border p-4 text-sm text-muted-foreground">
                                    <div className="font-medium text-foreground">How it works</div>
                                    <p className="mt-2">Variables are replaced per recipient. Example: <span className="font-medium text-foreground">@first_name</span> becomes <span className="font-medium text-foreground">Dickson</span>.</p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b py-4"><CardTitle className="text-base">Send summary</CardTitle></CardHeader>
                            <CardContent className="space-y-3 p-5 text-sm">
                                <SummaryRow label="Audience" value={String(audienceCount)} />
                                <SummaryRow label="Message length" value={`${data.message.length} characters`} />
                                <SummaryRow label="Template mode" value={selectedTemplate ? 'Template selected' : 'Custom message'} />
                                <SummaryRow label="Preview user" value={previewCustomer.name || 'Dickson'} />
                                <div className="rounded-2xl border bg-muted/20 p-4 text-muted-foreground">
                                    If the admin is using system SMS, the first 5 SMS are free. After that, the SMS balance is used.
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex items-center justify-end gap-3 rounded-2xl border bg-background p-4">
                            <Button type="button" variant="outline" asChild><Link href={routes.index}>Cancel</Link></Button>
                            <Button type="submit" disabled={processing || !audienceCount || !data.message.trim()} size="lg"><Send className="h-4 w-4" />Send message</Button>
                        </div>
                    </div>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

function SummaryRow({ label, value }: { label: string; value: string }) {
    return <div className="flex items-center justify-between rounded-xl border px-3 py-2"><span className="text-muted-foreground">{label}</span><span className="font-medium">{value}</span></div>;
}

function StatCard({ icon, label, value, hint }: { icon: ReactNode; label: string; value: string; hint: string }) {
    return (
        <Card>
            <CardContent className="flex items-center gap-3 p-4">
                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary">{icon}</div>
                <div>
                    <div className="text-sm text-muted-foreground">{label}</div>
                    <div className="text-2xl font-semibold">{value}</div>
                    <div className="text-xs text-muted-foreground">{hint}</div>
                </div>
            </CardContent>
        </Card>
    );
}

function personalize(body: string, customer: Customer) {
    const name = customer.name || 'Dickson';
    const parts = name.trim().split(/\s+/);
    const firstName = parts[0] || 'Dickson';
    const lastName = parts.length > 1 ? parts.slice(1).join(' ') : '';
    const values: Record<string, string> = {
        '@name': name,
        '@first_name': firstName,
        '@last_name': lastName,
        '@email': customer.email || 'dickson@example.com',
        '@phone': customer.phone || '0712345678',
        '@package_name': customer.package?.name || '1-Day Unlimited',
        '@company_name': 'StudyRoom Connect',
        '@expiry_date': customer.next_due_date || '2026-06-30',
        '@due_date': customer.next_due_date || '2026-06-30',
    };

    let text = body || '';
    Object.entries(values).forEach(([token, value]) => {
        text = text.split(token).join(value);
    });
    return text;
}
