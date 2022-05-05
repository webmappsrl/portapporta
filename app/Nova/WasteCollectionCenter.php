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
                Color::make('marker_color'),
                Text::make('marker_size'),
                Text::make('website'),
                Text::make('picture_url'),

                NovaTabTranslatable::make([
                    Text::make('name')->sortable(),
                    Textarea::make('description'),
                    Textarea::make('orario')
                ]),
            
           
                // LeafletMap::make('geometry')
                // ->type('GeoJson')
                // ->geoJson('{
                //     "type": "FeatureCollection",
                //     "features": [
                //       {
                //         "type": "Feature",
                //         "properties": {},
                //         "geometry": {
                //           "type": "Point",
                //           "coordinates": [
                //             54.4921875,
                //             49.15296965617042
                //           ]
                //         }
                //       }
                //     ]
                //   }')
                // // ->geoJson('{"type" : "FeatureCollection", "features" : [{"type": "Feature", "geometry": {"type":"Point","coordinates":[10.4187271,42.863860723]}}]}')
                // ->center('10','42')
                // ->zoom(12),
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
