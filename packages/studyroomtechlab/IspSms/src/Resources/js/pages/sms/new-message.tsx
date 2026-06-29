import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    MessageSquare,
    Router as RouterIcon,
    Search,
    Send,
    Settings,
    Tags,
    Users,
    X,
} from 'lucide-react';
import { FormEvent, useMemo, useRef, useState } from 'react';

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

type Segment = {
    key: string;
    label: string;
    description: string;
    count: number;
};

type Router = {
    id: number;
    name: string;
    host?: string | null;
    status?: string | null;
    customer_count: number;
};

type Template = {
    id: number;
    name: string;
    body: string;
};

type Props = {
    pageTitle: string;
    subtitle: string;
    customers: Customer[];
    segments: Segment[];
    routers: Router[];
    templates: Template[];
    routes: {
        index: string;
        send: string;
        settings: string;
        templates: string;
    };
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

const audienceTabs: Array<{ key: Audience; label: string; icon: typeof Users; description: string }> = [
    { key: 'specific', label: 'Specific users', icon: Users, description: 'Search and select individual customers.' },
    { key: 'segment', label: 'Segments', icon: Tags, description: 'Target customers by billing or service status.' },
    { key: 'mikrotik', label: 'MikroTik', icon: RouterIcon, description: 'Send to customers assigned to one router.' },
    { key: 'everyone', label: 'Everyone', icon: MessageSquare, description: 'All customers with valid phone numbers.' },
];

const statusClass = (value?: string | null) => {
    const status = String(value || '').toLowerCase();

    if (['active', 'paid', 'provisioned', 'sent'].includes(status)) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    }

    if (['overdue', 'failed', 'suspended', 'unpaid'].includes(status)) {
        return 'bg-rose-50 text-rose-700 border-rose-200';
    }

    return 'bg-slate-50 text-slate-700 border-slate-200';
};

export default function NewSmsMessage({
    pageTitle,
    subtitle,
    customers,
    segments,
    routers,
    templates,
    routes,
}: Props) {
    const [search, setSearch] = useState('');
    const [selectedTemplateId, setSelectedTemplateId] = useState('none');
    const searchRef = useRef<HTMLInputElement>(null);
    const defaultSegment = segments[0]?.key || '';
    const defaultRouter = routers[0]?.id ? String(routers[0].id) : '';

    const { data, setData, post, processing, errors } = useForm<FormData>({
        audience: 'specific',
        customer_ids: [],
        segment: defaultSegment,
        router_id: defaultRouter,
        message: '',
        confirm_everyone: false,
    });

    const validCustomers = useMemo(() => customers.filter((customer) => customer.has_valid_phone), [customers]);
    const selectedCustomers = useMemo(
        () => customers.filter((customer) => data.customer_ids.includes(customer.id)),
        [customers, data.customer_ids],
    );
    const selectedValidCustomers = selectedCustomers.filter((customer) => customer.has_valid_phone);
    const selectedSegment = segments.find((segment) => segment.key === data.segment);
    const selectedRouter = routers.find((router) => String(router.id) === data.router_id);

    const filteredCustomers = useMemo(() => {
        const term = search.trim().toLowerCase();

        if (!term) {
            return customers.slice(0, 12);
        }

        return customers
            .filter((customer) => [
                customer.name,
                customer.phone,
                customer.normalized_phone,
                customer.username,
                customer.email,
                customer.package?.name,
                customer.router?.name,
            ].filter(Boolean).join(' ').toLowerCase().includes(term))
            .slice(0, 20);
    }, [customers, search]);

    const audienceCount = (() => {
        if (data.audience === 'specific') {
            return selectedValidCustomers.length;
        }

        if (data.audience === 'segment') {
            return selectedSegment?.count || 0;
        }

        if (data.audience === 'mikrotik') {
            return selectedRouter?.customer_count || 0;
        }

        return validCustomers.length;
    })();

    const messageLength = data.message.length;
    const messageTooLong = messageLength > 480;
    const canSubmit = data.message.trim().length > 0 && !messageTooLong && audienceCount > 0 && !processing;

    const setAudience = (audience: Audience) => {
        setData('audience', audience);

        if (audience === 'specific') {
            window.setTimeout(() => searchRef.current?.focus(), 0);
        }
    };

    const toggleCustomer = (customer: Customer) => {
        if (!customer.has_valid_phone) {
            return;
        }

        const selected = data.customer_ids.includes(customer.id);
        const next = selected
            ? data.customer_ids.filter((id) => id !== customer.id)
            : [...data.customer_ids, customer.id];

        setData('customer_ids', next);
    };

    const insertTemplate = (templateId: string) => {
        setSelectedTemplateId(templateId);
        const template = templates.find((item) => String(item.id) === templateId);

        if (template) {
            setData('message', template.body);
        }
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!canSubmit) {
            return;
        }

        post(routes.send, {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'ISP SMS', url: routes.index },
                { label: pageTitle },
            ]}
            pageTitle={pageTitle}
        >
            <Head title={pageTitle} />

            <form onSubmit={submit} className="space-y-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-normal text-foreground">{pageTitle}</h1>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">{subtitle}</p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={routes.index}>
                                <ArrowLeft className="h-4 w-4" />
                                Outbox
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={routes.settings}>
                                <Settings className="h-4 w-4" />
                                Gateway
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center gap-3 p-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-md bg-primary/10 text-primary">
                                <Users className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">SMS-capable customers</p>
                                <p className="text-2xl font-semibold">{validCustomers.length}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 p-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-md bg-primary/10 text-primary">
                                <MessageSquare className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Current audience</p>
                                <p className="text-2xl font-semibold">{audienceCount}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 p-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-md bg-primary/10 text-primary">
                                <CheckCircle2 className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Existing outbox</p>
                                <p className="text-2xl font-semibold">isp_sms_messages</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
                    <Card>
                        <CardHeader className="border-b py-4">
                            <CardTitle className="text-base">Audience</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5 p-4">
                            <div className="grid gap-2 md:grid-cols-4">
                                {audienceTabs.map((tab) => {
                                    const Icon = tab.icon;
                                    const active = data.audience === tab.key;

                                    return (
                                        <button
                                            key={tab.key}
                                            type="button"
                                            onClick={() => setAudience(tab.key)}
                                            className={cn(
                                                'rounded-md border p-3 text-left transition-colors',
                                                active ? 'border-primary bg-primary/5 text-primary' : 'border-border hover:bg-muted',
                                            )}
                                        >
                                            <Icon className="mb-2 h-4 w-4" />
                                            <span className="block text-sm font-medium">{tab.label}</span>
                                            <span className="mt-1 block text-xs text-muted-foreground">{tab.description}</span>
                                        </button>
                                    );
                                })}
                            </div>
                            <InputError message={errors.audience} />

                            {data.audience === 'specific' && (
                                <div className="space-y-4">
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            ref={searchRef}
                                            value={search}
                                            onChange={(event) => setSearch(event.target.value)}
                                            placeholder="Search customers by name, phone, username, package, or router"
                                            className="pl-9"
                                        />
                                    </div>

                                    {selectedCustomers.length > 0 && (
                                        <div className="flex flex-wrap gap-2">
                                            {selectedCustomers.map((customer) => (
                                                <button
                                                    key={customer.id}
                                                    type="button"
                                                    onClick={() => toggleCustomer(customer)}
                                                    className="inline-flex items-center gap-2 rounded-md border bg-muted px-2.5 py-1.5 text-sm"
                                                >
                                                    {customer.name}
                                                    <X className="h-3.5 w-3.5" />
                                                </button>
                                            ))}
                                        </div>
                                    )}

                                    <div className="max-h-[420px] overflow-auto rounded-md border">
                                        {filteredCustomers.length === 0 ? (
                                            <div className="p-6 text-center text-sm text-muted-foreground">No matching customers found.</div>
                                        ) : (
                                            filteredCustomers.map((customer) => {
                                                const selected = data.customer_ids.includes(customer.id);

                                                return (
                                                    <button
                                                        key={customer.id}
                                                        type="button"
                                                        disabled={!customer.has_valid_phone}
                                                        onClick={() => toggleCustomer(customer)}
                                                        className={cn(
                                                            'flex w-full items-start justify-between gap-3 border-b p-3 text-left last:border-b-0',
                                                            selected ? 'bg-primary/5' : 'hover:bg-muted/70',
                                                            !customer.has_valid_phone && 'cursor-not-allowed opacity-60',
                                                        )}
                                                    >
                                                        <div className="min-w-0">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <span className="font-medium">{customer.name}</span>
                                                                {customer.connection_status && (
                                                                    <Badge variant="outline" className={statusClass(customer.connection_status)}>
                                                                        {customer.connection_status}
                                                                    </Badge>
                                                                )}
                                                                {customer.billing_status && (
                                                                    <Badge variant="outline" className={statusClass(customer.billing_status)}>
                                                                        {customer.billing_status}
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                            <p className="mt-1 text-sm text-muted-foreground">
                                                                {customer.phone || 'No phone'}{customer.package?.name ? ` - ${customer.package.name}` : ''}{customer.router?.name ? ` - ${customer.router.name}` : ''}
                                                            </p>
                                                            {!customer.has_valid_phone && (
                                                                <p className="mt-1 text-xs text-destructive">This customer has no valid SMS phone number.</p>
                                                            )}
                                                        </div>
                                                        <Checkbox checked={selected} disabled={!customer.has_valid_phone} aria-label={`Select ${customer.name}`} />
                                                    </button>
                                                );
                                            })
                                        )}
                                    </div>
                                    <InputError message={errors.customer_ids} />
                                </div>
                            )}

                            {data.audience === 'segment' && (
                                <div className="grid gap-3 md:grid-cols-2">
                                    {segments.map((segment) => {
                                        const active = data.segment === segment.key;

                                        return (
                                            <button
                                                key={segment.key}
                                                type="button"
                                                onClick={() => setData('segment', segment.key)}
                                                className={cn(
                                                    'rounded-md border p-4 text-left transition-colors',
                                                    active ? 'border-primary bg-primary/5' : 'border-border hover:bg-muted',
                                                )}
                                            >
                                                <div className="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p className="font-medium">{segment.label}</p>
                                                        <p className="mt-1 text-sm text-muted-foreground">{segment.description}</p>
                                                    </div>
                                                    <Badge variant="secondary">{segment.count}</Badge>
                                                </div>
                                            </button>
                                        );
                                    })}
                                    <InputError message={errors.segment} />
                                </div>
                            )}

                            {data.audience === 'mikrotik' && (
                                <div className="space-y-3">
                                    {routers.length === 0 ? (
                                        <div className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                                            No MikroTik routers were found for this ISP.
                                        </div>
                                    ) : (
                                        <div className="grid gap-3 md:grid-cols-2">
                                            {routers.map((router) => {
                                                const active = data.router_id === String(router.id);

                                                return (
                                                    <button
                                                        key={router.id}
                                                        type="button"
                                                        onClick={() => setData('router_id', String(router.id))}
                                                        className={cn(
                                                            'rounded-md border p-4 text-left transition-colors',
                                                            active ? 'border-primary bg-primary/5' : 'border-border hover:bg-muted',
                                                        )}
                                                    >
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div>
                                                                <p className="font-medium">{router.name}</p>
                                                                <p className="mt-1 text-sm text-muted-foreground">{router.host || 'No host recorded'}</p>
                                                            </div>
                                                            <Badge variant="outline" className={statusClass(router.status)}>
                                                                {router.status || 'unknown'}
                                                            </Badge>
                                                        </div>
                                                        <p className="mt-3 text-sm text-muted-foreground">
                                                            {router.customer_count} customer phone(s) available.
                                                        </p>
                                                        {router.customer_count === 0 && (
                                                            <p className="mt-1 text-xs text-destructive">
                                                                No recipient phone source is available for this router yet.
                                                            </p>
                                                        )}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    )}
                                    <InputError message={errors.router_id} />
                                </div>
                            )}

                            {data.audience === 'everyone' && (
                                <div className="space-y-4 rounded-md border p-4">
                                    <div>
                                        <p className="font-medium">All customers with valid phone numbers</p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            This will create one SMS outbox record per valid customer phone number.
                                        </p>
                                    </div>
                                    <Label className="flex items-start gap-3 rounded-md border bg-muted/40 p-3">
                                        <Checkbox
                                            checked={data.confirm_everyone}
                                            onCheckedChange={(checked) => setData('confirm_everyone', checked === true)}
                                        />
                                        <span className="text-sm leading-5">
                                            I understand this sends to {validCustomers.length} customer phone(s).
                                        </span>
                                    </Label>
                                    <InputError message={errors.confirm_everyone} />
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="xl:sticky xl:top-5 xl:self-start">
                        <CardHeader className="border-b py-4">
                            <CardTitle className="text-base">Message</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 p-4">
                            <div className="space-y-2">
                                <Label>Template</Label>
                                <Select value={selectedTemplateId} onValueChange={insertTemplate}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Choose template" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">No template</SelectItem>
                                        {templates.map((template) => (
                                            <SelectItem key={template.id} value={String(template.id)}>
                                                {template.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between gap-3">
                                    <Label htmlFor="sms-message">SMS body</Label>
                                    <span className={cn('text-xs text-muted-foreground', messageTooLong && 'text-destructive')}>
                                        {messageLength}/480
                                    </span>
                                </div>
                                <Textarea
                                    id="sms-message"
                                    value={data.message}
                                    onChange={(event) => setData('message', event.target.value)}
                                    placeholder="Hi {{first_name}}, your {{package_name}} package expires on {{expiry_date}}."
                                    rows={8}
                                />
                                <InputError message={errors.message} />
                                <p className="text-xs leading-5 text-muted-foreground">
                                    Variables: {'{{name}}'}, {'{{first_name}}'}, {'{{phone}}'}, {'{{username}}'}, {'{{package_name}}'}, {'{{expiry_date}}'}, {'{{company_name}}'}.
                                </p>
                            </div>

                            <div className="rounded-md border bg-muted/40 p-3">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-sm text-muted-foreground">Recipients</span>
                                    <Badge variant={audienceCount > 0 ? 'default' : 'destructive'}>{audienceCount}</Badge>
                                </div>
                                <p className="mt-2 text-xs leading-5 text-muted-foreground">
                                    SMS records are saved to the existing outbox and sent by the configured ISP SMS gateway service.
                                </p>
                            </div>

                            <Button type="submit" disabled={!canSubmit} className="w-full">
                                <Send className="h-4 w-4" />
                                Send SMS
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
