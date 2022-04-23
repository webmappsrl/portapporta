<?php
namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait GeojsonableTrait {
    public function getGeojsonGeometry() {
        if(is_null($this->geometry)) {
            return '[]';
        }
        if(empty($this->geometry)) {
            return '[]';
        }
        return DB::SELECT("SELECT ST_AsGeoJSON('$this->geometry') as g")[0]->g;
    }
}