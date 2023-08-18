<?php

namespace App\Jobs;

use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kutia\Larafirebase\Facades\Larafirebase;
use Illuminate\Support\Facades\Log;

class ProcessPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $pushNotification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(PushNotification $pushNotification)
    {
        $this->pushNotification = $pushNotification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Processing push notification: {$this->pushNotification->title}");
        Log::info("app company id: {$this->pushNotification->company_id}");
        Log::info("app company message: {$this->pushNotification->message}");
        $status = false;

        try {
            $fcmTokens =  User::whereNotNull('fcm_token')->where('app_company_id', $this->pushNotification->company_id)->pluck('fcm_token')->toArray();
            Log::info("token numbers: " . json_encode($fcmTokens));
            try {
                $res =  Larafirebase::fromArray(['title' => $this->pushNotification->title, 'body' => $this->pushNotification->message])->sendNotification($fcmTokens);
                Log::info("token numbers: " . $res->body());
                if ($res->status() === 200) {
                    $status = true;
                }
            } catch (\Exception $e) {
                Log::info("push error" . $e->getMessage());
            }
            $this->pushNotification->status = $status;
            $this->pushNotification->save();
            Log::info("push notification status: {$this->pushNotification->status}");
        } catch (\Exception $e) {
            Log::info("push error" . $e->getMessage());
            $this->pushNotification->status = false;
            $this->pushNotification->save();
        }
    }
}
