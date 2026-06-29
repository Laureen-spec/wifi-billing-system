<?php

namespace StudyRoomTechLab\Tr069\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Tr069\Models\Tr069CpeDevice;
use StudyRoomTechLab\Tr069\Models\Tr069DeviceLog;

class Tr069LogController extends Tr069Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $filters = [
            'device_id' => $request->integer('device_id') ?: null,
            'event_type' => $request->query('event_type', 'all'),
            'level' => $request->query('level', 'all'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'isp_id' => $request->integer('isp_id') ?: null,
        ];

        $query = $this->scopedToTenant(
            Tr069DeviceLog::query()->with(['device', 'isp']),
            $request,
            'tr069_device_logs'
        );

        if ($filters['device_id']) {
            $query->where('cpe_device_id', $filters['device_id']);
        }

        if (array_key_exists((string) $filters['event_type'], Tr069DeviceLog::eventTypes())) {
            $query->where('event_type', $filters['event_type']);
        }

        if (array_key_exists((string) $filters['level'], Tr069DeviceLog::levels())) {
            $query->where('level', $filters['level']);
        }

        $query->when($filters['from'], fn ($query, string $date) => $query->whereDate('created_at', '>=', $date));
        $query->when($filters['to'], fn ($query, string $date) => $query->whereDate('created_at', '<=', $date));

        return Inertia::render('tr069/logs', [
            'isp' => $this->pageIsp($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'filters' => $filters,
            'logs' => $query->latest()->paginate((int) $request->integer('per_page', 20))->withQueryString()
                ->through(fn (Tr069DeviceLog $log): array => [
                    'id' => $log->id,
                    'event_type' => $log->event_type,
                    'level' => $log->level,
                    'message' => $log->message,
                    'payload' => $log->payload,
                    'created_at' => optional($log->created_at)->toDateTimeString(),
                    'device' => $log->device ? [
                        'id' => $log->device->id,
                        'serial_number' => $log->device->serial_number,
                        'manufacturer' => $log->device->manufacturer,
                        'model' => $log->device->model,
                    ] : null,
                    'isp' => $log->isp ? ['id' => $log->isp->id, 'name' => $log->isp->name] : null,
                ]),
            'deviceOptions' => $this->deviceOptions($request),
            'eventOptions' => $this->optionList(['all' => 'All'] + Tr069DeviceLog::eventTypes()),
            'levelOptions' => $this->optionList(['all' => 'All'] + Tr069DeviceLog::levels()),
        ]);
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
