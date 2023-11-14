<?php

namespace App\Nova;

use Wm\MapPoint\MapPoint;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Illuminate\Validation\Rules;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\BelongsTo;
use Spatie\Permission\Models\Role;
use Laravel\Nova\Fields\MorphToMany;
use Illuminate\Database\Eloquent\Model;
use Vyuldashev\NovaPermission\RoleSelect;
use Laravel\Nova\Http\Requests\NovaRequest;

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
        'id', 'name', 'email', 'fiscal_code'
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
                ->sortable()->onlyOnForms(),
            Text::make('app_company_id')
                ->hideFromIndex(),
            BelongsTo::make('Admin of company', 'companyWhereAdmin', Company::class)
                ->nullable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            BelongsTo::make('Admin of company', 'companyWhereAdmin', Company::class)
                ->nullable()
                ->onlyOnForms()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('super_admin');
                }),
            MorphToMany::make('Roles', 'roles', \Vyuldashev\NovaPermission\Role::class),
            MorphToMany::make('Permissions', 'permissions', \Vyuldashev\NovaPermission\Permission::class),

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
                ->rules('nullable', 'max:16')
                ->creationRules('unique:users,fiscal_code')
                ->updateRules('unique:users,fiscal_code,{{resourceId}}'),
            Text::make('User code')
                ->rules('nullable', 'max:16')
                ->creationRules('unique:users,user_code')
                ->updateRules('unique:users,user_code,{{resourceId}}'),
            Text::make('Company', function () {
                if (!is_null($this->zone_id) && !is_null($this->zone)) {
                    return $this->zone->company->name;
                }
                return 'ND';
            })->onlyOnDetail(),
            HasMany::make('Addresses')
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

    public static function afterCreate(NovaRequest $request, Model $model)
    {
        if ($model->admin_company_id) {
            if ($model->hasRole('contributor')) {
                $model->removeRole('contributor');
            }
            $model->assignRole('company_admin');
            $model->app_company_id = $model->admin_company_id;
        } else {
            $model->removeRole('company_admin');
            $model->assignRole('contributor');
        }
        $model->save();
    }

    public static function afterUpdate(NovaRequest $request, Model $model)
    {
        if ($model->admin_company_id) {
            if ($model->hasRole('contributor')) {
                $model->removeRole('contributor');
            }
            $model->assignRole('company_admin');
            $model->app_company_id = $model->admin_company_id;
        } else {
            $model->removeRole('company_admin');
            $model->assignRole('contributor');
        }
        $model->save();
    }
}
