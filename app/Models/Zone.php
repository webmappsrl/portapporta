<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Traits\GeojsonableTrait;

class Zone extends Model
{
    use HasFactory;
    use GeojsonableTrait;

    protected $fillable = [
        'comune',
        'company_id',
        'label',
        'url',
        'geometry',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        if (auth()->check()) {
            static::creating(function ($zone) {
                $zone->company_id = auth()->user()->companyWhereAdmin->id;
            });
        }
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function userTypes()
    {
        return $this->belongsToMany(UserType::class);
    }


    public function users()
    {
        return $this->hasMany(User::class);
    }
}
