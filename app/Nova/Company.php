<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

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
    public static $title = 'name';

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
            new Panel('Company API',$this->apiPanel()),
            new Panel('Company Resources',$this->companyResources()),
        ];
    }

    public function apiPanel() {
        $apis = [
            'CONFIG' => 'company.config.json',
            'USER TYPES' => 'company.user_types.json',
            'TRASH TYPES' => 'company.trash_types.json',
            'WASTES' => 'company.wastes.json',
            'WASTE COLLECTION CENTER' => 'company.waste_collection_centers.geojson',
        ];
        $fields = [];
        foreach($apis as $label => $route) {
            $fields[] =  Text::make($label,function () use ($route) {
                $url = route($route,['id'=>$this->id]);
                return "<a href='$url' target='_blank'>$url</a>";
            })->asHtml()->onlyOnDetail();
        }
        return $fields;
    }

    public function companyResources() {
        return [
            Image::make(__('Icon'), 'icon')
                ->rules('image', 'mimes:png', 'dimensions: width=1024,height=1024')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'icon.png';
                })
                ->help(__('Required size is :widthx:heightpx', ['width' => 1024, 'height' => 1024]))
                ->hideFromIndex()
                ->disableDownload(),
            Image::make(__('Splash image'), 'splash')
                ->rules('image', 'mimes:png', 'dimensions:width=2732,height=2732')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'splash.png';
                })
                ->help(__('Required size is :widthx:heightpx', ['width' => 2732, 'height' => 2732]))
                ->hideFromIndex()
                ->disableDownload(),
            Image::make(__('Icon small'), 'icon_small')
                ->rules('image', 'mimes:png', 'dimensions:width=512,height=512')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'icon_small.png';
                })
                ->help(__('Required size is :widthx:heightpx', ['width' => 512, 'height' => 512]))
                ->hideFromIndex()
                ->disableDownload(),

            Image::make(__('Feature image'), 'feature_image')
                ->rules('image', 'mimes:png', 'dimensions:width=1024,height=500')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'feature_image.png';
                })
                ->help(__('Required size is :widthx:heightpx', ['width' => 1024, 'height' => 500]))
                ->hideFromIndex()
                ->disableDownload(),
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
        $current_id = $request->user()->id;
        if ($current_id !== 1) {
            return false;
        }
        return true;
    }
}
