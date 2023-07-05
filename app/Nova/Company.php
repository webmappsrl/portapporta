<?php

namespace App\Nova;

use App\Enums\Fonts;
use Laravel\Nova\Panel;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Color;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\BelongsTo;
use Datomatic\NovaMarkdownTui\MarkdownTui;
use Laravel\Nova\Http\Requests\NovaRequest;
use Datomatic\NovaMarkdownTui\Enums\EditorType;
use Laravel\Nova\Fields\Textarea;

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
        $androidLink = $this->android_store_link;
        $iosLink = $this->ios_store_link;
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make('name'),
            BelongsTo::make('User')->nullable(),
            Text::make('sku')
                ->hideWhenUpdating()
                ->help('Must be prefixed with "it.webmapp.{sku}"')
                ->rules(['required', 'starts_with:it.webmapp']),
            Text::make(__('Play Store link (android)'), 'android_store_link')
                ->displayUsing(function ($value, $resource, $attribute) use ($androidLink) {
                    if (!$androidLink) {
                        return '';
                    }
                    return '<a class="link-default" target="_blank" href="' . $androidLink . '">App Link</a>';
                })->asHtml(),
            Text::make(__('App Store link (iOS)'), 'ios_store_link')
                ->displayUsing(function ($value, $resource, $attribute) use ($iosLink) {
                    if (!$iosLink) {
                        return '';
                    }
                    return '<a class="link-default" target="_blank" href="' . $iosLink . '">App Link</a>';
                })->asHtml(),
            Text::make(__('Ticket E-mails'), 'ticket_email')->help('Seperate e-mails with a "," (comma) for multiple e-mail addresses.'),
            new Panel('Company API', $this->apiPanel()),
            new Panel('Company Resources', $this->companyResources()),
        ];
    }

    public function apiPanel()
    {
        $apis = [
            'CONFIG' => 'company.config.json',
            'USER TYPES' => 'company.user_types.json',
            'TRASH TYPES' => 'company.trash_types.json',
            'WASTES' => 'company.wastes.json',
            'WASTE COLLECTION CENTER' => 'company.waste_collection_centers.geojson',
        ];
        $fields = [];
        foreach ($apis as $label => $route) {
            $fields[] =  Text::make($label, function () use ($route) {
                $url = route($route, ['id' => $this->id]);
                return "<a href='$url' target='_blank'>$url</a>";
            })->asHtml()->onlyOnDetail();
        }
        return $fields;
    }

    public function companyResources()
    {
        return [
            Image::make(__('Icon'), 'icon')
                ->rules('image', 'mimes:png')
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

            Image::make(__('Header image'), 'header_image')
                ->rules('image', 'mimes:png', 'dimensions:width=1024,height=500')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'header_image.png';
                })
                ->help(__('Required size is :widthx:heightpx', ['width' => 1024, 'height' => 500])),

            Image::make(__('Footer image'), 'footer_image')
                ->rules('image', 'mimes:png')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'footer_image.png';
                })
                ->help(__('Required size is :widthx:heightpx', ['width' => 1024, 'height' => 500])),

            MarkdownTui::make(__('Header'), 'header')
                ->initialEditType(EditorType::WYSIWYG),

            MarkdownTui::make(__('Footer'), 'footer')
                ->initialEditType(EditorType::WYSIWYG)
                ->hideFromIndex(),

            Textarea::make('Variables', 'css_variables')
                ->help('go to <a traget="_blank" href="https://ionicframework.com/docs/theming/color-generator">Color Generator</a> to generate the variables by simply customize the colors and copy the generated variables here')
                ->hidefromIndex(),

            Select::make('Font')
                ->options(Fonts::toArray())
                ->displayUsingLabels()
                ->hideFromIndex(),

            Color::make('Primary Color', 'primary_color')
                ->hideFromIndex(),

            Color::make('Secondary Color', 'secondary_color')
                ->hideFromIndex(),

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
