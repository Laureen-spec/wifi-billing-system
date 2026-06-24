<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\EventsManagement\Events\UpdateEventBookingPaymentStatus;

class UpdateEventBookingPaymentStatusListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(UpdateEventBookingPaymentStatus $event)
    {
        if(Module_is_active('Account'))
        {
            $bankAccountId = $event->payment->bank_account_id ?? null;
            if($bankAccountId) {
                $this->bankTransactionsService->eventBookingPaymentStatus($event->payment, $bankAccountId);
                $this->journalService->eventBookingPaymentStatusJournal($event->payment, $bankAccountId);
            }
        }
    }
}
