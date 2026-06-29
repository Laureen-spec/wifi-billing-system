import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, MessageSquare, Settings, Tags } from 'lucide-react';

type Message = {
    id: number;
    recipient: string;
    phone: string;
    message: string;
    sending_mode?: string | null;
    provider?: string | null;
    status: string;
    provider_message_id?: string | null;
    provider_response?: Record<string, unknown> | null;
    result_message?: string | null;
    sent_at?: string | null;
    delivered_at?: string | null;
    failed_at?: string | null;
    created_at?: string | null;
    customer?: { name: string; username?: string | null } | null;
    isp?: { name: string } | null;
    sender?: { name: string } | null;
};

type Props = {
    pageTitle: string;
    message: Message;
    routes: {
        messages: string;
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

export default function SmsShow({ pageTitle, message, routes }: Props) {
    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: 'ISP SMS', url: routes.messages }, { label: pageTitle }]}
            pageTitle={pageTitle}
        >
            <Head title={`${pageTitle} #${message.id}`} />

            <div className="space-y-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-normal text-foreground">SMS Message #{message.id}</h1>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">Delivery details from the existing ISP SMS outbox.</p>
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
                        <Button variant="outline" asChild>
                            <Link href={routes.settings}>
                                <Settings className="h-4 w-4" />
                                Settings
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <Card>
                        <CardHeader className="border-b py-4">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <MessageSquare className="h-4 w-4" />
                                Message
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 p-4">
                            <div className="rounded-md border bg-muted/30 p-4 text-sm leading-6">
                                {message.message}
                            </div>

                            {message.result_message && (
                                <div className="rounded-md border bg-muted/30 p-4">
                                    <p className="text-sm font-medium">Gateway result</p>
                                    <p className="mt-1 text-sm text-muted-foreground">{message.result_message}</p>
                                </div>
                            )}

                            {message.provider_response && (
                                <div className="rounded-md border bg-muted/30 p-4">
                                    <p className="text-sm font-medium">Provider response</p>
                                    <pre className="mt-2 overflow-auto text-xs text-muted-foreground">
                                        {JSON.stringify(message.provider_response, null, 2)}
                                    </pre>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="self-start">
                        <CardHeader className="border-b py-4">
                            <CardTitle className="text-base">Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 p-4 text-sm">
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground">Status</span>
                                <Badge variant="outline" className={statusClass(message.status)}>{message.status}</Badge>
                            </div>
                            <Detail label="Recipient" value={message.recipient} />
                            <Detail label="Phone" value={message.phone} />
                            <Detail label="ISP" value={message.isp?.name} />
                            <Detail label="Mode" value={message.sending_mode || 'platform'} />
                            <Detail label="Provider" value={message.provider || '-'} />
                            <Detail label="Provider ID" value={message.provider_message_id || '-'} />
                            <Detail label="Sent by" value={message.sender?.name || '-'} />
                            <Detail label="Created" value={message.created_at || '-'} />
                            <Detail label="Sent" value={message.sent_at || '-'} />
                            <Detail label="Delivered" value={message.delivered_at || '-'} />
                            <Detail label="Failed" value={message.failed_at || '-'} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return (
        <div className="flex items-start justify-between gap-3 border-b pb-2 last:border-b-0 last:pb-0">
            <span className="text-muted-foreground">{label}</span>
            <span className="max-w-[210px] text-right font-medium">{value || '-'}</span>
        </div>
    );
}
