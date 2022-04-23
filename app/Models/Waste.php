<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Waste extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = [
        'name',
        'where',
        'notes'
    ];

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function trashType(){
        return $this->belongsTo(TrashType::class);
    }
}
