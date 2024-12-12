<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\{Address, PushNotification, User};

class ProcessPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pushNotification;
    protected $maxRetries = 3;

    public function __construct(PushNotification $pushNotification)
    {
        $this->pushNotification = $pushNotification;
    }

    public function handle()
    {
        $logger = Log::channel('push_notifications');
        $zones = $this->pushNotification->zone_ids;

        $usersByZone = [];

        $logger->info("Processing push notifications: {$this->pushNotification->title}");
        $logger->info("Company ID: {$this->pushNotification->company_id}, Message: {$this->pushNotification->message}");
        $logger->info("Zones: " . json_encode($zones));


        $appUsers = User::whereNotNull('fcm_token')
            ->where('app_company_id', $this->pushNotification->company_id)->get();

        $filteredUsers = $appUsers->filter(function ($user) use ($zones, &$usersByZone) {
            $address = Address::where('user_id', $user->id)->first();
            $inZone = $address && in_array($address->zone_id, $zones);
            if ($inZone) {
                $usersByZone[$address->zone_id][] = $user->name;
            }
            return $inZone;
        });
        $fcmTokens = $filteredUsers->pluck('fcm_token')->toArray();
        $logger->info("Total tokens to send: " . count($fcmTokens));

        // Log users by zone
        foreach ($usersByZone as $zone => $users) {
            $logger->info("Zone $zone: " . implode(', ', $users));
        }

        $tokenBatches = array_chunk($fcmTokens, 999);
        $logger->info("Total batches to send: " . count($tokenBatches));

        $batchStatus = [];
        // $this->pushNotification->status = false; # this is handled into the observer

        //PREPARE BATCH
        $jobs = [];
        foreach ($tokenBatches as $index => $batch) {
            $jobs[] = new SendBatchNotification($this->pushNotification, $batch, $index);
            $batchStatus[$index] = 'failed'; //set failed as default batch status
        }
        $this->pushNotification->batch_status = $batchStatus;
        $this->pushNotification->save();

        //jobs are chained, we execute batches 1 per time to avoid problems with db writes on batch_status column
        Bus::batch([$jobs])->name("Push notification: {$this->pushNotification->id}")->dispatch();
    }
}
