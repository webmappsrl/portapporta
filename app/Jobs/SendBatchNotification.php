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
        $attempt = 0;
        $success = false;

        $logger->info("Preparing to send batch " . ($this->batchIndex + 1));

        while (!$success && $attempt < 3) {
            $logger->info("Attempting to send batch " . ($this->batchIndex + 1) . ", attempt " . ($attempt + 1));
            $res = Larafirebase::fromArray(['title' => $this->pushNotification->title, 'body' => $this->pushNotification->message])->sendNotification($this->batch);

            if ($res->status() === 200) {
                $logger->info("Batch " . ($this->batchIndex + 1) . " sent successfully.");
                $success = true;
            } else {
                $logger->info("Failed to send batch " . ($this->batchIndex + 1) . ", attempt " . ($attempt + 1) . ", Status: " . $res->status());
                $attempt++;
                sleep(5); // Aspetta per 5 secondi prima del prossimo tentativo
            }
        }

        return $success;
    }
}
