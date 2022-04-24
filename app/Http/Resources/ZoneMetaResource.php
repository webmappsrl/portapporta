<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ZoneMetaResource extends JsonResource
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
        $json = [];

        if(count($this->zones)>0) {
            foreach($this->zones as $z) {
                $item = [
                        'id' => $z->id,
                        'comune' => $z->comune,
                        'label' => $z->label,
                        'url' => $z->url,
                    ];
                if(count($z->userTypes)>0) {
                    $item['types']=$z->userTypes->pluck('slug')->toArray();
                }
                $json[]=$item;
            }
        }
        return $json;
    }
}
