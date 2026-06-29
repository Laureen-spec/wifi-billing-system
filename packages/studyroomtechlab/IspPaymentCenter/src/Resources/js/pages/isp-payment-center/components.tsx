import { Button } from '@/components/ui/button';
import { Copy, Download, Eye, LockKeyhole } from 'lucide-react';

type SummaryCardProps = {
    title: string;
    value: string;
    description: string;
    tone?: string;
};

const toneClasses: Record<string, string> = {
    pink: 'border-l-pink-500',
    blue: 'border-l-sky-500',
    green: 'border-l-emerald-500',
    amber: 'border-l-amber-500',
    violet: 'border-l-violet-500',
    red: 'border-l-rose-500',
    slate: 'border-l-slate-400',
};

export function SummaryCard({ title, value, description, tone = 'slate' }: SummaryCardProps) {
    return (
        <div className={`rounded-lg border border-l-4 bg-card p-4 shadow-sm ${toneClasses[tone] || toneClasses.slate}`}>
            <div className="text-sm text-muted-foreground">{title}</div>
            <div className="mt-2 text-2xl font-semibold tracking-normal text-foreground">{value}</div>
            <div className="mt-1 text-xs text-muted-foreground">{description}</div>
        </div>
    );
}

export function FilterPill({ active, label, onClick }: { active: boolean; label: string; onClick: () => void }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-full border px-3 py-1.5 text-sm transition ${
                active
                    ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                    : 'border-input bg-background text-muted-foreground hover:bg-accent hover:text-accent-foreground'
            }`}
        >
            {label}
        </button>
    );
}

export function StatusBadge({ value }: { value?: string | null }) {
    const normalized = String(value || 'unknown').toLowerCase();
    const isGood = ['paid', 'confirmed', 'success', 'completed', 'posted', 'provisioned'].includes(normalized);
    const isBad = ['failed', 'cancelled', 'canceled', 'expired', 'reversed', 'refunded', 'chargeback'].includes(normalized);
    const className = isGood
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300'
        : isBad
            ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300'
            : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300';

    return (
        <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-medium capitalize ${className}`}>
            {String(value || 'Unknown').replace(/_/g, ' ')}
        </span>
    );
}

export function EmptyState() {
    return (
        <div className="rounded-lg border border-dashed bg-muted/30 p-10 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full border bg-background text-muted-foreground">
                <LockKeyhole className="h-5 w-5" />
            </div>
            <h3 className="mt-4 text-base font-semibold text-foreground">No payment activity found</h3>
            <p className="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                Collections and transaction records will appear here as existing payment tables receive data.
            </p>
        </div>
    );
}

export function RowActions({ receipt, viewUrl, receiptUrl }: { receipt?: string | null; viewUrl?: string | null; receiptUrl?: string | null }) {
    const copyReceipt = () => {
        if (receipt && navigator?.clipboard) {
            navigator.clipboard.writeText(receipt);
        }
    };

    return (
        <div className="flex items-center justify-end gap-2">
            <Button type="button" size="icon" variant="ghost" disabled={!viewUrl} title={viewUrl ? 'View' : 'View is not available yet'}>
                <Eye className="h-4 w-4" />
            </Button>
            <Button type="button" size="icon" variant="ghost" disabled={!receipt} onClick={copyReceipt} title="Copy receipt">
                <Copy className="h-4 w-4" />
            </Button>
            <Button type="button" size="icon" variant="ghost" disabled={!receiptUrl} title={receiptUrl ? 'Download receipt' : 'Download receipt is not available yet'}>
                <Download className="h-4 w-4" />
            </Button>
        </div>
    );
}
