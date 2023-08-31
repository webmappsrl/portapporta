<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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

        if (!empty($this->header_image)) {
            $resources['header_image'] = url(Storage::url($this->header_image));
        }

        if (!empty($this->footer_image)) {
            $resources['footer_image'] = url(Storage::url($this->footer_image));
        }

        if (!empty($this->css_variables)) {
            $resources['variables'] = $this->css_variables;
        }

        if (!empty($this->app_icon)) {
            $resources['app_icon'] = url(Storage::url($this->app_icon));
        }

        if (!empty($this->logo)) {
            $resources['logo'] = url(Storage::url($this->logo));
        }

        if (!empty($this->push_notification_plist_url)) {
            $resources['push_notification_plist_url'] = url(Storage::url($this->push_notification_plist_url));
        }

        if (!empty($this->push_notification_json_url)) {
            $resources['push_notification_json_url'] = url(Storage::url($this->push_notification_json_url));
        }
        if (!empty($this->min_zoom)) {
            $resources['min_zoom'] = intval($this->min_zoom);
        }
        if (!empty($this->max_zoom)) {
            $resources['max_zoom'] = intval($this->max_zoom);
        }
        if (!empty($this->default_zoom)) {
            $resources['default_zoom'] = intval($this->default_zoom);
        }
        if (!empty($this->location)) {
            $g = json_decode(DB::select("SELECT st_asgeojson('$this->location') as g")[0]->g);
            $resources['location'] = [$g->coordinates[1], $g->coordinates[0]];
        }
        if (!empty($this->company_page)) {
            $resources['company_page'] = $this->company_page;
        }

        $json['resources'] = $resources;

        return $json;
    }
}
