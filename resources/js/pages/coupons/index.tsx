import { useEffect, useRef, useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { usePageButtons } from '@/hooks/usePageButtons';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit, Trash2, Ticket, Eye, Search, X } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { formatDate } from '@/utils/helpers';
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import Create from './create';
import EditCoupon from './edit';
import NoRecordsFound from '@/components/no-records-found';

import { Coupon, CouponsIndexProps, CouponFilters, CouponModalState } from './types';

export default function Index() {
    const { t } = useTranslation();
    const { coupons, auth, ...pageProps } = usePage<CouponsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);
    const currencySymbol = (pageProps as any)?.companyAllSetting?.currencySymbol || '$';

    const [filters, setFilters] = useState<CouponFilters>({
        name: urlParams.get('name') || '',
        code: urlParams.get('code') || '',
        type: urlParams.get('type') || '',
        status: urlParams.get('status') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');

    const [modalState, setModalState] = useState<CouponModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [showFilters, setShowFilters] = useState(false);
    const firstSearchRender = useRef(true);

    useFlashMessages();

    const pageButtons = usePageButtons('couponBtn','Test data');

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'coupons.destroy',
        defaultMessage: t('Are you sure you want to delete this coupon?')
    });

    const handleFilter = () => {
        router.get(route('coupons.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('coupons.index'), {...filters, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ name: '', code: '', type: '', status: '' });
        router.get(route('coupons.index'), {per_page: perPage});
    };

    const clearSearch = () => {
        const nextFilters = {...filters, name: ''};
        setFilters(nextFilters);
        router.get(route('coupons.index'), {...nextFilters, per_page: perPage, sort: sortField, direction: sortDirection}, {
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
            router.get(route('coupons.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
                preserveState: true,
                replace: true
            });
        }, 450);

        return () => window.clearTimeout(timeout);
    }, [filters.name]);

    const openModal = (mode: 'add' | 'edit', data: Coupon | null = null) => {
        setModalState({
            isOpen: true,
            mode,
            data
        });
    };

    const closeModal = () => {
        setModalState({
            isOpen: false,
            mode: '',
            data: null
        });
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },
        {
            key: 'code',
            header: t('Code'),
            sortable: true,
            render: (value: string) => (
                <span className="font-mono bg-gray-100 px-2 py-1 rounded-full text-sm">
                    {value}
                </span>
            )
        },
        {
            key: 'discount',
            header: t('Discount'),
            render: (value: number, coupon: Coupon) => (
                <span className="font-medium">
                    {coupon.type === 'percentage' ? `${value}%` : `${currencySymbol}${value}`}
                </span>
            )
        },
        {
            key: 'type',
            header: t('Type'),
            sortable: true,
            render: (value: string) => (
                <span className="capitalize px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                    {value}
                </span>
            )
        },
        {
            key: 'limit',
            header: t('Limit'),
            render: (value: number) => value || t('Unlimited')
        },
        {
            key: 'expiry_date',
            header: t('Expiry Date'),
            render: (value: string) => value ? formatDate(value, pageProps) : '-'
        },
        {
            key: 'status',
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
        {
            key: 'actions',
            header: t('Actions'),
            render: (_: any, coupon: Coupon) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('view-coupons') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button 
                                        variant="ghost" 
                                        size="sm" 
                                        onClick={() => router.visit(route('coupons.show', coupon.id))}
                                        className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                    >
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-coupons') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', coupon)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-coupons') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(coupon.id)}
                                        className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Delete')}</p>
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
            breadcrumbs={[{label: t('Coupons')}]}
            pageTitle={t('Manage Coupons')}
            pageActions={
                <div className="flex gap-2">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('create-coupons') && (
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
            <Head title={t('Coupons')} />

            <Card className="shadow-sm">
                <CardContent className="border-b bg-gradient-to-r from-slate-50 via-white to-emerald-50/40 p-5">
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div className="flex min-w-0 flex-1 flex-col gap-1">
                            <div className="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700/80">
                                {t('Coupon Registry')}
                            </div>
                            <div className="relative w-full max-w-2xl">
                                <Search className="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={filters.name}
                                    onChange={(e) => setFilters({...filters, name: e.target.value})}
                                    placeholder={t('Search coupons by name...')}
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
                                {t('Typing automatically filters coupons. Use filters for code, type, and status.')}
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
                                    const activeFilters = [filters.code, filters.type, filters.status].filter(Boolean).length;
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
                {showFilters && (
                    <CardContent className="p-6 bg-blue-50/30 border-b">
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Code')}</label>
                                <Input
                                    placeholder={t('Filter by code')}
                                    value={filters.code}
                                    onChange={(e) => setFilters({...filters, code: e.target.value})}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Type')}</label>
                                <Select value={filters.type} onValueChange={(value) => setFilters({...filters, type: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by type')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="percentage">{t('Percentage')}</SelectItem>
                                        <SelectItem value="flat">{t('Flat')}</SelectItem>
                                        <SelectItem value="fixed">{t('Fixed')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">{t('Active')}</SelectItem>
                                        <SelectItem value="0">{t('Inactive')}</SelectItem>
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

                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={coupons.data}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={Ticket}
                                        title={t('No coupons found')}
                                        description={t('Get started by creating your first coupon.')}
                                        hasFilters={!!(filters.name || filters.code || filters.type || filters.status)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-coupons"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Coupon')}
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
                            routeName="coupons.index"
                            filters={{...filters, sort: sortField, direction: sortDirection}}
                            className="h-11 w-40 rounded-xl bg-white"
                        />
                        <div className="flex-1">
                            <Pagination
                                data={coupons}
                                routeName="coupons.index"
                                filters={{...filters, per_page: perPage, sort: sortField, direction: sortDirection}}
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
                    <EditCoupon
                        coupon={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Coupon')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}