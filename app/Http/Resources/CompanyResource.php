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
        $json = [
            'id' => $this->id,
            'name' => $this->name,
        ];

        if (!empty($this->sku)) {
            $json['sku'] = $this->sku;
        }

        $resources = [];

        if ($this->icon) {
            $resources['icon'] = url(Storage::url($this->icon));
        }

        if ($this->splash) {
            $resources['splash'] = url(Storage::url($this->splash));
        }

        if (!empty($this->font)) {
            $resources['font'] = $this->font;
        }

        if (!empty($this->header)) {
            $resources['header'] = $this->header;
        }

        if (!empty($this->footer)) {
            $resources['footer'] = $this->footer;
        }

        if (!empty($this->css_variables)) {
            $resources['variables'] = $this->css_variables;
        }

        $json['resources'] = $resources;

        return $json;
    }
}
