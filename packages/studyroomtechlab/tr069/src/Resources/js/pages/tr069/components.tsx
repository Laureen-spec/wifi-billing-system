import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { router } from '@inertiajs/react';
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

export type Option = {
    value: string;
    label: string;
};

export type IspOption = {
    id: number;
    name: string;
};

export type DeviceSummary = {
    id: number;
    serial_number: string;
    manufacturer?: string | null;
    model?: string | null;
    status?: string | null;
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export function titleCase(value?: string | null) {
    return value ? value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()) : 'Not set';
}

export function numberText(value?: number | string | null) {
    return Number(value ?? 0).toLocaleString('en-KE');
}

export function dateText(value?: string | null) {
    return value || 'Not set';
}

export function Tr069Status({ value }: { value?: string | boolean | null }) {
    const normalized = String(value ?? 'unknown').toLowerCase();
    const good = ['online', 'active', 'completed', 'success', 'ready', 'true'].includes(normalized);
    const bad = ['offline', 'failed', 'error', 'inactive', 'cancelled', 'false'].includes(normalized);
    const wait = ['pending', 'queued', 'running', 'draft'].includes(normalized);
    const className = good
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300'
        : bad
            ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300'
            : wait
                ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300'
                : 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300';

    return (
        <span className={`inline-flex w-fit rounded-full border px-2.5 py-1 text-xs font-medium capitalize ${className}`}>
            {typeof value === 'boolean' ? (value ? 'Enabled' : 'Disabled') : titleCase(String(value ?? 'unknown'))}
        </span>
    );
}

export function FilterChip({ active, children, onClick }: { active: boolean; children: ReactNode; onClick: () => void }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-md border px-3 py-1.5 text-sm transition ${
                active
                    ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                    : 'border-input bg-background text-muted-foreground hover:bg-accent hover:text-accent-foreground'
            }`}
        >
            {children}
        </button>
    );
}

export function PlatformIspSelect({
    isPlatform,
    isps,
    value,
    routeName,
    extraFilters = {},
}: {
    isPlatform: boolean;
    isps: IspOption[];
    value?: number | null;
    routeName: string;
    extraFilters?: Record<string, string | number | boolean | null | undefined>;
}) {
    if (!isPlatform) {
        return null;
    }

    return (
        <Select
            value={value ? String(value) : 'all'}
            onValueChange={(selected) => {
                const params: Record<string, string | number | boolean | null | undefined> = { ...extraFilters };
                if (selected !== 'all') {
                    params.isp_id = selected;
                } else {
                    delete params.isp_id;
                }

                router.get(route(routeName), params, {
                    preserveState: true,
                    replace: true,
                });
            }}
        >
            <SelectTrigger className="w-full sm:w-64">
                <SelectValue placeholder="All ISPs" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">All ISPs</SelectItem>
                {isps.map((isp) => (
                    <SelectItem key={isp.id} value={String(isp.id)}>
                        {isp.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export function DeviceLabel({ device }: { device?: DeviceSummary | null }) {
    if (!device) {
        return (
            <div>
                <div className="font-medium">Unknown CPE</div>
                <div className="text-xs text-muted-foreground">No device link</div>
            </div>
        );
    }

    return (
        <div>
            <div className="font-mono text-sm font-semibold">{device.serial_number}</div>
            <div className="text-xs text-muted-foreground">
                {[device.manufacturer, device.model].filter(Boolean).join(' ') || 'Unknown model'}
            </div>
        </div>
    );
}

export function safeJson(value: unknown) {
    if (!value) {
        return '';
    }

    try {
        return JSON.stringify(value, null, 2);
    } catch {
        return '';
    }
}
