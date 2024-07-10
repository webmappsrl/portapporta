<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessPushNotification;
use App\Models\PushNotification;

class StartPushNotificationFromBatch extends Command
{
    protected $signature = 'push:retry {notificationId} {batchNumber=4}';
    protected $description = 'Start processing a push notification from a specific batch';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $notificationId = $this->argument('notificationId');
        $batchNumber = (int) $this->argument('batchNumber');

        $pushNotification = PushNotification::find($notificationId);

        if (!$pushNotification) {
            $this->error('PushNotification not found.');
            return;
        }

        // Initialize batch status if not set
        $batchStatus = $pushNotification->batch_status ?? [];

        // Mark previous batches as success
        for ($i = 0; $i < $batchNumber - 1; $i++) {
            $batchStatus[$i] = 'success';
        }

        // Save the updated batch status
        $pushNotification->batch_status = $batchStatus;
        $pushNotification->save();

        // Dispatch the job to process the notification
        ProcessPushNotification::dispatch($pushNotification);

        $this->info('PushNotification processing started from batch ' . $batchNumber);
    }
}
