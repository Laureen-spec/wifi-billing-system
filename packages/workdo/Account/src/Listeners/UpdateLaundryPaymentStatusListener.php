<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\LaundryManagement\Events\UpdateLaundryPaymentStatus;

class UpdateLaundryPaymentStatusListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(UpdateLaundryPaymentStatus $event)
    {
        if(Module_is_active('Account'))
        {
            $bankAccountId = $event->laundryPayment->bank_account_id ?? null;
            if($bankAccountId) {
                $this->bankTransactionsService->laundryPaymentStatus($event->laundryRequest, $bankAccountId);
                $this->journalService->laundryPaymentStatusJournal($event->laundryRequest, $bankAccountId);
            }
        }
    }
}
