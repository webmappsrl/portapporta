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
        if ($this->configTs)
            $json['configTs'] = url(Storage::url($this->configTs));
        if ($this->configJson)
            $json['configJson'] = url(Storage::url($this->configJson));
        if ($this->configXMLID)
            $json['config.xml']['id'] = $this->configXMLID;
        if ($this->description)
            $json['config.xml']['description'] = $this->description;
        if ($this->name)
            $json['config.xml']['name'] = $this->name;
        if ($this->version)
            $json['config.xml']['version'] = $this->version;
        if ($this->icon)
            $json['resources']['icon'] = url(Storage::url($this->icon));
        if ($this->splash)
            $json['resources']['splash'] = url(Storage::url($this->splash));
        
        return $json;
    }
}
