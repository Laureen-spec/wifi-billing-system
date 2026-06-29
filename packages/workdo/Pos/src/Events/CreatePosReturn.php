<?php

namespace Workdo\Pos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\Pos\Models\PosReturn;

class CreatePosReturn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public PosReturn $return
    ) {}
}
