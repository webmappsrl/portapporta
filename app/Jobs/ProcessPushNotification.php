<?php

namespace App\Jobs;

use App\Models\{Address, PushNotification, User};
use Illuminate\Bus\Queueable;
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

    public function __construct(PushNotification $pushNotification)
    {
        $this->pushNotification = $pushNotification;
    }

    public function handle()
    {
        $logger = Log::channel('push_notifications');
        $zones = $this->pushNotification->zone_ids;
        $status = true;

        $logger->info("Processing push notifications: {$this->pushNotification->title}");
        $logger->info("Company ID: {$this->pushNotification->company_id}, Message: {$this->pushNotification->message}");
        $logger->info("Zones: " . json_encode($zones));

        try {
            $appUsers = User::whereNotNull('fcm_token')
                ->where('app_company_id', $this->pushNotification->company_id)->get();

            $filteredUsers = $appUsers->filter(function ($user) use ($zones, $logger) {
                $address = Address::where('user_id', $user->id)->first();
                $inZone = $address && in_array($address->zone_id, $zones);
                if ($inZone) $logger->info("User {$user->name} in zone {$address->zone_id}");
                return $inZone;
            });
            $fcmTokens = $filteredUsers->pluck('fcm_token')->toArray();
            $logger->info("Total tokens to send: " . count($fcmTokens));

            $tokenBatches = array_chunk($fcmTokens, 999);
            foreach ($tokenBatches as $index => $batch) {
                $attempt = 0;
                $success = false;
                while (!$success && $attempt < 3) { // Prova fino a 3 volte per ciascun batch
                    $res = Larafirebase::fromArray(['title' => $this->pushNotification->title, 'body' => $this->pushNotification->message])->sendNotification($batch);
                    if ($res->status() === 200) {
                        $logger->info("Batch " . ($index + 1) . " sent successfully.");
                        $success = true;
                    } else {
                        $logger->info("Failed to send batch " . ($index + 1) . ", attempt " . ($attempt + 1) . ", Status: " . $res->status());
                        $attempt++;
                        sleep(5); // Aspetta per 5 secondi prima del prossimo tentativo
                    }
                }
                if (!$success) {
                    $status = false;
                }
            }
        } catch (\Exception $e) {
            $status = false;
            $logger->info("Error processing push notifications: " . $e->getMessage());
        } finally {
            $this->pushNotification->status = $status;
            $this->pushNotification->save();
            $logger->info("Final status: " . ($status ? 'true' : 'false'));
        }
    }
}
