<?php

namespace Workdo\Pos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Pos\Models\PosReturn;

class CompletePosReturn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PosReturn $return
    ) {}
}
