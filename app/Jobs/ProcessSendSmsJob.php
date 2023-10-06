<?php

namespace App\Jobs;

use App\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Exception;
use App\Utils\NotificationUtil;

class ProcessSendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $transaction_id;
    protected $business_id;
    protected $notificationUtil;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($transaction_id, $business_id, NotificationUtil $notificationUtil)
    {
        $this->transaction_id = $transaction_id;
        $this->business_id = $business_id;
        $this->notificationUtil = $notificationUtil;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $business_id = $this->business_id;
            $transaction_id = $this->transaction_id;
            $transaction = Transaction::where('business_id', $business_id)
                // ->with(['customer'])
                ->findOrFail($transaction_id);

            $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
        } catch (Exception $e) {
            dd($e);
        }
    }
}
