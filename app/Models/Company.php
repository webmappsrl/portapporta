<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    public function get_file_name_extension($file) {
        return sha1($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
    }
}
