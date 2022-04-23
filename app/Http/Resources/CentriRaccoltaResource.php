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
                        'id' => $wcc->id,
                    ],
//                    'geometry' => json_decode($z->getGeojsonGeometry(),true),
                    'geometry' => '{}'
                    ];
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
