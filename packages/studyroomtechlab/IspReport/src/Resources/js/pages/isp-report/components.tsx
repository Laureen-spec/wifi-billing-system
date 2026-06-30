import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Search } from 'lucide-react';
import { FormEvent, ReactNode, useState } from 'react';

declare function route(name: string, params?: Record<string, unknown>): string;

export type Pagination = {
    current_page: number;
    last_page: number;
    from?: number | null;
    to?: number | null;
    total: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
};

export type Option = {
    value: string;
    label: string;
};

export type Stat = {
    label: string;
    value: string | number;
    description?: string;
};

export function ReportLayout({
    title,
    subtitle,
    children,
    actions,
}: {
    title: string;
    subtitle?: string;
    children: ReactNode;
    actions?: ReactNode;
}) {
    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'ISP Report', url: route('isp-reports.index') },
                { label: title },
            ]}
            pageTitle={title}
            pageActions={actions}
        >
            <Head title={title} />

            <div className="space-y-6">
                {subtitle ? (
                    <div className="rounded-2xl border bg-card p-6 shadow-sm">
                        <div className="max-w-3xl">
                            <p className="text-sm font-semibold uppercase tracking-wide text-primary">ISP Report</p>
                            <h1 className="mt-2 text-2xl font-semibold tracking-tight text-foreground">{title}</h1>
                            <p className="mt-2 text-sm leading-6 text-muted-foreground">{subtitle}</p>
                        </div>
                    </div>
                ) : null}
                {children}
            </div>
        </AuthenticatedLayout>
    );
}

export function StatsGrid({ stats }: { stats: Stat[] }) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {stats.map((item) => (
                <div key={item.label} className="rounded-2xl border bg-card p-5 shadow-sm">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{item.label}</p>
                    <p className="mt-3 text-2xl font-semibold text-foreground">{item.value}</p>
                    {item.description ? <p className="mt-2 text-sm text-muted-foreground">{item.description}</p> : null}
                </div>
            ))}
        </div>
    );
}

export function ReportCard({ title, description, children, action }: { title: string; description?: string; children: ReactNode; action?: ReactNode }) {
    return (
        <div className="rounded-2xl border bg-card shadow-sm">
            <div className="flex flex-col gap-3 border-b p-5 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 className="text-lg font-semibold text-foreground">{title}</h2>
                    {description ? <p className="mt-1 text-sm text-muted-foreground">{description}</p> : null}
                </div>
                {action}
            </div>
            <div className="p-5">{children}</div>
        </div>
    );
}

export function StatusBadge({ value }: { value?: string | null }) {
    const text = value || 'Unknown';
    const status = text.toLowerCase();
    const color = status.includes('paid') || status.includes('active') || status.includes('success') || status.includes('confirmed') || status.includes('posted') || status.includes('triggered')
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : status.includes('pending') || status.includes('review') || status.includes('unknown') || status.includes('not')
            ? 'border-amber-200 bg-amber-50 text-amber-700'
            : status.includes('failed') || status.includes('expired') || status.includes('offline') || status.includes('suspended')
                ? 'border-red-200 bg-red-50 text-red-700'
                : 'border-slate-200 bg-slate-50 text-slate-700';

    return <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${color}`}>{text}</span>;
}

export function ChipFilters({
    label,
    name,
    value,
    options,
    routeName,
    extra = {},
}: {
    label: string;
    name: string;
    value: string;
    options: Option[];
    routeName: string;
    extra?: Record<string, string | number | null | undefined>;
}) {
    const visit = (nextValue: string) => {
        router.get(route(routeName), { ...extra, [name]: nextValue, page: 1 }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <div className="flex flex-col gap-2 md:flex-row md:items-center">
            <span className="min-w-28 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</span>
            <div className="flex flex-wrap gap-2">
                {options.map((option) => {
                    const active = option.value === value;
                    return (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => visit(option.value)}
                            className={`inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition ${
                                active
                                    ? 'border-primary/30 bg-primary/10 text-primary shadow-sm'
                                    : 'border-border bg-background text-muted-foreground hover:border-primary/40 hover:text-foreground'
                            }`}
                        >
                            {option.label}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

export function SearchBox({ routeName, placeholder, filters = {} }: { routeName: string; placeholder: string; filters?: Record<string, string | number | null | undefined> }) {
    const [search, setSearch] = useState(String(filters.search ?? ''));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(route(routeName), { ...filters, search, page: 1 }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-2 sm:flex-row">
            <div className="relative flex-1">
                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={placeholder}
                    className="h-11 w-full rounded-xl border bg-background pl-10 pr-3 text-sm text-foreground outline-none transition focus:border-primary/50 focus:ring-2 focus:ring-primary/10"
                />
            </div>
            <button type="submit" className="h-11 rounded-xl bg-primary px-5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90">
                Search
            </button>
        </form>
    );
}

export function PaginationControls({ pagination }: { pagination: Pagination }) {
    if (!pagination || pagination.last_page <= 1) {
        return null;
    }

    return (
        <div className="flex flex-col gap-3 border-t px-5 py-4 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between">
            <span>
                Showing {pagination.from ?? 0} to {pagination.to ?? 0} of {pagination.total} records
            </span>
            <div className="flex gap-2">
                {pagination.prev_page_url ? (
                    <Link href={pagination.prev_page_url} preserveScroll className="inline-flex items-center gap-1 rounded-lg border px-3 py-2 font-medium text-foreground hover:bg-muted">
                        <ChevronLeft className="h-4 w-4" /> Previous
                    </Link>
                ) : null}
                {pagination.next_page_url ? (
                    <Link href={pagination.next_page_url} preserveScroll className="inline-flex items-center gap-1 rounded-lg border px-3 py-2 font-medium text-foreground hover:bg-muted">
                        Next <ChevronRight className="h-4 w-4" />
                    </Link>
                ) : null}
            </div>
        </div>
    );
}

export function EmptyRows({ message = 'No records found.' }: { message?: string }) {
    return (
        <div className="rounded-xl border border-dashed bg-muted/20 p-8 text-center text-sm text-muted-foreground">
            {message}
        </div>
    );
}

export function TableShell({ children }: { children: ReactNode }) {
    return <div className="overflow-hidden rounded-xl border bg-background">{children}</div>;
}
