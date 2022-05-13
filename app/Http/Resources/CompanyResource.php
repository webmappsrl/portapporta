<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CompanyResource extends JsonResource
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
        $json['id'] = $this->id;
        $json['name'] = $this->name;
        if ($this->icon)
            $json['resources']['icon'] = url(Storage::url($this->icon));
        if ($this->splash)
            $json['resources']['splash'] = url(Storage::url($this->splash));
        
        return $json;
    }
}
