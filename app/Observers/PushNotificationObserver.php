<?php

namespace App\Observers;

use App\Models\PushNotification;
use App\Jobs\ProcessPushNotification;
use Illuminate\Support\Facades\Log;

class PushNotificationObserver
{
    /**
     * Handle the PushNotification "created" event.
     *
     * @param  \App\Models\PushNotification  $pushNotification
     * @return void
     */
    public function created(PushNotification $pushNotification)
    {
        Log::info("created on: {$pushNotification->created_at}");
        $user = auth()->user();
        $pushNotification->company_id = $user->company->id;
        $pushNotification->save();
        // Metti in coda la job per la data prestabilita
        ProcessPushNotification::dispatch($pushNotification)
            ->delay($pushNotification->schedule_date)->onConnection('database');
    }

    /**
     * Handle the PushNotification "updated" event.
     *
     * @param  \App\Models\PushNotification  $pushNotification
     * @return void
     */
    public function updated(PushNotification $pushNotification)
    {
        Log::info("updated");
    }

    /**
     * Handle the PushNotification "deleted" event.
     *
     * @param  \App\Models\PushNotification  $pushNotification
     * @return void
     */
    public function deleted(PushNotification $pushNotification)
    {
        //
    }

    /**
     * Handle the PushNotification "restored" event.
     *
     * @param  \App\Models\PushNotification  $pushNotification
     * @return void
     */
    public function restored(PushNotification $pushNotification)
    {
        //
    }

    /**
     * Handle the PushNotification "force deleted" event.
     *
     * @param  \App\Models\PushNotification  $pushNotification
     * @return void
     */
    public function forceDeleted(PushNotification $pushNotification)
    {
        //
    }
}
