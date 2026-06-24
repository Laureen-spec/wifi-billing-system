<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\DairyCattleManagement\Events\UpdateDairyCattleExpenseTrackingStatus;

class UpdateDairyCattleExpenseTrackingStatusListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(UpdateDairyCattleExpenseTrackingStatus $event)
    {
        if(Module_is_active('Account'))
        {
            $this->bankTransactionsService->dairyCattleExpenseTracking($event->expenseTracking);
            $this->journalService->dairyCattleExpenseTrackingJournal($event->expenseTracking);
        }
    }
}
