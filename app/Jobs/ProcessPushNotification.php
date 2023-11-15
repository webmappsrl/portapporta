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
            Log::info("notification send to users: " . json_encode($AppUserFilteredByZones->pluck('name')->toArray()));
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
