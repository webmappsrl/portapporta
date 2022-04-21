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
}
