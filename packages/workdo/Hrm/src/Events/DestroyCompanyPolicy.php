<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\CompanyPolicy;

class DestroyCompanyPolicy
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CompanyPolicy $companyPolicy
    ) {}
}
