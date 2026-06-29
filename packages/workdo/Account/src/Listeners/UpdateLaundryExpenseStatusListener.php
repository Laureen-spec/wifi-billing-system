<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\LaundryManagement\Events\UpdateLaundryExpenseStatus;

class UpdateLaundryExpenseStatusListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(UpdateLaundryExpenseStatus $event)
    {
        if(Module_is_active('Account'))
        {
            $this->bankTransactionsService->laundryExpenseStatus($event->laundryExpense);
            $this->journalService->laundryExpenseStatusJournal($event->laundryExpense);
        }
    }
}
