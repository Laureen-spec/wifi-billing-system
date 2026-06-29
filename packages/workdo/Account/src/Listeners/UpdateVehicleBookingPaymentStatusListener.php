<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;
use Workdo\BeautySpaManagement\Events\MarkBeautyBookingPaymentPaid;
use Workdo\VehicleBookingManagement\Events\UpdateVehicleBookingPaymentStatus;

class UpdateVehicleBookingPaymentStatusListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(UpdateVehicleBookingPaymentStatus $event)
    {
        if(Module_is_active('Account'))
        {
            $bankAccountId = $event->payment->bank_account_id ?? null;
            if($bankAccountId) {
                $this->bankTransactionsService->vehicleBookingPayments($event->booking, $bankAccountId);
                $this->journalService->vehicleBookingPaymentsJournal($event->booking, $bankAccountId);
            }
        }
    }
}
