<?php

namespace App\Models;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Nova\Auth\Impersonatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable, Impersonatable, HasRoles;

    protected static function booted()
    {
        static::created(function ($user) {
            if ($user->company_id) {
                $user->assignRole('company_admin');
            } else {
                $user->assignRole('contributor');
            }
        });

        static::updated(function ($user) {
            if ($user->company_id) {
                $user->assignRole('company_admin');
            } else {
                $user->assignRole('contributor');
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'zone_id',
        'user_type_id',
        'location',
        'fcm_token',
        'app_company_id',
        'fiscal_code',
        'user_code'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function company()
    {
        return $this->hasOne(Company::class);
    }
    public function companyWhereAdmin()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }


    /**
     * Determine if the user can impersonate another user.
     *
     * @return bool
     */
    public function canImpersonate()
    {
        if (auth()->user()->id == 1) {
            return true;
        }
        return false;
    }

    /**
     * Determine if the user can be impersonated.
     *
     * @return bool
     */
    public function canBeImpersonated()
    {
        if (Company::where('user_id', $this->id)->count()) {
            return true;
        }
        return false;
    }
}
