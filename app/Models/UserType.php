<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;


class UserType extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = [
        'label',
    ];

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function zones(){
        return $this->belongsToMany(Zone::class);
    }

    public function wasteCollectionCenters(){
        return $this->belongsToMany(WasteCollectionCenter::class);
    }
}
