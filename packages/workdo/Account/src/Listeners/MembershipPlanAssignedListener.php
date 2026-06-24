<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\GymManagement\Events\MembershipPlanAssigned;

class MembershipPlanAssignedListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(MembershipPlanAssigned $event)
    {
        if(Module_is_active('Account'))
        {
            $this->bankTransactionsService->membershipPlanAssigned($event->payment);
            $this->journalService->membershipPlanAssignedJournal($event->payment);
        }
    }
}
