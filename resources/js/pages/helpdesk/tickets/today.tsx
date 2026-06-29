import { useMemo, useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog } from '@/components/ui/dialog';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Edit from './edit';
import { HelpdeskTicket, TodayTicketsProps, HelpdeskTicketModalState } from './types';
import { AlertTriangle, CheckCircle2, Clock3, Edit as EditIcon, Eye, Filter, Headphones, MessageSquare, PlayCircle, Search, Trash2, UserRound } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

type PriorityKey = 'low' | 'medium' | 'high' | 'urgent';

const priorityMeta: Record<PriorityKey, { label: string; ring: string; badge: string; rail: string }> = {
    urgent: {
        label: 'Urgent',
        ring: 'border-red-200 bg-red-50',
        badge: 'border-red-200 bg-red-100 text-red-700',
        rail: 'bg-red-500',
    },
    high: {
        label: 'High',
        ring: 'border-orange-200 bg-orange-50',
        badge: 'border-orange-200 bg-orange-100 text-orange-700',
        rail: 'bg-orange-500',
    },
    medium: {
        label: 'Medium',
        ring: 'border-yellow-200 bg-yellow-50',
        badge: 'border-yellow-200 bg-yellow-100 text-yellow-700',
        rail: 'bg-yellow-500',
    },
    low: {
        label: 'Low',
        ring: 'border-emerald-200 bg-emerald-50',
        badge: 'border-emerald-200 bg-emerald-100 text-emerald-700',
        rail: 'bg-emerald-500',
    },
};

export default function Today() {
    const { t } = useTranslation();
    const { tickets, stats, auth } = usePage<TodayTicketsProps>().props;

    const [searchQuery, setSearchQuery] = useState('');
    const [activePriorityFilter, setActivePriorityFilter] = useState<string>('all');
    const [modalState, setModalState] = useState<HelpdeskTicketModalState>({ isOpen: false, mode: '', data: null });

    useFlashMessages();

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'helpdesk-tickets.destroy',
        defaultMessage: t('Are you sure you want to delete this ticket?'),
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('helpdesk-tickets.today'), { search: searchQuery }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearSearch = () => {
        setSearchQuery('');
        router.get(route('helpdesk-tickets.today'));
    };

    const openModal = (mode: 'edit', data: HelpdeskTicket | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const handleQuickStatusChange = (ticket: HelpdeskTicket, newStatus: string) => {
        router.put(route('helpdesk-tickets.update', ticket.id), {
            title: ticket.title,
            description: ticket.description,
            status: newStatus,
            priority: ticket.priority,
            category_id: ticket.category_id,
        }, {
            preserveState: true,
            onSuccess: () => router.reload({ only: ['tickets', 'stats'] }),
        });
    };

    const getTimeAgo = (dateString: string) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffInMs = now.getTime() - date.getTime();
        const diffInMinutes = Math.floor(diffInMs / 60000);
        const diffInHours = Math.floor(diffInMinutes / 60);
        const diffInDays = Math.floor(diffInHours / 24);

        if (diffInMinutes < 1) return t('Just now');
        if (diffInMinutes < 60) return `${diffInMinutes}m ${t('ago')}`;
        if (diffInHours < 24) return `${diffInHours}h ${t('ago')}`;
        return `${diffInDays}d ${t('ago')}`;
    };

    const filteredTickets = useMemo(() => {
        let result = tickets;
        const query = searchQuery.toLowerCase().trim();

        if (query) {
            result = result.filter((ticket) =>
                ticket.ticket_id.toLowerCase().includes(query) ||
                ticket.title.toLowerCase().includes(query) ||
                ticket.description?.toLowerCase().includes(query)
            );
        }

        if (activePriorityFilter !== 'all') {
            result = result.filter((ticket) => ticket.priority === activePriorityFilter);
        }

        return result;
    }, [tickets, searchQuery, activePriorityFilter]);

    const groupedTickets = useMemo(() => ({
        urgent: filteredTickets.filter((ticket) => ticket.priority === 'urgent'),
        high: filteredTickets.filter((ticket) => ticket.priority === 'high'),
        medium: filteredTickets.filter((ticket) => ticket.priority === 'medium'),
        low: filteredTickets.filter((ticket) => ticket.priority === 'low'),
    }), [filteredTickets]);

    const renderTicketCard = (ticket: HelpdeskTicket) => {
        const meta = priorityMeta[ticket.priority as PriorityKey] || priorityMeta.medium;
        const replyCount = ticket.replies?.length || 0;
        const isOld = Math.floor((new Date().getTime() - new Date(ticket.created_at).getTime()) / 3600000) > 24;
        const lastReply = replyCount > 0 ? ticket.replies?.[replyCount - 1] : null;

        return (
            <div key={ticket.id} className="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
                <div className={`absolute left-0 top-0 h-full w-1.5 ${meta.rail}`} />
                <div className="pl-2">
                    <div className="mb-3 flex items-start justify-between gap-3">
                        <div className="min-w-0">
                            <button
                                type="button"
                                onClick={() => router.get(route('helpdesk-tickets.show', ticket.id))}
                                className="font-mono text-sm font-bold text-emerald-700 hover:text-emerald-800"
                            >
                                #{ticket.ticket_id}
                            </button>
                            <h3 className="mt-1 line-clamp-2 font-semibold text-slate-950" title={ticket.title}>{ticket.title}</h3>
                        </div>
                        <span className={`shrink-0 rounded-full border px-2.5 py-1 text-xs font-bold ${meta.badge}`}>
                            {t(meta.label)}
                        </span>
                    </div>

                    <div className="mb-4 space-y-2 text-xs text-slate-600">
                        <div className="flex flex-wrap items-center gap-2">
                            {ticket.category && (
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-1 font-medium">
                                    {ticket.category.color && <span className="h-2 w-2 rounded-full" style={{ backgroundColor: ticket.category.color }} />}
                                    {ticket.category.name}
                                </span>
                            )}
                            <span className="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 font-medium">
                                <UserRound className="h-3.5 w-3.5" />
                                {ticket.creator?.name || t('Unknown')}
                            </span>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium ${isOld ? 'bg-red-50 text-red-700' : 'bg-slate-50 text-slate-600'}`}>
                                <Clock3 className="h-3.5 w-3.5" />
                                {t('Created')}: {getTimeAgo(ticket.created_at)}
                            </span>
                            <span className="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 font-medium">
                                <MessageSquare className="h-3.5 w-3.5" />
                                {replyCount} {t('replies')}
                            </span>
                        </div>
                        {lastReply && (
                            <p className="truncate rounded-xl bg-slate-50 px-3 py-2 text-slate-500">
                                {t('Last reply')}: {lastReply.creator?.name || t('Unknown')} · {getTimeAgo(lastReply.created_at)}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                        {auth.user?.permissions?.includes('view-helpdesk-tickets') && (
                            <Button variant="outline" size="sm" onClick={() => router.get(route('helpdesk-tickets.show', ticket.id))} className="rounded-xl">
                                <MessageSquare className="mr-2 h-4 w-4" />
                                {t('Reply')}
                            </Button>
                        )}
                        {auth.user?.permissions?.includes('edit-helpdesk-tickets') && ticket.status === 'open' && (
                            <Button variant="outline" size="sm" onClick={() => handleQuickStatusChange(ticket, 'in_progress')} className="rounded-xl border-amber-200 text-amber-700 hover:bg-amber-50">
                                <PlayCircle className="mr-2 h-4 w-4" />
                                {t('Start')}
                            </Button>
                        )}
                        {auth.user?.permissions?.includes('edit-helpdesk-tickets') && ticket.status === 'in_progress' && (
                            <Button variant="outline" size="sm" onClick={() => handleQuickStatusChange(ticket, 'resolved')} className="rounded-xl border-emerald-200 text-emerald-700 hover:bg-emerald-50">
                                <CheckCircle2 className="mr-2 h-4 w-4" />
                                {t('Resolve')}
                            </Button>
                        )}
                        <div className="ml-auto flex items-center gap-1">
                            <TooltipProvider>
                                {auth.user?.permissions?.includes('view-helpdesk-tickets') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button variant="ghost" size="sm" onClick={() => router.get(route('helpdesk-tickets.show', ticket.id))} className="h-8 w-8 rounded-xl p-0 text-slate-500 hover:text-emerald-700">
                                                <Eye className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>{t('View')}</p></TooltipContent>
                                    </Tooltip>
                                )}
                                {auth.user?.permissions?.includes('edit-helpdesk-tickets') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button variant="ghost" size="sm" onClick={() => openModal('edit', ticket)} className="h-8 w-8 rounded-xl p-0 text-slate-500 hover:text-emerald-700">
                                                <EditIcon className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>{t('Edit')}</p></TooltipContent>
                                    </Tooltip>
                                )}
                                {auth.user?.permissions?.includes('delete-helpdesk-tickets') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button variant="ghost" size="sm" onClick={() => openDeleteDialog(ticket.id)} className="h-8 w-8 rounded-xl p-0 text-slate-400 hover:text-destructive">
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>{t('Delete')}</p></TooltipContent>
                                    </Tooltip>
                                )}
                            </TooltipProvider>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const renderPriorityColumn = (priority: PriorityKey, priorityTickets: HelpdeskTicket[]) => {
        const meta = priorityMeta[priority];
        return (
            <div key={priority} className={`rounded-3xl border p-4 ${meta.ring}`}>
                <div className="mb-4 flex items-center justify-between">
                    <div>
                        <h2 className="text-sm font-black uppercase tracking-[0.18em] text-slate-900">{t(meta.label)}</h2>
                        <p className="mt-1 text-xs text-slate-500">{priorityTickets.length} {t('tickets')}</p>
                    </div>
                    <span className={`h-3 w-3 rounded-full ${meta.rail}`} />
                </div>
                <div className="space-y-3">
                    {priorityTickets.length > 0 ? priorityTickets.map(renderTicketCard) : (
                        <div className="rounded-2xl border border-dashed border-slate-200 bg-white/70 p-6 text-center text-sm text-slate-500">
                            {t('No tickets in this lane')}
                        </div>
                    )}
                </div>
            </div>
        );
    };

    const statCards = [
        { label: t('Total'), value: stats.total, helper: t('active support queue'), icon: Headphones, tone: 'bg-primary/10 text-primary' },
        { label: t('Urgent'), value: stats.urgent, helper: t('needs fast action'), icon: AlertTriangle, tone: 'bg-red-50 text-red-700' },
        { label: t('Open'), value: stats.open, helper: t('waiting response'), icon: Clock3, tone: 'bg-sky-50 text-sky-700' },
        { label: t('In Progress'), value: stats.in_progress, helper: t('being handled'), icon: PlayCircle, tone: 'bg-amber-50 text-amber-700' },
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Helpdesk') }, { label: t('Today\'s Tickets') }]}
            pageTitle={t('Today\'s Tickets')}
        >
            <Head title={t('Today\'s Tickets')} />

            <div className="space-y-6">
                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-700">{t('Helpdesk — Today')}</p>
                            <h1 className="mt-2 text-3xl font-black tracking-tight text-slate-950">{t('Response board')}</h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                {t('A priority-based board for tickets that need attention today.')}
                            </p>
                        </div>
                        <form onSubmit={handleSearch} className="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <Select value={activePriorityFilter} onValueChange={(value) => setActivePriorityFilter(value)}>
                                <SelectTrigger className="rounded-xl bg-white sm:w-[170px]">
                                    <div className="flex items-center"><Filter className="mr-2 h-4 w-4" /> <SelectValue /></div>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('All')} ({stats.total})</SelectItem>
                                    <SelectItem value="urgent">{t('Urgent')} ({stats.urgent})</SelectItem>
                                    <SelectItem value="high">{t('High')} ({stats.high})</SelectItem>
                                    <SelectItem value="medium">{t('Medium')} ({stats.medium})</SelectItem>
                                    <SelectItem value="low">{t('Low')} ({stats.low})</SelectItem>
                                </SelectContent>
                            </Select>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder={t('Search tickets...')}
                                    className="rounded-xl pl-10 sm:w-72"
                                />
                            </div>
                            {searchQuery && <Button type="button" variant="outline" onClick={clearSearch} className="rounded-xl">{t('Clear')}</Button>}
                            <Button type="submit" className="rounded-xl bg-primary text-primary-foreground hover:bg-primary/90">{t('Search')}</Button>
                        </form>
                    </div>

                    <div className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {statCards.map((item) => {
                            const Icon = item.icon;
                            return (
                                <div key={item.label} className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div className={`mb-3 flex h-10 w-10 items-center justify-center rounded-xl ${item.tone}`}>
                                        <Icon className="h-5 w-5" />
                                    </div>
                                    <p className="text-2xl font-black text-slate-950">{item.value}</p>
                                    <p className="mt-1 text-xs font-bold uppercase tracking-wide text-slate-500">{item.label}</p>
                                    <p className="mt-1 text-xs text-slate-500">{item.helper}</p>
                                </div>
                            );
                        })}
                    </div>
                </section>

                {filteredTickets.length > 0 ? (
                    <div className="grid gap-4 xl:grid-cols-4">
                        {renderPriorityColumn('urgent', groupedTickets.urgent)}
                        {renderPriorityColumn('high', groupedTickets.high)}
                        {renderPriorityColumn('medium', groupedTickets.medium)}
                        {renderPriorityColumn('low', groupedTickets.low)}
                    </div>
                ) : (
                    <Card className="rounded-3xl border-slate-200 shadow-sm">
                        <CardContent className="py-16 text-center">
                            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                <CheckCircle2 className="h-8 w-8" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-950">
                                {searchQuery ? t('No tickets found') : t('No pending tickets')}
                            </h3>
                            <p className="mt-2 text-sm text-slate-500">
                                {searchQuery ? t('Try adjusting your search query') : t('All tickets are resolved or closed. Great job!')}
                            </p>
                            {searchQuery && <Button variant="outline" onClick={clearSearch} className="mt-4 rounded-xl">{t('Clear Search')}</Button>}
                        </CardContent>
                    </Card>
                )}
            </div>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'edit' && modalState.data && <Edit ticket={modalState.data} onSuccess={closeModal} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Ticket')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
