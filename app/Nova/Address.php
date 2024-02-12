<?php

namespace App\Nova;

use Laravel\Nova\Fields\BelongsTo;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Wm\MapPoint\MapPoint;

class Address extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Address>
     */
    public static $model = \App\Models\Address::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
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
            Select::make('Zone', 'zone_id')
                ->options(function () {
                    return \App\Models\Zone::where('company_id', $this->user->app_company_id)
                        ->pluck('label', 'id');
                })
                ->searchable()
                ->displayUsing(function ($value) {
                    return \App\Models\Zone::find($value)?->label ?? 'ND';
                }),
            Text::make('city'),
            Text::make('address'),
            Text::make('house_number'),
            Text::make('User Type', function () {
                if (!is_null($this->user_type_id)) {
                    return $this->userType->label;
                }
                return 'ND';
            })->onlyOnDetail(),
            BelongsTo::make('User')->nullable()->searchable(),
            MapPoint::make('location')->withMeta([
                'minZoom' => 5,
                'maxZoom' => 17,
                'defaultZoom' => 5
            ])->required(),
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

    public static function indexQuery(NovaRequest $request, $query)
    {
        $user = Auth()->user();
        if ($user->hasRole('super_admin')) {
            return parent::indexQuery($request, $query);
        } else if ($user->hasRole('company_admin')) {
            return $query
                ->join('users', 'addresses.user_id', '=', 'users.id')
                ->where('users.app_company_id', $user->admin_company_id)
                ->select('addresses.*');
        } else {
            return  parent::indexQuery($request, $query)->where('user_id', $user->id);
        }
    }

    /**
     * Hides the resource from menu it its not 'admin' or 'company_admin'
     *
     * @param  \Illuminate\Http\Request  $request
     * @return boolean
     */
    public static function availableForNavigation(Request $request)
    {
        return $request->user()->hasRole('super_admin');
    }
}
