<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class TicketAnswerViaMail extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Answer via email';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        //get the tickets selected
        foreach ($models as $ticket) {

            $user = $ticket->user;

            try {
                \Mail::to($user->email)->send(new \App\Mail\TicketAnswer($ticket, $fields->answer));
            } catch (\Exception $e) {

                \Log::error('Error sending email: ' . $e->getMessage());
            }
        }

        return Action::message('Email inviata correttamente');
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
            Textarea::make('Answer')->rules('required'),
        ];
    }
}
