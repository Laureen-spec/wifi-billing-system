<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\BeautySpaManagement\Events\MarkBeautyBookingPaymentPaid;

class MarkBeautyBookingPaymentPaidListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(MarkBeautyBookingPaymentPaid $event)
    {

        if(Module_is_active('Account'))
        {
            $bankAccountId = $event->booking->bank_account_id ?? null;
            if($bankAccountId) {
                $this->bankTransactionsService->createBeautyBookingPayment($event->booking, $bankAccountId);
                $this->journalService->createBeautyBookingPaymentJournal($event->booking, $bankAccountId);
            }
        }
    }
}
