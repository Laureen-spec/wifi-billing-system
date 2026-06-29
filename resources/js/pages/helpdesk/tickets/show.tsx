import { useState, useEffect, useRef } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import ChatMessage from '../components/ChatMessage';
import ReplyForm from '../components/ReplyForm';
import { formatDate } from '@/utils/helpers';
import { ShowHelpdeskTicketProps, HelpdeskReply } from './types';
import { CalendarClock, CheckCircle2, Clock3, Headphones, MessageSquare, ShieldCheck, Tag, UserRound } from 'lucide-react';

type StatusKey = 'open' | 'in_progress' | 'resolved' | 'closed';
type PriorityKey = 'low' | 'medium' | 'high' | 'urgent';

const statusStyles: Record<StatusKey, string> = {
    open: 'border-sky-200 bg-sky-50 text-sky-700',
    in_progress: 'border-amber-200 bg-amber-50 text-amber-700',
    resolved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    closed: 'border-slate-200 bg-slate-50 text-slate-600',
};

const priorityStyles: Record<PriorityKey, string> = {
    low: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    medium: 'border-yellow-200 bg-yellow-50 text-yellow-700',
    high: 'border-orange-200 bg-orange-50 text-orange-700',
    urgent: 'border-red-200 bg-red-50 text-red-700',
};

export default function Show() {
    const { ticket, auth } = usePage<ShowHelpdeskTicketProps>().props;
    const { t } = useTranslation();
    const [replies, setReplies] = useState<HelpdeskReply[]>(ticket.replies || []);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const [deleteDialog, setDeleteDialog] = useState({ isOpen: false, replyId: null as number | null });

    useFlashMessages();

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [replies]);

    const handleReplyAdded = (newReply: HelpdeskReply) => {
        setReplies((prev) => [...prev, newReply]);
    };

    const handleDeleteReply = (replyId: number) => {
        setDeleteDialog({ isOpen: true, replyId });
    };

    const confirmDeleteReply = async () => {
        if (!deleteDialog.replyId) return;

        try {
            const response = await fetch(route('helpdesk-replies.destroy', deleteDialog.replyId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                setReplies((prev) => prev.filter((reply) => reply.id !== deleteDialog.replyId));
                setDeleteDialog({ isOpen: false, replyId: null });
                router.reload({ only: [] });
            }
        } catch (error) {
            console.error('Error deleting reply:', error);
        }
    };

    const formatStatus = (status: string) => t(status.replace('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()));
    const formatPriority = (priority: string) => t(priority.charAt(0).toUpperCase() + priority.slice(1));

    const getStatusBadge = (status: string) => (
        <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold ${statusStyles[status as StatusKey] || statusStyles.open}`}>
            {formatStatus(status)}
        </span>
    );

    const getPriorityBadge = (priority: string) => (
        <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold ${priorityStyles[priority as PriorityKey] || priorityStyles.medium}`}>
            {formatPriority(priority)}
        </span>
    );

    const visibleReplies = replies.filter((reply) => !reply.is_internal || auth.user?.type === 'superadmin');
    const isClosed = ticket.status === 'closed';

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Support Tickets'), url: route('helpdesk-tickets.index') },
                { label: `#${ticket.ticket_id}` },
            ]}
            pageTitle={`Ticket #${ticket.ticket_id}`}
        >
            <Head title={`Ticket #${ticket.ticket_id} - ${ticket.title}`} />

            <div className="space-y-6">
                <section className="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="absolute right-0 top-0 h-32 w-32 rounded-full bg-emerald-100 blur-3xl" />
                    <div className="relative flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                        <div className="min-w-0 flex-1">
                            <div className="mb-4 flex flex-wrap items-center gap-2">
                                <span className="rounded-full bg-slate-950 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-white">
                                    #{ticket.ticket_id}
                                </span>
                                {getStatusBadge(ticket.status)}
                                {getPriorityBadge(ticket.priority)}
                            </div>
                            <h1 className="max-w-5xl text-3xl font-black tracking-tight text-slate-950">{ticket.title}</h1>
                            <div className="prose prose-sm mt-4 max-w-none text-slate-600" dangerouslySetInnerHTML={{ __html: ticket.description }} />
                        </div>

                        <div className="grid w-full gap-3 sm:grid-cols-2 xl:w-[430px]">
                            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                    <UserRound className="h-5 w-5" />
                                </div>
                                <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{t('Created By')}</p>
                                <p className="mt-1 truncate font-semibold text-slate-950">{ticket.creator?.name || '-'}</p>
                            </div>
                            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                                    <CalendarClock className="h-5 w-5" />
                                </div>
                                <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{t('Created At')}</p>
                                <p className="mt-1 truncate font-semibold text-slate-950">{formatDate(ticket.created_at)}</p>
                            </div>
                            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                    <Tag className="h-5 w-5" />
                                </div>
                                <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{t('Category')}</p>
                                <p className="mt-1 truncate font-semibold text-slate-950">{ticket.category?.name || '-'}</p>
                            </div>
                            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                                    <Headphones className="h-5 w-5" />
                                </div>
                                <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{t('Assigned To')}</p>
                                <p className="mt-1 truncate font-semibold text-slate-950">{ticket.assignedTo?.name || t('Unassigned')}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <Card className="flex min-h-[620px] flex-col overflow-hidden rounded-3xl border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-200 bg-slate-50/80 px-5 py-4">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <CardTitle className="flex items-center gap-2 text-lg text-slate-950">
                                        <MessageSquare className="h-5 w-5 text-emerald-600" />
                                        {t('Conversation timeline')}
                                    </CardTitle>
                                    <p className="mt-1 text-sm text-slate-500">
                                        {visibleReplies.length} {visibleReplies.length === 1 ? t('message') : t('messages')}
                                    </p>
                                </div>
                                {isClosed ? (
                                    <span className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                                        <CheckCircle2 className="h-3.5 w-3.5" />
                                        {t('Closed ticket')}
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        <Clock3 className="h-3.5 w-3.5" />
                                        {t('Reply enabled')}
                                    </span>
                                )}
                            </div>
                        </CardHeader>

                        <CardContent className="flex-1 overflow-hidden p-0">
                            <div className="flex h-full flex-col">
                                <div className="flex-1 overflow-y-auto bg-white p-5 sm:p-6">
                                    {visibleReplies.length === 0 ? (
                                        <div className="flex h-full min-h-[360px] items-center justify-center">
                                            <div className="max-w-sm text-center">
                                                <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                                    <MessageSquare className="h-8 w-8" />
                                                </div>
                                                <h3 className="text-lg font-bold text-slate-950">{t('No messages yet')}</h3>
                                                <p className="mt-2 text-sm text-slate-500">{t('Start the conversation by sending a clear reply below.')}</p>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="space-y-5">
                                            {visibleReplies.map((reply) => (
                                                <ChatMessage
                                                    key={reply.id}
                                                    reply={reply}
                                                    isOwnMessage={reply.created_by === auth.user?.id}
                                                    onDelete={handleDeleteReply}
                                                    canDelete={auth.user?.permissions?.includes('delete-helpdesk-replies')}
                                                />
                                            ))}
                                            <div ref={messagesEndRef} />
                                        </div>
                                    )}
                                </div>

                                {auth.user?.permissions?.includes('create-helpdesk-replies') && !isClosed && (
                                    <ReplyForm ticketId={ticket.id} onReplyAdded={handleReplyAdded} />
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <aside className="space-y-4">
                        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">{t('Case summary')}</p>
                            <div className="mt-5 space-y-4">
                                <div className="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 p-3">
                                    <span className="text-sm text-slate-500">{t('Status')}</span>
                                    {getStatusBadge(ticket.status)}
                                </div>
                                <div className="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 p-3">
                                    <span className="text-sm text-slate-500">{t('Priority')}</span>
                                    {getPriorityBadge(ticket.priority)}
                                </div>
                                <div className="rounded-2xl bg-slate-50 p-3">
                                    <span className="text-sm text-slate-500">{t('Ticket ID')}</span>
                                    <p className="mt-1 font-mono font-bold text-slate-950">#{ticket.ticket_id}</p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
                            <div className="flex items-start gap-3">
                                <ShieldCheck className="mt-0.5 h-5 w-5 text-emerald-700" />
                                <div>
                                    <p className="font-semibold text-emerald-950">{t('Support note')}</p>
                                    <p className="mt-1 text-sm leading-6 text-emerald-800/80">
                                        {t('Keep replies short, confirm the customer issue, and close the ticket only after the customer is satisfied.')}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>

            <ConfirmationDialog
                open={deleteDialog.isOpen}
                onOpenChange={(open) => setDeleteDialog({ isOpen: open, replyId: null })}
                title={t('Delete Reply')}
                message={t('Are you sure you want to delete this reply?')}
                confirmText={t('Delete')}
                onConfirm={confirmDeleteReply}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
