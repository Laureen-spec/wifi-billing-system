<?php

namespace StudyRoomTechLab\Tr069\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Tr069\Models\Tr069CpeDevice;
use StudyRoomTechLab\Tr069\Models\Tr069FirmwareUpdate;
use StudyRoomTechLab\Tr069\Services\Tr069Manager;

class Tr069FirmwareController extends Tr069Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $filters = [
            'status' => $request->query('status', 'all'),
            'q' => trim((string) $request->query('q', '')),
            'isp_id' => $request->integer('isp_id') ?: null,
        ];

        $query = $this->scopedToTenant(
            Tr069FirmwareUpdate::query()->with('isp'),
            $request,
            'tr069_firmware_updates'
        );

        if (array_key_exists((string) $filters['status'], Tr069FirmwareUpdate::statuses())) {
            $query->where('status', $filters['status']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('version', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        return Inertia::render('tr069/firmware', [
            'isp' => $this->pageIsp($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'filters' => $filters,
            'firmware' => $query->latest()->paginate((int) $request->integer('per_page', 15))->withQueryString()
                ->through(fn (Tr069FirmwareUpdate $firmware): array => [
                    'id' => $firmware->id,
                    'isp_id' => $firmware->isp_id,
                    'name' => $firmware->name,
                    'version' => $firmware->version,
                    'manufacturer' => $firmware->manufacturer,
                    'model' => $firmware->model,
                    'file_url' => $firmware->file_url,
                    'checksum' => $firmware->checksum,
                    'status' => $firmware->status,
                    'notes' => $firmware->notes,
                    'isp' => $firmware->isp ? ['id' => $firmware->isp->id, 'name' => $firmware->isp->name] : null,
                    'queue_url' => route('tr069.firmware.queue', $firmware),
                ]),
            'statusOptions' => $this->optionList(['all' => 'All'] + Tr069FirmwareUpdate::statuses()),
            'deviceOptions' => $this->deviceOptions($request),
            'storeUrl' => route('tr069.firmware.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request, true);

        $data = $request->validate([
            'isp_id' => ['nullable', 'integer', 'exists:isps,id'],
            'name' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:120'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'file_url' => ['nullable', 'url', 'max:2000'],
            'checksum' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(array_keys(Tr069FirmwareUpdate::statuses()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $ispId = $this->tenantIdForWrite($request);
        Tr069FirmwareUpdate::query()->create(array_merge($data, [
            'isp_id' => $ispId,
            'company_id' => $this->companyIdForWrite($request),
        ]));

        return back()->with('success', 'Firmware metadata registered.');
    }

    public function queue(Request $request, Tr069FirmwareUpdate $firmware, Tr069Manager $manager): RedirectResponse
    {
        $this->authorizeAccess($request, true);
        $this->authorizeIspRecord($request, $firmware->isp_id);

        $data = $request->validate([
            'device_id' => ['required', 'integer', 'exists:tr069_cpe_devices,id'],
        ]);

        $device = Tr069CpeDevice::query()->findOrFail($data['device_id']);
        $this->authorizeIspRecord($request, $device->isp_id);
        $manager->queueFirmwareUpdate($device, $firmware);
        $firmware->forceFill(['status' => 'queued'])->save();

        return back()->with('success', 'Firmware update job queued.');
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
}
