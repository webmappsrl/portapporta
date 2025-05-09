<?php

namespace App\Nova;

use App\Models\Company;
use Exception;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\MultiSelect;
use Illuminate\Support\Facades\Auth;

class PushNotification extends Resource
{
    public static function label()
    {
        return __('Push notifications');
    }
    public static function createButtonLabel()
    {
        return __('Create Push notifications');
    }

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\PushNotification>
     */
    public static $model = \App\Models\PushNotification::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'title', 'message'
    ];

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
            Date::make(__('created_at'), 'created_at')->hideWhenUpdating()->hideWhenCreating()->sortable(),
            Text::make(__('title'), 'title'),
            Textarea::make(__('message'), 'message')->maxlength(178)->enforceMaxlength(),
            DateTime::make(__('Schedule date'), 'schedule_date')->help(__('leave blank for instant scheduling'))->sortable(),
            Boolean::make(__('Status'), 'status')->hideFromDetail()->hideWhenUpdating()->hideWhenCreating(),
            MultiSelect::make(__('Zone'), 'zone_ids')
                ->options($this->getZones())
                ->default($this->getZones(['id']))
                ->displayUsing(function ($value) {
                    $user = Auth::user();
                    return $user->companyWhereAdmin->zones->whereIn('id', $value)->pluck('label')->toArray();
                })
                ->nullable(),
            ];
    }
    private function getZones($fields = ['label', 'id'])
    {
        try {
            $user = Auth::user();
            $zones = $user->companyWhereAdmin->zones;
            return  $zones->pluck(...$fields)->toArray();
        } catch (Exception $e) {
            return  $zones->pluck('label', 'id')->toArray();
        }
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
    public function taxonomyThemes()
    {
        return $this->morphToMany(TaxonomyTheme::class, 'taxonomy_themeable');
    }
    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
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
        return [];
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
        return $query->where('company_id', $request->user()->companyWhereAdmin->id);
    }
}
