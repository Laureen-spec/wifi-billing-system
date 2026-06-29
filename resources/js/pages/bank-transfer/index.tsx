import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import { formatAdminCurrency, formatDate as formatDateHelper, getPackageFavicon, getPackageAlias } from '@/utils/helpers';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Pagination } from '@/components/ui/pagination';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { FilterButton } from '@/components/ui/filter-button';
import { Eye, Check, X, Download, FileText, Trash2, Search } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import NoRecordsFound from '@/components/no-records-found';

interface BankTransferRequest {
    id: number;
    order_id: string;
    status: 'pending' | 'approved' | 'rejected';
    price_currency: string;
    attachment: string;
    price: number;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    plan?: {
        id: number;
        name: string;
    };
    request: {
        plan_id: number;
        user_counter_input: number;
        storage_counter_input: number;
        time_period: string;
        coupon_code?: string;
        addon_name?: string;
    };
}

interface Props {
    requests: {
        data: BankTransferRequest[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        links: any;
        meta: any;
    };
}

export default function BankTransferIndex({ requests }: Props) {
    const { t } = useTranslation();
    const pageProps = usePage().props as any;
    const { auth, imageUrlPrefix } = pageProps;
    const urlParams = new URLSearchParams(window.location.search);

    const [viewingRequest, setViewingRequest] = useState<BankTransferRequest | null>(null);
    const [processingId, setProcessingId] = useState<number | null>(null);
    const [deleteDialog, setDeleteDialog] = useState<{ isOpen: boolean; requestId: number | null }>({ isOpen: false, requestId: null });
    const [approveDialog, setApproveDialog] = useState<{ isOpen: boolean; request: BankTransferRequest | null }>({ isOpen: false, request: null });
    const [rejectDialog, setRejectDialog] = useState<{ isOpen: boolean; request: BankTransferRequest | null }>({ isOpen: false, request: null });
    const [filters, setFilters] = useState({
        order_number: urlParams.get('order_number') || '',
        status: urlParams.get('status') || '',
        user_name: urlParams.get('user_name') || ''
    });
    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'desc');
    const viewMode = 'list';
    const [showFilters, setShowFilters] = useState(false);
    const firstSearchRender = useRef(true);

    useFlashMessages();

    const getStatusBadge = (status: string) => {
        const variants = {
            pending: 'px-2 py-1 rounded-full text-sm bg-yellow-100 text-yellow-800',
            approved: 'px-2 py-1 rounded-full text-sm bg-green-100 text-green-800',
            rejected: 'px-2 py-1 rounded-full text-sm bg-red-100 text-red-800'
        };

        return (
            <span className={variants[status as keyof typeof variants]}>
                {t(status.charAt(0).toUpperCase() + status.slice(1))}
            </span>
        );
    };

    const handleFilter = () => {
        router.get(route('bank-transfer.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('bank-transfer.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ order_number: '', status: '', user_name: '' });
        router.get(route('bank-transfer.index'), {per_page: perPage, view: viewMode});
    };

    const clearSearch = () => {
        const nextFilters = {...filters, order_number: ''};
        setFilters(nextFilters);
        router.get(route('bank-transfer.index'), {...nextFilters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
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
            router.get(route('bank-transfer.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
                preserveState: true,
                replace: true
            });
        }, 450);

        return () => window.clearTimeout(timeout);
    }, [filters.order_number]);

    const handleApprove = (request: BankTransferRequest) => {
        setApproveDialog({ isOpen: true, request });
    };

    const confirmApprove = () => {
        if (approveDialog.request) {
            setProcessingId(approveDialog.request.id);
            router.post(route('bank-transfer.update', approveDialog.request.id), { status: 'approved' }, {
                onFinish: () => setProcessingId(null)
            });
        }
        setApproveDialog({ isOpen: false, request: null });
    };

    const handleReject = (request: BankTransferRequest) => {
        setRejectDialog({ isOpen: true, request });
    };

    const confirmReject = () => {
        if (rejectDialog.request) {
            setProcessingId(rejectDialog.request.id);
            router.post(route('bank-transfer.update', rejectDialog.request.id), { status: 'rejected' }, {
                onFinish: () => setProcessingId(null)
            });
        }
        setRejectDialog({ isOpen: false, request: null });
    };

    const handleDelete = (requestId: number) => {
        setProcessingId(requestId);
        router.delete(route('bank-transfer.destroy', requestId), {
            onFinish: () => setProcessingId(null)
        });
        setDeleteDialog({ isOpen: false, requestId: null });
    };

    const downloadReceipt = (attachment: string) => {
        const link = document.createElement('a');
        link.href = `${imageUrlPrefix}/${attachment}`;
        link.download = attachment.split('/').pop() || 'receipt';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    const tableColumns = [
        {
            key: 'order_id',
            header: t('Order Number'),
            sortable: true,
            render: (_: any, request: BankTransferRequest) => request.order_id
        },
        {
            key: 'user',
            header: t('User'),
            sortable: false,
            render: (_: any, request: BankTransferRequest) => (
                <div>
                    <div className="font-medium">{request.user?.name || '-'}</div>
                    <div className="text-sm text-gray-500">{request.user?.email || '-'}</div>
                </div>
            )
        },
        {
            key: 'plan',
            header: t('Plan'),
            render: (_: any, request: BankTransferRequest) => (
                <div>
                    <div>{request.plan?.name || '-'}</div>
                </div>
            )
        },
        {
            key: 'amount',
            header: t('Amount'),
            render: (_: any, request: BankTransferRequest) => formatAdminCurrency(request.price, pageProps)
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: true,
            render: (_: any, request: BankTransferRequest) => getStatusBadge(request.status)
        },
        {
            key: 'created_at',
            header: t('Date'),
            sortable: true,
            render: (_: any, request: BankTransferRequest) => formatDateHelper(request.created_at, pageProps)
        },
        {
            key: 'actions',
            header: t('Actions'),
            render: (_: any, request: BankTransferRequest) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {request.status === 'pending' && auth?.user?.roles?.includes('superadmin') && (
                            <>
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => handleApprove(request)}
                                            disabled={processingId === request.id}
                                            className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                        >
                                            <Check className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{t('Approve')}</p>
                                    </TooltipContent>
                                </Tooltip>
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => handleReject(request)}
                                            disabled={processingId === request.id}
                                            className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                        >
                                            <X className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{t('Reject')}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </>
                        )}
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setViewingRequest(request)}
                                    className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                >
                                    <Eye className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('View')}</p>
                            </TooltipContent>
                        </Tooltip>
                        {(auth?.user?.roles?.includes('superadmin') || request.status === 'pending') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setDeleteDialog({ isOpen: true, requestId: request.id })}
                                        disabled={processingId === request.id}
                                        className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
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
            breadcrumbs={[{ label: t('Bank Transfer Requests') }]}
            pageTitle={t('Manage Bank Transfer Requests')}
        >
            <Head title={t('Bank Transfer Requests')} />

            <Card className="shadow-sm">
                <CardContent className="border-b bg-gradient-to-r from-slate-50 via-white to-emerald-50/40 p-5">
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div className="flex min-w-0 flex-1 flex-col gap-1">
                            <div className="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700/80">
                                {t('Transfer Review Desk')}
                            </div>
                            <div className="relative w-full max-w-2xl">
                                <Search className="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={filters.order_number}
                                    onChange={(e) => setFilters({...filters, order_number: e.target.value})}
                                    placeholder={t('Search transfers by order number...')}
                                    className="h-12 rounded-2xl border-slate-200 bg-white/90 pl-11 pr-11 text-base shadow-sm transition focus-visible:ring-emerald-500"
                                />
                                {filters.order_number && (
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
                                {t('Typing automatically filters bank transfer requests. Use filters for status and user name.')}
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
                                    const activeFilters = [filters.status, filters.user_name].filter(Boolean).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pending">{t('Pending')}</SelectItem>
                                        <SelectItem value="approved">{t('Approved')}</SelectItem>
                                        <SelectItem value="rejected">{t('Rejected')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('User Name')}</label>
                                <Input
                                    placeholder={t('Filter by user name')}
                                    value={filters.user_name}
                                    onChange={(e) => setFilters({...filters, user_name: e.target.value})}
                                />
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
                                data={requests.data}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={FileText}
                                        title={t('No bank transfer requests found')}
                                        description={t('Bank transfer requests will appear here when users submit them.')}
                                        hasFilters={!!(filters.order_number || filters.status || filters.user_name)}
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
                            routeName="bank-transfer.index"
                            filters={{...filters, sort: sortField, direction: sortDirection, view: viewMode}}
                            className="h-11 w-40 rounded-xl bg-white"
                        />
                        <div className="flex-1">
                            <Pagination
                                data={requests}
                                routeName="bank-transfer.index"
                                filters={{...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}}
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* View Request Dialog */}
            <Dialog open={!!viewingRequest} onOpenChange={() => setViewingRequest(null)}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{t('Bank Transfer Request Details')}</DialogTitle>
                        <DialogDescription>
                            {t('Order')}: {viewingRequest?.order_id}
                        </DialogDescription>
                    </DialogHeader>

                    {viewingRequest && (
                        <div className="space-y-6">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">{t('User')}</Label>
                                    <div className="mt-1">
                                        <div className="font-medium">{viewingRequest.user?.name || '-'}</div>
                                        <div className="text-sm text-gray-500">{viewingRequest.user?.email || '-'}</div>
                                    </div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">{t('Plan')}</Label>
                                    <div className="mt-1">
                                        <div className="font-medium">{viewingRequest.plan?.name || '-'}</div>
                                        {viewingRequest.request?.addon_name && (
                                            <div className="text-sm text-gray-600 mt-1">
                                                {t('Add-on')}: {viewingRequest.request.addon_name}
                                            </div>
                                        )}
                                    </div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">{t('Amount')}</Label>
                                    <div className="mt-1 font-medium">{formatAdminCurrency(viewingRequest.price, pageProps)}</div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">{t('Status')}</Label>
                                    <div className="mt-1">{getStatusBadge(viewingRequest.status)}</div>
                                </div>
                            </div>

                            {viewingRequest.request?.coupon_code && (
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">{t('Coupon Code')}</Label>
                                    <div className="mt-1 font-medium">{viewingRequest.request.coupon_code}</div>
                                </div>
                            )}

                            {viewingRequest.attachment && (
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">{t('Payment Receipt')}</Label>
                                    <div className="mt-1">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => downloadReceipt(viewingRequest.attachment)}
                                        >
                                            <Download className="w-4 h-4 mr-2" />
                                            {t('Download Receipt')}
                                        </Button>
                                    </div>

                                    {/* Add-on List */}
                                    <div className="mt-3">
                                        <Label className="text-sm font-medium text-gray-500">{t('Add-ons')}</Label>
                                        <div className="mt-2">
                                            {(() => {
                                                const requestData = typeof viewingRequest.request === 'string' ? JSON.parse(viewingRequest.request) : viewingRequest.request;
                                                return requestData?.user_module_input ? (
                                                    <div className="max-h-96 overflow-y-auto">
                                                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                            {requestData.user_module_input.split(',').map((module: string, index: number) => (
                                                                module.trim() && (
                                                                    <div key={index} className="flex items-center gap-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                                                                        <img
                                                                            src={getPackageFavicon(module.trim())}
                                                                            alt={module.trim()}
                                                                            className="w-6 h-6 flex-shrink-0"
                                                                        />
                                                                        <span className="text-sm font-medium truncate">{getPackageAlias(module.trim())}</span>
                                                                    </div>
                                                                )
                                                            ))}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <div className="text-sm text-gray-500">{t('No add-ons')}</div>
                                                );
                                            })()}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {viewingRequest?.status === 'pending' && auth?.user?.type === 'superadmin' && (
                        <DialogFooter className="flex justify-end gap-2 pt-4">
                            <Button
                                onClick={() => {
                                    handleApprove(viewingRequest);
                                    setViewingRequest(null);
                                }}
                                disabled={processingId === viewingRequest.id}
                                className="bg-green-600 hover:bg-green-700"
                            >
                                <Check className="w-4 h-4" />
                                {t('Approve')}
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={() => {
                                    handleReject(viewingRequest);
                                    setViewingRequest(null);
                                }}
                                disabled={processingId === viewingRequest.id}
                            >
                                <X className="w-4 h-4" />
                                {t('Reject')}
                            </Button>
                        </DialogFooter>
                    )}
                </DialogContent>
            </Dialog>

            <ConfirmationDialog
                open={deleteDialog.isOpen}
                onOpenChange={(open) => setDeleteDialog({ isOpen: open, requestId: null })}
                title={t('Delete Request')}
                message={t('Are you sure you want to delete this bank transfer request? This action cannot be undone.')}
                confirmText={t('Delete')}
                onConfirm={() => deleteDialog.requestId && handleDelete(deleteDialog.requestId)}
                variant="destructive"
            />

            <ConfirmationDialog
                open={approveDialog.isOpen}
                onOpenChange={(open) => setApproveDialog({ isOpen: open, request: null })}
                title={t('Approve Request')}
                message={t('Are you sure you want to approve this bank transfer request?')}
                confirmText={t('Approve')}
                onConfirm={confirmApprove}
                variant="default"
            />

            <ConfirmationDialog
                open={rejectDialog.isOpen}
                onOpenChange={(open) => setRejectDialog({ isOpen: open, request: null })}
                title={t('Reject Request')}
                message={t('Are you sure you want to reject this bank transfer request?')}
                confirmText={t('Reject')}
                onConfirm={confirmReject}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
