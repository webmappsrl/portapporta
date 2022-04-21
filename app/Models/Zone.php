<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function userTypes(){
        return $this->belongsToMany(UserType::class);
    }
}
