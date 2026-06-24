<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Models\BankAccount;
use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\Holidayz\Events\ApproveHolidayzRoomBooking;

class ApproveHolidayzRoomBookingListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(ApproveHolidayzRoomBooking $event)
    {
        if (Module_is_active('Account')) {
            $bankAccountId = $event->booking->bank_account_id ?? null;
            if ($bankAccountId) {
                $this->bankTransactionsService->createHolidayzBookingPayment($event->booking, $bankAccountId);
                $this->journalService->createHolidayzBookingPaymentJournal($event->booking, $bankAccountId);
            }
        }
    }
}
