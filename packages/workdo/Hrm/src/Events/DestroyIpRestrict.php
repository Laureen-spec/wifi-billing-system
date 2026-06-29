<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\IpRestrict;

class DestroyIpRestrict
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public IpRestrict $ipRestrict
    )
    {}
}