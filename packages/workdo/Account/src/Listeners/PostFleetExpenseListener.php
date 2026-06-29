<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\Fleet\Events\PostFleetExpense;

class PostFleetExpenseListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(PostFleetExpense $event)
    {
        if(Module_is_active('Account'))
        {
            $this->bankTransactionsService->createPostFleetExpense($event->expense);
            $this->journalService->createPostFleetExpenseJournal($event->expense);
        }
    }
}
