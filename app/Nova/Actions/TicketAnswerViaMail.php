<?php

namespace App\Nova\Actions;

use App\Mail\TicketAnswer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

class TicketAnswerViaMail extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public function name()
    {
        return __('Answer via email');
    }

    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $ticket) {
            $user = $ticket->user;
            $loggedUser = Auth::user();

            try {
                \Mail::to($user->email)
                    ->cc($loggedUser->email)
                    ->send(new TicketAnswer($ticket, $fields->answer));

                $ticket->is_read = true;
                $ticket->save();
            } catch (\Exception $e) {
                \Log::error('Errore nell invio della mail: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

                return Action::danger('Errore nell invio della mail');
            }
        }

        return Action::message('Email inviata correttamente');
    }

    public function fields(NovaRequest $request)
    {
        return [
            Trix::make('Answer')->withFiles('public')->rules('required')->fullWidth(),
        ];
    }
}
