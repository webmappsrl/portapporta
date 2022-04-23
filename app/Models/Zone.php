<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Zone extends Model
{
    use HasFactory;

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function userTypes(){
        return $this->belongsToMany(UserType::class);
    }

    // TODO: move to trait
    // TODO: empty case returns '[]'
    public function getGeojsonGeometry() {
        return DB::SELECT("SELECT ST_AsGeoJSON('$this->geometry') as g")[0]->g;
    }
}
