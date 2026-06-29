import { Button } from '@/components/ui/button';
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

export type CustomerSummary = {
    id: number;
    name: string;
    phone?: string | null;
    username?: string | null;
    email?: string | null;
    isp_id?: number | null;
};

declare function route(name: string, params?: Record<string, unknown> | string | number): string;

export function points(value: number | string | null | undefined) {
    return Number(value ?? 0).toLocaleString('en-KE');
}

export function minutes(value?: number | string | null) {
    const count = Number(value ?? 0);

    if (!count) {
        return 'Not set';
    }

    if (count >= 1440) {
        return `${Math.round(count / 1440)} day${Math.round(count / 1440) === 1 ? '' : 's'}`;
    }

    if (count >= 60) {
        return `${Math.round(count / 60)} hour${Math.round(count / 60) === 1 ? '' : 's'}`;
    }

    return `${count} min`;
}

export function titleCase(value?: string | null) {
    return value ? value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()) : 'Not set';
}

export function LoyaltyStatus({ value }: { value?: string | boolean | null }) {
    const normalized = String(value ?? 'unknown').toLowerCase();
    const good = ['active', 'enabled', 'earned', 'unused', 'redeemed', 'true'].includes(normalized);
    const bad = ['inactive', 'disabled', 'expired', 'cancelled', 'false'].includes(normalized);
    const className = good
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300'
        : bad
            ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300'
            : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300';

    return (
        <span className={`inline-flex w-fit rounded-full border px-2.5 py-1 text-xs font-medium capitalize ${className}`}>
            {typeof value === 'boolean' ? (value ? 'Active' : 'Inactive') : titleCase(String(value ?? 'unknown'))}
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

export function CustomerLabel({ customer }: { customer?: CustomerSummary | null }) {
    if (!customer) {
        return (
            <div>
                <div className="font-medium">Unknown customer</div>
                <div className="text-xs text-muted-foreground">No customer link</div>
            </div>
        );
    }

    return (
        <div>
            <div className="font-medium">{customer.name}</div>
            <div className="text-xs text-muted-foreground">
                {[customer.phone, customer.username].filter(Boolean).join(' - ') || 'No phone or username'}
            </div>
        </div>
    );
}

export function ToolbarButton({ children, onClick }: { children: ReactNode; onClick: () => void }) {
    return (
        <Button type="button" variant="outline" onClick={onClick}>
            {children}
        </Button>
    );
}
