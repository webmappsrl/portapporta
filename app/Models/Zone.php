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
    ];
    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function userTypes(){
        return $this->belongsToMany(UserType::class);
    }

}
