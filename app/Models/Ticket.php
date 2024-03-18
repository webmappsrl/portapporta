<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_read', 'status'
    ];

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
