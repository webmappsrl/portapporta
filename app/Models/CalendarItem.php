<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarItem extends Model
{
    use HasFactory;

    public function calendar() {
        return $this->belongsTo(Calendar::class);
    }

    public function trashTypes(){
        return $this->belongsToMany(TrashType::class);
    }


}
