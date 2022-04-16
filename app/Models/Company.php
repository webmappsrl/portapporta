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

    /**
     * Create a info.json from the company
     *
     * @return array
     */
    public function createInfoJson($company) {
        $json = [];
        if ($company->configTs)
            $json['configTs'] = Storage::path($company->configTs);
        if ($company->configJson)
            $json['configJson'] = Storage::path($company->configJson);
        if ($company->configXMLID)
            $json['config.xml']['id'] = $company->configXMLID;
        if ($company->description)
            $json['config.xml']['description'] = $company->description;
        if ($company->name)
            $json['config.xml']['name'] = $company->name;
        if ($company->version)
            $json['config.xml']['version'] = $company->version;
        if ($company->icon)
            $json['resources']['icon'] = Storage::path($company->icon);
        if ($company->splash)
            $json['resources']['splash'] = Storage::path($company->splash);
        
        return $json;
    }
}
