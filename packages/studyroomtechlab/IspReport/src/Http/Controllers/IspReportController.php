<?php

namespace StudyRoomTechLab\IspReport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\IspReport\Services\IspReportService;

class IspReportController extends Controller
{
    public function __construct(
        private readonly IspTenantResolver $tenantResolver,
        private readonly IspReportService $reports
    ) {
    }

    public function index(Request $request): Response
    {
        $context = $this->context($request);

        return Inertia::render('isp-report/index', [
            'pageTitle' => 'ISP Report',
            'subtitle' => 'A clean reporting workspace for subscribers, staff activity, connections, and payments.',
            'isp' => $context['isp'],
            ...$this->reports->overview($request, $context['isp_id']),
        ]);
    }

    public function staffLogs(Request $request): Response
    {
        $context = $this->context($request);

        return Inertia::render('isp-report/staff-logs', [
            'pageTitle' => 'Staff Logs',
            'subtitle' => 'Track staff sign-ins and system changes made inside the ISP workspace.',
            'isp' => $context['isp'],
            ...$this->reports->staffLogs($request, $context['isp_id']),
        ]);
    }

    public function connectionLogs(Request $request): Response
    {
        $context = $this->context($request);

        return Inertia::render('isp-report/connection-logs', [
            'pageTitle' => 'Connection Logs',
            'subtitle' => 'Review Hotspot and PPPoE customer connection activity, usage, routers, and expiry status.',
            'isp' => $context['isp'],
            ...$this->reports->connectionLogs($request, $context['isp_id']),
        ]);
    }

    public function paymentLogs(Request $request): Response
    {
        $context = $this->context($request);

        return Inertia::render('isp-report/payment-logs', [
            'pageTitle' => 'Payment Logs',
            'subtitle' => 'Audit M-Pesa, manual, voucher, bank, and system package payment activity.',
            'isp' => $context['isp'],
            ...$this->reports->paymentLogs($request, $context['isp_id']),
        ]);
    }

    private function context(Request $request): array
    {
        $this->authorizeAccess($request);

        $isp = $this->tenantResolver->resolve($request, $request->integer('isp_id') ?: null);

        return [
            'isp_id' => (int) $isp->id,
            'isp' => [
                'id' => $isp->id,
                'name' => $isp->name,
                'status' => $isp->status,
            ],
        ];
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($this->isWorkspaceOwner($user)) {
            return;
        }

        abort_unless(
            $user->can('view-wifi-dashboard')
            || $user->can('manage-wifi-dashboard')
            || $user->can('view-isp-customers')
            || $user->can('manage-isp-customers'),
            403
        );
    }

    private function isWorkspaceOwner(User $user): bool
    {
        $types = ['superadmin', 'super_admin', 'control_isp', 'company', 'admin', 'isp_admin'];

        return in_array((string) $user->type, $types, true)
            || (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($types));
    }
}
