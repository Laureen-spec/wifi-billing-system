<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Models\BankAccount;
use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\EventsManagement\Events\EventBookingPayments;

class EventBookingPaymentsListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(EventBookingPayments $event)
    {
        if(Module_is_active('Account', $event->eventBookingPayment->created_by))
        {
            $bankAccount = BankAccount::where('payment_gateway', $event->eventBookingPayment->payment_type)->where('created_by', $event->eventBookingPayment->created_by)->first();
            if ($bankAccount) {
                $this->bankTransactionsService->eventBookingPaymentStatus($event->eventBookingPayment, $bankAccount->id);
                $this->journalService->eventBookingPaymentStatusJournal($event->eventBookingPayment, $bankAccount->id);
            }
        }
    }
}
