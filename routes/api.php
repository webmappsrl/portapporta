<?php

use App\Http\Resources\CentriRaccoltaResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\RifiutarioResource;
use App\Http\Resources\TrashTypeResource;
use App\Http\Resources\UtenzeMetaResource;
use App\Http\Resources\ZoneConfiniResource;
use App\Http\Resources\ZoneMetaResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('c')->name('company')->group(function () {
    Route::get('/{id}/info.json', function ($id) {
        return new CompanyResource(Company::findOrFail($id));
    })->name('info.json');

    // LEGACY Route::get('/{id}/data/utenze_meta.json', function ($id) {
    Route::get('/{id}/user_types.json', function ($id) {
            return new UtenzeMetaResource(Company::findOrFail($id));
    })->name('utenze_meta.json');

    // LEGACY Route::get('/{id}/data/tipi_rifiuto.json', function ($id) {
    Route::get('/{id}/trash_types.json', function ($id) {
            return new TrashTypeResource(Company::findOrFail($id));
    })->name('tipi_rifiuto.json');

    Route::get('/{id}/data/zone_confini.geojson', function ($id) {
        return new ZoneConfiniResource(Company::findOrFail($id));
    })->name('zone_confini.geojson');


    Route::get('/{id}/data/centri_raccolta.geojson', function ($id) {
        return new CentriRaccoltaResource(Company::findOrFail($id));
    })->name('centri_raccolta.geojson');

    Route::get('/{id}/data/rifiutario.json', function ($id) {
        return new RifiutarioResource(Company::findOrFail($id));
    })->name('rifiutario.json');

    Route::get('/{id}/data/zone_meta.json', function ($id) {
        return new ZoneMetaResource(Company::findOrFail($id));
    })->name('zone_meta.json');
    
});


