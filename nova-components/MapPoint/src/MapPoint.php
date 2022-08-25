<?php

namespace Wm\MapPoint;

use Laravel\Nova\Fields\Field;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;

class MapPoint extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'map-point';
    public $latlng = [];

    /**
     * Resolve the field's value.
     *
     * @param  mixed  $resource
     * @param  string|null  $attribute
     * @return void
     */
    public function resolve($resource, $attribute = null)
    {
        parent::resolve($resource, $attribute = null);
        $this->latlng = $this->geometryToLatLon($this->value);
        $this->withMeta(['latlng' => $this->latlng]);
    }
    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     * @return void
     */
    protected function fillAttributeFromRequest(
        NovaRequest $request,
        $requestAttribute,
        $model,
        $attribute
    ) {
        if ($request->exists($requestAttribute)) {
            $lonLat = explode(',', $request[$requestAttribute]);
            $model->{$attribute} = $this->latLonToGeometry($lonLat);
        }
    }

    public function geometryToLatLon($geometry)
    {
        $coords = [];
        if (!is_null($geometry)) {
            // g->coordinates == [lon,lat] we needs inverted order
            $g = json_decode(DB::select("SELECT st_asgeojson('$geometry') as g")[0]->g);
            $coords = [$g->coordinates[1], $g->coordinates[0]];
        }
        return $coords;
    }

    public function latLonToGeometry($latlon)
    {
        $lat = $latlon[0];
        $lon = $latlon[1];
        return DB::select("SELECT ST_GeomFromText('POINT($lon $lat)') as g")[0]->g;
    }
}
