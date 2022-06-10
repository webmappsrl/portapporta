<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;


class UserType extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = [
        'label',
    ];

    protected $fillable = [
        'slug',
        'label',
        'company_id',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        if (auth()->check()) {
            static::creating(function ($user_type) {
                $user_type->company_id = auth()->user()->company->id;
            });
        }
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function zones()
    {
        return $this->belongsToMany(Zone::class);
    }

    public function wasteCollectionCenters()
    {
        return $this->belongsToMany(WasteCollectionCenter::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
