<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteCollectionCenter extends Model
{
    use HasFactory;

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function userTypes(){
        return $this->belongsToMany(UserType::class);
    }
    
    public function trashTypes(){
        return $this->belongsToMany(TrashType::class);
    }
}
