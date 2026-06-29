import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { LucideIcon } from 'lucide-react';
import { ReactNode } from 'react';

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
};

export const money = (value: number | string | null | undefined) => {
    const amount = Number(value ?? 0);

    return `KES ${amount.toLocaleString('en-KE', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
};

export const titleCase = (value?: string | null) => {
    if (!value) {
        return 'Not set';
    }

    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
};

export const formatBytes = (value?: number | string | null) => {
    const bytes = Number(value ?? 0);

    if (bytes >= 1073741824) {
        return `${(bytes / 1073741824).toFixed(2)} GB`;
    }

    if (bytes >= 1048576) {
        return `${(bytes / 1048576).toFixed(2)} MB`;
    }

    if (bytes >= 1024) {
        return `${(bytes / 1024).toFixed(2)} KB`;
    }

    return `${bytes} B`;
};

export const memoryText = (free?: number | string | null, total?: number | string | null) => {
    const freeBytes = Number(free ?? 0);
    const totalBytes = Number(total ?? 0);

    if (!freeBytes || !totalBytes) {
        return 'Unknown';
    }

    return `${formatBytes(freeBytes)} / ${formatBytes(totalBytes)} free`;
};

const toneClass = (value?: string | null) => {
    const status = (value ?? '').toLowerCase();

    if (['active', 'paid', 'online', 'connected', 'success', 'successful', 'present', 'synced'].includes(status)) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300';
    }

    if (['pending', 'queued', 'not_synced', 'unpaid', 'provision_pending', 'waiting_for_link', 'sync_pending', 'unknown'].includes(status)) {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300';
    }

    if (['expired', 'overdue', 'failed', 'offline', 'disconnected', 'suspended', 'missing', 'inactive'].includes(status)) {
        return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300';
    }

    return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300';
};

export function StatusBadge({ value }: { value?: string | null }) {
    return (
        <Badge variant="outline" className={cn('whitespace-nowrap', toneClass(value))}>
            {titleCase(value)}
        </Badge>
    );
}

export function MetricCard({
    title,
    value,
    note,
    icon: Icon,
    tone = 'default',
}: {
    title: string;
    value: ReactNode;
    note?: string;
    icon: LucideIcon;
    tone?: 'default' | 'success' | 'warning' | 'danger';
}) {
    const toneMap = {
        default: 'bg-primary/10 text-primary',
        success: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        warning: 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        danger: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    };

    return (
        <Card>
            <CardContent className="p-4">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="text-sm text-muted-foreground">{title}</p>
                        <div className="mt-2 text-2xl font-semibold tracking-tight">{value}</div>
                        {note && <p className="mt-1 text-xs text-muted-foreground">{note}</p>}
                    </div>
                    <span className={cn('rounded-md p-2', toneMap[tone])}>
                        <Icon className="h-4 w-4" />
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}

export function PageHeader({
    title,
    description,
    actions,
}: {
    title: string;
    description: string;
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-4 rounded-lg border bg-card p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 className="text-2xl font-semibold tracking-tight">{title}</h2>
                <p className="mt-1 max-w-3xl text-sm text-muted-foreground">{description}</p>
            </div>
            {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
        </div>
    );
}

export function EmptyState({
    title,
    description,
    action,
}: {
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex min-h-56 flex-col items-center justify-center gap-3 rounded-lg border border-dashed bg-muted/20 p-8 text-center">
            <div>
                <h3 className="text-base font-semibold">{title}</h3>
                <p className="mt-1 max-w-md text-sm text-muted-foreground">{description}</p>
            </div>
            {action}
        </div>
    );
}

export function ResetButton({ onClick }: { onClick: () => void }) {
    return (
        <Button type="button" variant="outline" onClick={onClick}>
            Reset
        </Button>
    );
}
