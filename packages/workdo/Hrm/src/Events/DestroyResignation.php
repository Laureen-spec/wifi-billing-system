<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Resignation;

class DestroyResignation
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Resignation $resignation
    )
    {}
}