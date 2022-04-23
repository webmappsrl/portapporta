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
        // if(count($this->zones)>0) {
        //     foreach($this->zones as $z) {
        //         $item = [
        //             'type' => 'Feature',
        //             'properties' => [
        //                 'id' => $z->id,
        //                 'COMUNE' => $z->comune
        //             ],
        //             'geometry' => json_decode($z->getGeojsonGeometry(),true),
        //             ];
        //         $features[]=$item;

        //     }
        // }

        $json = [
            'type' => 'FeatureCollection',
            'features' => $features
        ];
        return $json;
    }
}
