<?php

namespace Workdo\Pos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\Pos\Models\PosBillingCounter;

class DestroyPosBillingCounter
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PosBillingCounter $counter
    ) {}
}