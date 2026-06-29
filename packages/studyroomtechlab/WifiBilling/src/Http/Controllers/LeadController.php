<?php

namespace StudyRoomTechLab\WifiBilling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        if (Route::has('studyroom-leads.index')
            && function_exists('Module_is_active')
            && Module_is_active('Leads')) {
            return redirect()->route('studyroom-leads.index');
        }

        abort_unless(
            app(IspTenantResolver::class)->isPlatform($request)
            || $request->user()->can('view-isp-customers')
            || $request->user()->can('manage-isp-customers'),
            403
        );

        $leads = collect();
        $hasLeadTable = Schema::hasTable('isp_leads');

        if ($hasLeadTable) {
            $query = DB::table('isp_leads');

            if (Schema::hasColumn('isp_leads', 'isp_id') && ! app(IspTenantResolver::class)->isPlatform($request)) {
                $query->where('isp_id', app(IspTenantResolver::class)->resolve($request)->id);
            }

            if (Schema::hasColumn('isp_leads', 'created_at')) {
                $query->orderByDesc('created_at');
            } elseif (Schema::hasColumn('isp_leads', 'id')) {
                $query->orderByDesc('id');
            }

            $leads = $query->paginate(15)->withQueryString();
        }

        return view('wifi-billing::isp-leads.index', [
            'leads' => $leads,
            'hasLeadTable' => $hasLeadTable,
        ]);
    }
}
