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

    public function __construct(PushNotification $pushNotification)
    {
        $this->pushNotification = $pushNotification;
    }

    public function handle()
    {
        $logger = Log::channel('push_notifications');
        $zones = $this->pushNotification->zone_ids;
        $status = true;
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
            $allSuccess = true;

            foreach ($tokenBatches as $index => $batch) {
                if (isset($batchStatus[$index]) && $batchStatus[$index] === 'success') {
                    continue;
                }

                $success = SendBatchNotification::dispatchSync($this->pushNotification, $batch, $index);
                $batchStatus[$index] = $success ? 'success' : 'failed';
                if (!$success) {
                    $allSuccess = false;
                }
            }

            // Retry failed batches
            foreach ($batchStatus as $index => $status) {
                if ($status === 'failed') {
                    $batch = $tokenBatches[$index];
                    $success = SendBatchNotification::dispatchSync($this->pushNotification, $batch, $index);
                    $batchStatus[$index] = $success ? 'success' : 'failed';
                    if (!$success) {
                        $allSuccess = false;
                    }
                }
            }
        } catch (\Exception $e) {
            $status = false;
            $logger->info("Error processing push notifications: " . $e->getMessage());
        } finally {
            $this->pushNotification->status = $allSuccess;
            $this->pushNotification->batch_status = $batchStatus;
            $this->pushNotification->save();

            $logger->info("Final status: " . ($allSuccess ? 'true' : 'false'));
        }
    }
}
