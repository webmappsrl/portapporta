<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory;

    /**
     * creates a sha1 from the uploaded file name with the original file extension
     *
     * @param [type] $file
     * @return string
     */
    public function get_file_name_extension($file) {
        return sha1($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
    }

    public function trashTypes(){
        return $this->hasMany(TrashType::class);
    }
    
    public function wastes(){
        return $this->hasMany(Waste::class);
    }
    
    public function wasteCollectionCenters(){
        return $this->hasMany(WasteCollectionCenter::class);
    }
    
    public function userTypes(){
        return $this->hasMany(UserType::class);
    }
    
    public function zones(){
        return $this->hasMany(Zone::class);
    }
    
    public function user(){
        return $this->belongsTo(User::class);
    }
}
