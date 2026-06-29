<?php

namespace StudyRoomTechLab\RentalManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use StudyRoomTechLab\RentalManagement\Models\RentalInvoice;
use StudyRoomTechLab\RentalManagement\Models\RentalPayment;
use StudyRoomTechLab\RentalManagement\Models\RentalProperty;
use StudyRoomTechLab\RentalManagement\Models\RentalTenant;
use StudyRoomTechLab\RentalManagement\Models\RentalUnit;

class RentalDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'properties' => RentalProperty::count(),
            'units' => RentalUnit::count(),
            'vacant_units' => RentalUnit::where('status', 'vacant')->count(),
            'occupied_units' => RentalUnit::where('status', 'occupied')->count(),
            'tenants' => RentalTenant::where('status', 'active')->count(),
            'unpaid_invoices' => RentalInvoice::whereIn('status', ['unpaid', 'partial'])->count(),
            'expected_rent' => RentalUnit::sum('rent_amount'),
            'collected' => RentalPayment::where('status', 'paid')->sum('amount'),
        ];

        return view('rental-management::dashboard.index', [
            'stats' => $stats,
            'recentTenants' => RentalTenant::with(['property', 'unit'])->latest()->limit(6)->get(),
            'recentInvoices' => RentalInvoice::with('tenant')->latest()->limit(6)->get(),
        ]);
    }
}
