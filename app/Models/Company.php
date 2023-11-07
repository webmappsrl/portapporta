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

    public function companyAdmins()
    {
        return $this->hasMany(User::class)->whereHas('roles', function ($query) {
            $query->where('name', 'company_admin');
        });
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

    /**
     * update the media collections for the model
     * @return void
     */

    public function updateMediaCollections(): void
    {
        $content = $this->company_page;
        $imgUrls = [];

        //if an img tag exists in the content field get the src attribute
        if (preg_match_all('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $content, $images)) {
            $imgUrls = $images['src'];

            foreach ($imgUrls as $imgUrl) {
                $this->addMediaFromUrl($imgUrl)->toMediaCollection('content-images');
            }
        }
        //else if no img tag exists in the content field delete the media from the content-images collection
        else {
            if (count($this->getMedia('content-images')) > 0) {
                $this->clearMediaCollection('content-images');
            }
        }
    }
}
