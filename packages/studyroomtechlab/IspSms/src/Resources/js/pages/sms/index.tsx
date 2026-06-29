import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Head, Link, router } from '@inertiajs/react';
import { MessageSquare, Plus, Search, Settings, Tags } from 'lucide-react';
import { FormEvent, useState } from 'react';

type Message = {
    id: number;
    recipient: string;
    phone: string;
    message: string;
    sending_mode?: string | null;
    provider?: string | null;
    status: string;
    result_message?: string | null;
    sent_at?: string | null;
    created_at?: string | null;
    show_url: string;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from?: number | null;
    to?: number | null;
    total: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
};

type Props = {
    pageTitle: string;
    subtitle: string;
    messages: Paginated<Message>;
    stats: Record<string, number>;
    filters: {
        q?: string;
        status?: string;
        sending_mode?: string;
        direction?: string;
    };
    routes: {
        index: string;
        newMessage: string;
        settings: string;
        templates: string;
    };
};

const statusClass = (status: string) => {
    const value = status.toLowerCase();

    if (['sent', 'delivered'].includes(value)) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    }

    if (value === 'failed') {
        return 'bg-rose-50 text-rose-700 border-rose-200';
    }

    return 'bg-amber-50 text-amber-700 border-amber-200';
};

const trimMessage = (message: string) => message.length > 110 ? `${message.slice(0, 110)}...` : message;

export default function SmsMessages({ pageTitle, subtitle, messages, stats, filters, routes }: Props) {
    const [localFilters, setLocalFilters] = useState({
        q: filters.q || '',
        status: filters.status || '',
        sending_mode: filters.sending_mode || '',
        direction: filters.direction || '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        router.get(routes.index, Object.fromEntries(
            Object.entries(localFilters).filter(([, value]) => value !== '')
        ), {
            preserveState: true,
            replace: true,
        });
    };

    const reset = () => {
        setLocalFilters({ q: '', status: '', sending_mode: '', direction: '' });
        router.get(routes.index, {}, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: 'ISP SMS', url: routes.index }, { label: pageTitle }]}
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
                            <Link href={routes.settings}>
                                <Settings className="h-4 w-4" />
                                Settings
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={routes.templates}>
                                <Tags className="h-4 w-4" />
                                Templates
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

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    {['total', 'queued', 'sent', 'delivered', 'failed'].map((key) => (
                        <Card key={key}>
                            <CardContent className="flex items-center gap-3 p-4">
                                <div className="flex h-10 w-10 items-center justify-center rounded-md bg-primary/10 text-primary">
                                    <MessageSquare className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="text-sm capitalize text-muted-foreground">{key}</p>
                                    <p className="text-2xl font-semibold">{stats[key] || 0}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent className="p-4">
                        <form onSubmit={submit} className="grid gap-3 lg:grid-cols-[minmax(0,2fr)_1fr_1fr_1fr_auto_auto]">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={localFilters.q}
                                    onChange={(event) => setLocalFilters({ ...localFilters, q: event.target.value })}
                                    placeholder="Search phone, message, customer"
                                    className="pl-9"
                                />
                            </div>
                            <select
                                value={localFilters.status}
                                onChange={(event) => setLocalFilters({ ...localFilters, status: event.target.value })}
                                className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">All status</option>
                                <option value="queued">Queued</option>
                                <option value="sent">Sent</option>
                                <option value="delivered">Delivered</option>
                                <option value="failed">Failed</option>
                            </select>
                            <select
                                value={localFilters.sending_mode}
                                onChange={(event) => setLocalFilters({ ...localFilters, sending_mode: event.target.value })}
                                className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">All modes</option>
                                <option value="platform">Platform</option>
                                <option value="own">Own gateway</option>
                            </select>
                            <select
                                value={localFilters.direction}
                                onChange={(event) => setLocalFilters({ ...localFilters, direction: event.target.value })}
                                className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">All direction</option>
                                <option value="outbound">Outbound</option>
                                <option value="inbound">Inbound</option>
                            </select>
                            <Button type="button" variant="outline" onClick={reset}>Reset</Button>
                            <Button type="submit">Search</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b py-4">
                        <CardTitle className="text-base">Outbox</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {messages.data.length === 0 ? (
                            <div className="p-10 text-center">
                                <MessageSquare className="mx-auto h-12 w-12 text-muted-foreground" />
                                <h2 className="mt-4 text-lg font-semibold">No SMS messages yet</h2>
                                <p className="mt-1 text-sm text-muted-foreground">Send a message to create the first outbox record.</p>
                                <Button className="mt-4" asChild>
                                    <Link href={routes.newMessage}>
                                        <Plus className="h-4 w-4" />
                                        New Message
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-border text-sm">
                                    <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3">Recipient</th>
                                            <th className="px-4 py-3">Mode</th>
                                            <th className="px-4 py-3">Message</th>
                                            <th className="px-4 py-3">Status</th>
                                            <th className="px-4 py-3">Sent</th>
                                            <th className="px-4 py-3 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {messages.data.map((message) => (
                                            <tr key={message.id} className="hover:bg-muted/40">
                                                <td className="px-4 py-3">
                                                    <div className="font-medium">{message.recipient}</div>
                                                    <div className="text-xs text-muted-foreground">{message.phone}</div>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    <div>{message.sending_mode || 'platform'}</div>
                                                    <div className="text-xs">{message.provider || '-'}</div>
                                                </td>
                                                <td className="max-w-xl px-4 py-3 text-muted-foreground">{trimMessage(message.message)}</td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="outline" className={statusClass(message.status)}>{message.status}</Badge>
                                                    {message.result_message && <div className="mt-1 max-w-xs text-xs text-muted-foreground">{message.result_message}</div>}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">{message.sent_at || message.created_at || '-'}</td>
                                                <td className="px-4 py-3 text-right">
                                                    <Button size="sm" variant="outline" asChild>
                                                        <Link href={message.show_url}>View</Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        <div className="flex flex-col gap-3 border-t p-4 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                            <div>Showing {messages.from || 0} to {messages.to || 0} of {messages.total}</div>
                            <div className="flex gap-2">
                                <Button variant="outline" disabled={!messages.prev_page_url} asChild={Boolean(messages.prev_page_url)}>
                                    {messages.prev_page_url ? <Link href={messages.prev_page_url}>Previous</Link> : <span>Previous</span>}
                                </Button>
                                <Button variant="outline" disabled={!messages.next_page_url} asChild={Boolean(messages.next_page_url)}>
                                    {messages.next_page_url ? <Link href={messages.next_page_url}>Next</Link> : <span>Next</span>}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
