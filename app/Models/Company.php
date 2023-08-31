<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * creates a sha1 from the uploaded file name with the original file extension
     *
     * @param [type] $file
     * @return string
     */
    public function get_file_name_extension($file)
    {
        return sha1($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
    }

    public function trashTypes()
    {
        return $this->hasMany(TrashType::class);
    }

    public function wastes()
    {
        return $this->hasMany(Waste::class);
    }

    public function wasteCollectionCenters()
    {
        return $this->hasMany(WasteCollectionCenter::class);
    }

    public function userTypes()
    {
        return $this->hasMany(UserType::class);
    }

    public function zones()
    {
        return $this->hasMany(Zone::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function calendars()
    {
        return $this->hasMany(Calendar::class);
    }

    /**
     * Registering media collections for spaties media library
     * @return void
     *
     * @see https://spatie.be/docs/laravel-medialibrary/v10/working-with-media-collections/defining-media-collections
     */
    public function registerMediaCollections(): void
    {
        $acceptedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg'];

        $this->addMediaCollection('content-images')
            ->acceptsMimeTypes($acceptedMimeTypes);
    }
}
