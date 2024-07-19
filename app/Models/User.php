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

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // static::deleting(function ($user) {
        //     $company = Company::where('user_id', $user->id)->first();
        //     if ($company) {
        //         $company->update(['user_id' => null]);
        //     }
        // });
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
        'user_code',
        'admin_company_id',
        'form_data'
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

    public function companyWhereAdmin()
    {
        return $this->belongsTo(Company::class, 'admin_company_id');
    }
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class, 'app_company_id');
    }
    public function getFormDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setFormDataAttribute($value)
    {
        $this->attributes['form_data'] = json_encode($value);
    }

    /**
     * Determine if the user can impersonate another user.
     *
     * @return bool
     */
    public function canImpersonate()
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Determine if the user can be impersonated.
     *
     * @return bool
     */
    public function canBeImpersonated()
    {
        return $this->hasRole('company_admin');
    }
}
