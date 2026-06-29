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
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    CircleDot,
    MessageSquareText,
    RadioTower,
    Search,
    Send,
    Settings,
    Tags,
    UserCheck,
    Users,
    WifiOff,
    X,
} from 'lucide-react';
import { FormEvent, ReactNode, useMemo, useState } from 'react';

type Audience = 'specific' | 'everyone' | 'online' | 'offline';

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

const variables = [
    '@first_name',
    '@last_name',
    '@email',
    '@phone',
    '@package_name',
    '@company_name',
    '@expiry_date',
    '@due_date',
];

const isOnlineCustomer = (customer: Customer) => {
    const status = String(customer.connection_status || '').toLowerCase();
    return ['online', 'active', 'connected'].includes(status);
};

const statusLabel = (customer: Customer) => {
    if (isOnlineCustomer(customer)) {
        return 'Online';
    }

    const value = customer.connection_status || 'Offline';
    return value.charAt(0).toUpperCase() + value.slice(1);
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
    const [customerToAdd, setCustomerToAdd] = useState('');
    const [selectedTemplate, setSelectedTemplate] = useState('');

    const { data, setData, post, processing, errors } = useForm<FormData>({
        audience: 'specific',
        customer_ids: [],
        segment: segments[0]?.key || '',
        router_id: routers[0]?.id ? String(routers[0].id) : '',
        message: '',
        confirm_everyone: false,
    });

    const validCustomers = useMemo(() => customers.filter((customer) => customer.has_valid_phone), [customers]);
    const onlineCustomers = useMemo(() => validCustomers.filter(isOnlineCustomer), [validCustomers]);
    const offlineCustomers = useMemo(() => validCustomers.filter((customer) => !isOnlineCustomer(customer)), [validCustomers]);
    const selectedCustomers = useMemo(
        () => customers.filter((customer) => data.customer_ids.includes(customer.id)),
        [customers, data.customer_ids],
    );

    const selectableCustomers = useMemo(() => {
        const term = search.trim().toLowerCase();
        return validCustomers
            .filter((customer) => {
                if (!term) return true;
                return [
                    customer.name,
                    customer.phone,
                    customer.normalized_phone,
                    customer.username,
                    customer.email,
                    customer.package?.name,
                    customer.router?.name,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase()
                    .includes(term);
            })
            .slice(0, 120);
    }, [validCustomers, search]);

    const audienceOptions: Array<{
        key: Audience;
        title: string;
        description: string;
        count: number;
        icon: ReactNode;
    }> = [
        {
            key: 'specific',
            title: 'Specific user',
            description: 'Pick one or more customers from a dropdown list.',
            count: selectedCustomers.length,
            icon: <UserCheck className="h-5 w-5" />,
        },
        {
            key: 'everyone',
            title: 'All users',
            description: 'Send to every customer with a valid phone number.',
            count: validCustomers.length,
            icon: <Users className="h-5 w-5" />,
        },
        {
            key: 'online',
            title: 'Online users',
            description: 'Target currently active or connected subscribers.',
            count: onlineCustomers.length,
            icon: <RadioTower className="h-5 w-5" />,
        },
        {
            key: 'offline',
            title: 'Offline users',
            description: 'Reach customers who are inactive, suspended, or disconnected.',
            count: offlineCustomers.length,
            icon: <WifiOff className="h-5 w-5" />,
        },
    ];

    const audienceCount = audienceOptions.find((option) => option.key === data.audience)?.count || 0;
    const segmentsCount = Math.ceil(Math.max(data.message.length, 1) / 160);
    const canSubmit = data.message.trim().length > 0 && data.message.length <= 480 && audienceCount > 0 && !processing;
    const smsConfigurationError = typeof errors.message === 'string'
        && errors.message.toLowerCase().includes('configure now');

    const chooseAudience = (audience: Audience) => {
        setData('audience', audience);
        if (audience !== 'everyone') {
            setData('confirm_everyone', false);
        }
    };

    const addSelectedCustomer = () => {
        const customerId = Number(customerToAdd);
        if (!customerId || data.customer_ids.includes(customerId)) {
            return;
        }

        setData('customer_ids', [...data.customer_ids, customerId]);
        setCustomerToAdd('');
    };

    const removeCustomer = (customerId: number) => {
        setData('customer_ids', data.customer_ids.filter((id) => id !== customerId));
    };

    const insertVariable = (variable: string) => {
        const spacer = data.message.trim() && !data.message.endsWith(' ') ? ' ' : '';
        setData('message', `${data.message}${spacer}${variable}`);
    };

    const pickTemplate = (templateId: string) => {
        setSelectedTemplate(templateId);
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

        post(routes.send, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: 'ISP SMS', url: routes.index }, { label: pageTitle }]}
            pageTitle={pageTitle}
        >
            <Head title={pageTitle} />

            <form onSubmit={submit} className="space-y-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-muted-foreground">Communication — compose SMS</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-foreground">SMS composer</h1>
                        <p className="mt-2 max-w-3xl text-sm text-muted-foreground">{subtitle}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={routes.index}>
                                <ArrowLeft className="h-4 w-4" />
                                Email / SMS Log
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={routes.settings}>
                                <Settings className="h-4 w-4" />
                                SMS Settings
                            </Link>
                        </Button>
                    </div>
                </div>

                {smsConfigurationError && (
                    <div className="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-start gap-3">
                            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                            <div>
                                <p className="font-semibold">SMS gateway needs configuration</p>
                                <p className="mt-1 text-amber-800">{errors.message}</p>
                            </div>
                        </div>
                        <Button asChild variant="outline" className="border-amber-300 bg-white text-amber-900 hover:bg-amber-100">
                            <Link href={routes.settings}>Configure now</Link>
                        </Button>
                    </div>
                )}

                <div className="grid gap-4 md:grid-cols-3">
                    <MetricCard label="Reachable users" value={validCustomers.length} helper="valid phone numbers" icon={<Users className="h-5 w-5" />} />
                    <MetricCard label="Selected audience" value={audienceCount} helper="recipients for this send" icon={<MessageSquareText className="h-5 w-5" />} />
                    <MetricCard label="SMS segments" value={segmentsCount} helper={`${data.message.length} / 480 characters`} icon={<CheckCircle2 className="h-5 w-5" />} />
                </div>

                <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
                    <div className="space-y-5">
                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Audience rule</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-5 p-5">
                                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                    {audienceOptions.map((option) => {
                                        const active = data.audience === option.key;
                                        return (
                                            <button
                                                key={option.key}
                                                type="button"
                                                onClick={() => chooseAudience(option.key)}
                                                className={`rounded-2xl border p-4 text-left transition ${
                                                    active
                                                        ? 'border-primary bg-primary/5 shadow-sm ring-1 ring-primary/20'
                                                        : 'border-border bg-background hover:bg-muted/40'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between gap-3">
                                                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                                        {option.icon}
                                                    </div>
                                                    {active ? <Badge>Selected</Badge> : <Badge variant="outline">{option.count}</Badge>}
                                                </div>
                                                <div className="mt-4 font-semibold text-foreground">{option.title}</div>
                                                <p className="mt-1 text-xs leading-5 text-muted-foreground">{option.description}</p>
                                            </button>
                                        );
                                    })}
                                </div>

                                {data.audience === 'specific' && (
                                    <div className="rounded-2xl border bg-muted/20 p-4">
                                        <div className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_auto]">
                                            <div className="relative">
                                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                                <Input
                                                    value={search}
                                                    onChange={(event) => setSearch(event.target.value)}
                                                    placeholder="Filter customer dropdown..."
                                                    className="pl-9"
                                                />
                                            </div>
                                            <select
                                                value={customerToAdd}
                                                onChange={(event) => setCustomerToAdd(event.target.value)}
                                                className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                            >
                                                <option value="">Select customer from list</option>
                                                {selectableCustomers.map((customer) => (
                                                    <option key={customer.id} value={String(customer.id)}>
                                                        {customer.name} · {customer.phone || customer.normalized_phone} · {customer.package?.name || 'No package'}
                                                    </option>
                                                ))}
                                            </select>
                                            <Button type="button" onClick={addSelectedCustomer} disabled={!customerToAdd}>
                                                Add
                                            </Button>
                                        </div>

                                        {selectedCustomers.length > 0 && (
                                            <div className="mt-4 flex flex-wrap gap-2">
                                                {selectedCustomers.map((customer) => (
                                                    <button
                                                        key={customer.id}
                                                        type="button"
                                                        onClick={() => removeCustomer(customer.id)}
                                                        className="inline-flex items-center gap-2 rounded-full border bg-background px-3 py-1.5 text-sm hover:bg-muted/50"
                                                    >
                                                        <CircleDot className={`h-3.5 w-3.5 ${isOnlineCustomer(customer) ? 'text-emerald-600' : 'text-muted-foreground'}`} />
                                                        {customer.name}
                                                        <span className="text-muted-foreground">{customer.phone}</span>
                                                        <X className="h-3.5 w-3.5" />
                                                    </button>
                                                ))}
                                            </div>
                                        )}

                                        <InputError message={errors.customer_ids} />
                                    </div>
                                )}

                                {data.audience === 'everyone' && (
                                    <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                        <div className="font-semibold">Confirm broadcast to all users</div>
                                        <p className="mt-1 text-amber-800">
                                            This will send SMS to every customer with a valid phone number.
                                        </p>
                                        <div className="mt-3 flex items-center gap-3">
                                            <Checkbox
                                                checked={data.confirm_everyone}
                                                onCheckedChange={(value) => setData('confirm_everyone', Boolean(value))}
                                            />
                                            <span>I confirm this bulk SMS broadcast.</span>
                                        </div>
                                        <InputError message={errors.confirm_everyone} />
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Message content</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 p-5">
                                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                                    <div className="space-y-2">
                                        <Label htmlFor="message">SMS body</Label>
                                        <Textarea
                                            id="message"
                                            value={data.message}
                                            onChange={(event) => setData('message', event.target.value)}
                                            placeholder="Type the SMS message here. Use variables like @first_name or @package_name."
                                            className="min-h-[220px] resize-y text-base leading-7"
                                        />
                                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                                            <span>{data.message.length} / 480 characters</span>
                                            <span>{segmentsCount} SMS segment{segmentsCount === 1 ? '' : 's'}</span>
                                        </div>
                                        {smsConfigurationError ? (
                                            <div className="mt-2 flex flex-wrap items-center gap-2 text-sm text-destructive">
                                                <span>{errors.message}</span>
                                                <Link href={routes.settings} className="font-semibold underline underline-offset-4">Configure now</Link>
                                            </div>
                                        ) : (
                                            <InputError message={errors.message} />
                                        )}
                                    </div>

                                    <div className="space-y-3">
                                        <div className="space-y-2">
                                            <Label htmlFor="template">Template</Label>
                                            <select
                                                id="template"
                                                value={selectedTemplate}
                                                onChange={(event) => pickTemplate(event.target.value)}
                                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                            >
                                                <option value="">Write custom message</option>
                                                {templates.map((template) => (
                                                    <option key={template.id} value={String(template.id)}>
                                                        {template.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div className="rounded-2xl border p-4">
                                            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Variables</div>
                                            <div className="mt-3 flex flex-wrap gap-2">
                                                {variables.map((variable) => (
                                                    <button
                                                        key={variable}
                                                        type="button"
                                                        onClick={() => insertVariable(variable)}
                                                        className="rounded-full border bg-background px-2.5 py-1 text-xs font-medium hover:bg-muted/50"
                                                    >
                                                        {variable}
                                                    </button>
                                                ))}
                                            </div>
                                            <p className="mt-3 text-xs leading-5 text-muted-foreground">
                                                Variables are replaced per customer when the SMS is sent.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-5">
                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Audience status</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 p-5 text-sm">
                                <StatusRow label="Specific selected" value={selectedCustomers.length} />
                                <StatusRow label="All users" value={validCustomers.length} />
                                <StatusRow label="Online users" value={onlineCustomers.length} />
                                <StatusRow label="Offline users" value={offlineCustomers.length} />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Selected recipient details</CardTitle>
                            </CardHeader>
                            <CardContent className="max-h-[360px] space-y-2 overflow-y-auto p-5">
                                {data.audience === 'specific' && selectedCustomers.length === 0 && (
                                    <div className="rounded-2xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                                        Select customers from the dropdown list.
                                    </div>
                                )}

                                {data.audience !== 'specific' && (
                                    <div className="rounded-2xl border bg-muted/20 p-4 text-sm text-muted-foreground">
                                        The system will resolve recipients when you send based on the selected audience rule.
                                    </div>
                                )}

                                {data.audience === 'specific' && selectedCustomers.map((customer) => (
                                    <div key={customer.id} className="rounded-2xl border p-3">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <div className="font-medium text-foreground">{customer.name}</div>
                                                <div className="text-xs text-muted-foreground">{customer.phone || customer.normalized_phone}</div>
                                            </div>
                                            <Badge variant="outline">{statusLabel(customer)}</Badge>
                                        </div>
                                        <div className="mt-2 text-xs text-muted-foreground">
                                            {customer.package?.name || 'No package'} · {customer.router?.name || 'No router'}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <div className="rounded-2xl border bg-background p-4">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                                <Button type="button" variant="outline" asChild>
                                    <Link href={routes.index}>Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={!canSubmit} size="lg">
                                    <Send className="h-4 w-4" />
                                    Send message
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

function MetricCard({ label, value, helper, icon }: { label: string; value: number; helper: string; icon: ReactNode }) {
    return (
        <Card>
            <CardContent className="flex items-center gap-3 p-4">
                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary">{icon}</div>
                <div>
                    <div className="text-sm text-muted-foreground">{label}</div>
                    <div className="text-2xl font-semibold text-foreground">{value.toLocaleString()}</div>
                    <div className="text-xs text-muted-foreground">{helper}</div>
                </div>
            </CardContent>
        </Card>
    );
}

function StatusRow({ label, value }: { label: string; value: number }) {
    return (
        <div className="flex items-center justify-between rounded-xl border px-3 py-2">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-semibold text-foreground">{value.toLocaleString()}</span>
        </div>
    );
}
