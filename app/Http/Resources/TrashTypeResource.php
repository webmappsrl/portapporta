<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TrashTypeResource extends JsonResource
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
        if(count($this->trashTypes)>0) {
            foreach($this->trashTypes as $tt) {
                $json[$tt->slug] = [
                    'name' => $tt->getTranslation('name','it'),
                    'description' => $tt->getTranslation('description','it'),
                    'howto' => $tt->getTranslation('howto','it'),
                    'where' => $tt->getTranslation('where','it'),
                    'color' => $tt->color,
                    'allowed' => [],
                    'notallowed' => [],
                    'translations' => []
                ];
            }
        }
        return $json;
    }
}
