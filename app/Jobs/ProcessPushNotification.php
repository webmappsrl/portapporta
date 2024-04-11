<?php

namespace App\Jobs;

use App\Models\Address;
use App\Models\PushNotification;
use App\Models\User;
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
        $zones = $this->pushNotification->zone_ids;
        Log::info("Processing push notifications: {$this->pushNotification->title}");
        Log::info("app company id: {$this->pushNotification->company_id}");
        Log::info("app company message: {$this->pushNotification->message}");
        $azone_ids = json_encode($zones, true);
        Log::info("app company zones: {$azone_ids}");
        $status = false;

        try {
            try {
                $appUsers = User::whereNotNull('fcm_token')->where('app_company_id', $this->pushNotification->company_id)->get();
            } catch (\Exception $e) {
                Log::info("error " . json_encode($e));
                $appUsers = [];
            }
            Log::info("push notification filtering process");
            $AppUserFilteredByZones = $appUsers->filter(
                function ($appUsr) use ($zones) {
                    Log::info("user: {$appUsr->name}");
                    try {
                        $addresses = Address::where('user_id', $appUsr->id)->get();
                        if (is_null($addresses)) {
                            return false;
                        }
                        $address = $addresses->first();
                        if (is_null($address) || is_null($address->zone_id)) {
                            return false;
                        }
                        Log::info("user zone id: {$address->zone_id}");
                        return in_array($address->zone_id, $zones);
                    } catch (\Exception $e) {
                        return false;
                    }
                }
            );

            $fcmTokens =  $AppUserFilteredByZones->pluck('fcm_token')->toArray();
            Log::info("push notification count: " . count($fcmTokens));

            try {
                // Inizialmente impostiamo lo status a true, presumendo che tutto andrà bene
                $status = true;

                // Logga il numero totale di tokens
                Log::info("Push notification total token count: " . count($fcmTokens));

                // Suddividi l'array $fcmTokens in gruppi di 1000 token
                $tokenBatches = array_chunk($fcmTokens, 999);
                foreach ($tokenBatches as $index => $batch) {
                    // Invia la notifica push per il batch corrente
                    $res = Larafirebase::fromArray(['title' => $this->pushNotification->title, 'body' => $this->pushNotification->message])->sendNotification($batch);

                    // Controlla lo status della risposta per ogni batch
                    if ($res->status() === 200) {
                        // Log success for the current batch
                        Log::info("Push notification batch " . ($index + 1) . " sent successfully. Status: " . $res->status());
                        Log::info("Push notification body: " . $res->body());
                    } else {
                        // Se anche un solo batch fallisce, impostiamo lo status a false
                        $status = false;
                        Log::info("Failed to send push notification batch " . ($index + 1) . ". Status: " . $res->status());
                        Log::info("Push notification body: " . $res->body());
                        // Non interrompiamo il ciclo per tentare di inviare tutti i batch, ma puoi scegliere di fare diversamente
                    }
                }
            } catch (\Exception $e) {
                $status = false;
                Log::info("Push notification error: " . $e->getMessage());
            } finally {
                // Aggiorna lo status della notifica push con il risultato finale
                $this->pushNotification->status = $status;
                $this->pushNotification->save();
                // Log dello status finale della notifica push
                Log::info("Final push notification status: " . ($status ? 'true' : 'false'));
            }
        } catch (\Exception $e) {
            Log::info("push error" . $e->getMessage());
            $this->pushNotification->status = false;
            $this->pushNotification->save();
        }
    }
}
