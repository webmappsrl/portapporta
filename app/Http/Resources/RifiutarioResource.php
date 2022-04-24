<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class RifiutarioResource extends JsonResource
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
        if(count($this->wastes)>0){
            foreach($this->wastes as $w) {
                $item = [
                    'name' => $w->getTranslation('name','it'),
                    'where' => $w->getTranslation('where','it'),
                    'notes' => $w->getTranslation('notes','it'),
                    'pap' => $w->pap,
                    'delivery' => $w->delivery,
                    'collection_center' => $w->collection_center,
                    'translations' => [
                        'en' => [
                            'name' => $w->getTranslation('name','en'),
                            'where' => $w->getTranslation('where','en'),
                            'notes' => $w->getTranslation('notes','en'),        
                        ],
                    ],
                ];
                if(isset($w->trash_type_id)) {
                    $item['category']=$w->trashType->slug;
                }
                $json[]=$item;
            }
        }
        return $json;
    }
}
