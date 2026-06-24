<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\Hrm\Models\IpRestrict;

class UpdateIpRestrict
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public IpRestrict $ipRestrict
    ) {}
}