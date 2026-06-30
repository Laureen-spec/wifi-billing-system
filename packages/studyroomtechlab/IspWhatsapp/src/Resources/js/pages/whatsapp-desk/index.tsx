import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    Bot,
    CheckCircle2,
    Clock,
    FileText,
    Inbox,
    Megaphone,
    MessageCircle,
    NotebookPen,
    Receipt,
    Save,
    Send,
    Settings,
    ShieldCheck,
    Tags,
    WalletCards,
} from 'lucide-react';
import { FormEvent, ReactNode, useEffect, useMemo, useState } from 'react';

declare function route(name: string): string;

type Tab = { key: string; label: string; route: string };
type Stats = {
    whatsapp_balance: string;
    messages_sent_this_month: number;
    estimated_cost_this_month: string;
    failed_messages: number;
    topup_payment_status: string;
    open_conversations: number;
};
type Setting = {
    scope: string;
    provider_mode: string;
    provider: string;
    business_phone?: string | null;
    phone_number_id?: string | null;
    waba_id?: string | null;
    sender_name?: string | null;
    api_base_url?: string | null;
    webhook_verify_token_saved?: boolean;
    credentials_saved?: string[];
    is_active: boolean;
    allow_platform_api: boolean;
    allow_own_api: boolean;
    reply_window_minutes: number;
    whatsapp_balance: number;
    estimated_cost_per_message: number;
    low_balance_threshold: number;
    billing_enabled: boolean;
    billing_status: string;
    topup_payment_status?: string | null;
    messages_sent: number;
    messages_failed: number;
    last_test_status?: string | null;
    last_test_message?: string | null;
    last_tested_at?: string | null;
};
type Conversation = {
    id: number;
    phone: string;
    customer_name: string;
    status: string;
    opted_out: boolean;
    blocked: boolean;
    reply_window_status: string;
    reply_window_expires_at?: string | null;
    reply_window_seconds_remaining: number;
    template_required: boolean;
    last_message_preview?: string | null;
    last_message_at?: string | null;
    customer?: {
        id: number;
        name: string;
        phone?: string | null;
        plan_name?: string | null;
        expiry_time?: string | null;
        status?: string | null;
    } | null;
};
type Message = {
    id: number;
    direction: string;
    message_type: string;
    body?: string | null;
    status: string;
    error_message?: string | null;
    provider_mode: string;
    provider?: string | null;
    cost: number;
    sent_at?: string | null;
    template?: { id: number; name: string } | null;
    sender?: { id: number; name: string } | null;
};
type Template = {
    id: number;
    name: string;
    key: string;
    category: string;
    provider_template_name?: string | null;
    language: string;
    body: string;
    variables: string[];
    status: string;
    enabled: boolean;
    scope: string;
};
type PaymentRequest = {
    id: number;
    customer: string;
    phone: string;
    plan_name?: string | null;
    amount: number;
    currency: string;
    method: string;
    status: string;
    checkout_request_id?: string | null;
    receipt_code?: string | null;
    payment_center_record_id?: string | null;
    requested_at?: string | null;
    confirmed_at?: string | null;
};
type ReceiptRow = {
    id: number;
    phone: string;
    receipt_code: string;
    amount: number;
    currency: string;
    status: string;
    sent_at?: string | null;
    created_at?: string | null;
};
type TicketRow = {
    id: number;
    ticket_number: string;
    phone: string;
    subject: string;
    status: string;
    priority: string;
    description?: string | null;
    created_at?: string | null;
};
type BroadcastRow = {
    id: number;
    name: string;
    audience: string;
    recipient_count: number;
    status: string;
    requires_confirmation: boolean;
    confirmed_at?: string | null;
    created_at?: string | null;
    template?: { id: number; name: string } | null;
};
type LogRow = {
    id: number;
    phone: string;
    message_type: string;
    provider_mode: string;
    provider?: string | null;
    cost: number;
    status: string;
    error_message?: string | null;
    sent_at?: string | null;
    direction?: string;
};
type CustomerOption = { id: number; name: string; phone?: string | null; plan_name?: string | null };
type PackageOption = { id: number; name: string; price: number; validity_days?: number | null; status?: string | null };
type ProviderOption = { value: string; label: string };

type Props = {
    pageTitle: string;
    subtitle: string;
    activeTab: string;
    tabs: Tab[];
    tablesReady: boolean;
    isPlatform: boolean;
    stats: Stats;
    setting?: Setting | null;
    platformSetting?: Setting | null;
    providerOptions: ProviderOption[];
    templateVariables: string[];
    templateCategories: string[];
    botMenu: string[];
    botKeywords: string[];
    conversations: Conversation[];
    selectedConversation?: Conversation | null;
    conversationMessages: Message[];
    templates: Template[];
    paymentRequests: PaymentRequest[];
    receipts: ReceiptRow[];
    supportTickets: TicketRow[];
    broadcasts: BroadcastRow[];
    usageLogs: LogRow[];
    logs: LogRow[];
    customers: CustomerOption[];
    packages: PackageOption[];
    routes: Record<string, string>;
};

const actionUrl = (template: string, id: number | string) => template.replace('__ID__', String(id));
const money = (currency: string, amount: number) => `${currency} ${Number(amount || 0).toFixed(2)}`;
const humanize = (value: string) => value.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function WhatsappDeskIndex(props: Props) {
    const {
        pageTitle,
        subtitle,
        activeTab,
        tabs,
        tablesReady,
        isPlatform,
        stats,
        setting,
        platformSetting,
        providerOptions,
        templateVariables,
        templateCategories,
        botMenu,
        botKeywords,
        conversations,
        selectedConversation,
        conversationMessages,
        templates,
        paymentRequests,
        receipts,
        supportTickets,
        broadcasts,
        usageLogs,
        logs,
        customers,
        packages,
        routes,
    } = props;

    const [secondsRemaining, setSecondsRemaining] = useState(selectedConversation?.reply_window_seconds_remaining || 0);
    const currentSetting = isPlatform ? (platformSetting || setting) : setting;

    useEffect(() => {
        setSecondsRemaining(selectedConversation?.reply_window_seconds_remaining || 0);
    }, [selectedConversation?.id, selectedConversation?.reply_window_seconds_remaining]);

    useEffect(() => {
        const interval = window.setInterval(() => {
            setSecondsRemaining((value) => Math.max(0, value - 1));
        }, 1000);

        return () => window.clearInterval(interval);
    }, []);

    const replyForm = useForm({ body: '' });
    const templateMessageForm = useForm<{ template_id: string; variables: Record<string, string> }>({
        template_id: '',
        variables: {},
    });
    const noteForm = useForm({ body: '' });
    const conversationSafetyForm = useForm({
        blocked: selectedConversation?.blocked || false,
        opted_out: selectedConversation?.opted_out || false,
        status: selectedConversation?.status || 'open',
    });
    const paymentForm = useForm({
        conversation_id: selectedConversation ? String(selectedConversation.id) : '',
        internet_package_id: '',
        method: 'mpesa',
    });
    const settingsForm = useForm({
        scope: isPlatform ? 'platform' : 'isp',
        provider_mode: currentSetting?.provider_mode || 'platform',
        provider: currentSetting?.provider && currentSetting.provider !== 'platform' ? currentSetting.provider : 'meta_cloud',
        business_phone: currentSetting?.business_phone || '',
        phone_number_id: currentSetting?.phone_number_id || '',
        waba_id: currentSetting?.waba_id || '',
        sender_name: currentSetting?.sender_name || '',
        api_base_url: currentSetting?.api_base_url || '',
        webhook_verify_token: '',
        reply_window_minutes: currentSetting?.reply_window_minutes || 120,
        whatsapp_balance: String(currentSetting?.whatsapp_balance || 0),
        estimated_cost_per_message: String(currentSetting?.estimated_cost_per_message || 1),
        low_balance_threshold: String(currentSetting?.low_balance_threshold || 10),
        billing_enabled: currentSetting?.billing_enabled ?? true,
        billing_status: currentSetting?.billing_status || 'active',
        topup_payment_status: currentSetting?.topup_payment_status || '',
        is_active: currentSetting?.is_active ?? true,
        allow_platform_api: currentSetting?.allow_platform_api ?? true,
        allow_own_api: currentSetting?.allow_own_api ?? true,
        credentials: {
            access_token: '',
            api_key: '',
            api_secret: '',
            account_sid: '',
            auth_token: '',
            username: '',
            password: '',
            bearer_token: '',
        },
    });
    const templateForm = useForm({
        id: '',
        scope: isPlatform ? 'platform' : 'isp',
        name: '',
        key: '',
        category: 'support',
        provider_template_name: '',
        language: 'en',
        body: '',
        variables: [] as string[],
        status: 'approved',
        enabled: true,
    });
    const broadcastForm = useForm({
        name: '',
        template_id: '',
        audience: 'specific',
        customer_ids: [] as number[],
    });
    const confirmBroadcastForm = useForm({ confirm_broadcast: false });
    const confirmPaymentForm = useForm({ receipt_code: '' });

    const selectedTemplate = useMemo(
        () => templates.find((template) => template.id === Number(templateMessageForm.data.template_id)),
        [templates, templateMessageForm.data.template_id],
    );

    useEffect(() => {
        conversationSafetyForm.setData({
            blocked: selectedConversation?.blocked || false,
            opted_out: selectedConversation?.opted_out || false,
            status: selectedConversation?.status || 'open',
        });
        paymentForm.setData('conversation_id', selectedConversation ? String(selectedConversation.id) : '');
    }, [selectedConversation?.id]);

    useEffect(() => {
        if (selectedTemplate) {
            const nextVariables = { ...templateMessageForm.data.variables };
            selectedTemplate.variables.forEach((variable) => {
                if (nextVariables[variable] === undefined) {
                    nextVariables[variable] = '';
                }
            });
            templateMessageForm.setData('variables', nextVariables);
        }
    }, [selectedTemplate?.id]);

    const replyWindowOpen = Boolean(selectedConversation && secondsRemaining > 0);
    const selectedConversationUrl = selectedConversation ? `?conversation=${selectedConversation.id}` : '';

    const submitReply = (event: FormEvent) => {
        event.preventDefault();
        if (!selectedConversation) return;
        replyForm.post(actionUrl(routes.sendReply, selectedConversation.id), {
            preserveScroll: true,
            onSuccess: () => replyForm.reset(),
        });
    };

    const submitTemplateMessage = (event: FormEvent) => {
        event.preventDefault();
        if (!selectedConversation) return;
        templateMessageForm.post(actionUrl(routes.sendTemplate, selectedConversation.id), {
            preserveScroll: true,
            onSuccess: () => templateMessageForm.reset(),
        });
    };

    const submitNote = (event: FormEvent) => {
        event.preventDefault();
        if (!selectedConversation) return;
        noteForm.post(actionUrl(routes.internalNote, selectedConversation.id), {
            preserveScroll: true,
            onSuccess: () => noteForm.reset(),
        });
    };

    const submitSafety = (event: FormEvent) => {
        event.preventDefault();
        if (!selectedConversation) return;
        conversationSafetyForm.patch(actionUrl(routes.updateConversation, selectedConversation.id), { preserveScroll: true });
    };

    const submitHandover = () => {
        if (!selectedConversation) return;
        router.post(actionUrl(routes.handover, selectedConversation.id), { note: 'Manual admin handover from WhatsApp Desk.' }, { preserveScroll: true });
    };

    const submitSettings = (event: FormEvent) => {
        event.preventDefault();
        settingsForm.post(routes.saveSettings, { preserveScroll: true });
    };

    const testConnection = () => {
        router.post(routes.testConnection, { scope: settingsForm.data.scope }, { preserveScroll: true });
    };

    const submitTemplate = (event: FormEvent) => {
        event.preventDefault();
        templateForm.post(routes.saveTemplate, {
            preserveScroll: true,
            onSuccess: () => templateForm.reset(),
        });
    };

    const submitPaymentRequest = (event: FormEvent) => {
        event.preventDefault();
        paymentForm.post(routes.createPaymentRequest, { preserveScroll: true });
    };

    const submitBroadcast = (event: FormEvent) => {
        event.preventDefault();
        broadcastForm.post(routes.saveBroadcast, {
            preserveScroll: true,
            onSuccess: () => broadcastForm.reset(),
        });
    };

    const confirmBroadcast = (broadcast: BroadcastRow) => {
        confirmBroadcastForm.post(actionUrl(routes.confirmBroadcast, broadcast.id), { preserveScroll: true });
    };

    const confirmPayment = (payment: PaymentRequest) => {
        confirmPaymentForm.post(actionUrl(routes.confirmPaymentRequest, payment.id), { preserveScroll: true });
    };

    const pageActions = (
        <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline">
                <Link href={tabs.find((tab) => tab.key === 'inbox')?.route || '#'}>
                    <Inbox className="h-4 w-4" />
                    Inbox
                </Link>
            </Button>
            <Button asChild variant="outline">
                <Link href={tabs.find((tab) => tab.key === 'api-settings')?.route || '#'}>
                    <ShieldCheck className="h-4 w-4" />
                    API Settings
                </Link>
            </Button>
        </div>
    );

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'WhatsApp Desk' },
            ]}
            pageTitle={pageTitle}
            pageActions={pageActions}
        >
            <Head title={pageTitle} />

            <div className="space-y-5">
                {!tablesReady && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        WhatsApp Desk tables are not migrated yet.
                    </div>
                )}

                <section className="rounded-lg border bg-card">
                    <div className="flex flex-col gap-4 border-b px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">Workspace communications</div>
                            <h1 className="mt-1 text-2xl font-semibold tracking-tight text-foreground">WhatsApp Desk</h1>
                            <p className="mt-1 max-w-3xl text-sm text-muted-foreground">{subtitle}</p>
                        </div>
                        <div className="grid min-w-[280px] grid-cols-2 gap-2 text-sm">
                            <StatusTile label="Provider mode" value={currentSetting?.provider_mode === 'own_api' ? 'Own API' : 'Platform API'} />
                            <StatusTile label="Reply window" value={`${currentSetting?.reply_window_minutes || 120} min`} />
                        </div>
                    </div>

                    <div className="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-5">
                        <Metric label="WhatsApp balance" value={stats.whatsapp_balance} icon={<WalletCards className="h-4 w-4" />} />
                        <Metric label="Sent this month" value={String(stats.messages_sent_this_month)} icon={<Send className="h-4 w-4" />} />
                        <Metric label="Estimated cost" value={stats.estimated_cost_this_month} icon={<Receipt className="h-4 w-4" />} />
                        <Metric label="Failed messages" value={String(stats.failed_messages)} icon={<AlertTriangle className="h-4 w-4" />} />
                        <Metric label="Top-up status" value={humanize(stats.topup_payment_status)} icon={<CheckCircle2 className="h-4 w-4" />} />
                    </div>
                </section>

                <div className="flex gap-2 overflow-x-auto rounded-lg border bg-card p-2">
                    {tabs.map((tab) => (
                        <Button key={tab.key} asChild size="sm" variant={activeTab === tab.key ? 'default' : 'ghost'} className="shrink-0">
                            <Link href={`${tab.route}${selectedConversationUrl}`}>{tab.label}</Link>
                        </Button>
                    ))}
                </div>

                {activeTab === 'overview' && (
                    <div className="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
                        <OverviewPanel stats={stats} setting={setting} platformSetting={platformSetting} />
                        <BotSummary botMenu={botMenu} botKeywords={botKeywords} />
                    </div>
                )}

                {activeTab === 'inbox' && (
                    <InboxPanel
                        conversations={conversations}
                        selectedConversation={selectedConversation}
                        messages={conversationMessages}
                        templates={templates}
                        replyWindowOpen={replyWindowOpen}
                        secondsRemaining={secondsRemaining}
                        replyForm={replyForm}
                        templateMessageForm={templateMessageForm}
                        noteForm={noteForm}
                        conversationSafetyForm={conversationSafetyForm}
                        selectedTemplate={selectedTemplate}
                        onSubmitReply={submitReply}
                        onSubmitTemplate={submitTemplateMessage}
                        onSubmitNote={submitNote}
                        onSubmitSafety={submitSafety}
                        onHandover={submitHandover}
                    />
                )}

                {activeTab === 'bot-flows' && (
                    <BotFlowsPanel botMenu={botMenu} botKeywords={botKeywords} packages={packages} />
                )}

                {activeTab === 'payment-requests' && (
                    <PaymentRequestsPanel
                        paymentRequests={paymentRequests}
                        selectedConversation={selectedConversation}
                        packages={packages}
                        paymentForm={paymentForm}
                        confirmPaymentForm={confirmPaymentForm}
                        onSubmitPaymentRequest={submitPaymentRequest}
                        onConfirmPayment={confirmPayment}
                    />
                )}

                {activeTab === 'receipts' && <ReceiptsPanel receipts={receipts} />}
                {activeTab === 'support-tickets' && <TicketsPanel tickets={supportTickets} />}

                {activeTab === 'broadcasts' && (
                    <BroadcastsPanel
                        broadcasts={broadcasts}
                        templates={templates}
                        customers={customers}
                        form={broadcastForm}
                        confirmForm={confirmBroadcastForm}
                        onSubmit={submitBroadcast}
                        onConfirm={confirmBroadcast}
                    />
                )}

                {activeTab === 'templates' && (
                    <TemplatesPanel
                        templates={templates}
                        variables={templateVariables}
                        categories={templateCategories}
                        form={templateForm}
                        isPlatform={isPlatform}
                        onSubmit={submitTemplate}
                    />
                )}

                {activeTab === 'usage' && <UsagePanel usageLogs={usageLogs} setting={currentSetting} />}

                {activeTab === 'api-settings' && (
                    <ApiSettingsPanel
                        form={settingsForm}
                        providerOptions={providerOptions}
                        setting={currentSetting}
                        platformSetting={platformSetting}
                        isPlatform={isPlatform}
                        onSubmit={submitSettings}
                        onTest={testConnection}
                    />
                )}

                {activeTab === 'logs' && <LogsPanel logs={logs} />}

                {activeTab === 'settings' && (
                    <SettingsPanel
                        form={settingsForm}
                        setting={currentSetting}
                        onSubmit={submitSettings}
                    />
                )}
            </div>
        </AuthenticatedLayout>
    );
}

function Metric({ label, value, icon }: { label: string; value: string; icon: ReactNode }) {
    return (
        <div className="rounded-lg border bg-background p-3">
            <div className="flex items-center justify-between gap-3">
                <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</span>
                <span className="text-muted-foreground">{icon}</span>
            </div>
            <div className="mt-2 text-2xl font-semibold text-foreground">{value}</div>
        </div>
    );
}

function StatusTile({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border bg-background px-3 py-2">
            <div className="text-xs text-muted-foreground">{label}</div>
            <div className="text-sm font-semibold text-foreground">{value}</div>
        </div>
    );
}

function StatusBadge({ value }: { value: string }) {
    const tone = ['failed', 'expired', 'blocked', 'past_due', 'disabled', 'rejected'].includes(value)
        ? 'destructive'
        : ['sent', 'paid', 'issued', 'active', 'approved', 'confirmed', 'open'].includes(value)
            ? 'default'
            : 'secondary';

    return <Badge variant={tone as 'default' | 'destructive' | 'secondary'}>{humanize(value || 'unknown')}</Badge>;
}

function OverviewPanel({ stats, setting, platformSetting }: { stats: Stats; setting?: Setting | null; platformSetting?: Setting | null }) {
    return (
        <div className="grid gap-4 md:grid-cols-2">
            <InfoBlock title="Provider / API mode" icon={<ShieldCheck className="h-4 w-4" />}>
                <InfoRow label="Workspace mode" value={setting?.provider_mode === 'own_api' ? 'Use Own WhatsApp API' : 'Use Platform WhatsApp API'} />
                <InfoRow label="Workspace provider" value={setting?.provider || 'platform'} />
                <InfoRow label="Platform provider" value={platformSetting?.provider || 'platform controlled'} />
                <InfoRow label="Own API credentials" value={(setting?.credentials_saved || []).length ? 'Saved and hidden' : 'Not saved'} />
            </InfoBlock>
            <InfoBlock title="Reply rules" icon={<Clock className="h-4 w-4" />}>
                <InfoRow label="Reply window" value={`${setting?.reply_window_minutes || 120} minutes`} />
                <InfoRow label="After expiry" value="Approved template required" />
                <InfoRow label="Internal notes" value="Never sent to customer" />
                <InfoRow label="Free-text safety" value="Blocked when window expires" />
            </InfoBlock>
            <InfoBlock title="Billing usage" icon={<WalletCards className="h-4 w-4" />}>
                <InfoRow label="Balance" value={stats.whatsapp_balance} />
                <InfoRow label="Sent this month" value={String(stats.messages_sent_this_month)} />
                <InfoRow label="Estimated cost" value={stats.estimated_cost_this_month} />
                <InfoRow label="Failed messages" value={String(stats.failed_messages)} />
            </InfoBlock>
            <InfoBlock title="Safety controls" icon={<AlertTriangle className="h-4 w-4" />}>
                <InfoRow label="Broadcasts" value="Confirmation required" />
                <InfoRow label="Opt-out phones" value="Sending blocked" />
                <InfoRow label="Blocked phones" value="Sending blocked" />
                <InfoRow label="API keys" value="Hidden after save" />
            </InfoBlock>
        </div>
    );
}

function BotSummary({ botMenu, botKeywords }: { botMenu: string[]; botKeywords: string[] }) {
    return (
        <Card className="rounded-lg">
            <CardHeader className="border-b py-4">
                <CardTitle className="flex items-center gap-2 text-base"><Bot className="h-4 w-4" />Bot self-service</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4 p-4">
                <div className="rounded-lg border bg-muted/20 p-3">
                    <div className="text-sm font-medium text-foreground">Menu</div>
                    <div className="mt-2 space-y-1 text-sm text-muted-foreground">
                        {botMenu.map((item) => <div key={item}>{item}</div>)}
                    </div>
                </div>
                <div className="flex flex-wrap gap-2">
                    {botKeywords.map((keyword) => <Badge key={keyword} variant="secondary">{keyword}</Badge>)}
                </div>
            </CardContent>
        </Card>
    );
}

function InboxPanel({
    conversations,
    selectedConversation,
    messages,
    templates,
    replyWindowOpen,
    secondsRemaining,
    replyForm,
    templateMessageForm,
    noteForm,
    conversationSafetyForm,
    selectedTemplate,
    onSubmitReply,
    onSubmitTemplate,
    onSubmitNote,
    onSubmitSafety,
    onHandover,
}: {
    conversations: Conversation[];
    selectedConversation?: Conversation | null;
    messages: Message[];
    templates: Template[];
    replyWindowOpen: boolean;
    secondsRemaining: number;
    replyForm: any;
    templateMessageForm: any;
    noteForm: any;
    conversationSafetyForm: any;
    selectedTemplate?: Template;
    onSubmitReply: (event: FormEvent) => void;
    onSubmitTemplate: (event: FormEvent) => void;
    onSubmitNote: (event: FormEvent) => void;
    onSubmitSafety: (event: FormEvent) => void;
    onHandover: () => void;
}) {
    return (
        <div className="grid gap-5 xl:grid-cols-[360px_minmax(0,1fr)]">
            <Card className="rounded-lg">
                <CardHeader className="border-b py-4">
                    <CardTitle className="flex items-center gap-2 text-base"><Inbox className="h-4 w-4" />Conversations</CardTitle>
                </CardHeader>
                <CardContent className="max-h-[720px] overflow-y-auto p-0">
                    {conversations.length === 0 ? (
                        <Empty label="No WhatsApp conversations yet" />
                    ) : conversations.map((conversation) => (
                        <Link
                            key={conversation.id}
                            href={`${route('isp.whatsapp.inbox')}?conversation=${conversation.id}`}
                            className={`block border-b px-4 py-3 hover:bg-muted/40 ${selectedConversation?.id === conversation.id ? 'bg-muted/50' : ''}`}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <div className="font-medium text-foreground">{conversation.customer_name}</div>
                                    <div className="text-xs text-muted-foreground">{conversation.phone}</div>
                                </div>
                                <StatusBadge value={conversation.reply_window_status} />
                            </div>
                            <div className="mt-2 line-clamp-2 text-sm text-muted-foreground">{conversation.last_message_preview || 'No preview'}</div>
                        </Link>
                    ))}
                </CardContent>
            </Card>

            <div className="space-y-5">
                {!selectedConversation ? (
                    <Empty label="Select a WhatsApp conversation" />
                ) : (
                    <>
                        <Card className="rounded-lg">
                            <CardContent className="flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="text-lg font-semibold">{selectedConversation.customer_name}</h2>
                                        <StatusBadge value={replyWindowOpen ? 'reply window open' : 'template message required'} />
                                        {selectedConversation.opted_out && <StatusBadge value="opted_out" />}
                                        {selectedConversation.blocked && <StatusBadge value="blocked" />}
                                    </div>
                                    <div className="mt-1 text-sm text-muted-foreground">
                                        {selectedConversation.customer?.plan_name || 'Unlinked conversation'} · {selectedConversation.phone}
                                    </div>
                                </div>
                                <div className="rounded-lg border bg-background px-3 py-2 text-sm">
                                    <div className="text-xs uppercase tracking-wide text-muted-foreground">Countdown</div>
                                    <div className="font-semibold text-foreground">{formatCountdown(secondsRemaining)}</div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="rounded-lg">
                            <CardContent className="max-h-[520px] space-y-3 overflow-y-auto p-4">
                                {messages.length === 0 ? <Empty label="No messages in this conversation" /> : messages.map((message) => (
                                    <div key={message.id} className={`flex ${message.direction === 'outbound' ? 'justify-end' : message.direction === 'internal' ? 'justify-center' : 'justify-start'}`}>
                                        <div className={`max-w-[76%] rounded-lg border px-3 py-2 text-sm ${message.direction === 'outbound' ? 'bg-primary text-primary-foreground' : message.direction === 'internal' ? 'bg-amber-50 text-amber-950' : 'bg-background'}`}>
                                            <div className="whitespace-pre-wrap">{message.body || '(empty)'}</div>
                                            <div className={`mt-2 text-[11px] ${message.direction === 'outbound' ? 'text-primary-foreground/80' : 'text-muted-foreground'}`}>
                                                {message.message_type} · {message.status} · {message.sent_at || 'pending'}
                                            </div>
                                            {message.error_message && <div className="mt-1 text-xs text-destructive">{message.error_message}</div>}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <div className="grid gap-5 xl:grid-cols-2">
                            <Card className="rounded-lg">
                                <CardHeader className="border-b py-4">
                                    <CardTitle className="flex items-center gap-2 text-base"><Send className="h-4 w-4" />Reply</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4 p-4">
                                    <form onSubmit={onSubmitReply} className="space-y-3">
                                        <Textarea
                                            rows={4}
                                            value={replyForm.data.body}
                                            onChange={(event) => replyForm.setData('body', event.target.value)}
                                            disabled={!replyWindowOpen || selectedConversation.blocked || selectedConversation.opted_out}
                                            placeholder={replyWindowOpen ? 'Type a free-text reply' : 'Reply window expired'}
                                        />
                                        <InputError message={replyForm.errors.body} />
                                        <Button type="submit" disabled={replyForm.processing || !replyWindowOpen}>
                                            <Send className="h-4 w-4" />
                                            Send Reply
                                        </Button>
                                    </form>

                                    <form onSubmit={onSubmitTemplate} className="space-y-3 border-t pt-4">
                                        <Label>Approved template</Label>
                                        <select value={templateMessageForm.data.template_id} onChange={(event) => templateMessageForm.setData('template_id', event.target.value)} className="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                            <option value="">Select template</option>
                                            {templates.filter((template) => template.enabled && template.status === 'approved').map((template) => (
                                                <option key={template.id} value={template.id}>{template.name} ({template.category})</option>
                                            ))}
                                        </select>
                                        {selectedTemplate && selectedTemplate.variables.length > 0 && (
                                            <div className="grid gap-3 md:grid-cols-2">
                                                {selectedTemplate.variables.map((variable) => (
                                                    <div key={variable} className="space-y-1">
                                                        <Label>{`{{${variable}}}`}</Label>
                                                        <Input
                                                            value={templateMessageForm.data.variables[variable] || ''}
                                                            onChange={(event) => templateMessageForm.setData('variables', { ...templateMessageForm.data.variables, [variable]: event.target.value })}
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                        <Button type="submit" variant="outline" disabled={templateMessageForm.processing || !templateMessageForm.data.template_id}>
                                            <Tags className="h-4 w-4" />
                                            Send Template
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>

                            <Card className="rounded-lg">
                                <CardHeader className="border-b py-4">
                                    <CardTitle className="flex items-center gap-2 text-base"><NotebookPen className="h-4 w-4" />Notes and safety</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4 p-4">
                                    <form onSubmit={onSubmitNote} className="space-y-3">
                                        <Textarea rows={3} value={noteForm.data.body} onChange={(event) => noteForm.setData('body', event.target.value)} placeholder="Internal note" />
                                        <Button type="submit" variant="outline" disabled={noteForm.processing}>
                                            <NotebookPen className="h-4 w-4" />
                                            Add Note
                                        </Button>
                                    </form>

                                    <form onSubmit={onSubmitSafety} className="space-y-3 border-t pt-4">
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <label className="flex items-center gap-2 rounded-lg border p-3 text-sm">
                                                <Checkbox checked={conversationSafetyForm.data.opted_out} onCheckedChange={(value) => conversationSafetyForm.setData('opted_out', Boolean(value))} />
                                                Opted out
                                            </label>
                                            <label className="flex items-center gap-2 rounded-lg border p-3 text-sm">
                                                <Checkbox checked={conversationSafetyForm.data.blocked} onCheckedChange={(value) => conversationSafetyForm.setData('blocked', Boolean(value))} />
                                                Blocked
                                            </label>
                                        </div>
                                        <select value={conversationSafetyForm.data.status} onChange={(event) => conversationSafetyForm.setData('status', event.target.value)} className="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                            <option value="open">Open</option>
                                            <option value="pending">Pending</option>
                                            <option value="resolved">Resolved</option>
                                            <option value="closed">Closed</option>
                                        </select>
                                        <div className="flex flex-wrap gap-2">
                                            <Button type="submit" variant="outline" disabled={conversationSafetyForm.processing}>
                                                <Save className="h-4 w-4" />
                                                Save
                                            </Button>
                                            <Button type="button" variant="outline" onClick={onHandover}>
                                                <Clock className="h-4 w-4" />
                                                Handover
                                            </Button>
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

function BotFlowsPanel({ botMenu, botKeywords, packages }: { botMenu: string[]; botKeywords: string[]; packages: PackageOption[] }) {
    return (
        <div className="grid gap-5 lg:grid-cols-3">
            <InfoBlock title="Customer menu" icon={<Bot className="h-4 w-4" />}>
                {botMenu.map((item) => <InfoRow key={item} label={item.split('.')[0]} value={item.replace(/^\d+\.\s*/, '')} />)}
            </InfoBlock>
            <InfoBlock title="Plan check keywords" icon={<MessageCircle className="h-4 w-4" />}>
                <div className="flex flex-wrap gap-2">
                    {botKeywords.map((keyword) => <Badge key={keyword} variant="secondary">{keyword}</Badge>)}
                </div>
            </InfoBlock>
            <InfoBlock title="Package source" icon={<WalletCards className="h-4 w-4" />}>
                {packages.slice(0, 8).map((item) => <InfoRow key={item.id} label={item.name} value={money('KES', item.price)} />)}
                {packages.length === 0 && <Empty label="No packages available" />}
            </InfoBlock>
        </div>
    );
}

function PaymentRequestsPanel({
    paymentRequests,
    selectedConversation,
    packages,
    paymentForm,
    confirmPaymentForm,
    onSubmitPaymentRequest,
    onConfirmPayment,
}: {
    paymentRequests: PaymentRequest[];
    selectedConversation?: Conversation | null;
    packages: PackageOption[];
    paymentForm: any;
    confirmPaymentForm: any;
    onSubmitPaymentRequest: (event: FormEvent) => void;
    onConfirmPayment: (payment: PaymentRequest) => void;
}) {
    return (
        <div className="space-y-5">
            <Card className="rounded-lg">
                <CardHeader className="border-b py-4">
                    <CardTitle className="flex items-center gap-2 text-base"><WalletCards className="h-4 w-4" />Create WhatsApp payment request</CardTitle>
                </CardHeader>
                <CardContent className="p-4">
                    <form onSubmit={onSubmitPaymentRequest} className="grid gap-4 md:grid-cols-4">
                        <div className="space-y-2 md:col-span-2">
                            <Label>Conversation</Label>
                            <Input value={selectedConversation ? `${selectedConversation.customer_name} · ${selectedConversation.phone}` : 'Select a conversation in Inbox'} readOnly />
                        </div>
                        <div className="space-y-2">
                            <Label>Package</Label>
                            <select value={paymentForm.data.internet_package_id} onChange={(event) => paymentForm.setData('internet_package_id', event.target.value)} className="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="">Current plan</option>
                                {packages.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Method</Label>
                            <select value={paymentForm.data.method} onChange={(event) => paymentForm.setData('method', event.target.value)} className="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="mpesa">M-Pesa STK push</option>
                                <option value="instructions">Payment instructions</option>
                            </select>
                        </div>
                        <div className="md:col-span-4">
                            <Button type="submit" disabled={paymentForm.processing || !selectedConversation}>
                                <Send className="h-4 w-4" />
                                Create Request
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <DataTable
                columns={['Customer', 'Phone', 'Plan', 'Amount', 'Method', 'Status', 'Payment Center', 'Requested', 'Action']}
                rows={paymentRequests.map((payment) => [
                    payment.customer,
                    payment.phone,
                    payment.plan_name || 'Current plan',
                    money(payment.currency, payment.amount),
                    payment.method,
                    <StatusBadge value={payment.status} />,
                    payment.payment_center_record_id || 'Not linked',
                    payment.requested_at || '',
                    <div className="flex items-center gap-2" key={payment.id}>
                        <Input className="h-8 w-32" placeholder="Receipt" value={confirmPaymentForm.data.receipt_code} onChange={(event) => confirmPaymentForm.setData('receipt_code', event.target.value)} />
                        <Button type="button" size="sm" variant="outline" onClick={() => onConfirmPayment(payment)}>Confirm</Button>
                    </div>,
                ])}
                empty="No WhatsApp payment requests"
            />
        </div>
    );
}

function ReceiptsPanel({ receipts }: { receipts: ReceiptRow[] }) {
    return (
        <DataTable
            columns={['Receipt', 'Phone', 'Amount', 'Status', 'Sent At', 'Created']}
            rows={receipts.map((receipt) => [receipt.receipt_code, receipt.phone, money(receipt.currency, receipt.amount), <StatusBadge value={receipt.status} />, receipt.sent_at || 'Not sent', receipt.created_at || ''])}
            empty="No WhatsApp receipts"
        />
    );
}

function TicketsPanel({ tickets }: { tickets: TicketRow[] }) {
    return (
        <DataTable
            columns={['Ticket', 'Phone', 'Subject', 'Priority', 'Status', 'Created']}
            rows={tickets.map((ticket) => [ticket.ticket_number, ticket.phone, ticket.subject, ticket.priority, <StatusBadge value={ticket.status} />, ticket.created_at || ''])}
            empty="No WhatsApp support tickets"
        />
    );
}

function BroadcastsPanel({
    broadcasts,
    templates,
    customers,
    form,
    confirmForm,
    onSubmit,
    onConfirm,
}: {
    broadcasts: BroadcastRow[];
    templates: Template[];
    customers: CustomerOption[];
    form: any;
    confirmForm: any;
    onSubmit: (event: FormEvent) => void;
    onConfirm: (broadcast: BroadcastRow) => void;
}) {
    return (
        <div className="space-y-5">
            <Card className="rounded-lg">
                <CardHeader className="border-b py-4">
                    <CardTitle className="flex items-center gap-2 text-base"><Megaphone className="h-4 w-4" />Prepare broadcast</CardTitle>
                </CardHeader>
                <CardContent className="p-4">
                    <form onSubmit={onSubmit} className="grid gap-4 lg:grid-cols-4">
                        <div className="space-y-2">
                            <Label>Name</Label>
                            <Input value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                            <InputError message={form.errors.name} />
                        </div>
                        <div className="space-y-2">
                            <Label>Template</Label>
                            <select value={form.data.template_id} onChange={(event) => form.setData('template_id', event.target.value)} className="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="">Select template</option>
                                {templates.filter((template) => template.category === 'broadcast' && template.status === 'approved').map((template) => <option key={template.id} value={template.id}>{template.name}</option>)}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Audience</Label>
                            <select value={form.data.audience} onChange={(event) => form.setData('audience', event.target.value)} className="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="specific">Specific customers</option>
                                <option value="active_customers">Active customers</option>
                                <option value="expired_customers">Expired customers</option>
                                <option value="all_customers">All customers</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Customers</Label>
                            <select multiple value={form.data.customer_ids.map(String)} onChange={(event) => form.setData('customer_ids', Array.from(event.currentTarget.selectedOptions).map((option) => Number(option.value)))} className="h-24 w-full rounded-md border bg-background px-3 py-2 text-sm">
                                {customers.map((customer) => <option key={customer.id} value={customer.id}>{customer.name} · {customer.phone}</option>)}
                            </select>
                        </div>
                        <div className="lg:col-span-4">
                            <Button type="submit" disabled={form.processing}>
                                <Save className="h-4 w-4" />
                                Save for Confirmation
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <DataTable
                columns={['Name', 'Template', 'Audience', 'Recipients', 'Status', 'Created', 'Confirm']}
                rows={broadcasts.map((broadcast) => [
                    broadcast.name,
                    broadcast.template?.name || 'Template missing',
                    humanize(broadcast.audience),
                    String(broadcast.recipient_count),
                    <StatusBadge value={broadcast.status} />,
                    broadcast.created_at || '',
                    broadcast.status === 'pending_confirmation'
                        ? <div className="flex items-center gap-2" key={broadcast.id}>
                            <Checkbox checked={confirmForm.data.confirm_broadcast} onCheckedChange={(value) => confirmForm.setData('confirm_broadcast', Boolean(value))} />
                            <Button type="button" size="sm" variant="outline" onClick={() => onConfirm(broadcast)} disabled={!confirmForm.data.confirm_broadcast}>Confirm</Button>
                        </div>
                        : broadcast.confirmed_at || 'Confirmed',
                ])}
                empty="No broadcasts prepared"
            />
        </div>
    );
}

function TemplatesPanel({
    templates,
    variables,
    categories,
    form,
    isPlatform,
    onSubmit,
}: {
    templates: Template[];
    variables: string[];
    categories: string[];
    form: any;
    isPlatform: boolean;
    onSubmit: (event: FormEvent) => void;
}) {
    return (
        <div className="grid gap-5 xl:grid-cols-[420px_minmax(0,1fr)]">
            <Card className="rounded-lg">
                <CardHeader className="border-b py-4">
                    <CardTitle className="flex items-center gap-2 text-base"><Tags className="h-4 w-4" />Template</CardTitle>
                </CardHeader>
                <CardContent className="p-4">
                    <form onSubmit={onSubmit} className="space-y-3">
                        {isPlatform && (
                            <div className="space-y-2">
                                <Label>Scope</Label>
                                <select value={form.data.scope} onChange={(event) => form.setData('scope', event.target.value)} className="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                    <option value="platform">Platform template</option>
                                    <option value="isp">Workspace template</option>
                                </select>
                            </div>
                        )}
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-2"><Label>Name</Label><Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} /></div>
                            <div className="space-y-2"><Label>Key</Label><Input value={form.data.key} onChange={(e) => form.setData('key', e.target.value)} /></div>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Category</Label>
                                <select value={form.data.category} onChange={(e) => form.setData('category', e.target.value)} className="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                    {categories.map((category) => <option key={category} value={category}>{humanize(category)}</option>)}
                                </select>
                            </div>
                            <div className="space-y-2"><Label>Language</Label><Input value={form.data.language} onChange={(e) => form.setData('language', e.target.value)} /></div>
                        </div>
                        <div className="space-y-2"><Label>Provider template name</Label><Input value={form.data.provider_template_name} onChange={(e) => form.setData('provider_template_name', e.target.value)} /></div>
                        <div className="space-y-2"><Label>Body</Label><Textarea rows={6} value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} /></div>
                        <div className="flex flex-wrap gap-2">
                            {variables.map((variable) => {
                                const checked = form.data.variables.includes(variable);
                                return (
                                    <label key={variable} className="flex items-center gap-2 rounded-md border px-2 py-1 text-xs">
                                        <Checkbox checked={checked} onCheckedChange={(value) => form.setData('variables', value ? [...form.data.variables, variable] : form.data.variables.filter((item: string) => item !== variable))} />
                                        {`{{${variable}}}`}
                                    </label>
                                );
                            })}
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} className="h-10 rounded-md border bg-background px-3 text-sm">
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <label className="flex items-center gap-2 rounded-md border px-3 text-sm">
                                <Checkbox checked={form.data.enabled} onCheckedChange={(value) => form.setData('enabled', Boolean(value))} />
                                Enabled
                            </label>
                        </div>
                        <Button type="submit" disabled={form.processing}>
                            <Save className="h-4 w-4" />
                            Save Template
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <DataTable
                columns={['Name', 'Category', 'Scope', 'Status', 'Variables', 'Body']}
                rows={templates.map((template) => [template.name, humanize(template.category), template.scope, <StatusBadge value={template.status} />, template.variables.join(', '), <span className="line-clamp-2" key={template.id}>{template.body}</span>])}
                empty="No WhatsApp templates"
            />
        </div>
    );
}

function UsagePanel({ usageLogs, setting }: { usageLogs: LogRow[]; setting?: Setting | null }) {
    return (
        <div className="space-y-5">
            <div className="grid gap-4 md:grid-cols-4">
                <Metric label="Balance" value={String(setting?.whatsapp_balance ?? 0)} icon={<WalletCards className="h-4 w-4" />} />
                <Metric label="Messages sent" value={String(setting?.messages_sent ?? 0)} icon={<Send className="h-4 w-4" />} />
                <Metric label="Messages failed" value={String(setting?.messages_failed ?? 0)} icon={<AlertTriangle className="h-4 w-4" />} />
                <Metric label="Cost/message" value={String(setting?.estimated_cost_per_message ?? 0)} icon={<Receipt className="h-4 w-4" />} />
            </div>
            <DataTable
                columns={['Phone', 'Type', 'Mode', 'Provider', 'Cost', 'Status', 'Error', 'Sent At']}
                rows={usageLogs.map((log) => [log.phone, log.message_type, log.provider_mode, log.provider || '', Number(log.cost).toFixed(2), <StatusBadge value={log.status} />, log.error_message || '', log.sent_at || ''])}
                empty="No usage logs"
            />
        </div>
    );
}

function ApiSettingsPanel({
    form,
    providerOptions,
    setting,
    platformSetting,
    isPlatform,
    onSubmit,
    onTest,
}: {
    form: any;
    providerOptions: ProviderOption[];
    setting?: Setting | null;
    platformSetting?: Setting | null;
    isPlatform: boolean;
    onSubmit: (event: FormEvent) => void;
    onTest: () => void;
}) {
    const ownApi = form.data.provider_mode === 'own_api';

    return (
        <form onSubmit={onSubmit} className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <Card className="rounded-lg">
                <CardHeader className="border-b py-4">
                    <CardTitle className="flex items-center gap-2 text-base"><ShieldCheck className="h-4 w-4" />Provider / API mode</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4 p-4">
                    {isPlatform && (
                        <div className="space-y-2">
                            <Label>Scope</Label>
                            <select value={form.data.scope} onChange={(event) => form.setData('scope', event.target.value)} className="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="platform">Platform credentials</option>
                                <option value="isp">Workspace settings</option>
                            </select>
                        </div>
                    )}
                    <div className="grid gap-4 md:grid-cols-2">
                        <label className="rounded-lg border p-4">
                            <div className="flex items-center gap-2">
                                <input type="radio" checked={form.data.provider_mode === 'platform'} onChange={() => form.setData('provider_mode', 'platform')} />
                                <span className="font-medium">Use Platform WhatsApp API</span>
                            </div>
                            <p className="mt-2 text-sm text-muted-foreground">No admin provider keys. Usage reduces WhatsApp balance.</p>
                        </label>
                        <label className="rounded-lg border p-4">
                            <div className="flex items-center gap-2">
                                <input type="radio" checked={ownApi} onChange={() => form.setData('provider_mode', 'own_api')} />
                                <span className="font-medium">Use Own WhatsApp API</span>
                            </div>
                            <p className="mt-2 text-sm text-muted-foreground">Admin provider credentials. Platform balance is not charged.</p>
                        </label>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Provider</Label>
                            <select value={ownApi ? form.data.provider : 'platform'} onChange={(event) => form.setData('provider', event.target.value)} disabled={!ownApi && form.data.scope !== 'platform'} className="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                {providerOptions.filter((provider) => ownApi ? provider.value !== 'platform' : true).map((provider) => <option key={provider.value} value={provider.value}>{provider.label}</option>)}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Business phone</Label>
                            <Input value={form.data.business_phone} onChange={(event) => form.setData('business_phone', event.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Phone number ID</Label>
                            <Input value={form.data.phone_number_id} onChange={(event) => form.setData('phone_number_id', event.target.value)} disabled={!ownApi && form.data.scope !== 'platform'} />
                        </div>
                        <div className="space-y-2">
                            <Label>WABA ID</Label>
                            <Input value={form.data.waba_id} onChange={(event) => form.setData('waba_id', event.target.value)} disabled={!ownApi && form.data.scope !== 'platform'} />
                        </div>
                        <div className="space-y-2">
                            <Label>Sender name</Label>
                            <Input value={form.data.sender_name} onChange={(event) => form.setData('sender_name', event.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Custom HTTP URL</Label>
                            <Input value={form.data.api_base_url} onChange={(event) => form.setData('api_base_url', event.target.value)} disabled={form.data.provider !== 'custom_http' && form.data.provider !== 'other'} />
                        </div>
                    </div>

                    {(ownApi || form.data.scope === 'platform') && (
                        <div className="grid gap-4 border-t pt-4 md:grid-cols-2">
                            {Object.keys(form.data.credentials).map((key) => (
                                <div key={key} className="space-y-2">
                                    <Label>{humanize(key)}</Label>
                                    <Input
                                        type={key.includes('secret') || key.includes('token') || key.includes('password') ? 'password' : 'text'}
                                        value={form.data.credentials[key]}
                                        placeholder={(setting?.credentials_saved || platformSetting?.credentials_saved || []).includes(key) ? 'Saved and hidden' : ''}
                                        onChange={(event) => form.setData('credentials', { ...form.data.credentials, [key]: event.target.value })}
                                    />
                                </div>
                            ))}
                        </div>
                    )}

                    <div className="grid gap-4 border-t pt-4 md:grid-cols-3">
                        <div className="space-y-2"><Label>Reply window minutes</Label><Input type="number" min="1" value={form.data.reply_window_minutes} onChange={(e) => form.setData('reply_window_minutes', Number(e.target.value))} /></div>
                        <div className="space-y-2"><Label>Balance</Label><Input type="number" min="0" step="0.01" value={form.data.whatsapp_balance} onChange={(e) => form.setData('whatsapp_balance', e.target.value)} /></div>
                        <div className="space-y-2"><Label>Cost/message</Label><Input type="number" min="0" step="0.01" value={form.data.estimated_cost_per_message} onChange={(e) => form.setData('estimated_cost_per_message', e.target.value)} /></div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" disabled={form.processing}>
                            <Save className="h-4 w-4" />
                            Save Settings
                        </Button>
                        <Button type="button" variant="outline" onClick={onTest}>
                            <ShieldCheck className="h-4 w-4" />
                            Test Connection
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <InfoBlock title="Connection status" icon={<Settings className="h-4 w-4" />}>
                <InfoRow label="Test status" value={setting?.last_test_status || 'Not tested'} />
                <InfoRow label="Test message" value={setting?.last_test_message || 'Save settings, then test'} />
                <InfoRow label="Last tested" value={setting?.last_tested_at || 'Never'} />
                <InfoRow label="Tokens" value={(setting?.credentials_saved || []).length ? 'Saved and hidden' : 'Not saved'} />
            </InfoBlock>
        </form>
    );
}

function SettingsPanel({ form, setting, onSubmit }: { form: any; setting?: Setting | null; onSubmit: (event: FormEvent) => void }) {
    return (
        <form onSubmit={onSubmit} className="grid gap-5 md:grid-cols-2">
            <InfoBlock title="Admin reply rules" icon={<Clock className="h-4 w-4" />}>
                <div className="space-y-2">
                    <Label>Reply window minutes</Label>
                    <Input type="number" min="1" value={form.data.reply_window_minutes} onChange={(event) => form.setData('reply_window_minutes', Number(event.target.value))} />
                </div>
                <div className="mt-4 flex flex-wrap gap-2">
                    <label className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"><Checkbox checked={form.data.is_active} onCheckedChange={(value) => form.setData('is_active', Boolean(value))} />WhatsApp active</label>
                    <label className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"><Checkbox checked={form.data.billing_enabled} onCheckedChange={(value) => form.setData('billing_enabled', Boolean(value))} />Billing enabled</label>
                </div>
                <Button type="submit" className="mt-4" disabled={form.processing}><Save className="h-4 w-4" />Save Rules</Button>
            </InfoBlock>
            <InfoBlock title="Current settings" icon={<FileText className="h-4 w-4" />}>
                <InfoRow label="Reply window" value={`${setting?.reply_window_minutes || 120} minutes`} />
                <InfoRow label="Provider mode" value={setting?.provider_mode || 'platform'} />
                <InfoRow label="Billing status" value={setting?.billing_status || 'active'} />
                <InfoRow label="Top-up status" value={setting?.topup_payment_status || 'not_started'} />
            </InfoBlock>
        </form>
    );
}

function LogsPanel({ logs }: { logs: LogRow[] }) {
    return (
        <DataTable
            columns={['Phone', 'Direction', 'Type', 'Mode', 'Provider', 'Cost', 'Status', 'Error', 'At']}
            rows={logs.map((log) => [log.phone, log.direction || '', log.message_type, log.provider_mode, log.provider || '', Number(log.cost).toFixed(2), <StatusBadge value={log.status} />, log.error_message || '', log.sent_at || ''])}
            empty="No WhatsApp logs"
        />
    );
}

function InfoBlock({ title, icon, children }: { title: string; icon: ReactNode; children: ReactNode }) {
    return (
        <Card className="rounded-lg">
            <CardHeader className="border-b py-4">
                <CardTitle className="flex items-center gap-2 text-base">{icon}{title}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 p-4">{children}</CardContent>
        </Card>
    );
}

function InfoRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4 rounded-md border bg-background px-3 py-2 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium text-foreground">{value}</span>
        </div>
    );
}

function DataTable({ columns, rows, empty }: { columns: string[]; rows: ReactNode[][]; empty: string }) {
    return (
        <div className="overflow-hidden rounded-lg border bg-card">
            {rows.length === 0 ? (
                <Empty label={empty} />
            ) : (
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-border text-sm">
                        <thead className="bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>{columns.map((column) => <th key={column} className="px-4 py-3">{column}</th>)}</tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {rows.map((row, index) => (
                                <tr key={index} className="hover:bg-muted/30">
                                    {row.map((cell, cellIndex) => <td key={cellIndex} className="max-w-[360px] px-4 py-3 align-top">{cell}</td>)}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

function Empty({ label }: { label: string }) {
    return <div className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">{label}</div>;
}

function formatCountdown(seconds: number) {
    if (seconds <= 0) {
        return 'Expired';
    }

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;

    return [hours, minutes, remainingSeconds].map((value) => String(value).padStart(2, '0')).join(':');
}
