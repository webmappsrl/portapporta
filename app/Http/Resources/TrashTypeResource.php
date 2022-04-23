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
                    'allowed' => $tt->getTranslation('allowed','it'),
                    'notallowed' => $tt->getTranslation('notallowed','it'),
                    'translations' => [ 'en' => [
                        'name' => $tt->getTranslation('name','en'),
                        'description' => $tt->getTranslation('description','en'),
                        'howto' => $tt->getTranslation('howto','en'),
                        'where' => $tt->getTranslation('where','en'),
                        'allowed' => $tt->getTranslation('allowed','en'),
                        'notallowed' => $tt->getTranslation('notallowed','en'),
                        ]],
                ];
            }
        }
        return $json;
    }
}
