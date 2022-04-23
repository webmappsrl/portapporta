<?php
namespace App\Traits;

trait GeojsonableTrait {
    public function getGeojsonGeometry() {
        return '[]';
    }
}