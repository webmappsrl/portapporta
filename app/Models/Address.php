<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'zone_id',
        'user_type_id',
        'location',
        'city',
        'address',
        'house_number'
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
