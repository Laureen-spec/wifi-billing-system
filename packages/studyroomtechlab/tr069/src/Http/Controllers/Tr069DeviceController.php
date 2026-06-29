<?php

namespace StudyRoomTechLab\Tr069\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Tr069\Models\Tr069ConfigJob;
use StudyRoomTechLab\Tr069\Models\Tr069CpeDevice;
use StudyRoomTechLab\Tr069\Models\Tr069DeviceLog;
use StudyRoomTechLab\Tr069\Models\Tr069Profile;
use StudyRoomTechLab\Tr069\Services\Tr069Manager;

class Tr069DeviceController extends Tr069Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status', 'all'),
            'isp_id' => $request->integer('isp_id') ?: null,
        ];

        $query = $this->scopedToTenant(
            Tr069CpeDevice::query()->with(['customer', 'isp']),
            $request,
            'tr069_cpe_devices'
        );

        if (array_key_exists((string) $filters['status'], Tr069CpeDevice::statuses())) {
            $query->where('status', $filters['status']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function ($query) use ($search): void {
                $query->where('serial_number', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('mac_address', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        $devices = $query
            ->orderByDesc('last_inform_at')
            ->latest()
            ->paginate((int) $request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (Tr069CpeDevice $device): array => $this->devicePayload($device));

        return Inertia::render('tr069/devices', [
            'isp' => $this->pageIsp($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'filters' => $filters,
            'devices' => $devices,
            'statusOptions' => $this->optionList(['all' => 'All'] + Tr069CpeDevice::statuses()),
            'profileOptions' => $this->profileOptions($request),
        ]);
    }

    public function show(Request $request, Tr069CpeDevice $device): Response
    {
        $this->authorizeAccess($request);
        $this->authorizeIspRecord($request, $device->isp_id);

        $device->load(['customer', 'isp']);

        return Inertia::render('tr069/device-show', [
            'device' => $this->devicePayload($device),
            'profileOptions' => $this->profileOptions($request),
            'recentJobs' => Tr069ConfigJob::query()
                ->with('profile')
                ->where('cpe_device_id', $device->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (Tr069ConfigJob $job): array => [
                    'id' => $job->id,
                    'job_type' => $job->job_type,
                    'status' => $job->status,
                    'result_message' => $job->result_message,
                    'queued_at' => optional($job->queued_at)->toDateTimeString(),
                    'completed_at' => optional($job->completed_at)->toDateTimeString(),
                    'profile' => $job->profile ? ['id' => $job->profile->id, 'name' => $job->profile->name] : null,
                ])->all(),
            'recentLogs' => Tr069DeviceLog::query()
                ->where('cpe_device_id', $device->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (Tr069DeviceLog $log): array => [
                    'id' => $log->id,
                    'event_type' => $log->event_type,
                    'level' => $log->level,
                    'message' => $log->message,
                    'created_at' => optional($log->created_at)->toDateTimeString(),
                ])->all(),
        ]);
    }

    public function queueProvision(Request $request, Tr069CpeDevice $device, Tr069Manager $manager): RedirectResponse
    {
        $this->authorizeAccess($request, true);
        $this->authorizeIspRecord($request, $device->isp_id);

        $profile = $request->integer('profile_id')
            ? Tr069Profile::query()->whereKey($request->integer('profile_id'))->firstOrFail()
            : null;

        $manager->queueProvisioning($device, $profile);

        return back()->with('success', 'Provisioning job queued.');
    }

    public function queueReboot(Request $request, Tr069CpeDevice $device, Tr069Manager $manager): RedirectResponse
    {
        $this->authorizeAccess($request, true);
        $this->authorizeIspRecord($request, $device->isp_id);

        $manager->queueReboot($device);

        return back()->with('success', 'Reboot job queued.');
    }

    public function pushProfile(Request $request, Tr069CpeDevice $device, Tr069Manager $manager): RedirectResponse
    {
        $this->authorizeAccess($request, true);
        $this->authorizeIspRecord($request, $device->isp_id);

        $data = $request->validate([
            'profile_id' => ['required', 'integer', 'exists:tr069_profiles,id'],
        ]);

        $profile = Tr069Profile::query()->whereKey($data['profile_id'])->firstOrFail();
        $this->authorizeIspRecord($request, $profile->isp_id);
        $manager->pushProfile($device, $profile);

        return back()->with('success', 'Profile push job queued.');
    }

    private function profileOptions(Request $request): array
    {
        return $this->scopedToTenant(
            Tr069Profile::query()->where('status', Tr069Profile::STATUS_ACTIVE),
            $request,
            'tr069_profiles'
        )
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Tr069Profile $profile): array => ['id' => $profile->id, 'name' => $profile->name])
            ->values()
            ->all();
    }

    private function devicePayload(Tr069CpeDevice $device): array
    {
        return [
            'id' => $device->id,
            'isp_id' => $device->isp_id,
            'serial_number' => $device->serial_number,
            'oui' => $device->oui,
            'product_class' => $device->product_class,
            'manufacturer' => $device->manufacturer,
            'model' => $device->model,
            'firmware_version' => $device->firmware_version,
            'hardware_version' => $device->hardware_version,
            'ip_address' => $device->ip_address,
            'mac_address' => $device->mac_address,
            'connection_request_url' => $device->connection_request_url,
            'connection_username' => $device->connection_username,
            'connection_password' => $this->mask($device->connection_password),
            'last_inform_at' => optional($device->last_inform_at)->toDateTimeString(),
            'last_seen_ip' => $device->last_seen_ip,
            'status' => $device->status,
            'notes' => $device->notes,
            'metadata' => $device->metadata,
            'customer' => $device->customer ? [
                'id' => $device->customer->id,
                'name' => $device->customer->name,
                'phone' => $device->customer->phone,
                'username' => $device->customer->username,
            ] : null,
            'isp' => $device->isp ? ['id' => $device->isp->id, 'name' => $device->isp->name] : null,
            'show_url' => route('tr069.devices.show', $device),
            'provision_url' => route('tr069.devices.provision', $device),
            'reboot_url' => route('tr069.devices.reboot', $device),
            'push_profile_url' => route('tr069.devices.push-profile', $device),
        ];
    }
}
