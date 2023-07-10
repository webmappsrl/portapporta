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

    protected $fillable = [
        'name',
        'zone_id',
        'user_type_id',
        'start_date',
        'stop_date',
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

    public function calendarItems()
    {
        return $this->hasMany(CalendarItem::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function userType()
    {
        return $this->belongsTo(UserType::class);
    }
}
