<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Models\BankAccount;
use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\ParkingManagement\Events\ParkingBookingPayments;

class ParkingBookingPaymentsListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(ParkingBookingPayments $event)
    {
        if(Module_is_active('Account', $event->booking->created_by))
        {
            $bankAccount = BankAccount::where('payment_gateway', $event->booking->payment_method)->where('created_by', $event->booking->created_by)->first();
            if ($bankAccount) {
                $this->bankTransactionsService->parkingBookingPayments($event->booking, $bankAccount->id);
                $this->journalService->parkingBookingPaymentsJournal($event->booking, $bankAccount->id);
            }
        }
    }
}
