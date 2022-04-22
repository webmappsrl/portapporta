<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UtenzeMetaResource extends JsonResource
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
        if(count($this->userTypes)>0) {
            foreach($this->userTypes as $ut) {
                $json[$ut->slug]=[
                    'locale' => 'it',
                    'label' => $ut->getTranslation('label','it'),
                    'translations' => [ 'en' => ['label'=>$ut->getTranslation('label','en')]],
                ];
            }
        }
        return $json;
    }
}
