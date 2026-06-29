import { useEffect, useRef, useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog } from '@/components/ui/dialog';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Pagination } from '@/components/ui/pagination';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Input } from '@/components/ui/input';
import {
    AlertTriangle,
    CheckCircle2,
    Clock3,
    Edit as EditIcon,
    Eye,
    Filter,
    Headphones,
    MessageSquare,
    Plus,
    Search,
    ShieldCheck,
    Sparkles,
    Ticket,
    Trash2,
    UserRound,
    X,
} from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import Create from './create';
import Edit from './edit';
import NoRecordsFound from '@/components/no-records-found';
import { HelpdeskTicket, HelpdeskTicketsIndexProps, HelpdeskTicketFilters, HelpdeskTicketModalState } from './types';

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

const priorityRail: Record<PriorityKey, string> = {
    low: 'bg-emerald-500',
    medium: 'bg-yellow-500',
    high: 'bg-orange-500',
    urgent: 'bg-red-500',
};

export default function Index() {
    const { t } = useTranslation();
    const { tickets, categories, companies, auth } = usePage<HelpdeskTicketsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<HelpdeskTicketFilters>({
        title: urlParams.get('title') || '',
        status: urlParams.get('status') || '',
        priority: urlParams.get('priority') || '',
        category_id: urlParams.get('category_id') || '',
        company_id: urlParams.get('company_id') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [modalState, setModalState] = useState<HelpdeskTicketModalState>({
        isOpen: false,
        mode: '',
        data: null,
    });
    const [showFilters, setShowFilters] = useState(false);
    const didMountSearch = useRef(false);

    useFlashMessages();

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'helpdesk-tickets.destroy',
        defaultMessage: t('Are you sure you want to delete this ticket?'),
    });

    const canCreate = auth.user?.permissions?.includes('create-helpdesk-tickets');
    const canView = auth.user?.permissions?.includes('view-helpdesk-tickets');
    const canEdit = auth.user?.permissions?.includes('edit-helpdesk-tickets');
    const canDelete = auth.user?.permissions?.includes('delete-helpdesk-tickets');

    const statusLabel = (status: string) => t(status.replace('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()));
    const priorityLabel = (priority: string) => t(priority.charAt(0).toUpperCase() + priority.slice(1));

    const applyFilters = (nextFilters: HelpdeskTicketFilters = filters) => {
        router.get(route('helpdesk-tickets.index'), { ...nextFilters, per_page: perPage }, {
            preserveState: true,
            replace: true,
        });
    };

    const setStatusFilter = (status: string) => {
        const nextFilters = { ...filters, status };
        setFilters(nextFilters);
        applyFilters(nextFilters);
    };

    const clearFilters = () => {
        const nextFilters = { title: '', status: '', priority: '', category_id: '', company_id: '' };
        setFilters(nextFilters);
        router.get(route('helpdesk-tickets.index'), { per_page: perPage });
    };

    useEffect(() => {
        if (!didMountSearch.current) {
            didMountSearch.current = true;
            return;
        }

        const timer = window.setTimeout(() => {
            applyFilters(filters);
        }, 450);

        return () => window.clearTimeout(timer);
    }, [filters.title]);

    const openModal = (mode: 'add' | 'edit', data: HelpdeskTicket | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const activeFilters = [filters.title, filters.status, filters.priority, filters.category_id, filters.company_id].filter(Boolean).length;
    const pageTickets = tickets.data || [];
    const openCount = pageTickets.filter((ticket) => ticket.status === 'open').length;
    const progressCount = pageTickets.filter((ticket) => ticket.status === 'in_progress').length;
    const resolvedCount = pageTickets.filter((ticket) => ticket.status === 'resolved').length;
    const urgentCount = pageTickets.filter((ticket) => ticket.priority === 'urgent').length;

    const statusTabs = [
        { label: t('All'), value: '', count: tickets.total },
        { label: t('Open'), value: 'open', count: openCount },
        { label: t('In Progress'), value: 'in_progress', count: progressCount },
        { label: t('Resolved'), value: 'resolved', count: resolvedCount },
        { label: t('Closed'), value: 'closed', count: pageTickets.filter((ticket) => ticket.status === 'closed').length },
    ];

    const metricCards = [
        {
            label: t('Total tickets'),
            value: tickets.total,
            helper: t('all support records'),
            icon: Ticket,
            tone: 'bg-emerald-50 text-emerald-700',
        },
        {
            label: t('Open cases'),
            value: openCount,
            helper: t('need first response'),
            icon: Clock3,
            tone: 'bg-sky-50 text-sky-700',
        },
        {
            label: t('In progress'),
            value: progressCount,
            helper: t('being handled'),
            icon: Headphones,
            tone: 'bg-amber-50 text-amber-700',
        },
        {
            label: t('Urgent'),
            value: urgentCount,
            helper: t('priority queue'),
            icon: AlertTriangle,
            tone: 'bg-red-50 text-red-700',
        },
    ];

    const renderStatusBadge = (status: string) => (
        <span className={`inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold ${statusStyles[status as StatusKey] || statusStyles.open}`}>
            {statusLabel(status)}
        </span>
    );

    const renderPriorityBadge = (priority: string) => (
        <span className={`inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold ${priorityStyles[priority as PriorityKey] || priorityStyles.medium}`}>
            {priorityLabel(priority)}
        </span>
    );

    const renderTicketCard = (ticket: HelpdeskTicket) => (
        <div
            key={ticket.id}
            className="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md"
        >
            <div className={`absolute left-0 top-0 h-full w-1.5 ${priorityRail[ticket.priority as PriorityKey] || 'bg-slate-300'}`} />
            <div className="p-5 pl-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="min-w-0 flex-1">
                        <div className="mb-3 flex flex-wrap items-center gap-2">
                            {canView ? (
                                <button
                                    type="button"
                                    onClick={() => router.get(route('helpdesk-tickets.show', ticket.id))}
                                    className="font-mono text-sm font-bold text-emerald-700 hover:text-emerald-800"
                                >
                                    #{ticket.ticket_id}
                                </button>
                            ) : (
                                <span className="font-mono text-sm font-bold text-slate-900">#{ticket.ticket_id}</span>
                            )}
                            {renderStatusBadge(ticket.status)}
                            {renderPriorityBadge(ticket.priority)}
                            {ticket.category && (
                                <span className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600">
                                    {ticket.category.color && <span className="h-2 w-2 rounded-full" style={{ backgroundColor: ticket.category.color }} />}
                                    {ticket.category.name}
                                </span>
                            )}
                        </div>

                        <h3
                            className="max-w-3xl truncate text-lg font-semibold text-slate-950 group-hover:text-emerald-800"
                            title={ticket.title}
                        >
                            {ticket.title}
                        </h3>

                        <div className="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2">
                                <UserRound className="h-4 w-4 text-slate-400" />
                                <span className="truncate">{ticket.creator?.name || t('Unknown user')}</span>
                            </div>
                            <div className="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2">
                                <Clock3 className="h-4 w-4 text-slate-400" />
                                <span>{new Date(ticket.created_at).toLocaleDateString()}</span>
                            </div>
                            <div className="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2">
                                <MessageSquare className="h-4 w-4 text-slate-400" />
                                <span>{ticket.replies?.length || 0} {t('replies')}</span>
                            </div>
                        </div>
                    </div>

                    {(canView || canEdit || canDelete) && (
                        <div className="flex shrink-0 items-center gap-2">
                            <TooltipProvider>
                                {canView && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => router.get(route('helpdesk-tickets.show', ticket.id))}
                                                className="rounded-xl"
                                            >
                                                <Eye className="mr-2 h-4 w-4" />
                                                {t('Open')}
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>{t('View')}</p></TooltipContent>
                                    </Tooltip>
                                )}
                                {canEdit && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button variant="ghost" size="sm" onClick={() => openModal('edit', ticket)} className="h-9 w-9 rounded-xl p-0 text-slate-500 hover:text-emerald-700">
                                                <EditIcon className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>{t('Edit')}</p></TooltipContent>
                                    </Tooltip>
                                )}
                                {canDelete && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button variant="ghost" size="sm" onClick={() => openDeleteDialog(ticket.id)} className="h-9 w-9 rounded-xl p-0 text-slate-400 hover:text-destructive">
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>{t('Delete')}</p></TooltipContent>
                                    </Tooltip>
                                )}
                            </TooltipProvider>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Helpdesk') }, { label: t('All Tickets') }]}
            pageTitle={t('Support Desk')}
            pageActions={canCreate ? (
                <Button onClick={() => openModal('add')} className="rounded-xl bg-emerald-600 px-4 text-white hover:bg-emerald-700">
                    <Plus className="mr-2 h-4 w-4" />
                    {t('New Ticket')}
                </Button>
            ) : null}
        >
            <Head title={t('Support Tickets')} />

            <div className="space-y-6">
                <section className="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-white via-emerald-50/50 to-slate-50 p-6 text-slate-950 shadow-sm">
                    <div className="absolute right-0 top-0 h-40 w-40 rounded-full bg-emerald-200/60 blur-3xl" />
                    <div className="absolute bottom-0 left-1/3 h-32 w-32 rounded-full bg-sky-100/70 blur-3xl" />
                    <div className="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div className="max-w-2xl">
                            <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700 shadow-sm">
                                <Sparkles className="h-3.5 w-3.5" />
                                {t('Service Desk')}
                            </div>
                            <h1 className="text-3xl font-black tracking-tight md:text-4xl">{t('Helpdesk command center')}</h1>
                            <p className="mt-3 text-sm leading-6 text-slate-600 md:text-base">
                                {t('Track support tickets, assign priority, and keep every customer conversation moving from one clean workspace.')}
                            </p>
                        </div>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:w-[560px]">
                            {metricCards.map((item) => {
                                const Icon = item.icon;
                                return (
                                    <div key={item.label} className="rounded-2xl border border-slate-200 bg-white/85 p-4 shadow-sm backdrop-blur">
                                        <div className={`mb-3 flex h-10 w-10 items-center justify-center rounded-xl ${item.tone}`}>
                                            <Icon className="h-5 w-5" />
                                        </div>
                                        <p className="text-2xl font-black">{item.value}</p>
                                        <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{item.label}</p>
                                        <p className="mt-1 text-xs text-slate-500">{item.helper}</p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                <Card className="overflow-hidden rounded-3xl border-slate-200 shadow-sm">
                    <CardContent className="border-b border-slate-200 bg-white p-4 sm:p-5">
                        <div className="flex flex-col gap-5">
                            <div className="grid grid-cols-2 gap-2 md:grid-cols-5">
                                {statusTabs.map((tab) => {
                                    const active = filters.status === tab.value;
                                    return (
                                        <button
                                            key={tab.value || 'all'}
                                            type="button"
                                            onClick={() => setStatusFilter(tab.value)}
                                            className={`inline-flex items-center justify-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold transition ${
                                                active
                                                    ? 'border-emerald-500 bg-emerald-600 text-white shadow-sm shadow-emerald-100'
                                                    : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700'
                                            }`}
                                        >
                                            {tab.label}
                                            <span className={`rounded-full px-2 py-0.5 text-xs ${active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'}`}>
                                                {tab.count}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>

                            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div className="relative w-full lg:max-w-xl">
                                    <Search className="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        value={filters.title}
                                        onChange={(e) => setFilters({ ...filters, title: e.target.value })}
                                        placeholder={t('Search by ticket, title, customer...')}
                                        className="h-12 rounded-2xl border-slate-200 bg-white pl-11 pr-10 shadow-sm focus-visible:ring-emerald-500/20"
                                    />
                                    {filters.title && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                const nextFilters = { ...filters, title: '' };
                                                setFilters(nextFilters);
                                                applyFilters(nextFilters);
                                            }}
                                            className="absolute right-3 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                            aria-label={t('Clear search')}
                                        >
                                            <X className="h-4 w-4" />
                                        </button>
                                    )}
                                </div>
                                <Button variant="outline" onClick={() => setShowFilters(!showFilters)} className="relative h-12 rounded-2xl px-5">
                                    <Filter className="mr-2 h-4 w-4" />
                                    {t('Filters')}
                                    {activeFilters > 0 && (
                                        <span className="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">{activeFilters}</span>
                                    )}
                                </Button>
                            </div>
                        </div>
                    </CardContent>

                    {showFilters && (
                        <CardContent className="border-b border-slate-200 bg-slate-50/80 p-5">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                                <div>
                                    <label className="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">{t('Status')}</label>
                                    <Select value={filters.status || undefined} onValueChange={(value) => setFilters({ ...filters, status: value || '' })}>
                                        <SelectTrigger className="rounded-xl bg-white"><SelectValue placeholder={t('Any status')} /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="open">{t('Open')}</SelectItem>
                                            <SelectItem value="in_progress">{t('In Progress')}</SelectItem>
                                            <SelectItem value="resolved">{t('Resolved')}</SelectItem>
                                            <SelectItem value="closed">{t('Closed')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">{t('Priority')}</label>
                                    <Select value={filters.priority || undefined} onValueChange={(value) => setFilters({ ...filters, priority: value || '' })}>
                                        <SelectTrigger className="rounded-xl bg-white"><SelectValue placeholder={t('Any priority')} /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="urgent">{t('Urgent')}</SelectItem>
                                            <SelectItem value="high">{t('High')}</SelectItem>
                                            <SelectItem value="medium">{t('Medium')}</SelectItem>
                                            <SelectItem value="low">{t('Low')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">{t('Category')}</label>
                                    <Select value={filters.category_id || undefined} onValueChange={(value) => setFilters({ ...filters, category_id: value || '' })}>
                                        <SelectTrigger className="rounded-xl bg-white"><SelectValue placeholder={t('Any category')} /></SelectTrigger>
                                        <SelectContent>
                                            {categories?.map((category) => (
                                                <SelectItem key={category.id} value={category.id.toString()}>{category.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                {auth.user?.type === 'superadmin' && (
                                    <div>
                                        <label className="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">{t('Company')}</label>
                                        <Select value={filters.company_id || undefined} onValueChange={(value) => setFilters({ ...filters, company_id: value || '' })}>
                                            <SelectTrigger className="rounded-xl bg-white"><SelectValue placeholder={t('Any company')} /></SelectTrigger>
                                            <SelectContent>
                                                {companies?.map((company) => (
                                                    <SelectItem key={company.id} value={company.id.toString()}>{company.name}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}
                                <div className="flex items-end gap-2">
                                    <Button onClick={() => applyFilters()} className="flex-1 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">{t('Apply')}</Button>
                                    <Button variant="outline" onClick={clearFilters} className="rounded-xl">{t('Clear')}</Button>
                                </div>
                            </div>
                        </CardContent>
                    )}

                    <CardContent className="bg-slate-50/40 p-4 sm:p-5">
                        {pageTickets.length > 0 ? (
                            <div className="space-y-3">
                                {pageTickets.map(renderTicketCard)}
                            </div>
                        ) : (
                            <NoRecordsFound
                                icon={Ticket}
                                title={t('No tickets found')}
                                description={t('Get started by creating your first support ticket.')}
                                hasFilters={activeFilters > 0}
                                onClearFilters={clearFilters}
                                className="h-auto rounded-2xl bg-white py-14"
                            />
                        )}
                    </CardContent>

                    <CardContent className="border-t border-slate-200 bg-white px-4 py-3 sm:px-5">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <PerPageSelector
                                routeName="helpdesk-tickets.index"
                                filters={{ ...filters }}
                                defaultValue={perPage}
                                className="h-11 w-36 rounded-xl border-slate-200 bg-white"
                            />
                            <div className="flex-1">
                                <Pagination
                                    data={tickets}
                                    routeName="helpdesk-tickets.index"
                                    filters={{ ...filters, per_page: perPage }}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                        <div className="flex items-start gap-3">
                            <ShieldCheck className="mt-0.5 h-5 w-5 text-emerald-700" />
                            <div>
                                <p className="font-semibold text-emerald-950">{t('Cleaner support workflow')}</p>
                                <p className="mt-1 text-sm text-emerald-800/80">{t('Use filters, priority badges, and ticket cards to quickly spot what needs attention.')}</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <div className="flex items-start gap-3">
                            <AlertTriangle className="mt-0.5 h-5 w-5 text-amber-700" />
                            <div>
                                <p className="font-semibold text-amber-950">{t('Urgent queue')}</p>
                                <p className="mt-1 text-sm text-amber-800/80">{t('Urgent tickets are visually marked so admins can respond before customers escalate.')}</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-5">
                        <div className="flex items-start gap-3">
                            <CheckCircle2 className="mt-0.5 h-5 w-5 text-slate-700" />
                            <div>
                                <p className="font-semibold text-slate-950">{t('Resolved history')}</p>
                                <p className="mt-1 text-sm text-slate-600">{t('Resolved and closed cases stay searchable for future reference.')}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && <Create onSuccess={closeModal} />}
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
