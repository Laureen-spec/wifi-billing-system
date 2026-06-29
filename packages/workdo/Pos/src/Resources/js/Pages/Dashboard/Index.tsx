import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ShoppingCart, Package, Users, RotateCcw, Monitor, TrendingUp, AlertTriangle } from 'lucide-react';
import { formatDate, formatCurrency } from '@/utils/helpers';
import { LineChart } from '@/components/charts';
import { getImagePath } from '@/utils/helpers';

interface PosProps {
    stats: {
        total_sales: number;
        total_revenue: number;
        avg_transaction: number;
        total_products: number;
        total_customers: number;
        walk_in_sales: number;
        total_returns: number;
        returns_amount: number;
    };
    topProducts: Array<{
        name: string;
        total_quantity: number;
        total_revenue: number;
    }>;
    recentSales: Array<{
        id: number;
        sale_number: string;
        total: number;
        created_at: string;
        customer?: { name: string };
        warehouse?: { name: string };
    }>;
    recentReturns: Array<{
        id: number;
        return_number: string;
        total_amount: number;
        created_at: string;
        status: string;
        customer?: { name: string };
    }>;
    last10DaysSales: Array<{
        date: string;
        sales: number;
    }>;
    outOfStockProductsList: Array<{
        product_name: string;
        sku: string;
        warehouse_name: string;
        stock: number;
        image?: string;
    }>;
    counterWiseSales: Array<{
        counter_name: string;
        counter_code: string;
        today_sales: number;
        today_revenue: number;
    }>;
}

const returnStatusConfig: Record<string, { bg: string; text: string; label: string }> = {
    completed: { bg: 'bg-green-100', text: 'text-green-700', label: 'Completed' },
    approved:  { bg: 'bg-blue-100',  text: 'text-blue-700',  label: 'Approved'  },
    pending:   { bg: 'bg-yellow-100',text: 'text-yellow-700',label: 'Pending'   },
    rejected:  { bg: 'bg-red-100',   text: 'text-red-700',   label: 'Rejected'  },
};

const rankColors = ['#f59e0b', '#94a3b8', '#cd7c3e'];
const rankLabels = ['1st', '2nd', '3rd'];

export default function PosIndex({ stats, topProducts, recentSales, recentReturns, last10DaysSales, outOfStockProductsList, counterWiseSales }: PosProps) {
    const { t } = useTranslation();

    const maxRevenue = topProducts?.length
        ? Math.max(...topProducts.map(p => Number(p.total_revenue)))
        : 1;

    const totalCounterRevenue = counterWiseSales?.reduce((sum, c) => sum + Number(c.today_revenue || 0), 0) || 0;

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('POS') }]}
            pageTitle={t('POS Dashboard')}
        >
            <Head title={t('POS Dashboard')} />

            <div className="space-y-6">

                {/* ── Key Metrics Cards ──────────────────────────────────── */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Total Sales */}
                    <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200 hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-medium text-blue-700">{t('Total Sales')}</CardTitle>
                            <div className="bg-blue-200/50 rounded-lg p-2">
                                <ShoppingCart className="h-4 w-4 text-blue-600" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-700">{stats.total_sales}</div>
                            <p className="text-xs text-blue-600 opacity-80 mt-1">{formatCurrency(stats.total_revenue)} {t('revenue')}</p>
                        </CardContent>
                    </Card>

                    {/* Total Returns */}
                    <Card className="bg-gradient-to-br from-red-50 to-red-100 border-red-200 hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-medium text-red-700">{t('Total Returns')}</CardTitle>
                            <div className="bg-red-200/50 rounded-lg p-2">
                                <RotateCcw className="h-4 w-4 text-red-600" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-700">{stats.total_returns || 0}</div>
                            <p className="text-xs text-red-600 opacity-80 mt-1">{formatCurrency(stats.returns_amount || 0)} {t('returned')}</p>
                        </CardContent>
                    </Card>

                    {/* Avg Transaction */}
                    <Card className="bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200 hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-medium text-purple-700">{t('Avg Transaction')}</CardTitle>
                            <div className="bg-purple-200/50 rounded-lg p-2">
                                <Users className="h-4 w-4 text-purple-600" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-700">{formatCurrency(stats.avg_transaction)}</div>
                            <p className="text-xs text-purple-600 opacity-80 mt-1">{stats.total_customers} {t('customers')}</p>
                        </CardContent>
                    </Card>

                    {/* Total Products */}
                    <Card className="bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200 hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-medium text-orange-700">{t('Total Products')}</CardTitle>
                            <div className="bg-orange-200/50 rounded-lg p-2">
                                <Package className="h-4 w-4 text-orange-600" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-orange-700">{stats.total_products}</div>
                            <p className="text-xs text-orange-600 opacity-80 mt-1">{stats.walk_in_sales} {t('walk-in sales')}</p>
                        </CardContent>
                    </Card>
                </div>

                {/* ── Last 10 Days Sales Report (Full Width) ─────────────── */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <TrendingUp className="h-4 w-4 text-primary" />
                            {t('Last 10 Days Sales Report')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <LineChart
                            data={last10DaysSales}
                            height={300}
                            showTooltip={true}
                            showGrid={true}
                            lines={[{ dataKey: 'sales', color: '#6366f1', name: t('Daily Sales') }]}
                            xAxisKey="date"
                            showLegend={true}
                        />
                    </CardContent>
                </Card>

                {/* ── Out of Stock + Counter Wise Sales ────────────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Out of Stock */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <AlertTriangle className="h-4 w-4 text-red-500" />
                                {t('Out of Stock Products')}
                                {outOfStockProductsList?.length > 0 && (
                                    <span className="ml-auto text-xs font-semibold bg-red-100 text-red-600 px-2 py-0.5 rounded-full">
                                        {outOfStockProductsList.length}
                                    </span>
                                )}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 max-h-72 overflow-y-auto pr-1">
                                {outOfStockProductsList?.length > 0 ? (
                                    outOfStockProductsList.map((item, index) => (
                                        <div key={index} className="flex items-center gap-3 p-3 rounded-xl border border-red-100 bg-red-50/50 hover:bg-red-50 transition-colors">
                                            <div className="w-10 h-10 bg-white rounded-lg border border-red-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                                {item.image ? (
                                                    <img
                                                        src={getImagePath(item.image)}
                                                        alt={item.product_name}
                                                        className="w-full h-full object-cover"
                                                        onError={(e) => {
                                                            const t = e.target as HTMLImageElement;
                                                            t.style.display = 'none';
                                                            if (t.parentElement) t.parentElement.innerHTML = '<svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';
                                                        }}
                                                    />
                                                ) : (
                                                    <Package className="w-5 h-5 text-gray-300" />
                                                )}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="font-medium text-sm text-gray-800 truncate">{item.product_name}</p>
                                                <p className="text-xs text-gray-500 truncate">SKU: {item.sku} · {item.warehouse_name}</p>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center py-12 text-gray-400">
                                        <Package className="h-10 w-10 mx-auto mb-3 opacity-30" />
                                        <p className="text-sm font-medium">{t('All products are in stock')}</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Counter Wise Sales */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Monitor className="h-4 w-4 text-blue-600" />
                                {t('Billing Counter — Today')}
                                <span className="ml-auto text-sm font-bold text-green-600">
                                    {formatCurrency(totalCounterRevenue)}
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 max-h-72 overflow-y-auto pr-1">
                                {counterWiseSales?.length > 0 ? (
                                    counterWiseSales.map((counter, index) => (
                                        <div key={index} className="flex items-center gap-3 p-3 rounded-xl border border-blue-100 bg-gradient-to-r from-blue-50/60 to-white hover:from-blue-100/70 transition-colors">
                                            <div className="flex-1 min-w-0">
                                                <p className="font-medium text-sm text-gray-800 truncate">{counter.counter_name}</p>
                                                <p className="text-xs text-gray-500">{t('Code')}: {counter.counter_code}</p>
                                            </div>
                                            <div className="text-right flex-shrink-0">
                                                <p className="text-sm font-bold text-green-600">{formatCurrency(counter.today_revenue)}</p>
                                                <p className="text-xs text-gray-500">{counter.today_sales} {t('sales')}</p>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center py-12 text-gray-400">
                                        <Monitor className="h-10 w-10 mx-auto mb-3 opacity-30" />
                                        <p className="text-sm font-medium">{t('No counter data available')}</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* ── Top Products · Recent Transactions · Recent Returns ───── */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Top Selling Products */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Package className="h-4 w-4 text-primary" />
                                {t('Top Selling Products')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {topProducts?.length > 0 ? (
                                <div className="space-y-3">
                                    {topProducts.slice(0, 5).map((product, index) => {
                                        const barWidth = Math.round((Number(product.total_revenue) / maxRevenue) * 100);
                                        return (
                                            <div key={index} className="group">
                                                <div className="flex items-center gap-2 mb-1">
                                                    {index < 3 ? (
                                                        <span
                                                            className="text-xs font-bold w-7 h-5 flex items-center justify-center rounded"
                                                            style={{ backgroundColor: rankColors[index] + '22', color: rankColors[index] }}
                                                        >
                                                            {rankLabels[index]}
                                                        </span>
                                                    ) : (
                                                        <span className="text-xs text-gray-400 w-7 text-center">{index + 1}</span>
                                                    )}
                                                    <p className="flex-1 text-sm font-medium text-gray-800 truncate">{product.name}</p>
                                                    <p className="text-sm font-bold text-green-600 flex-shrink-0">{formatCurrency(product.total_revenue)}</p>
                                                </div>
                                                <div className="flex items-center gap-2 pl-9">
                                                    <div className="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                                        <div
                                                            className="h-full rounded-full transition-all duration-500"
                                                            style={{ width: `${barWidth}%`, backgroundColor: index < 3 ? rankColors[index] : '#6b7280' }}
                                                        />
                                                    </div>
                                                    <span className="text-xs text-gray-400 flex-shrink-0">{product.total_quantity} {t('units')}</span>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="text-center py-12 text-gray-400">
                                    <Package className="h-10 w-10 mx-auto mb-3 opacity-30" />
                                    <p className="text-sm font-medium">{t('No product data')}</p>
                                    <p className="text-xs">{t('Top products will appear here')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Transactions */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ShoppingCart className="h-4 w-4 text-primary" />
                                {t('Recent Transactions')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentSales?.length > 0 ? (
                                <div className="space-y-2">
                                    {recentSales.slice(0, 5).map((sale) => (
                                        <div key={sale.id} className="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                                            <div className="flex-1 min-w-0">
                                                <p className="font-medium text-sm text-gray-800">{sale.sale_number}</p>
                                                <p className="text-xs text-gray-500 truncate">{sale.customer?.name || t('Walk-in Customer')}</p>
                                            </div>
                                            <div className="text-right flex-shrink-0">
                                                <p className="text-sm font-bold text-gray-800">{formatCurrency(sale.total)}</p>
                                                <p className="text-xs text-gray-400">{formatDate(sale.created_at)}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12 text-gray-400">
                                    <ShoppingCart className="h-10 w-10 mx-auto mb-3 opacity-30" />
                                    <p className="text-sm font-medium">{t('No recent sales')}</p>
                                    <p className="text-xs">{t('New transactions will appear here')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Returns */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <RotateCcw className="h-4 w-4 text-red-500" />
                                {t('Recent Returns')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentReturns?.length > 0 ? (
                                <div className="space-y-2">
                                    {recentReturns.slice(0, 5).map((ret) => {
                                        const cfg = returnStatusConfig[ret.status?.toLowerCase()] ?? { bg: 'bg-gray-100', text: 'text-gray-600', label: ret.status };
                                        return (
                                            <div key={ret.id} className="flex items-center gap-3 p-3 rounded-xl bg-red-50/40 hover:bg-red-50 transition-colors">
                                                <div className="flex-1 min-w-0">
                                                    <p className="font-medium text-sm text-gray-800">{ret.return_number}</p>
                                                    <p className="text-xs text-gray-500 truncate">{ret.customer?.name || t('Walk-in Customer')}</p>
                                                </div>
                                                <div className="text-right flex-shrink-0">
                                                    <p className="text-sm font-bold text-red-600">{formatCurrency(ret.total_amount)}</p>
                                                    <span className={`text-xs px-2 py-0.5 rounded-full ${cfg.bg} ${cfg.text}`}>
                                                        {t(cfg.label)}
                                                    </span>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="text-center py-12 text-gray-400">
                                    <RotateCcw className="h-10 w-10 mx-auto mb-3 opacity-30" />
                                    <p className="text-sm font-medium">{t('No recent returns')}</p>
                                    <p className="text-xs">{t('Returns will appear here')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

            </div>
        </AuthenticatedLayout>
    );
}
