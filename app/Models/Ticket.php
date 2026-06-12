<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => TicketStatus::class,
    ];

    protected $fillable = [
        'is_read', 'status', 'zone_id'
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trashType()
    {
        return $this->belongsTo(TrashType::class);
    }
    public function address()
    {
        return $this->belongsTo(Address::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function resolvePhone(): ?string
    {
        if (filled($this->phone)) {
            return $this->phone;
        }
        return $this->user?->phone_number ?? null;
    }

    public function isLunigianaZone(): bool
    {
        return $this->zone_id !== null && in_array($this->zone_id, config('lunigiana.zones', []));
    }

    /**
     * Determine if the current user can update the resource.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return bool
     */
    public function authorizedToUpdate($request)
    {
        return false;
    }
}
