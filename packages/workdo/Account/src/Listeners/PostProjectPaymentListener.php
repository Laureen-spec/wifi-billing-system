<?php

namespace Workdo\Account\Listeners;

use Workdo\Taskly\Events\PostProjectPayment;
use Workdo\Account\Services\JournalService;
use Workdo\Account\Services\BankTransactionsService;

class PostProjectPaymentListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(PostProjectPayment $event)
    {
        if(Module_is_active('Account'))
        {
            $this->journalService->createProjectPaymentJournal($event->projectPayment);
            $this->bankTransactionsService->createProjectPayment($event->projectPayment);
        }
    }
}
