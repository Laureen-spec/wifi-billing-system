<?php

namespace Workdo\Pos\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Workdo\Pos\Models\Pos;
use Workdo\Pos\Models\PosReturn;
use Workdo\Pos\Models\PosBillingCounter;
use Workdo\ProductService\Models\ProductServiceItem;
use App\Models\User;
use Carbon\Carbon;
use Workdo\Pos\Models\PosPayment;
use Workdo\Pos\Models\PosItem;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-pos-dashboard')) {
            $user = Auth::user();
            $userType = $user->type;

            // Route to appropriate dashboard based on user type
            switch ($userType) {
                case 'company':
                    return $this->companyDashboard();
                case 'client':
                default:
                    return $this->clientDashboard();
            }
        }
    }

    private function companyDashboard()
    {
        $creatorId = creatorId();
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        $totalSales = Pos::where('created_by', $creatorId)->count();
        $totalRevenue = PosPayment::where('created_by', $creatorId)->sum('discount_amount');
        $avgTransaction = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        // Product Stats
        $totalProducts = ProductServiceItem::where('created_by', $creatorId)
            ->where('type', '!=', 'service')
            ->count();

        $returnSubquery = DB::table('pos_return_items')
            ->join('pos_returns', 'pos_return_items.return_id', '=', 'pos_returns.id')
            ->where('pos_returns.created_by', $creatorId)
            ->whereIn('pos_returns.status', ['approved', 'completed'])
            ->select('pos_return_items.product_id')
            ->selectRaw('SUM(pos_return_items.return_quantity) as total_return_quantity')
            ->selectRaw('SUM(pos_return_items.total_amount) as total_return_amount')
            ->groupBy('pos_return_items.product_id');

        // Top Products
        $topProducts = DB::table('pos_items')
            ->join('pos', 'pos_items.pos_id', '=', 'pos.id')
            ->join('product_service_items', 'pos_items.product_id', '=', 'product_service_items.id')
            ->leftJoinSub($returnSubquery, 'returns', function ($join) {
                $join->on('pos_items.product_id', '=', 'returns.product_id');
            })
            ->where('pos.created_by', $creatorId)
            ->select(
                'product_service_items.name',
                DB::raw('GREATEST(SUM(pos_items.quantity) - IFNULL(returns.total_return_quantity, 0), 0) as total_quantity'),
                DB::raw('GREATEST(SUM(pos_items.total_amount) - IFNULL(returns.total_return_amount, 0), 0) as total_revenue')
            )
            ->groupBy('pos_items.product_id', 'product_service_items.name', 'returns.total_return_quantity', 'returns.total_return_amount')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();

        // Recent Sales
        $recentSales = Pos::with(['customer:id,name', 'warehouse:id,name'])
            ->where('created_by', $creatorId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($sale) {
                $payment = PosPayment::where('pos_id', $sale->id)->first();
                $sale->total = $payment ? $payment->discount_amount : 0;
                return $sale;
            });

        // Customer Stats
        $totalCustomers = User::whereHas('roles', function ($query) {
            $query->where('name', 'client');
        })->where('created_by', $creatorId)->count();

        $walkInSales = Pos::where('created_by', $creatorId)
            ->whereNull('customer_id')
            ->count();

        // Returns Stats
        $totalReturns = PosReturn::where('created_by', $creatorId)->count();
        $returnsAmount = PosReturn::where('created_by', $creatorId)
            ->whereIn('status', ['approved', 'completed'])
            ->sum('total_amount');

        // Recent Returns
        $recentReturns = PosReturn::with(['customer:id,name'])
            ->where('created_by', $creatorId)
            ->latest()
            ->limit(5)
            ->get();

        // Last 10 days sales report
        $last10DaysSales = [];
        for ($i = 9; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailySales = DB::table('pos_payments')
                ->join('pos', 'pos_payments.pos_id', '=', 'pos.id')
                ->where('pos.created_by', $creatorId)
                ->whereDate('pos_payments.created_at', $date)
                ->sum('pos_payments.discount_amount');
            $last10DaysSales[] = [
                'date' => $date->format('M d'),
                'sales' => $dailySales
            ];
        }

        // Out of stock products warehouse wise
        $outOfStockProductsList = ProductServiceItem::where('created_by', $creatorId)
            ->where('type', '!=', 'service')
            ->with(['warehouseStocks.warehouse'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->flatMap(function ($product) {
                return $product->warehouseStocks
                    ->filter(function ($stock) {
                        return $stock->quantity == 0;
                    })
                    ->map(function ($stock) use ($product) {
                        return [
                            'product_name' => $product->name,
                            'sku' => $product->sku ?? 'N/A',
                            'warehouse_name' => $stock->warehouse->name ?? 'Unknown',
                            'stock' => $stock->quantity,
                            'image' => $product->image ?? null
                        ];
                    });
            })
            ->values();

        // Billing Counter wise daily sales data (Today)
        $counterWiseSales = PosBillingCounter::where('created_by', $creatorId)
            ->where('status', true)
            ->get()
            ->map(function ($counter) use ($creatorId, $today) {
                $todaySales = Pos::where('billing_counter_id', $counter->id)
                    ->where('created_by', $creatorId)
                    ->whereDate('created_at', $today)
                    ->count();

                $todayRevenue = DB::table('pos_payments')
                    ->join('pos', 'pos_payments.pos_id', '=', 'pos.id')
                    ->where('pos.billing_counter_id', $counter->id)
                    ->where('pos.created_by', $creatorId)
                    ->whereDate('pos_payments.created_at', $today)
                    ->sum('pos_payments.discount_amount');

                return [
                    'counter_name' => $counter->name,
                    'counter_code' => $counter->code,
                    'today_sales' => $todaySales,
                    'today_revenue' => $todayRevenue,
                ];
            });
        return Inertia::render('Pos/Dashboard/Index', [
            'stats' => [
                'total_sales' => $totalSales,
                'total_revenue' => $totalRevenue,
                'avg_transaction' => $avgTransaction,
                'total_products' => $totalProducts,
                'total_customers' => $totalCustomers,
                'walk_in_sales' => $walkInSales,
                'total_returns' => $totalReturns,
                'returns_amount' => $returnsAmount,
            ],
            'topProducts' => $topProducts,
            'recentSales' => $recentSales,
            'recentReturns' => $recentReturns,
            'last10DaysSales' => $last10DaysSales,
            'outOfStockProductsList' => $outOfStockProductsList,
            'counterWiseSales' => $counterWiseSales,

        ]);
    }
    private function clientDashboard()
    {
        $userId = Auth::id();
        $creatorId = creatorId();

        // Client's purchase stats
        $clientSales = Pos::where('customer_id', $userId)
            ->where('created_by', $creatorId)
            ->with(['items.product'])
            ->get();

        $totalPurchases = $clientSales->count();
        $totalSpent = $clientSales->sum(function ($sale) {
            return $sale->items->sum('total_amount');
        });
        $avgOrderValue = $totalPurchases > 0 ? $totalSpent / $totalPurchases : 0;

        // Recent purchases
        $recentPurchases = Pos::where('customer_id', $userId)
            ->where('created_by', $creatorId)
            ->with(['warehouse:id,name', 'items.product:id,name,sku'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($sale) {
                $sale->total = $sale->items->sum('total_amount');
                return $sale;
            });

        // Purchased products
        $purchasedProducts = PosItem::whereHas('sale', function ($query) use ($userId, $creatorId) {
            $query->where('customer_id', $userId)->where('created_by', $creatorId);
        })
            ->with('product')
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                $product = $items->first()->product;
                return [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'total_quantity' => $items->sum('quantity'),
                    'total_spent' => $items->sum('total_amount'),
                    'orders_count' => $items->pluck('pos_id')->unique()->count()
                ];
            })
            ->sortByDesc('total_spent')
            ->take(10)
            ->values();

        // Daily spending for last 10 days
        $monthlySpending = [];
        for ($i = 9; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $spending = PosItem::whereHas('sale', function ($query) use ($userId, $creatorId) {
                $query->where('customer_id', $userId)->where('created_by', $creatorId);
            })
                ->whereDate('created_at', $date)
                ->sum('total_amount');
            $monthlySpending[] = [
                'month' => $date->format('M d'),
                'spending' => $spending
            ];
        }

        return Inertia::render('Pos/Dashboard/ClientDashboard', [
            'stats' => [
                'total_purchases' => $totalPurchases,
                'total_spent' => $totalSpent,
                'avg_order_value' => $avgOrderValue,
            ],
            'recentPurchases' => $recentPurchases,
            'purchasedProducts' => $purchasedProducts,
            'monthlySpending' => $monthlySpending,
        ]);
    }
}
