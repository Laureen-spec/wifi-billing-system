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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Edit, Bell, Search, X } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import NoRecordsFound from '@/components/no-records-found';
import { getPackageAlias } from '@/utils/helpers';

interface NotificationTemplate {
    id: number;
    module: string;
    action: string;
    type: string;
    status: string;
    permissions: string;
}

interface Props {
    [key: string]: any;
    notificationTemplates: {
        data: NotificationTemplate[];
        links: any[];
        meta: any;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    allTypes: string[];
    activeType: string;
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Index() {
    const { t } = useTranslation();
    const { notificationTemplates, allTypes, activeType, auth } = usePage<Props>().props;

    const availableTypes = allTypes.filter(type => type !== 'mail');
    const urlParams = new URLSearchParams(window.location.search);
    const [searchValue, setSearchValue] = useState(urlParams.get('action') || '');
    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const firstSearchRender = useRef(true);

    useFlashMessages();

    const handleTabChange = (type: string) => {
        router.get(route('notification-templates.index'), {type, action: searchValue, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const clearSearch = () => {
        setSearchValue('');
        router.get(route('notification-templates.index'), {type: activeType, action: '', per_page: perPage, sort: sortField, direction: sortDirection}, {
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
            router.get(route('notification-templates.index'), {type: activeType, action: searchValue, per_page: perPage, sort: sortField, direction: sortDirection}, {
                preserveState: true,
                replace: true
            });
        }, 450);

        return () => window.clearTimeout(timeout);
    }, [searchValue]);

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('notification-templates.index'), {type: activeType, action: searchValue, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const tableColumns = [
        {
            key: 'action',
            header: t('Subject'),
            sortable: true
        },
        {
            key: 'module',
            header: t('Module'),
            sortable: true,
            render: (value: string) => (value ? getPackageAlias(value) : '-')
        },
        {
            key: 'actions',
            header: t('Actions'),
            render: (_: any, template: NotificationTemplate) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-notification-templates') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => router.visit(route('notification-templates.edit', template.id))}
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
            breadcrumbs={[{ label: t('Notification Templates') }]}
            pageTitle={t('Manage Notification Templates')}
        >
            <Head title={t('Notification Templates')} />

            {availableTypes.length > 0 && (
                <div className='mb-4'>
                    <Tabs value={activeType} onValueChange={handleTabChange}>
                        <TabsList className="mb-3 w-full justify-start overflow-x-auto overflow-y-hidden h-auto p-1">
                            {availableTypes.map(type => (
                                <TabsTrigger key={type} value={type} className="capitalize whitespace-nowrap flex-shrink-0  ">
                                    {getPackageAlias(type)}
                                </TabsTrigger>
                            ))}
                        </TabsList>
                    </Tabs>
                </div>
            )}

            <Card className="shadow-sm">
                <CardContent className="border-b bg-gradient-to-r from-slate-50 via-white to-emerald-50/40 p-5">
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div className="flex min-w-0 flex-1 flex-col gap-1">
                            <div className="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700/80">
                                {t('Notification Template Library')}
                            </div>
                            <div className="relative w-full max-w-2xl">
                                <Search className="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={searchValue}
                                    onChange={(e) => setSearchValue(e.target.value)}
                                    placeholder={t('Search notification templates by subject...')}
                                    className="h-12 rounded-2xl border-slate-200 bg-white/90 pl-11 pr-11 text-base shadow-sm transition focus-visible:ring-emerald-500"
                                />
                                {searchValue && (
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
                                {t('Typing automatically filters notification templates for the selected type.')}
                            </p>
                        </div>
                        <span className="inline-flex h-10 w-fit items-center rounded-full border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700">
                            {t('List View')}
                        </span>
                    </div>
                </CardContent>



                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={notificationTemplates.data}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={Bell}
                                        title={t('No notification templates found')}
                                        description={t('Notification templates will appear here.')}

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
                            routeName="notification-templates.index"
                            filters={{type: activeType, action: searchValue, sort: sortField, direction: sortDirection}}
                            className="h-11 w-40 rounded-xl bg-white"
                        />
                        <div className="flex-1">
                            <Pagination
                                data={notificationTemplates}
                                routeName="notification-templates.index"
                                filters={{type: activeType, action: searchValue, per_page: perPage, sort: sortField, direction: sortDirection}}
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
