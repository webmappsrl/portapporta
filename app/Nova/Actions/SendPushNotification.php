<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Actions\Action;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Nova\Http\Requests\NovaRequest;
use Kutia\Larafirebase\Facades\Larafirebase;
use App\Services\FirebaseNotificationsService;

class SendPushNotification extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $users)
    {
        $fcm_tokens = [];
        foreach ($users as $user) {
            if (!is_null($user->fcm_token)) {
                $fcm_tokens[] = $user->fcm_token;
            }
        }
        $res = FirebaseNotificationsService::getService()->sendNotification(
            [
                'title' => $fields->title,
                'body' => $fields->message
            ],
            $fcm_tokens,
            [
                'page_on_click' => '/home'
            ]
        );

        return Action::message('push notification sended successfully!');
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make('Title')
                ->rules('required', 'max:255')
                ->help('Inserisci il titolo della notifica push.'),
            Text::make('Message')
                ->rules('required')
                ->help('Inserisci la descrizione della notifica push.'),
        ];
    }
}
