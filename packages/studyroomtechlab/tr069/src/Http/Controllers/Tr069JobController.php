<?php

namespace StudyRoomTechLab\Tr069\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Tr069\Models\Tr069ConfigJob;
use StudyRoomTechLab\Tr069\Models\Tr069CpeDevice;
use StudyRoomTechLab\Tr069\Models\Tr069Profile;
use StudyRoomTechLab\Tr069\Services\Tr069Manager;

class Tr069JobController extends Tr069Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $filters = [
            'status' => $request->query('status', 'all'),
            'job_type' => $request->query('job_type', 'all'),
            'isp_id' => $request->integer('isp_id') ?: null,
        ];

        $query = $this->scopedToTenant(
            Tr069ConfigJob::query()->with(['device.customer', 'profile', 'isp', 'creator']),
            $request,
            'tr069_config_jobs'
        );

        if (array_key_exists((string) $filters['status'], Tr069ConfigJob::statuses())) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists((string) $filters['job_type'], Tr069ConfigJob::jobTypes())) {
            $query->where('job_type', $filters['job_type']);
        }

        return Inertia::render('tr069/jobs/index', [
            'isp' => $this->pageIsp($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'filters' => $filters,
            'jobs' => $query->latest()->paginate((int) $request->integer('per_page', 15))->withQueryString()
                ->through(fn (Tr069ConfigJob $job): array => $this->jobPayload($job)),
            'statusOptions' => $this->optionList(['all' => 'All'] + Tr069ConfigJob::statuses()),
            'jobTypeOptions' => $this->optionList(['all' => 'All'] + Tr069ConfigJob::jobTypes()),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeAccess($request, true);

        return Inertia::render('tr069/jobs/form', [
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'devices' => $this->deviceOptions($request),
            'profiles' => $this->profileOptions($request),
            'jobTypeOptions' => $this->optionList(Tr069ConfigJob::jobTypes()),
            'storeUrl' => route('tr069.jobs.store'),
            'defaults' => [
                'isp_id' => $this->isPlatform($request) ? ($request->integer('isp_id') ?: '') : $this->resolveIsp($request)->id,
                'cpe_device_id' => $request->integer('device_id') ?: '',
                'tr069_profile_id' => '',
                'job_type' => 'provision',
                'payload_json' => '',
            ],
        ]);
    }

    public function store(Request $request, Tr069Manager $manager): RedirectResponse
    {
        $this->authorizeAccess($request, true);

        $data = $request->validate([
            'isp_id' => ['nullable', 'integer', 'exists:isps,id'],
            'cpe_device_id' => ['required', 'integer', 'exists:tr069_cpe_devices,id'],
            'tr069_profile_id' => ['nullable', 'integer', 'exists:tr069_profiles,id'],
            'job_type' => ['required', 'string', Rule::in(array_keys(Tr069ConfigJob::jobTypes()))],
            'payload_json' => ['nullable', 'string'],
        ]);

        $device = Tr069CpeDevice::query()->findOrFail($data['cpe_device_id']);
        $this->authorizeIspRecord($request, $device->isp_id);
        $profile = ! empty($data['tr069_profile_id']) ? Tr069Profile::query()->findOrFail($data['tr069_profile_id']) : null;
        if ($profile) {
            $this->authorizeIspRecord($request, $profile->isp_id);
        }

        $payload = $this->decodeJson($request->input('payload_json'), 'Payload') ?: [];
        match ($data['job_type']) {
            'provision' => $manager->queueProvisioning($device, $profile, $payload),
            'reboot' => $manager->queueReboot($device),
            'wifi_update' => $manager->queueWifiUpdate($device, $payload),
            'firmware_update' => $this->queueAdHocJob($device, $profile, 'firmware_update', $payload, $manager),
            'parameter_push' => $manager->queueParameterPush($device, $payload),
            'diagnostics' => $this->queueAdHocJob($device, $profile, 'diagnostics', $payload, $manager),
            default => null,
        };

        return redirect()
            ->route('tr069.jobs.index', $this->isPlatform($request) ? ['isp_id' => $device->isp_id] : [])
            ->with('success', 'TR069 configuration job queued.');
    }

    private function deviceOptions(Request $request): array
    {
        return $this->scopedToTenant(Tr069CpeDevice::query(), $request, 'tr069_cpe_devices')
            ->orderBy('serial_number')
            ->limit(1000)
            ->get(['id', 'serial_number', 'manufacturer', 'model'])
            ->map(fn (Tr069CpeDevice $device): array => [
                'id' => $device->id,
                'label' => trim($device->serial_number . ' - ' . ($device->manufacturer ?: 'CPE') . ' ' . ($device->model ?: '')),
            ])->values()->all();
    }

    private function queueAdHocJob(Tr069CpeDevice $device, ?Tr069Profile $profile, string $jobType, array $payload, Tr069Manager $manager): Tr069ConfigJob
    {
        $job = Tr069ConfigJob::query()->create([
            'isp_id' => $device->isp_id,
            'company_id' => $device->company_id,
            'cpe_device_id' => $device->id,
            'tr069_profile_id' => $profile?->id,
            'job_type' => $jobType,
            'payload' => $this->safePayload($payload),
            'status' => Tr069ConfigJob::STATUS_QUEUED,
            'queued_at' => now(),
            'created_by' => auth()->id(),
        ]);

        $manager->logDeviceEvent($device, $jobType === 'diagnostics' ? 'diagnostics' : 'firmware', 'TR069 job queued: ' . $jobType . '.', [
            'job_id' => $job->id,
        ], 'info');

        return $job;
    }

    private function profileOptions(Request $request): array
    {
        return $this->scopedToTenant(Tr069Profile::query(), $request, 'tr069_profiles')
            ->where('status', Tr069Profile::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Tr069Profile $profile): array => ['id' => $profile->id, 'name' => $profile->name])
            ->values()->all();
    }

    private function jobPayload(Tr069ConfigJob $job): array
    {
        return [
            'id' => $job->id,
            'job_type' => $job->job_type,
            'status' => $job->status,
            'payload' => $this->safePayload($job->payload ?: []),
            'result_message' => $job->result_message,
            'queued_at' => optional($job->queued_at)->toDateTimeString(),
            'started_at' => optional($job->started_at)->toDateTimeString(),
            'completed_at' => optional($job->completed_at)->toDateTimeString(),
            'failed_at' => optional($job->failed_at)->toDateTimeString(),
            'device' => $job->device ? [
                'id' => $job->device->id,
                'serial_number' => $job->device->serial_number,
                'manufacturer' => $job->device->manufacturer,
                'model' => $job->device->model,
            ] : null,
            'profile' => $job->profile ? ['id' => $job->profile->id, 'name' => $job->profile->name] : null,
            'isp' => $job->isp ? ['id' => $job->isp->id, 'name' => $job->isp->name] : null,
            'created_by' => $job->creator?->name,
        ];
    }

    private function safePayload(array $payload): array
    {
        $masked = [];

        foreach ($payload as $key => $value) {
            $name = strtolower((string) $key);
            if (str_contains($name, 'password') || str_contains($name, 'token') || str_contains($name, 'secret')) {
                $masked[$key] = $value ? '********' : $value;
                continue;
            }

            $masked[$key] = is_array($value) ? $this->safePayload($value) : $value;
        }

        return $masked;
    }
}
