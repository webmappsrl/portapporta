<?php

namespace App\Models;

use App\Traits\GeojsonableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class WasteCollectionCenter extends Model
{
    use HasFactory;
    use GeojsonableTrait;
    use HasTranslations;

    public $translatable = [
        'name',
        'description',
        'orario'
    ];

    protected $fillable = [
        'name',
        'description',
        'orario',
        'company_id',
        'marker_color',
        'marker_size',
        'website',
        'picture_url',
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
            static::creating(function ($collection_center) {
                $collection_center->company_id = auth()->user()->companyWhereAdmin->id;
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

    public function trashTypes()
    {
        return $this->belongsToMany(TrashType::class);
    }
}
