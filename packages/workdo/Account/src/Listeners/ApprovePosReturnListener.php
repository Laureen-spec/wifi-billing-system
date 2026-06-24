<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Models\BankAccount;
use Workdo\Pos\Events\ApprovePosReturn;
use Workdo\Account\Services\JournalService;
use Workdo\Account\Services\BankTransactionsService;

class ApprovePosReturnListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(ApprovePosReturn $event)
    {
        if (Module_is_active('Account')) {
            $posReturn = $event->return;

            // Get bank account from original POS sale
            $bankAccount = BankAccount::where('id', $posReturn->originalPos->bank_account_id)
                ->where('created_by', creatorId())
                ->first();

            if ($bankAccount) {
                $this->bankTransactionsService->approvePosReturnPayment($posReturn, $bankAccount->id);
            }

            $this->journalService->approvePosReturnJournal($posReturn);
            $this->journalService->approvePosReturnCOGSJournal($posReturn);
        }
    }
}
