<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\Warranty\Events\CompleteWarrantyClaim;

class CompleteWarrantyClaimListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(CompleteWarrantyClaim $event)
    {
        if (Module_is_active('Account')) {
            $this->bankTransactionsService->completeWarrantyClaim($event->claim);
            $this->journalService->completeWarrantyClaimJournal($event->claim);
        }
    }
}
