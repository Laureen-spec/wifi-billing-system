<?php

namespace StudyRoomTechLab\Tr069\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use StudyRoomTechLab\Tr069\Models\Tr069ConfigJob;
use StudyRoomTechLab\Tr069\Models\Tr069CpeDevice;
use StudyRoomTechLab\Tr069\Models\Tr069DeviceLog;
use StudyRoomTechLab\Tr069\Models\Tr069FirmwareUpdate;
use StudyRoomTechLab\Tr069\Models\Tr069Profile;
use StudyRoomTechLab\Tr069\Models\Tr069Setting;

class Tr069Manager
{
    public function getSettings($ispId = null): Tr069Setting
    {
        return Tr069Setting::query()->firstOrCreate(
            ['isp_id' => $ispId],
            Tr069Setting::defaults($ispId ? (int) $ispId : null)
        );
    }

    public function registerInform(array $payload): Tr069CpeDevice
    {
        return DB::transaction(function () use ($payload): Tr069CpeDevice {
            $device = $this->registerOrUpdateDevice($payload);
            $this->markDeviceOnline($device);
            $this->logDeviceEvent($device, 'inform', 'CPE inform received.', $this->safePayload($payload), 'info');

            $settings = $this->getSettings($device->isp_id);
            if ($settings->default_profile_id && ! $device->jobs()->where('status', Tr069ConfigJob::STATUS_QUEUED)->exists()) {
                $profile = Tr069Profile::query()->whereKey($settings->default_profile_id)->first();
                if ($profile) {
                    $this->queueProvisioning($device, $profile, ['source' => 'default_profile']);
                }
            }

            return $device->fresh(['jobs', 'logs']);
        });
    }

    public function registerOrUpdateDevice(array $payload): Tr069CpeDevice
    {
        $serial = trim((string) ($payload['serial_number'] ?? $payload['serial'] ?? ''));
        if ($serial === '') {
            throw ValidationException::withMessages([
                'serial_number' => 'TR069 inform payload must include a serial number.',
            ]);
        }

        $ispId = isset($payload['isp_id']) && $payload['isp_id'] !== '' ? (int) $payload['isp_id'] : null;
        $settings = $this->getSettings($ispId);

        $device = Tr069CpeDevice::query()
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->where('serial_number', $serial)
            ->first();

        if (! $device && (! $settings->allow_auto_register || $settings->require_known_serial)) {
            throw ValidationException::withMessages([
                'serial_number' => 'This CPE serial number is not registered for TR069 access.',
            ]);
        }

        $values = [
            'isp_id' => $ispId,
            'company_id' => $payload['company_id'] ?? null,
            'customer_id' => $payload['customer_id'] ?? null,
            'serial_number' => $serial,
            'oui' => $payload['oui'] ?? null,
            'product_class' => $payload['product_class'] ?? null,
            'manufacturer' => $payload['manufacturer'] ?? null,
            'model' => $payload['model'] ?? null,
            'firmware_version' => $payload['firmware_version'] ?? null,
            'hardware_version' => $payload['hardware_version'] ?? null,
            'ip_address' => $payload['ip_address'] ?? null,
            'mac_address' => $payload['mac_address'] ?? null,
            'connection_request_url' => $payload['connection_request_url'] ?? null,
            'connection_username' => $payload['connection_username'] ?? null,
            'connection_password' => $payload['connection_password'] ?? null,
            'last_inform_at' => now(),
            'last_seen_ip' => $payload['last_seen_ip'] ?? request()?->ip(),
            'status' => Tr069CpeDevice::STATUS_ONLINE,
            'metadata' => $this->safePayload($payload['metadata'] ?? $payload),
        ];

        if ($device) {
            $device->update(array_filter($values, fn ($value) => $value !== null));
            return $device->fresh();
        }

        return Tr069CpeDevice::query()->create($values);
    }

    public function queueProvisioning($device, $profile = null, array $payload = []): Tr069ConfigJob
    {
        return $this->createJob($device, 'provision', $profile, $payload);
    }

    public function pushProfile($device, $profile): Tr069ConfigJob
    {
        return $this->createJob($device, 'parameter_push', $profile, [
            'profile_id' => $this->modelId($profile),
            'profile_snapshot' => $this->profilePayload($profile),
        ]);
    }

    public function queueReboot($device): Tr069ConfigJob
    {
        return $this->createJob($device, 'reboot', null, [
            'safe_action' => 'queue_reboot_only',
        ]);
    }

    public function queueWifiUpdate($device, array $wifiSettings): Tr069ConfigJob
    {
        return $this->createJob($device, 'wifi_update', null, $this->safePayload($wifiSettings));
    }

    public function queueFirmwareUpdate($device, $firmware): Tr069ConfigJob
    {
        $record = $firmware instanceof Tr069FirmwareUpdate
            ? $firmware
            : Tr069FirmwareUpdate::query()->findOrFail($firmware);

        return $this->createJob($device, 'firmware_update', null, [
            'firmware_id' => $record->id,
            'name' => $record->name,
            'version' => $record->version,
            'file_url' => $record->file_url,
            'checksum' => $record->checksum,
        ]);
    }

    public function queueParameterPush($device, array $parameters): Tr069ConfigJob
    {
        return $this->createJob($device, 'parameter_push', null, [
            'parameters' => $this->safePayload($parameters),
        ]);
    }

    public function markDeviceOnline($device): Tr069CpeDevice
    {
        $record = $this->device($device);
        $record->forceFill([
            'status' => Tr069CpeDevice::STATUS_ONLINE,
            'last_inform_at' => now(),
        ])->save();

        return $record->fresh();
    }

    public function markDeviceOffline($device): Tr069CpeDevice
    {
        $record = $this->device($device);
        $record->forceFill(['status' => Tr069CpeDevice::STATUS_OFFLINE])->save();
        $this->logDeviceEvent($record, 'status_change', 'CPE marked offline.', [], 'warning');

        return $record->fresh();
    }

    public function logDeviceEvent($device, string $eventType, string $message, array $payload = [], string $level = 'info'): Tr069DeviceLog
    {
        $record = $device ? $this->device($device) : null;

        return Tr069DeviceLog::query()->create([
            'isp_id' => $record?->isp_id,
            'company_id' => $record?->company_id,
            'cpe_device_id' => $record?->id,
            'event_type' => $eventType,
            'level' => $level,
            'message' => $message,
            'payload' => $this->safePayload($payload),
        ]);
    }

    private function createJob($device, string $type, $profile = null, array $payload = []): Tr069ConfigJob
    {
        $record = $this->device($device);
        $profileRecord = $profile ? $this->profile($profile) : null;

        $job = Tr069ConfigJob::query()->create([
            'isp_id' => $record->isp_id,
            'company_id' => $record->company_id,
            'cpe_device_id' => $record->id,
            'tr069_profile_id' => $profileRecord?->id,
            'job_type' => $type,
            'payload' => $this->safePayload($payload ?: $this->profilePayload($profileRecord)),
            'status' => Tr069ConfigJob::STATUS_QUEUED,
            'queued_at' => now(),
            'created_by' => auth()->id(),
        ]);

        $this->logDeviceEvent($record, $this->eventTypeForJob($type), 'TR069 job queued: ' . $type . '.', [
            'job_id' => $job->id,
            'profile_id' => $profileRecord?->id,
        ], 'info');

        return $job->fresh(['device', 'profile']);
    }

    private function profilePayload(?Tr069Profile $profile): array
    {
        if (! $profile) {
            return [];
        }

        return $this->safePayload([
            'wan_mode' => $profile->wan_mode,
            'pppoe_username' => $profile->pppoe_username,
            'pppoe_password' => $profile->pppoe_password,
            'static_ip' => $profile->static_ip,
            'static_gateway' => $profile->static_gateway,
            'static_dns' => $profile->static_dns,
            'vlan_id' => $profile->vlan_id,
            'wifi_ssid' => $profile->wifi_ssid,
            'wifi_password' => $profile->wifi_password,
            'wifi_enabled' => $profile->wifi_enabled,
            'tr069_parameters' => $profile->tr069_parameters,
        ]);
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

    private function device($device): Tr069CpeDevice
    {
        if ($device instanceof Tr069CpeDevice) {
            return $device;
        }

        return Tr069CpeDevice::query()->findOrFail($this->modelId($device));
    }

    private function profile($profile): Tr069Profile
    {
        if ($profile instanceof Tr069Profile) {
            return $profile;
        }

        return Tr069Profile::query()->findOrFail($this->modelId($profile));
    }

    private function modelId($value): int
    {
        if ($value instanceof Model) {
            return (int) $value->getKey();
        }

        return (int) $value;
    }

    private function eventTypeForJob(string $type): string
    {
        return match ($type) {
            'provision', 'parameter_push', 'wifi_update' => 'provision',
            'reboot' => 'reboot',
            'firmware_update' => 'firmware',
            'diagnostics' => 'diagnostics',
            default => 'status_change',
        };
    }
}
