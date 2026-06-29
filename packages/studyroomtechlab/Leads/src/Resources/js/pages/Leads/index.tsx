import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Head, router, useForm } from '@inertiajs/react';
import {
    CalendarClock,
    CheckCircle2,
    ClipboardList,
    Edit3,
    Filter,
    PhoneCall,
    Plus,
    RotateCw,
    Search,
    Trash2,
    UserRoundCheck,
    UsersRound,
} from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';

declare function route(name: string, params?: unknown): string;

type Option = { value: string; label: string };
type UserOption = { id: number; name: string };

type Lead = {
    id: number;
    name: string;
    phone?: string | null;
    email?: string | null;
    location?: string | null;
    source: string;
    source_label: string;
    interest?: string | null;
    status: string;
    status_label: string;
    priority: string;
    priority_label: string;
    value_estimate?: string | number | null;
    value_formatted: string;
    next_follow_up_at?: string | null;
    next_follow_up_raw?: string | null;
    last_contact_at?: string | null;
    notes?: string | null;
    assigned_user_id?: number | null;
    assigned_user_name?: string | null;
    converted_customer_id?: number | null;
    created_at?: string | null;
    is_due: boolean;
    routes: {
        update: string;
        contacted: string;
        convert: string;
        destroy: string;
    };
};

type Pagination<T> = {
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
    leads: Pagination<Lead>;
    stats: {
        total: number;
        new: number;
        contacted: number;
        converted: number;
        due: number;
    };
    filters: {
        q?: string;
        status?: string;
        source?: string;
        priority?: string;
    };
    options: {
        statuses: Option[];
        priorities: Option[];
        sources: Option[];
        users: UserOption[];
    };
    permissions: {
        canManage: boolean;
        canConvert: boolean;
    };
    routes: {
        index: string;
        store: string;
    };
};

type LeadForm = {
    name: string;
    phone: string;
    email: string;
    location: string;
    source: string;
    interest: string;
    status: string;
    priority: string;
    value_estimate: string;
    next_follow_up_at: string;
    assigned_user_id: string;
    notes: string;
};

const defaultForm = (): LeadForm => ({
    name: '',
    phone: '',
    email: '',
    location: '',
    source: 'walk-in',
    interest: '',
    status: 'new',
    priority: 'warm',
    value_estimate: '',
    next_follow_up_at: '',
    assigned_user_id: '',
    notes: '',
});

const inputDateTime = (value?: string | null) => {
    if (!value) return '';
    const cleaned = String(value).replace(' ', 'T');
    return cleaned.length >= 16 ? cleaned.slice(0, 16) : cleaned;
};

const statusClass = (status: string) => {
    const classes: Record<string, string> = {
        new: 'border-slate-200 bg-slate-50 text-slate-700',
        contacted: 'border-blue-200 bg-blue-50 text-blue-700',
        quoted: 'border-amber-200 bg-amber-50 text-amber-800',
        scheduled: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        converted: 'border-emerald-200 bg-emerald-600 text-white',
        lost: 'border-rose-200 bg-rose-50 text-rose-700',
    };

    return classes[status] || classes.new;
};

const priorityClass = (priority: string) => {
    const classes: Record<string, string> = {
        hot: 'bg-rose-100 text-rose-700',
        warm: 'bg-amber-100 text-amber-800',
        cold: 'bg-slate-100 text-slate-600',
    };

    return classes[priority] || classes.warm;
};

const StatCard = ({ title, value, helper, icon: Icon }: { title: string; value: number | string; helper: string; icon: any }) => (
    <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div className="flex items-start justify-between gap-4">
            <div>
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{title}</p>
                <p className="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{value}</p>
                <p className="mt-1 text-sm text-slate-500">{helper}</p>
            </div>
            <div className="rounded-xl border border-slate-200 bg-slate-50 p-3 text-slate-600">
                <Icon className="h-5 w-5" />
            </div>
        </div>
    </div>
);

export default function LeadsIndex({ pageTitle, subtitle, leads, stats, filters, options, permissions, routes }: Props) {
    const [q, setQ] = useState(filters.q || '');
    const [showPanel, setShowPanel] = useState(false);
    const [editingLead, setEditingLead] = useState<Lead | null>(null);

    const form = useForm(defaultForm());

    const activeFilterCount = useMemo(() => [filters.status, filters.source, filters.priority].filter(Boolean).length, [filters]);

    const openCreate = () => {
        setEditingLead(null);
        form.clearErrors();
        form.setData(defaultForm());
        setShowPanel(true);
    };

    const openEdit = (lead: Lead) => {
        setEditingLead(lead);
        form.clearErrors();
        form.setData({
            name: lead.name || '',
            phone: lead.phone || '',
            email: lead.email || '',
            location: lead.location || '',
            source: lead.source || 'walk-in',
            interest: lead.interest || '',
            status: lead.status || 'new',
            priority: lead.priority || 'warm',
            value_estimate: lead.value_estimate ? String(lead.value_estimate) : '',
            next_follow_up_at: inputDateTime(lead.next_follow_up_raw),
            assigned_user_id: lead.assigned_user_id ? String(lead.assigned_user_id) : '',
            notes: lead.notes || '',
        });
        setShowPanel(true);
    };

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        router.get(routes.index, { ...filters, q, page: 1 }, { preserveState: true, replace: true });
    };

    const changeFilter = (key: keyof Props['filters'], value: string) => {
        router.get(routes.index, { ...filters, [key]: value || undefined, q, page: 1 }, { preserveState: true, replace: true });
    };

    const submitLead = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setShowPanel(false);
                setEditingLead(null);
                form.reset();
            },
        };

        if (editingLead) {
            form.put(editingLead.routes.update, options);
        } else {
            form.post(routes.store, options);
        }
    };

    const markContacted = (lead: Lead) => {
        router.post(lead.routes.contacted, {}, { preserveScroll: true });
    };

    const convertLead = (lead: Lead) => {
        if (!confirm(`Convert ${lead.name} to an ISP customer?`)) return;
        router.post(lead.routes.convert, {}, { preserveScroll: true });
    };

    const deleteLead = (lead: Lead) => {
        if (!confirm(`Remove lead ${lead.name}?`)) return;
        router.delete(lead.routes.destroy, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title={pageTitle} />

            <div className="min-h-screen bg-[#f6f4ef] px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <section className="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div className="max-w-3xl">
                                <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                                    <span>Operations</span>
                                    <span className="h-px w-8 bg-slate-300" />
                                    <span>Lead Desk</span>
                                </div>
                                <h1 className="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">{pageTitle}</h1>
                                <p className="mt-3 text-base text-slate-600">{subtitle}</p>
                            </div>

                            <div className="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    onClick={() => router.reload({ preserveScroll: true })}
                                    className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                >
                                    <RotateCw className="h-4 w-4" />
                                    Refresh
                                </button>
                                {permissions.canManage && (
                                    <button
                                        type="button"
                                        onClick={openCreate}
                                        className="inline-flex items-center gap-2 rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                                    >
                                        <Plus className="h-4 w-4" />
                                        New lead
                                    </button>
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        <StatCard title="Total" value={stats.total} helper="prospects captured" icon={UsersRound} />
                        <StatCard title="New" value={stats.new} helper="not contacted" icon={ClipboardList} />
                        <StatCard title="Active talks" value={stats.contacted} helper="contacted / quoted" icon={PhoneCall} />
                        <StatCard title="Converted" value={stats.converted} helper="now customers" icon={UserRoundCheck} />
                        <StatCard title="Due" value={stats.due} helper="follow-ups waiting" icon={CalendarClock} />
                    </section>

                    <section className="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                            <form onSubmit={submitSearch} className="flex min-w-0 flex-1 items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                                <Search className="h-5 w-5 flex-none text-slate-400" />
                                <input
                                    value={q}
                                    onChange={(event) => setQ(event.target.value)}
                                    placeholder="Search name, phone, location, package interest..."
                                    className="min-w-0 flex-1 bg-transparent text-sm text-slate-800 outline-none placeholder:text-slate-400"
                                />
                                <button type="submit" className="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100">
                                    Search
                                </button>
                            </form>

                            <div className="grid gap-2 sm:grid-cols-3 xl:w-[620px]">
                                <select
                                    value={filters.status || ''}
                                    onChange={(event) => changeFilter('status', event.target.value)}
                                    className="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none"
                                >
                                    <option value="">All status</option>
                                    {options.statuses.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                                </select>
                                <select
                                    value={filters.source || ''}
                                    onChange={(event) => changeFilter('source', event.target.value)}
                                    className="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none"
                                >
                                    <option value="">All sources</option>
                                    {options.sources.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                                </select>
                                <select
                                    value={filters.priority || ''}
                                    onChange={(event) => changeFilter('priority', event.target.value)}
                                    className="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none"
                                >
                                    <option value="">All priority</option>
                                    {options.priorities.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                                </select>
                            </div>
                        </div>

                        <div className="mt-3 flex items-center gap-2 text-sm text-slate-500">
                            <Filter className="h-4 w-4" />
                            {activeFilterCount > 0 ? `${activeFilterCount} filter${activeFilterCount === 1 ? '' : 's'} active` : 'No filters active'}
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                        <div className="hidden grid-cols-[1.25fr_.9fr_.9fr_.75fr_.8fr_.9fr_.7fr] gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4 text-xs font-bold uppercase tracking-[0.16em] text-slate-500 xl:grid">
                            <span>Lead</span>
                            <span>Interest</span>
                            <span>Follow-up</span>
                            <span>Status</span>
                            <span>Source</span>
                            <span>Owner</span>
                            <span className="text-right">Actions</span>
                        </div>

                        {leads.data.length === 0 ? (
                            <div className="px-6 py-16 text-center">
                                <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                    <ClipboardList className="h-7 w-7" />
                                </div>
                                <h3 className="mt-4 text-lg font-semibold text-slate-950">No leads found</h3>
                                <p className="mt-2 text-sm text-slate-500">Add your first prospect or clear the current filters.</p>
                            </div>
                        ) : (
                            <div className="divide-y divide-slate-100">
                                {leads.data.map((lead) => (
                                    <article key={lead.id} className="grid gap-4 px-5 py-5 xl:grid-cols-[1.25fr_.9fr_.9fr_.75fr_.8fr_.9fr_.7fr] xl:items-center">
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="text-base font-semibold text-slate-950">{lead.name}</h3>
                                                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${priorityClass(lead.priority)}`}>{lead.priority_label}</span>
                                                {lead.is_due && <span className="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Due</span>}
                                            </div>
                                            <div className="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-sm text-slate-500">
                                                {lead.phone && <span>{lead.phone}</span>}
                                                {lead.location && <span>{lead.location}</span>}
                                                {lead.email && <span>{lead.email}</span>}
                                            </div>
                                        </div>

                                        <div className="text-sm">
                                            <p className="font-semibold text-slate-800">{lead.interest || 'Not specified'}</p>
                                            <p className="mt-1 text-slate-500">{lead.value_formatted}</p>
                                        </div>

                                        <div className="text-sm">
                                            <p className="font-medium text-slate-800">{lead.next_follow_up_at || 'No follow-up set'}</p>
                                            <p className="mt-1 text-slate-500">Last contact: {lead.last_contact_at || '—'}</p>
                                        </div>

                                        <div>
                                            <span className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${statusClass(lead.status)}`}>{lead.status_label}</span>
                                        </div>

                                        <div className="text-sm text-slate-700">{lead.source_label}</div>
                                        <div className="text-sm text-slate-700">{lead.assigned_user_name || 'Unassigned'}</div>

                                        <div className="flex items-center justify-start gap-2 xl:justify-end">
                                            {permissions.canManage && (
                                                <>
                                                    <button
                                                        type="button"
                                                        onClick={() => markContacted(lead)}
                                                        title="Mark contacted"
                                                        className="rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-50"
                                                    >
                                                        <CheckCircle2 className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(lead)}
                                                        title="Edit lead"
                                                        className="rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-50"
                                                    >
                                                        <Edit3 className="h-4 w-4" />
                                                    </button>
                                                    {permissions.canConvert && lead.status !== 'converted' && (
                                                        <button
                                                            type="button"
                                                            onClick={() => convertLead(lead)}
                                                            title="Convert to customer"
                                                            className="rounded-xl border border-emerald-200 p-2 text-emerald-700 hover:bg-emerald-50"
                                                        >
                                                            <UserRoundCheck className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                    <button
                                                        type="button"
                                                        onClick={() => deleteLead(lead)}
                                                        title="Delete lead"
                                                        className="rounded-xl border border-rose-200 p-2 text-rose-700 hover:bg-rose-50"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}

                        <div className="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
                            <span>
                                Showing {leads.from || 0} to {leads.to || 0} of {leads.total} leads
                            </span>
                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    disabled={!leads.prev_page_url}
                                    onClick={() => leads.prev_page_url && router.visit(leads.prev_page_url, { preserveScroll: true })}
                                    className="rounded-xl border border-slate-200 bg-white px-4 py-2 font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Previous
                                </button>
                                <span className="px-2">{leads.current_page} / {leads.last_page}</span>
                                <button
                                    type="button"
                                    disabled={!leads.next_page_url}
                                    onClick={() => leads.next_page_url && router.visit(leads.next_page_url, { preserveScroll: true })}
                                    className="rounded-xl border border-slate-200 bg-white px-4 py-2 font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            {showPanel && (
                <div className="fixed inset-0 z-50 flex items-stretch justify-end bg-slate-950/35 backdrop-blur-sm">
                    <button type="button" className="hidden flex-1 cursor-default lg:block" onClick={() => setShowPanel(false)} aria-label="Close lead panel" />
                    <aside className="h-full w-full overflow-y-auto bg-white shadow-2xl sm:max-w-xl">
                        <form onSubmit={submitLead} className="flex min-h-full flex-col">
                            <div className="border-b border-slate-200 px-6 py-5">
                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{editingLead ? 'Update prospect' : 'Capture prospect'}</p>
                                <h2 className="mt-2 text-2xl font-semibold text-slate-950">{editingLead ? 'Edit lead' : 'New lead'}</h2>
                                <p className="mt-1 text-sm text-slate-500">Keep sales follow-up separate from active billing customers until conversion.</p>
                            </div>

                            <div className="grid flex-1 gap-5 px-6 py-6">
                                <label className="grid gap-2 text-sm font-semibold text-slate-700">
                                    Full name
                                    <input value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400" placeholder="e.g. John Mwangi" />
                                    {form.errors.name && <span className="text-xs font-medium text-rose-600">{form.errors.name}</span>}
                                </label>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <label className="grid gap-2 text-sm font-semibold text-slate-700">
                                        Phone
                                        <input value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400" placeholder="07..." />
                                        {form.errors.phone && <span className="text-xs font-medium text-rose-600">{form.errors.phone}</span>}
                                    </label>
                                    <label className="grid gap-2 text-sm font-semibold text-slate-700">
                                        Email
                                        <input value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400" placeholder="optional" />
                                        {form.errors.email && <span className="text-xs font-medium text-rose-600">{form.errors.email}</span>}
                                    </label>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <label className="grid gap-2 text-sm font-semibold text-slate-700">
                                        Location
                                        <input value={form.data.location} onChange={(event) => form.setData('location', event.target.value)} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400" placeholder="estate / apartment" />
                                    </label>
                                    <label className="grid gap-2 text-sm font-semibold text-slate-700">
                                        Interested in
                                        <input value={form.data.interest} onChange={(event) => form.setData('interest', event.target.value)} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400" placeholder="home WiFi, hotspot, office..." />
                                    </label>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <label className="grid gap-2 text-sm font-semibold text-slate-700">
                                        Source
                                        <select value={form.data.source} onChange={(event) => form.setData('source', event.target.value)} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400">
                                            {options.sources.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                                        </select>
                                    </label>
                                    <label className="grid gap-2 text-sm font-semibold text-slate-700">
                                        Status
                                        <select value={form.data.status} onChange={(event) => form.setData('status', event.target.value)} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400">
                                            {options.statuses.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                                        </select>
                                    </label>
                                    <label className="grid gap-2 text-sm font-semibold text-slate-700">
                                        Priority
                                        <select value={form.data.priority} onChange={(event) => form.setData('priority', event.target.value)} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400">
                                            {options.priorities.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                                        </select>
                                    </label>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <label className="grid gap-2 text-sm font-semibold text-slate-700">
                                        Estimated value
                                        <input type="number" min="0" step="0.01" value={form.data.value_estimate} onChange={(event) => form.setData('value_estimate', event.target.value)} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400" placeholder="KES" />
                                    </label>
                                    <label className="grid gap-2 text-sm font-semibold text-slate-700 sm:col-span-2">
                                        Next follow-up
                                        <input type="datetime-local" value={form.data.next_follow_up_at} onChange={(event) => form.setData('next_follow_up_at', event.target.value)} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400" />
                                    </label>
                                </div>

                                <label className="grid gap-2 text-sm font-semibold text-slate-700">
                                    Assign to
                                    <select value={form.data.assigned_user_id} onChange={(event) => form.setData('assigned_user_id', event.target.value)} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400">
                                        <option value="">Unassigned</option>
                                        {options.users.map((user) => <option key={user.id} value={String(user.id)}>{user.name}</option>)}
                                    </select>
                                </label>

                                <label className="grid gap-2 text-sm font-semibold text-slate-700">
                                    Notes
                                    <textarea value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} rows={4} className="rounded-2xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-slate-400" placeholder="Need router, wants installation this weekend..." />
                                    {form.errors.notes && <span className="text-xs font-medium text-rose-600">{form.errors.notes}</span>}
                                </label>
                            </div>

                            <div className="sticky bottom-0 flex items-center justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                <button type="button" onClick={() => setShowPanel(false)} className="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                    Cancel
                                </button>
                                <button type="submit" disabled={form.processing} className="rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60">
                                    {form.processing ? 'Saving...' : editingLead ? 'Save changes' : 'Save lead'}
                                </button>
                            </div>
                        </form>
                    </aside>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
