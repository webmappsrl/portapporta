<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Text;
use Illuminate\Validation\Rules;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\MorphToMany;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Support\Facades\Log;

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

    public static function label()
    {
        return __('Users');
    }
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
        $fields =  [
            ID::make()->sortable(),
            Gravatar::make()->maxWidth(50),
            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('fcm_token')
                ->sortable()
                ->onlyOnForms()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('super_admin');
                }),
            Text::make(__('Company'), 'app_company_id')
                ->required()
                ->onlyOnForms()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('super_admin');
                }),
            Text::make(__('Company'), 'app_company_id')
                ->displayUsing(function ($value) {
                    return \App\Models\Company::find($value)?->name ?? 'ND';
                })
                ->asHtml()
                ->onlyOnDetail()
                ->readonly(function (NovaRequest $request) {
                    return !$request->user()->hasRole('super_admin');
                })
                ->canSee(function ($request) {
                    return $request->user()->hasRole('super_admin');
                }),
            BelongsTo::make('Admin of company', 'companyWhereAdmin', Company::class)
                ->nullable()
                ->onlyOnForms()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('super_admin');
                }),
            MorphToMany::make('Roles', 'roles', \Vyuldashev\NovaPermission\Role::class)->canSee(function ($request) {
                return $request->user()->hasRole('super_admin');
            }),
            MorphToMany::make('Permissions', 'permissions', \Vyuldashev\NovaPermission\Permission::class)->canSee(function ($request) {
                return $request->user()->hasRole('super_admin');
            }),

            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:254')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}'),
            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', Rules\Password::defaults())
                ->updateRules('nullable', Rules\Password::defaults()),
            Boolean::make(__('Email Verified'), 'email_verified_at')
                ->displayUsing(function ($value) {
                    return isset($value);
                })
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->required(),

            HasMany::make('Addresses')
        ];
        $formData = [];
        $company = \App\Models\Company::find($this->app_company_id);
        if ($company) {
            $formData = json_decode($company->form_json, true) ?? [];
        }
        $harcoded_fields = ['name', 'email', 'password', 'password_confirmation', 'secondStep'];
        foreach ($formData as $field) {
            $key = $field['id'];
            if (in_array($key, $harcoded_fields)) {
                continue;
            }
            $rules = [];
            if (isset($field['validators'])) {
                foreach ($field['validators'] as $validator) {
                    if ($validator['name'] === 'required') {
                        $rules[] = 'required';
                    } elseif ($validator['name'] === 'email') {
                        $rules[] = 'email';
                    } elseif ($validator['name'] === 'minLength' && isset($validator['value'])) {
                        $rules[] = 'min:' . $validator['value'];
                    }
                }
            }
            $label = ucwords(str_replace('_', ' ', $field['label']));
            $fields[] = Text::make(__($label), "form_data->$key")
                ->rules($rules);
        }
        return $fields;
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
        return [
            (new Actions\VerifyEmail())
                ->showInline(),
            (new Actions\SendPushNotification())
                ->showInline()
                ->canSee(function ($request) {
                    return $request->user()->hasRole('super_admin');
                }),
        ];
    }



    public static function afterCreate(NovaRequest $request, Model $model)
    {
        if ($request->user()->hasRole('company_admin')) {
            $model->app_company_id = $request->user()->app_company_id;
        }
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

    public static function indexQuery(NovaRequest $request, $query)
    {
        $user = Auth()->user();
        if ($user->hasRole('super_admin')) {
            return parent::indexQuery($request, $query);
        } else if ($user->hasRole('company_admin')) {
            return parent::indexQuery($request, $query)->where('app_company_id', $user->admin_company_id);
        } else {
            return  parent::indexQuery($request, $query)->where('user_id', $user->id);
        }
    }
}
