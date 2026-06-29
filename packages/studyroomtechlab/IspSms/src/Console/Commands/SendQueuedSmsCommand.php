<?php

namespace StudyRoomTechLab\IspSms\Console\Commands;

use Illuminate\Console\Command;
use StudyRoomTechLab\IspSms\Services\SmsManager;

class SendQueuedSmsCommand extends Command
{
    protected $signature = 'isp-sms:send-queued {--limit=25 : Maximum queued SMS messages to process}';

    protected $description = 'Send queued ISP SMS messages through platform or ISP-owned gateway settings.';

    public function handle(SmsManager $manager): int
    {
        $count = $manager->sendQueued((int) $this->option('limit'));
        $this->info("Processed {$count} queued SMS message(s).");

        return self::SUCCESS;
    }
}
