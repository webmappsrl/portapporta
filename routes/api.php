<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TicketController;
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
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);

Route::prefix('c')->name('company.')->middleware('auth:sanctum')->group(function () {

    // Route::get('/{id}/info.json', function ($id) {
    Route::get('/{id}/config.json', function ($id) {
        return new CompanyResource(Company::findOrFail($id));
    })->name('config.json');

    // LEGACY Route::get('/{id}/data/utenze_meta.json', function ($id) {
    Route::get('/{id}/user_types.json', function ($id) {
        return new UtenzeMetaResource(Company::findOrFail($id));
    })->name('user_types.json');

    // LEGACY Route::get('/{id}/data/tipi_rifiuto.json', function ($id) {
    Route::get('/{id}/trash_types.json', function ($id) {
        return new TrashTypeResource(Company::findOrFail($id));
    })->name('trash_types.json');

    // LEGACY Route::get('/{id}/data/rifiutario.json', function ($id) {
    Route::get('/{id}/wastes.json', function ($id) {
        return new RifiutarioResource(Company::findOrFail($id));
    })->name('wastes.json');

    // LEGACY Route::get('/{id}/data/centri_raccolta.geojson', function ($id) {
    Route::get('/{id}/waste_collection_centers.geojson', function ($id) {
        return new CentriRaccoltaResource(Company::findOrFail($id));
    })->name('waste_collection_centers.geojson');

    Route::get('/{id}/data/zone_confini.geojson', function ($id) {
        return new ZoneConfiniResource(Company::findOrFail($id));
    })->name('zone_confini.geojson');

    Route::get('/{id}/data/zone_meta.json', function ($id) {
        return new ZoneMetaResource(Company::findOrFail($id));
    })->name('zone_meta.json');

    // Ticket
    Route::post('/{id}/ticket', [TicketController::class, 'store'])->name('ticket');

});
