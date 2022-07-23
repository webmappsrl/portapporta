<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\MultiSelect;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class CalendarItemAdvancedReplicateAction extends Action
{
    use InteractsWithQueue, Queueable;

    private $start;
    private $stop;

    public function __construct($start,$stop)
    {
        $this->start=$start;
        $this->stop=$stop;
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
        $days = $fields['day_of_week'];
        if(count($days)==0) {
            return Action::danger("Please select atleast one day of week!");
        }
        foreach($models as $item) {
            foreach($days as $day) {
                $newItem=$item->replicate();
                $newItem->day_of_week=$day;
                $newItem->start_time=$fields['start_time'];
                $newItem->stop_time=$fields['stop_time'];
                $newItem->save();
                $newItem->trashTypes()->sync($item->trashTypes->pluck('id'));

            }
        }
        $count = count($days);
        return Action::message("Calendar Item replicated in to $count new items.");
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $start = $this->start;
        $stop = $this->stop;
        return [
            MultiSelect::make('day_of_week')->options([
                0 => 'Sun',
                1 => 'Mon',
                2 => 'Tue' ,
                3 => 'Wed',
                4 => 'Thu',
                5 => 'Fri',
                6 => 'Sat',
            ])->displayUsingLabels()->required(),
            Text::make('start_time')->default($start)->required(),
            Text::make('stop_time')->default($stop)->required(),
        ];
    }
}
