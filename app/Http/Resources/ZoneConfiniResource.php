<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ZoneConfiniResource extends JsonResource
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

        if (count($this->zones) > 0) {
            foreach ($this->zones as $z) {
                $item = [
                    'type' => 'Feature',
                    'properties' => [
                        'id' => $z->id,
                        'comune' => $z->comune,
                        'label' => $z->label,
                        'url' => $z->url,
                    ],
                    'geometry' => json_decode($z->getGeojsonGeometry(), true),
                ];

                if (count($z->userTypes) > 0) {
                    $item['properties']['types'] = $z->userTypes->pluck('id')->toArray();
                    $avalaibleUserTypes = $z->userTypes->map(function ($userType) {
                        unset($userType['created_at']);
                        unset($userType['updated_at']);
                        unset($userType['company_id']);
                        unset($userType['slug']);
                        unset($userType['pivot']);
                        return $userType;
                    });
                    $item['properties']['availableUserTypes'] = $avalaibleUserTypes;
                }
                $features[] = $item;
            }
        }

        $json = [
            'type' => 'FeatureCollection',
            'name' => 'zones',
            'features' => $features
        ];
        return $json;
    }
}
