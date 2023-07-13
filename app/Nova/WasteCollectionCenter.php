<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Color;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Eminiarts\Tabs\Tabs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Imumz\LeafletMap\LeafletMap;
use Wm\MapPoint\MapPoint;

class WasteCollectionCenter extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\WasteCollectionCenter::class;

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
        'name'
    ];

    public function position()
    {
        $coords = [];
        if (!is_null($this->geometry)) {
            $geojson = DB::select(DB::raw("select st_asgeojson(geometry) as g from waste_collection_centers where id={$this->id} "))[0]->g;
            $coords = json_decode($geojson, true)['coordinates'];
        }
        return $coords;
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        // $geom = DB::select("SELECT ST_AsGeoJSON('".$this->geometry."') as g")[0]->g;
        // $geojson = "{'type': 'FeatureCollection','features':[{'type': 'Feature','geometry': '$geom'}]}";
        // $geojson = '{"type" : "FeatureCollection", "features" : [{"type": "Feature", "geometry": '.$geom.'}]}';
        return [
            ID::make()->sortable(),
            Color::make('Marker Color', 'marker_color')->hideFromIndex(),
            Text::make('Marker Size', 'marker_size')->hideFromIndex(),
            Text::make('Website', 'website')->hideFromIndex(),
            MapPoint::make('Geometry', 'geometry')->withMeta([
                'center' => ["43", "10"],
                'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                'minZoom' => 8,
                'maxZoom' => 17,
                'defaultZoom' => 13
            ]),
            // Text::make('picture_url')->hideFromIndex(),

            Text::make('Position', function () {
                if (!is_null($this->geometry)) {
                    $coord = $this->position();
                    $lon = $coord[0];
                    $lat = $coord[1];
                    return "<a href='https://www.google.it/maps/@$lat,$lon,15z' target='_blank'>($lon,$lat)</a>";
                }
                return 'ND';
            })->asHtml()->hideFromIndex(),

            NovaTabTranslatable::make([
                Text::make('name')->sortable(),
                Textarea::make('description'),
                Textarea::make('orario')
            ]),
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
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->where('company_id', $request->user()->company->id);
    }
}
