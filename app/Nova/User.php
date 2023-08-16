<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Wm\MapPoint\MapPoint;

class User extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\User::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'email',
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
            Gravatar::make()->maxWidth(50),
            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('fcm_token')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('app_company_id'),
            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:254')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}'),
            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', Rules\Password::defaults())
                ->updateRules('nullable', Rules\Password::defaults()),
            Text::make('Phone Number')
                ->rules('nullable', 'regex:/^\d{10,}$/') // Aggiungi le regole di validazione necessarie
                ->creationRules('unique:users,phone_number')
                ->updateRules('unique:users,phone_number,{{resourceId}}'),
            Text::make('Fiscal code')
                ->rules('nullable', 'max:16', 'unique:users,fiscal_code'),
            Text::make('User code')
                ->rules('nullable', 'max:16', 'unique:users,user_code'),
            Text::make('Company', function () {
                if (!is_null($this->zone_id)) {
                    return $this->zone->company->name;
                }
                return 'ND';
            })->onlyOnDetail(),
            MapPoint::make('location')->withMeta([
                'center' => ["42", "10"],
            ]),
            Text::make('Zone', function () {
                if (!is_null($this->zone_id)) {
                    return $this->zone->label;
                }
                return 'ND';
            })->onlyOnDetail(),
            Text::make('User Type', function () {
                if (!is_null($this->user_type_id)) {
                    return $this->userType->label;
                }
                return 'ND';
            })->onlyOnDetail(),
            HasMany::make('Addresses')
            // BelongsTo::make('Zone')->onlyOnForms(),

            // BelongsTo::make('User Type')->onlyOnForms(),

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

    /**
     * Hides the resource from menu it its not admin@webmapp.it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return boolean
     */
    public static function availableForNavigation(Request $request)
    {
        $current_id = $request->user()->id;
        if ($current_id !== 1) {
            return false;
        }
        return true;
    }
}
