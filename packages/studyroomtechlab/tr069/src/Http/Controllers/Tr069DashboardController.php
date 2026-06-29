<?php

namespace StudyRoomTechLab\Tr069\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Tr069\Models\Tr069ConfigJob;
use StudyRoomTechLab\Tr069\Models\Tr069CpeDevice;
use StudyRoomTechLab\Tr069\Models\Tr069DeviceLog;

class Tr069DashboardController extends Tr069Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $devices = $this->scopedToTenant(Tr069CpeDevice::query(), $request, 'tr069_cpe_devices');
        $jobs = $this->scopedToTenant(Tr069ConfigJob::query(), $request, 'tr069_config_jobs');

        return Inertia::render('tr069/index', [
            'isp' => $this->pageIsp($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'filters' => ['isp_id' => $request->integer('isp_id') ?: null],
            'stats' => [
                'total_cpes' => (int) (clone $devices)->count(),
                'online_cpes' => (int) (clone $devices)->where('status', Tr069CpeDevice::STATUS_ONLINE)->count(),
                'offline_cpes' => (int) (clone $devices)->where('status', Tr069CpeDevice::STATUS_OFFLINE)->count(),
                'pending_jobs' => (int) (clone $jobs)->where('status', Tr069ConfigJob::STATUS_QUEUED)->count(),
                'failed_jobs' => (int) (clone $jobs)->where('status', Tr069ConfigJob::STATUS_FAILED)->count(),
            ],
            'statusSummary' => collect(Tr069CpeDevice::statuses())->map(fn (string $label, string $value): array => [
                'status' => $value,
                'label' => $label,
                'count' => (int) (clone $devices)->where('status', $value)->count(),
            ])->values()->all(),
            'recentInforms' => $this->scopedToTenant(
                Tr069DeviceLog::query()->with('device')->where('event_type', 'inform'),
                $request,
                'tr069_device_logs'
            )->latest()->limit(8)->get()->map(fn (Tr069DeviceLog $log): array => [
                'id' => $log->id,
                'message' => $log->message,
                'level' => $log->level,
                'created_at' => optional($log->created_at)->toDateTimeString(),
                'device' => $log->device ? $this->deviceSummary($log->device) : null,
            ])->all(),
            'recentJobs' => $this->scopedToTenant(
                Tr069ConfigJob::query()->with(['device', 'profile']),
                $request,
                'tr069_config_jobs'
            )->latest()->limit(8)->get()->map(fn (Tr069ConfigJob $job): array => $this->jobSummary($job))->all(),
        ]);
    }

    private function deviceSummary(Tr069CpeDevice $device): array
    {
        return [
            'id' => $device->id,
            'serial_number' => $device->serial_number,
            'manufacturer' => $device->manufacturer,
            'model' => $device->model,
            'status' => $device->status,
        ];
    }

    private function jobSummary(Tr069ConfigJob $job): array
    {
        return [
            'id' => $job->id,
            'job_type' => $job->job_type,
            'status' => $job->status,
            'queued_at' => optional($job->queued_at)->toDateTimeString(),
            'device' => $job->device ? $this->deviceSummary($job->device) : null,
            'profile' => $job->profile ? ['id' => $job->profile->id, 'name' => $job->profile->name] : null,
        ];
    }
}
