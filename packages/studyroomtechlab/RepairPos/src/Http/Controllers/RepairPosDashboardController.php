<?php

namespace StudyRoomTechLab\RepairPos\Http\Controllers;

use App\Http\Controllers\Controller;
use StudyRoomTechLab\RepairPos\Models\RepairCustomer;
use StudyRoomTechLab\RepairPos\Models\RepairJob;
use StudyRoomTechLab\RepairPos\Models\RepairProduct;
use StudyRoomTechLab\RepairPos\Models\RepairSale;

class RepairPosDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'customers' => RepairCustomer::count(),
            'jobs' => RepairJob::count(),
            'pending_jobs' => RepairJob::whereIn('status', ['received', 'diagnosing', 'repairing'])->count(),
            'completed_jobs' => RepairJob::where('status', 'completed')->count(),
            'products' => RepairProduct::count(),
            'low_stock' => RepairProduct::whereColumn('stock', '<=', 'low_stock_alert')->count(),
            'sales_total' => RepairSale::sum('total'),
            'sales_count' => RepairSale::count(),
        ];

        return view('repair-pos::dashboard.index', [
            'stats' => $stats,
            'recentJobs' => RepairJob::with('customer')->latest()->limit(7)->get(),
            'recentSales' => RepairSale::with('customer')->latest()->limit(7)->get(),
        ]);
    }
}
