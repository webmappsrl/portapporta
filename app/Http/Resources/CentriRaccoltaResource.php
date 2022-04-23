<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CentriRaccoltaResource extends JsonResource
{

    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $features = [];
        if(count($this->wasteCollectionCenters)>0) {
            foreach($this->wasteCollectionCenters as $wcc) {
                $item = [
                    'type' => 'Feature',
                    'properties' => [
                        'marker-color' => $wcc->marker_color,
                        'marker-size' => $wcc->marker_size,
                        'website' => $wcc->website,
                        'picture_url' => $wcc->picture_url,
                        'name' => $wcc->getTranslation('name','it'),
                        'orario' => $wcc->getTranslation('orario','it'),
                        'description' => $wcc->getTranslation('description','it'),
                        'translations' => [ 'en' => [
                            'name' => $wcc->getTranslation('name','en'),
                            'orario' => $wcc->getTranslation('orario','en'),
                            'description' => $wcc->getTranslation('description','en'),    
                        ]]
                     ],
                    'geometry' => json_decode($wcc->getGeojsonGeometry(),true),
                ];
                if(count($wcc->userTypes)>0) {
                    $item['properties']['userTypes']=$wcc->userTypes->pluck('slug')->toArray();
                }
                $features[]=$item;
            }
        }

        $json = [
            'type' => 'FeatureCollection',
            'features' => $features
        ];
        return $json;
    }
}
