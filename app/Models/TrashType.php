<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TrashType extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = [
        'name',
        'description',
        'where',
        'howto',
        'allowed',
        'notallowed'
    ];
    protected $fillable = [
        'slug',
        'company_id',
        'name',
        'description',
        'where',
        'howto',
        'allowed',
        'notallowed',
        'color'
    ];

    protected $casts = [
        'allowed' => 'json',
        'notallowed' => 'json',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        if (auth()->check()) {
            static::creating(function ($trash_type) {
                $trash_type->company_id = auth()->user()->companyWhereAdmin->id;
            });
        }
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function wastes()
    {
        return $this->hasMany(Waste::class);
    }

    public function wasteCollectionCenters()
    {
        return $this->belongsToMany(WasteCollectionCenter::class);
    }
}
