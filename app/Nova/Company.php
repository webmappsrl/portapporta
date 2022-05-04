<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Company extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Company::class;

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
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make('name'),
            BelongsTo::make('User')->nullable(),
            File::make(__('Config .TS'),'configTs')
                ->acceptedTypes('.ts')
                ->disk('public')
                ->store(function (Request $request, $model) {
                    $file = $request->file('configTs');
                    return $model->get_file_name_extension($file);
                }),
            File::make(__('Config .JSON'),'configJson')
                ->acceptedTypes('.json')
                ->disk('public')
                ->store(function (Request $request, $model) {
                    $file = $request->file('configJson');
                    return $model->get_file_name_extension($file);
                }),
            Text::make(__('configXML ID'),'configXMLID'),
            Textarea::make(__('configXML description'),'description'),
            Text::make(__('configXML Version'),'version'),
            File::make(__('ICON'),'icon')
                ->acceptedTypes('image/*')
                ->disk('public')
                ->store(function (Request $request, $model) {
                    $file = $request->file('icon');
                    return $model->get_file_name_extension($file);
                }),
            File::make(__('SPLASH'),'splash')
                ->acceptedTypes('image/*')
                ->disk('public')
                ->store(function (Request $request, $model) {
                    $file = $request->file('splash');
                    return $model->get_file_name_extension($file);
                })
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
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
        $current_id = auth()->user()->id;
        if ($current_id !== 1) {
            return false;
        }
        return true;
    }
}
