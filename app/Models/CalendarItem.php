<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarItem extends Model
{

    protected $fillable = [
        'calendar_id',
        'start_date',
        'stop_date',
        'start_time',
        'stop_time',
        'trash_type_id',
        'company_id',
        'day_of_week',
        'frequency',
    ];
    use HasFactory;

    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }

    public function trashTypes()
    {
        return $this->belongsToMany(TrashType::class);
    }
}
