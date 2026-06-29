<?php

namespace StudyRoomTechLab\WifiBilling\Services;

use App\Models\MikrotikRouter;

class MikrotikRouterLiveService
{
    public function snapshot(MikrotikRouter $router): array
    {
        $payload = is_array($router->last_heartbeat_payload) ? $router->last_heartbeat_payload : [];
        return [
            'online' => $router->last_seen_at && $router->last_seen_at->gt(now()->subMinutes(2)),
            'last_seen_at' => $router->last_seen_at,
            'identity' => $router->identity,
            'version' => $router->routeros_version,
            'board' => $router->board_name,
            'uptime' => $router->uptime,
            'cpu' => $router->cpu_load,
            'memory_free' => $router->memory_free,
            'memory_total' => $router->memory_total,
            'hotspot_active' => $payload['hotspot_active'] ?? null,
            'pppoe_active' => $payload['pppoe_active'] ?? null,
            'payload' => $payload,
        ];
    }
}
