<?php

namespace StudyRoomTechLab\WifiBilling\Console\Commands;

use Illuminate\Console\Command;
use StudyRoomTechLab\WifiBilling\Services\CustomerAutoProvisioningService;

class RetryPendingProvisioningCommand extends Command
{
    protected $signature = 'wifi-billing:retry-pending-provisioning';
    protected $description = 'Keep pending WiFi Billing provisioning commands ready for MikroTik agent pull.';

    public function handle(CustomerAutoProvisioningService $service): int
    {
        $summary = $service->refreshPendingSummary();
        $this->info('Pending commands: ' . ($summary['pending'] ?? 0));
        return self::SUCCESS;
    }
}
