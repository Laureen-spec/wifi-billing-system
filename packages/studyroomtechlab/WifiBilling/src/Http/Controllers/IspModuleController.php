<?php

namespace StudyRoomTechLab\WifiBilling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IspModuleController extends Controller
{
    public function vouchers(Request $request)
    {
        return $this->placeholder($request, [
            'title' => 'Access Vouchers',
            'subtitle' => 'Internet access vouchers tied to packages and customer activation.',
            'status' => 'Ready for setup',
            'columns' => ['Code', 'Package', 'Customer', 'Status', 'Created At', 'Action'],
            'note' => 'Voucher generation can be connected later without changing MikroTik provisioning.',
        ]);
    }

    public function invoices(Request $request)
    {
        return $this->placeholder($request, [
            'title' => 'Invoices',
            'subtitle' => 'WiFi customer invoices for internet subscriptions and service charges.',
            'status' => 'Ready for setup',
            'columns' => ['Invoice No.', 'Customer', 'Package', 'Amount', 'Status', 'Due Date', 'Action'],
            'note' => 'This page is safely staged. Real invoice storage can be enabled after migrations/models are ready.',
        ]);
    }

    public function payments(Request $request)
    {
        return $this->placeholder($request, [
            'title' => 'Payments',
            'subtitle' => 'Payments received from WiFi customers.',
            'status' => 'Ready for setup',
            'columns' => ['Customer', 'Reference', 'Method', 'Amount', 'Status', 'Paid At', 'Action'],
            'note' => 'M-Pesa payments remain handled by the MpesaPayment add-on.',
        ]);
    }

    public function receipts(Request $request)
    {
        return $this->placeholder($request, [
            'title' => 'Receipts',
            'subtitle' => 'Receipts issued to WiFi customers after successful payment.',
            'status' => 'Ready for setup',
            'columns' => ['Receipt No.', 'Customer', 'Payment Ref', 'Amount', 'Issued At', 'Action'],
            'note' => 'Receipt records can be generated from confirmed payments later.',
        ]);
    }

    public function tickets(Request $request)
    {
        return $this->placeholder($request, [
            'title' => 'Support Tickets',
            'subtitle' => 'Customer support tickets for WiFi and ISP service issues.',
            'status' => 'Ready for setup',
            'columns' => ['Ticket No.', 'Customer', 'Issue', 'Priority', 'Status', 'Created At', 'Action'],
            'note' => 'This is separate from other ERP helpdesk modules.',
        ]);
    }

    public function fieldVisits(Request $request)
    {
        return $this->placeholder($request, [
            'title' => 'Field Visits',
            'subtitle' => 'Technician visits for installations, router checks, and customer support.',
            'status' => 'Ready for setup',
            'columns' => ['Customer', 'Technician', 'Purpose', 'Status', 'Scheduled At', 'Action'],
            'note' => 'This can later connect to your field team workflow.',
        ]);
    }

    public function overdue(Request $request)
    {
        $this->authorizeAccess($request);

        $customers = collect();
        $invoices = collect();

        if (Schema::hasTable((new Customer())->getTable())) {
            $query = Customer::query()
                ->with(['internetPackage:id,name', 'mikrotikRouter:id,name'])
                ->where(function ($query) {
                    $query->where('billing_status', 'overdue')
                        ->orWhere(function ($query) {
                            $query->whereNotNull('next_due_date')
                                ->whereDate('next_due_date', '<', now()->toDateString());
                        });
                });

            if (! $this->isPlatform($request)) {
                $query->where('isp_id', $this->resolveIsp($request)->id);
            }

            $customers = $query->orderBy('next_due_date')->paginate(15)->withQueryString();
        }

        if (Schema::hasTable('isp_invoices')) {
            $invoiceQuery = DB::table('isp_invoices')
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->whereDate('due_date', '<', now()->toDateString());

            if (Schema::hasColumn('isp_invoices', 'isp_id') && ! $this->isPlatform($request)) {
                $invoiceQuery->where('isp_id', $this->resolveIsp($request)->id);
            }

            $invoices = $invoiceQuery->orderBy('due_date')->paginate(15, ['*'], 'invoice_page')->withQueryString();
        }

        return view('wifi-billing::isp-modules.overdue', [
            'customers' => $customers,
            'invoices' => $invoices,
        ]);
    }

    private function placeholder(Request $request, array $page)
    {
        $this->authorizeAccess($request);

        return view('wifi-billing::isp-modules.placeholder', [
            'page' => $page,
        ]);
    }

    private function authorizeAccess(Request $request): void
    {
        abort_unless(
            $this->isPlatform($request)
            || $request->user()->can('view-wifi-dashboard')
            || $request->user()->can('manage-wifi-dashboard')
            || $request->user()->can('view-isp-customers')
            || $request->user()->can('manage-isp-customers'),
            403
        );
    }

    private function resolveIsp(Request $request)
    {
        return app(IspTenantResolver::class)->resolve($request);
    }

    private function isPlatform(Request $request): bool
    {
        return app(IspTenantResolver::class)->isPlatform($request);
    }
}
