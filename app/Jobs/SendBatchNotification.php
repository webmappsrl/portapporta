<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
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
        $this->pushNotification = $pushNotification;
        $this->pushNotification->batch_status = $this->pushNotification->batch_status ?? [];
        $this->batch = $batch;
        $this->batchIndex = $batchIndex;
    }

    public function handle()
    {
        $this->batch = [$this->batch[0]];
        $logger = Log::channel('push_notifications');
        $logger->info("Preparing to send batch " . ($this->batchIndex + 1));

        $res = Larafirebase::fromArray(['title' => $this->pushNotification->title, 'body' => $this->pushNotification->message])->sendNotification($this->batch);

        $batchStatus = $this->pushNotification->batchStatus;
        if ($res->status() === 200) {
            $logger->info("Batch " . ($this->batchIndex + 1) . " sent successfully.");
            $batchStatus[$this->batchIndex] = 'success';
            $this->pushNotification->update(['batch_status' => $batchStatus]);
        } else {
            $message = "Batch " . ($this->batchIndex + 1) . " failed to send." . $res->body();
            $logger->error($message);
            $batchStatus[$this->batchIndex] = 'failed';
            $this->pushNotification->update(['batch_status' => $batchStatus]);
            $this->fail($message);
        }
    }
}
