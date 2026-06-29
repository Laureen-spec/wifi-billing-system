<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\LegalCaseManagement\Events\MarkCaseExpenseAsPaid;

class MarkCaseExpenseAsPaidListner
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(MarkCaseExpenseAsPaid $event)
    {

        if(Module_is_active('Account'))
        {
            $this->bankTransactionsService->markCaseExpenseAsPaid($event->caseExpense);
            $this->journalService->markCaseExpenseAsPaidJournal($event->caseExpense);
        }
    }
}
