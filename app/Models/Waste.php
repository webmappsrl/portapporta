<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Waste extends ExportableModel
{
    use HasFactory;
    use HasTranslations;


    public $translatable = [
        'name',
        'where',
        'notes'
    ];

    protected $fillable = [
        'name',
        'where',
        'notes',
        'company_id',
        'trash_type_id',
        'pap',
        'delivery',
        'collection_center',
    ];

    protected $casts = [
        'pap' => 'boolean',
        'delivery' => 'boolean',
        'collection_center' => 'boolean',
    ];
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        if (auth()->check()) {
            static::creating(function ($waste) {
                $waste->company_id = auth()->user()->companyWhereAdmin->id;
            });
        }
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function trashType()
    {
        return $this->belongsTo(TrashType::class);
    }
}
