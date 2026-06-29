import { useEffect, useRef, useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Pagination } from '@/components/ui/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Edit, Mail, Search, X } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { FilterButton } from '@/components/ui/filter-button';
import NoRecordsFound from '@/components/no-records-found';
import { getPackageAlias } from '@/utils/helpers';

interface EmailTemplate {
    id: number;
    name: string;
    from: string;
    module_name: string;
    template_langs: Array<{
        id: number;
        lang: string;
        subject: string;
    }>;
}

interface EmailTemplateFilters {
    name: string;
    module_name: string;
}

interface Props {
    [key: string]: any;
    emailTemplates: {
        data: EmailTemplate[];
        links: any[];
        meta: any;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    allModules: string[];
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Index() {
    const { t } = useTranslation();
    const { emailTemplates, allModules, auth } = usePage<Props>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<EmailTemplateFilters>({
        name: urlParams.get('name') || '',
        module_name: urlParams.get('module_name') || ''
    });

    const [showFilters, setShowFilters] = useState(false);

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const firstSearchRender = useRef(true);


    useFlashMessages();

    const handleFilter = () => {
        router.get(route('email-templates.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('email-templates.index'), {...filters, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ name: '', module_name: '' });
        router.get(route('email-templates.index'), {per_page: perPage});
    };

    const clearSearch = () => {
        const nextFilters = {...filters, name: ''};
        setFilters(nextFilters);
        router.get(route('email-templates.index'), {...nextFilters, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    useEffect(() => {
        if (firstSearchRender.current) {
            firstSearchRender.current = false;
            return;
        }

        const timeout = window.setTimeout(() => {
            router.get(route('email-templates.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
                preserveState: true,
                replace: true
            });
        }, 450);

        return () => window.clearTimeout(timeout);
    }, [filters.name]);

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },
        {
            key: 'module_name',
            header: t('Module'),
            sortable: true,
            render: (value: string) => (value ? getPackageAlias(value) : '-')
        },
        {
            key: 'actions',
            header: t('Actions'),
            render: (_: any, template: EmailTemplate) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-email-templates') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => router.visit(route('email-templates.edit', template.id))}
                                        className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                    >
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </TooltipProvider>
                </div>
            )
        }
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Email Templates') }]}
            pageTitle={t('Manage Email Templates')}
        >
            <Head title={t('Email Templates')} />

            <Card className="shadow-sm">
                <CardContent className="border-b bg-gradient-to-r from-slate-50 via-white to-emerald-50/40 p-5">
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div className="flex min-w-0 flex-1 flex-col gap-1">
                            <div className="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700/80">
                                {t('Message Template Library')}
                            </div>
                            <div className="relative w-full max-w-2xl">
                                <Search className="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={filters.name}
                                    onChange={(e) => setFilters({...filters, name: e.target.value})}
                                    placeholder={t('Search email templates by name...')}
                                    className="h-12 rounded-2xl border-slate-200 bg-white/90 pl-11 pr-11 text-base shadow-sm transition focus-visible:ring-emerald-500"
                                />
                                {filters.name && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={clearSearch}
                                        className="absolute right-2 top-1/2 h-8 w-8 -translate-y-1/2 rounded-full p-0 text-muted-foreground hover:text-foreground"
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {t('Typing automatically filters email templates. Use filters to narrow by module.')}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="inline-flex h-10 items-center rounded-full border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700">
                                {t('List View')}
                            </span>
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.module_name].filter(Boolean).length;
                                    return activeFilters > 0 && (
                                        <span className="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">
                                            {activeFilters}
                                        </span>
                                    );
                                })()}
                            </div>
                        </div>
                    </div>
                </CardContent>

                {/* Advanced Filters */}
                {showFilters && (
                    <CardContent className="p-6 bg-blue-50/30 border-b">
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Module')}</label>
                                <Select value={filters.module_name} onValueChange={(value) => setFilters({...filters, module_name: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by module')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {allModules.map(module => (
                                            <SelectItem key={module} value={module}>{module.charAt(0).toUpperCase() + module.slice(1)}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end gap-2">
                                <Button onClick={handleFilter} size="sm">{t('Apply')}</Button>
                                <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                            </div>
                        </div>
                    </CardContent>
                )}

                {/* Table Content */}
                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={emailTemplates.data}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={Mail}
                                        title={t('No email templates found')}
                                        description={t('Email templates will appear here.')}
                                        hasFilters={!!(filters.name || filters.module_name)}
                                        onClearFilters={clearFilters}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="border-t bg-slate-50/70 px-5 py-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <PerPageSelector
                            routeName="email-templates.index"
                            filters={{...filters, sort: sortField, direction: sortDirection}}
                            className="h-11 w-40 rounded-xl bg-white"
                        />
                        <div className="flex-1">
                            <Pagination
                                data={emailTemplates}
                                routeName="email-templates.index"
                                filters={{...filters, per_page: perPage, sort: sortField, direction: sortDirection}}
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>


        </AuthenticatedLayout>
    );
}
