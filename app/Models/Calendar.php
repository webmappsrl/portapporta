<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;
    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'stop_date' => 'date:Y-m-d',
    ];
        
}
