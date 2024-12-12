<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Models\PushNotification;
use FirebaseNotificationsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Kutia\Larafirebase\Facades\Larafirebase;

class SendBatchNotification implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pushNotification;
    protected $batch;
    protected $batchIndex;

    public function __construct($pushNotification, $batch, $batchIndex)
    {
        $this->pushNotification = $pushNotification; //serialized model ... it could be outdated
        $this->batch = $batch;
        $this->batchIndex = $batchIndex;
    }

    public function handle()
    {
        $logger = Log::channel('push_notifications');
        $logger->info("Preparing to send batch " . ($this->batchIndex + 1));

        // TO TEST JOBS
        // if ($this->batchIndex > 2) {
        //     throw new Exception('test');
        // }
        // Http::fake([
        //     '*' => Http::response('Hello World', 200, ['Headers']),
        // ]);


        $res = FirebaseNotificationsService::getService()->sendNotification(['title' => $this->pushNotification->title, 'body' => $this->pushNotification->message], $this->batch);

        //get the last version of batch_status
        $updatedPushNotification = PushNotification::find($this->pushNotification->id);
        $batchStatus = $updatedPushNotification->batch_status;
        $humanIndex = $this->batchIndex + 1;
        if ($res) {
            $logger->info("Batch $humanIndex sent successfully.");
            $batchStatus[$this->batchIndex] = 'success';
            $updatedPushNotification->batch_status = $batchStatus;
            $updatedPushNotification->save();
        } else {
            $message = "Batch $humanIndex failed to send.";
            $logger->error($message);
            throw new Exception($message); //the job fails here
        }
    }

    //https://laravel.com/docs/8.x/queues#time-based-attempts
    public function retryUntil()
    {
        // will keep retrying, by backoff logic below
        // until 2 hours from first run.
        // After that, if it fails it will go
        // to the failed_jobs table
        return now()->addHours(2);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        // first 5 retries, after first failure
        // will be 5 minutes (300 seconds) apart,
        // further attempts will be
        // 10 minutes (600 seconds) after
        // previous attempt
        return [300, 300, 300, 300, 300, 600];
    }
}
