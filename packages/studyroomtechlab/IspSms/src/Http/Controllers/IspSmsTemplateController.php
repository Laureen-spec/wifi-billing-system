<?php

namespace StudyRoomTechLab\IspSms\Http\Controllers;

use App\Http\Controllers\Controller;
use StudyRoomTechLab\IspSms\Models\IspSmsTemplate;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IspSmsTemplateController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeView($request);

        $templates = collect();
        $isPlatform = $this->isPlatform($request);
        $isp = $isPlatform ? null : $this->resolveIsp($request);

        if (Schema::hasTable('isp_sms_templates')) {
            $templates = IspSmsTemplate::query()
                ->when($isp, fn ($query) => $query->where(function ($subQuery) use ($isp) {
                    $subQuery->whereNull('isp_id')->orWhere('isp_id', $isp->id);
                }))
                ->when($isPlatform && $request->query('scope') === 'platform', fn ($query) => $query->whereNull('isp_id'))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        }

        return $this->viewOrPlaceholder('templates.index', [
            'templates' => $templates,
            'hasSmsTables' => Schema::hasTable('isp_sms_templates'),
            'isPlatform' => $isPlatform,
        ], [
            'title' => 'SMS Templates',
            'subtitle' => 'Reusable SMS templates for customer communication.',
            'status' => 'Ready for setup',
            'columns' => ['Name', 'Key', 'Enabled', 'Updated At', 'Action'],
            'note' => 'Templates can use variables later, such as {customer_name}, {package_name}, and {due_date}.',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage($request);

        abort_unless(Schema::hasTable('isp_sms_templates'), 500, 'SMS templates table is not migrated yet.');

        $data = $request->validate([
            'scope' => ['nullable', Rule::in(['isp', 'platform'])],
            'name' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $isPlatform = $this->isPlatform($request);
        $scope = $data['scope'] ?? 'isp';

        if (! $isPlatform) {
            $scope = 'isp';
        }

        if ($scope === 'platform') {
            abort_unless($isPlatform, 403, 'Only platform users can create platform SMS templates.');
            $ispId = null;
        } else {
            $ispId = $this->resolveIsp($request)->id;
        }

        $key = $data['key'] ?: Str::slug($data['name'], '_');

        $template = IspSmsTemplate::firstOrNew([
            'isp_id' => $ispId,
            'key' => $key,
        ]);

        if (! $template->exists) {
            $template->created_by = $request->user()->id;
        }

        $template->fill([
            'isp_id' => $ispId,
            'name' => $data['name'],
            'key' => $key,
            'body' => $data['body'],
            'enabled' => $request->boolean('enabled', true),
            'updated_by' => $request->user()->id,
        ]);

        $template->save();

        return redirect()
            ->route('isp.sms.templates.index')
            ->with('success', 'SMS template saved.');
    }

    private function resolveIsp(Request $request)
    {
        return app(IspTenantResolver::class)->resolve($request);
    }

    private function isPlatform(Request $request): bool
    {
        return app(IspTenantResolver::class)->isPlatform($request);
    }

    private function authorizeView(Request $request): void
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

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $this->isPlatform($request)
            || $request->user()->can('manage-wifi-dashboard')
            || $request->user()->can('manage-isp-customers'),
            403
        );
    }

    private function viewOrPlaceholder(string $view, array $data, array $page)
    {
        $packageView = 'isp-sms::' . $view;

        if (view()->exists($packageView)) {
            return view($packageView, $data);
        }

        if (view()->exists($view)) {
            return view($view, $data);
        }

        return view('isp-modules.placeholder', [
            'page' => $page,
        ]);
    }
}