<?php

namespace StudyRoomTechLab\Tr069\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StudyRoomTechLab\Tr069\Models\Tr069ConfigJob;
use StudyRoomTechLab\Tr069\Models\Tr069CpeDevice;
use StudyRoomTechLab\Tr069\Models\Tr069Setting;
use StudyRoomTechLab\Tr069\Services\Tr069Manager;

class Tr069ApiController extends Controller
{
    public function inform(Request $request, Tr069Manager $manager): JsonResponse
    {
        $setting = $this->authenticate($request);

        $payload = array_merge($request->all(), [
            'isp_id' => $setting->isp_id,
            'company_id' => $setting->company_id,
            'last_seen_ip' => $request->ip(),
        ]);

        $device = $manager->registerInform($payload);

        return response()->json([
            'ok' => true,
            'device_id' => $device->id,
            'serial_number' => $device->serial_number,
            'inform_interval' => $setting->inform_interval,
        ]);
    }

    public function jobs(Request $request, Tr069CpeDevice $device): JsonResponse
    {
        $this->authenticate($request, $device->isp_id);

        $jobs = Tr069ConfigJob::query()
            ->where('cpe_device_id', $device->id)
            ->where('status', Tr069ConfigJob::STATUS_QUEUED)
            ->orderBy('queued_at')
            ->limit(10)
            ->get()
            ->map(fn (Tr069ConfigJob $job): array => [
                'id' => $job->id,
                'job_type' => $job->job_type,
                'payload' => $this->safePayload($job->payload ?: []),
                'queued_at' => optional($job->queued_at)->toDateTimeString(),
            ])
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'jobs' => $jobs,
        ]);
    }

    public function completeJob(Request $request, Tr069ConfigJob $job, Tr069Manager $manager): JsonResponse
    {
        $this->authenticate($request, $job->isp_id);

        $job->forceFill([
            'status' => Tr069ConfigJob::STATUS_COMPLETED,
            'result_message' => $request->input('result_message', 'Completed by CPE.'),
            'completed_at' => now(),
            'failed_at' => null,
        ])->save();

        $manager->logDeviceEvent($job->device, 'status_change', 'TR069 job completed.', [
            'job_id' => $job->id,
            'job_type' => $job->job_type,
        ], 'success');

        return response()->json(['ok' => true]);
    }

    public function failJob(Request $request, Tr069ConfigJob $job, Tr069Manager $manager): JsonResponse
    {
        $this->authenticate($request, $job->isp_id);

        $job->forceFill([
            'status' => Tr069ConfigJob::STATUS_FAILED,
            'result_message' => $request->input('result_message', 'Failed by CPE.'),
            'failed_at' => now(),
        ])->save();

        $manager->logDeviceEvent($job->device, 'error', 'TR069 job failed.', [
            'job_id' => $job->id,
            'job_type' => $job->job_type,
        ], 'error');

        return response()->json(['ok' => true]);
    }

    private function authenticate(Request $request, ?int $ispId = null): Tr069Setting
    {
        $token = $request->bearerToken()
            ?: $request->header('X-TR069-Token')
            ?: $request->input('api_token');

        abort_unless($token, 401, 'TR069 API token is required.');

        $query = Tr069Setting::query()
            ->where('enabled', true)
            ->whereNotNull('api_token');

        if ($ispId) {
            $query->where('isp_id', $ispId);
        }

        $settings = $query->get();
        foreach ($settings as $setting) {
            if (hash_equals((string) $setting->api_token, (string) $token)) {
                return $setting;
            }
        }

        abort(403, 'Invalid TR069 API token.');
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
