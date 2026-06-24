<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\MedicalLabManagement\Events\ApproveMedicalOrderPayment;

class ApproveMedicalOrderPaymentListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(ApproveMedicalOrderPayment $event)
    {
        if(Module_is_active('Account'))
        {
            $this->bankTransactionsService->medicalOrderPayment($event->medicalOrderPayment);
            $this->journalService->medicalOrderPaymentJournal($event->medicalOrderPayment);
        }
    }
}
