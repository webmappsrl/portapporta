<?php

namespace App\Nova\Actions;

use App\Enums\TicketStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class TicketMarkAsAction extends Action
{
    use InteractsWithQueue;
    use Queueable;
    protected $desiredValue;
    protected $field;
    public function __construct($field = 'status', $value = TicketStatus::Done)
    {
        // Imposta lo value desiderato con il valore predefinito 'execute' se non specificato
        $this->desiredValue = $value;
        $this->field = $field;
    }
    /**
     * Get the displayable name of the action.
     *
     * @return string
     */
    public function name()
    {
        return __($this->field) . ' ' . __('Mark as') . ' ' . __($this->desiredValue->value);
    }

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {

        foreach ($models as $model) {
            if (auth()->user()->hasRole('company_admin')) {
                $model->update([
                    $this->field => $this->desiredValue->value
                ]);
            }
        }

        return Action::message('Ticket field' . $this->field . 'marked as' . $this->desiredValue->value . '!');
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [];
    }
}
