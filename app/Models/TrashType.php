<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TrashType extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = [
        'name',
        'where',
        'howto',
        'allowed',
        'notallowed'
    ];

    protected $casts = [
        'allowed' => 'array',
        'notallowed' => 'array',
    ];

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function wastes(){
        return $this->hasMany(Waste::class);
    }

    public function wasteCollectionCenters(){
        return $this->belongsToMany(WasteCollectionCenter::class);
    }
}
