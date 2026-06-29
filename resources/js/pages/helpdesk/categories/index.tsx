import { useEffect, useRef, useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { usePageButtons } from '@/hooks/usePageButtons';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Dialog } from "@/components/ui/dialog";
import { Plus, Edit as EditIcon, Trash2, Tag, Search, X } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Input } from "@/components/ui/input";
import { PerPageSelector } from "@/components/ui/per-page-selector";
import { DataTable } from "@/components/ui/data-table";
import NoRecordsFound from '@/components/no-records-found';
import { Pagination } from "@/components/ui/pagination";
import { ConfirmationDialog } from "@/components/ui/confirmation-dialog";

import Create from './create';
import Edit from './edit';
import { HelpdeskCategoriesIndexProps, HelpdeskCategoryFilters, HelpdeskCategoryModalState } from './types';

export default function Index() {
    const { categories, auth } = usePage<HelpdeskCategoriesIndexProps>().props;
    const { t } = useTranslation();
    const urlParams = new URLSearchParams(window.location.search);

    const [searchName, setSearchName] = useState(urlParams.get('name') || '');
    const didMountSearch = useRef(false);

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [modalState, setModalState] = useState<HelpdeskCategoryModalState>({
        isOpen: false,
        mode: '',
        data: null
    });

    useFlashMessages();

    const pageButtons = usePageButtons('helpdeskCategoryBtn', 'Test data');

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'helpdesk-categories.destroy',
        defaultMessage: t('Are you sure you want to delete this Helpdesk category?')
    });

    const handleFilter = () => {
        router.get(route('helpdesk-categories.index'), {
            name: searchName,
            per_page: perPage,
            sort: sortField,
            direction: sortDirection
        }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('helpdesk-categories.index'), {
            name: searchName,
            per_page: perPage,
            sort: field,
            direction
        }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setSearchName('');
        router.get(route('helpdesk-categories.index'), {per_page: perPage});
    };

    useEffect(() => {
        if (!didMountSearch.current) {
            didMountSearch.current = true;
            return;
        }

        const timer = window.setTimeout(() => {
            handleFilter();
        }, 450);

        return () => window.clearTimeout(timer);
    }, [searchName]);

    const openModal = (mode: 'add' | 'edit', data: any = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },
        {
            key: 'description',
            header: t('Description')
        },
        {
            key: 'color',
            header: t('Color'),
            render: (value: string) => (
                <div className="flex items-center gap-2">
                    <div className="w-4 h-4 rounded" style={{ backgroundColor: value }}></div>
                </div>
            )
        },
        {
            key: 'is_active',
            header: t('Status'),
            sortable: true,
            render: (value: boolean) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }`}>
                    {value ? t('Active') : t('Inactive')}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['edit-helpdesk-categories', 'delete-helpdesk-categories'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, category: any) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-helpdesk-categories') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', category)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent><p>{t('Edit')}</p></TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-helpdesk-categories') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(category.id)}
                                        className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent><p>{t('Delete')}</p></TooltipContent>
                            </Tooltip>
                        )}
                    </TooltipProvider>
                </div>
            )
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: t('Helpdesk')}, {label: t('Helpdesk Categories')}]}
            pageTitle={t('Manage Helpdesk Categories')}
            pageActions={
                <div className="flex gap-2">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('create-helpdesk-categories') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => openModal('add')}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Create')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {pageButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                    </TooltipProvider>
                </div>
            }
        >
            <Head title={t('Helpdesk Categories')} />

            <Card className="shadow-sm">
                <CardContent className="border-b border-slate-200 bg-white p-4 sm:p-5">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p className="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">{t('Helpdesk categories')}</p>
                            <p className="mt-1 text-sm text-slate-500">{t('Organize tickets by department, issue type, or service queue.')}</p>
                        </div>
                        <div className="relative w-full lg:max-w-md">
                            <Search className="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                value={searchName}
                                onChange={(e) => setSearchName(e.target.value)}
                                placeholder={t('Search categories...')}
                                className="h-12 rounded-2xl border-slate-200 bg-white pl-11 pr-10 shadow-sm focus-visible:ring-emerald-500/20"
                            />
                            {searchName && (
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="absolute right-3 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                    aria-label={t('Clear search')}
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            )}
                        </div>
                    </div>
                </CardContent>



                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={categories.data}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={Tag}
                                        title={t('No categories found')}
                                        description={t('Get started by creating your first category.')}
                                        hasFilters={!!searchName}
                                        onClearFilters={clearFilters}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="border-t border-slate-200 bg-white px-4 py-3 sm:px-5">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <PerPageSelector
                            routeName="helpdesk-categories.index"
                            filters={{ name: searchName, sort: sortField, direction: sortDirection }}
                            defaultValue={perPage}
                            className="h-11 w-36 rounded-xl border-slate-200 bg-white"
                        />
                        <div className="flex-1">
                            <Pagination
                                data={categories}
                                routeName="helpdesk-categories.index"
                                filters={{name: searchName, per_page: perPage, sort: sortField, direction: sortDirection}}
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <Edit
                        category={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Category')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
