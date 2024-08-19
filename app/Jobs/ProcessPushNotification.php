<?php

namespace App\Jobs;

use App\Models\{Address, PushNotification, User};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        $batchStatus = $this->pushNotification->batch_status ?? [];
        $usersByZone = [];

        $logger->info("Processing push notifications: {$this->pushNotification->title}");
        $logger->info("Company ID: {$this->pushNotification->company_id}, Message: {$this->pushNotification->message}");
        $logger->info("Zones: " . json_encode($zones));

        try {
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
            $allSuccess = true;

            foreach ($tokenBatches as $index => $batch) {
                $retryCount = 0;
                $success = false;

                if (isset($batchStatus[$index]) && $batchStatus[$index] === 'success') {
                    continue;
                }

                while (!$success && $retryCount < $this->maxRetries) {
                    $logger->info("Sending batch " . ($index + 1) . ", attempt " . ($retryCount + 1));
                    $success = SendBatchNotification::dispatchSync($this->pushNotification, $batch, $index);
                    $retryCount++;
                }

                $batchStatus[$index] = $success ? 'success' : 'failed';
                if (!$success) {
                    $allSuccess = false;
                    $logger->error("Batch " . ($index + 1) . " failed after $retryCount attempts.");
                }
            }
        } catch (\Exception $e) {
            $logger->info("Error processing push notifications: " . $e->getMessage());
        } finally {
            $this->pushNotification->status = $allSuccess;
            $this->pushNotification->batch_status = $batchStatus;
            $this->pushNotification->save();

            $logger->info("Final status: " . ($allSuccess ? 'true' : 'false'));
        }
    }
}
