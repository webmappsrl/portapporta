<?php

namespace App\Nova;

use App\Models\Company;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Text;
use NovaAttachMany\AttachMany;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Nova\Filters\CalendarItemsCalendarFilter;
use Laravel\Nova\Query\Search\SearchableRelation;
use App\Nova\Actions\CalendarItemAdvancedReplicateAction;


class CalendarItem extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\CalendarItem::class;

    public static $perPageViaRelationship = 20;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * Get the searchable columns for the resource.
     *
     * @return array
     */
    public static function searchableColumns()
    {
        return [new SearchableRelation('calendar', 'name'), new SearchableRelation('trashTypes', 'name')];
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        $calendars = $request->user()->companyWhereAdmin->calendars->pluck('id')->toArray();
        return $query->whereIn('calendar_id', $calendars);
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Calendar'),
            Text::make('Trash Types', function () {
                if ($this->trashTypes->count() > 0) {
                    $out = "<ul>\n";
                    foreach ($this->trashTypes as $item) {
                        $out .= "  <li>{$item->name}</li>\n";
                    }
                    $out .= "</ul>\n";
                    return $out;
                }
                return 'ND';
            })->asHtml(),
            Select::make('Day of Week', 'day_of_week')->options([
                0 => 'Sun',
                1 => 'Mon',
                2 => 'Tue',
                3 => 'Wed',
                4 => 'Thu',
                5 => 'Fry',
                6 => 'Sat',
            ])->displayUsingLabels()
                ->required(),
            Select::make('Frequency', 'frequency')->options([
                'weekly' => 'weekly',
                'biweekly' => 'biweekly',
            ])
                ->required(),
            Date::make('Base Date', 'base_date')
                ->help('Only used for biweekly frequency. Supported format: YYYY-MM-DD')
                ->hideFromIndex(),
            Text::make('Start Time', 'start_time')
                ->help('Supported format: HH:MM:SS'),
            Text::make('Stop Time', 'stop_time')
                ->help('Supported format: HH:MM:SS'),

            BelongsToMany::make('Trash Types', 'TrashTypes'),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [
            new CalendarItemsCalendarFilter,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [
            (new CalendarItemAdvancedReplicateAction($this->start_time, $this->stop_time))->onlyInline(),
        ];
    }
}
