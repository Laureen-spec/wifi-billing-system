<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\CateringManagement\Events\MarkCateringExpenseTrackingAsPaid;

class MarkCateringExpenseTrackingAsPaidListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(MarkCateringExpenseTrackingAsPaid $event)
    {
        if(Module_is_active('Account'))
        {
            $this->bankTransactionsService->cateringExpenseTracking($event->cateringexpensetracking);
            $this->journalService->cateringExpenseTrackingJournal($event->cateringexpensetracking);
        }
    }
}
