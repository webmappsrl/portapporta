<?php

use App\Http\Controllers\CompaniesController;
use App\Http\Controllers\CompanyController;
use App\Http\Resources\CompanyResource;
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
    Route::get('/{id}/data/utenze_meta.json', function ($id) {
        return new CompanyResource(Company::findOrFail($id));
    })->name('utenze_meta.json');
});


