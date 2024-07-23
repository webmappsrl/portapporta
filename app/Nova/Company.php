<?php

namespace App\Nova;

use App\Enums\Fonts;
use Laravel\Nova\Panel;
use Manogi\Tiptap\Tiptap;
use Wm\MapPoint\MapPoint;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Color;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Ebess\AdvancedNovaMediaLibrary\Fields\Images;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\HasMany;

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
    public static function label()
    {
        return __('Companies');
    }
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
            Text::make(__('Company name'), 'name')
                ->required(),
            Text::make(__('App name'), 'app_name')
                ->required(),
            Text::make('Admins', function ($request) {
                $admins = $this->companyAdmins;
                $html = '';
                foreach ($admins as $admin) {
                    $html .= '<a style="color:#44b3d6;" href=/resources/users/' . $admin->id . '>' . $admin->name . '</a><br>';
                }
                return $html;
            })->asHtml()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            HasMany::make('Company Admins', 'companyAdmins', User::class),
            Text::make('sku')
                ->hideWhenUpdating()
                ->help('Must be prefixed with "it.webmapp.{sku}"')
                ->rules(['required', 'regex:/^it.webmapp.[a-z0-9]+$/', 'unique:companies,sku']),
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
            Text::make(__('Ticket E-mails'), 'ticket_email')->help('Seperate e-mails with a "," (comma) for multiple e-mail addresses.')->hideFromIndex(),
            Text::make(__('Ticket E-mails'), function () {
                $emails = $this->ticket_email;
                if (!$emails) {
                    return '';
                }
                $emails = explode(',', $emails);
                $html = '';
                foreach ($emails as $email) {
                    $html .= $email . '<br>';
                }
                return $html;
            })->asHtml()->help('Seperate e-mails with a "," (comma) for multiple e-mail addresses.')->onlyOnIndex(),
            new Panel('Company API', $this->apiPanel()),
            new Panel('Company Location', $this->companyLocation()),
            new Panel('Company Resources', $this->companyResources()),
            new Panel('Company Panel', $this->companyPage()),
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

    public function companyLocation()
    {
        return [
            Number::make('default zoom')
                ->help('The default zoom of the map, the value can be  min_zoom >= start_zoom >= max_zoom')
                ->rules('lte:max_zoom', 'gte:min_zoom')
                ->default('min_zoom'),
            Number::make('max zoom')
                ->help('The max zoom of the map')
                ->default(17),
            Number::make('min zoom')
                ->help('The min zoom of the map')
                ->default(5),
            MapPoint::make('location')->withMeta([
                'minZoom' => 5,
                'maxZoom' => 17,
                'defaultZoom' => 5
            ]),
        ];
    }
    public function companyResources()
    {
        $path = 'storage/resources/' . $this->model()->id;
        $iconUrl = url($path . '/icon.png');
        $splashUrl = url($path . '/splash.png');
        $iconSmallUrl = url($path . '/icon_small.png');
        $featureImageUrl = url($path . '/feature_image.png');
        $headerImageUrl = url($path . '/header_image.png');
        $footerImageUrl = url($path . '/footer_image.png');
        $appIconUrl = url($path . '/app_icon.png');
        $logoIconUrl = url($path . '/logo.png');

        return [
            Image::make(__('Icon'), 'icon')
                ->rules('image', 'mimes:png', 'dimensions:width=1024,height=1024')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'icon.png';
                })
                ->help(__('Required size is :widthx:heightpx. Once the image is uploaded, you can find it at this Link:  <a href="' . $iconUrl . '" target="_blank">' . $iconUrl . '</a>', ['width' => 1024, 'height' => 1024]))
                ->hideFromIndex()
                ->disableDownload(),

            Image::make(__('Splash image'), 'splash')
                ->rules('image', 'mimes:png', 'dimensions:width=2732,height=2732')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'splash.png';
                })
                ->help(__('Required size is :widthx:heightpx. Once the image is uploaded, you can find it at this Link:  <a href="' . $splashUrl . '" target="_blank">' . $splashUrl . '</a>', ['width' => 2732, 'height' => 2732]))
                ->hideFromIndex()
                ->disableDownload(),

            Image::make(__('Icon small'), 'icon_small')
                ->rules('image', 'mimes:png', 'dimensions:width=512,height=512')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'icon_small.png';
                })
                ->help(__('Required size is :widthx:heightpx. Once the image is uploaded, you can find it at this Link:  <a href="' . $iconSmallUrl . '" target="_blank">' . $iconSmallUrl . '</a>', ['width' => 512, 'height' => 512]))
                ->hideFromIndex()
                ->disableDownload(),

            Image::make(__('Feature image'), 'feature_image')
                ->rules('image', 'mimes:png', 'dimensions:width=1024,height=500')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'feature_image.png';
                })
                ->help(__('Required size is :widthx:heightpx. Once the image is uploaded, you can find it at this Link:  <a href="' . $featureImageUrl . '" target="_blank">' . $featureImageUrl . '</a>', ['width' => 1024, 'height' => 500]))
                ->hideFromIndex()
                ->disableDownload(),

            Image::make(__('Header image'), 'header_image')
                ->rules('image', 'mimes:png')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'header_image.png';
                })
                ->help(__('Once the image is uploaded, you can find it at this Link:  <a href="' . $headerImageUrl . '" target="_blank">' . $headerImageUrl . '</a>'))
                ->hideFromIndex()
                ->disableDownload(),

            Image::make(__('Footer image'), 'footer_image')
                ->rules('image', 'mimes:png')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->storeAs(function () {
                    return 'footer_image.png';
                })
                ->help(__('Once the image is uploaded, you can find it at this Link:  <a href="' . $footerImageUrl . '" target="_blank">' . $footerImageUrl . '</a>'))
                ->hideFromIndex()
                ->disableDownload(),

            Image::make(__('App icon'), 'app_icon')
                ->rules('image', 'mimes:png')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->hideFromIndex()
                ->disableDownload()
                ->help(__('Once the image is uploaded, you can find it at this Link:  <a href="' . $appIconUrl . '" target="_blank">' . $appIconUrl . '</a>'))
                ->storeAs(function () {
                    return 'app_icon.png';
                }),

            Image::make('Logo', 'logo')
                ->rules('image', 'mimes:png')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->hideFromIndex()
                ->disableDownload()
                ->help(__('Once the image is uploaded, you can find it at this Link:  <a href="' . $logoIconUrl . '" target="_blank">' . $logoIconUrl . '</a>'))
                ->storeAs(function () {
                    return 'logo.png';
                }),


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

            File::make('Push Notification Plist', 'push_notification_plist_url')
                // ->rules('exclude:mimes:php')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->hideFromIndex()
                ->help(__('follow this <a href="https://capacitorjs.com/docs/guides/push-notifications-firebase" target="_blank">link</a>'))
                ->storeAs(function () {
                    return 'GoogleService-Info.plist';
                }),

            File::make('Push Notification Json', 'push_notification_json_url')
                ->rules('mimes:json')
                ->disk('public')
                ->path('resources/' . $this->model()->id)
                ->hideFromIndex()
                ->help(__('follow this <a href="https://capacitorjs.com/docs/guides/push-notifications-firebase" target="_blank">link</a>'))
                ->storeAs(function () {
                    return 'google-services.json';
                }),
            Code::make('Form JSON', 'form_json')
                ->language('json')
                ->rules('required', 'json')
                ->help('I campi \'name\', \'email\', \'password\', \'password_confirmation\' sono obbligatori.')
                ->hideFromIndex(),
        ];
    }

    public function companyPage()
    {
        $allButtons = [
            'heading',
            '|',
            'italic',
            'bold',
            '|',
            'link',
            'code',
            'strike',
            'underline',
            'highlight',
            '|',
            'bulletList',
            'orderedList',
            'br',
            'codeBlock',
            'blockquote',
            '|',
            'horizontalRule',
            'hardBreak',
            '|',
            'table',
            '|',
            'image',
            '|',
            'textAlign',
            '|',
            'rtl',
            '|',
            'history',
            '|',
            'editHtml',
        ];
        return [
            Tiptap::make('Company Page', 'company_page')
                ->buttons($allButtons)
                ->hideFromIndex()
                ->help('You can use HTML tags to format the content. Please insert image only by external link.'),
            Text::make('Other info url', 'other_info_url')->hideFromIndex()->hideFromIndex()
                ->help('this url can be displayed as default in app other info url in calendar page'),
            Images::make('Images', 'content-images')
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
        return auth()->user()->hasRole('super_admin');
    }

    public static function afterCreate(NovaRequest $request, Model $model)
    {
        //this function is called after the creation of the model and parse the content field to get the image url if exists and update the media in the featured-image collection
        $model->updateMediaCollections();
    }

    public static function afterUpdate(NovaRequest $request, Model $model)
    {
        //this function is called after the update of the model and parse the content field to get the image url if exists and update the media in the featured-image collection
        $model->updateMediaCollections();
    }
}
