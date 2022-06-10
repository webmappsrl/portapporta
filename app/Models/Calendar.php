<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calendar extends Model
{
    use HasFactory;

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'stop_date' => 'date:Y-m-d',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        if (auth()->check()) {
            static::creating(function ($calendar) {
                $calendar->company_id = auth()->user()->company->id;
            });
        }
    }

    public function calendarItems() {
        return $this->hasMany(CalendarItem::class);
    }

    public function company() {
        return $this->belongsTo(Company::class);
    }

        
}
