<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Taskly\Models\ProjectPayment;

class DestroyProjectPayment
{
    use Dispatchable;

    public function __construct(
        public ProjectPayment $projectPayment
    ) {}
}
