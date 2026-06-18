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
        'import_id',
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

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function userTypes()
    {
        return $this->belongsToMany(UserType::class);
    }


    public function users()
    {
        return $this->hasMany(User::class);
    }

    private const SRID_WGS84 = 4326;

    public static function findByPoint(string $geometry, int $companyId): ?self
    {
        $result = DB::selectOne(
            'SELECT id FROM zones WHERE company_id = ? AND geometry IS NOT NULL AND ST_Contains(geometry::geometry, ST_SetSRID(?::geometry, ' . self::SRID_WGS84 . ')) ORDER BY ST_Area(geometry::geometry) ASC LIMIT 1',
            [$companyId, $geometry]
        );

        return $result ? self::find($result->id) : null;
    }
}
