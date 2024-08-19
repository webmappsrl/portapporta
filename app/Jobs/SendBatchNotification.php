<?php

namespace App\Jobs;

use Kutia\Larafirebase\Facades\Larafirebase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBatchNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pushNotification;
    protected $batch;
    protected $batchIndex;

    public function __construct($pushNotification, $batch, $batchIndex)
    {
        $this->pushNotification = $pushNotification;
        $this->batch = $batch;
        $this->batchIndex = $batchIndex;
    }

    public function handle()
    {
        $logger = Log::channel('push_notifications');
        $logger->info("Preparing to send batch " . ($this->batchIndex + 1));

        $res = Larafirebase::withTitle($this->pushNotification->title)
            ->withBody($this->pushNotification->message)
            ->withAdditionalData([
                'page_on_click' => '/push-notification'
            ])->sendNotification($this->batch);

        if ($res->status() === 200) {
            $logger->info("Batch " . ($this->batchIndex + 1) . " sent successfully.");
            return true;
        } else {
            $logger->error("Batch " . ($this->batchIndex + 1) . " failed to send.");
            return false;
        }
    }
}
