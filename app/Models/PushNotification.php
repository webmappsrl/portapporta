<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    use HasFactory;
    protected $casts = [
        'schedule_date' => 'datetime', // Aggiungi questo per castare il campo come 'datetime'
    ];
    protected $fillable = [
        'created_at',
    ];
}
