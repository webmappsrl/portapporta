<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PushNotificationController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UpdateUserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\CentriRaccoltaResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\RifiutarioResource;
use App\Http\Resources\TrashTypeResource;
use App\Http\Resources\UtenzeMetaResource;
use App\Http\Resources\ZoneConfiniResource;
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


Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);
Route::patch('/fcm-token', [LoginController::class, 'updateToken'])->name('fcmToken');
// NO MIDDLEWARES
Route::prefix('c')->name('company.')->group(function () {
    Route::get('/{id}/wastes.json', function ($id) {
        return new RifiutarioResource(Company::findOrFail($id));
    })->name('wastes.json');
    Route::get('/{id}/user_types.json', function ($id) {
        return new UtenzeMetaResource(Company::findOrFail($id));
    })->name('user_types.json');
    Route::get('/{id}/trash_types.json', function ($id) {
        return new TrashTypeResource(Company::findOrFail($id));
    })->name('trash_types.json');
    Route::get('/{id}/zones.geojson', function ($id) {
        return new ZoneConfiniResource(Company::findOrFail($id));
    })->name('zones.geojson');
    Route::get('/{id}/waste_collection_centers.geojson', function ($id) {
        return new CentriRaccoltaResource(Company::findOrFail($id));
    })->name('waste_collection_centers.geojson');
    Route::get('/{id}/company_page', function ($id) {
        $company = Company::findOrFail($id);
        return response($company->company_page, 200)
            ->header('Content-Type', 'text/plain');
    });
});

// AUTH
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    $user = $request->user();
    if ($user->location != null) {
        $geometry = $user->location;
        $g = json_decode(DB::select("SELECT st_asgeojson('$geometry') as g")[0]->g);
        $user->location = [$g->coordinates[1], $g->coordinates[0]];
    }

    return $user;
});

Route::middleware('auth:sanctum')->post('/user', [UpdateUserController::class, 'update'])->name('update');

Route::middleware('auth:sanctum')->get('/delete', [UpdateUserController::class, 'delete'])->name('delete');

// AUTH AND SIGNED WITH COMPANY
Route::prefix('c')->name('company.')->middleware('auth:sanctum', 'verified')->group(function () {
    Route::get('/{id}/config.json', function ($id) {
        return new CompanyResource(Company::findOrFail($id));
    })->name('config.json');
    Route::post('/{id}/ticket', [TicketController::class, 'store'])->name('ticket');
    Route::get('/{id}/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/{id}/tickets', [TicketController::class, 'index'])->name('ticket.list');
});

Route::get('email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
Route::get('email/verify/{id}', [VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');

Route::prefix('v1')->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::patch('/fcm-token', [LoginController::class, 'updateToken']);
    // NO MIDDLEWARES
    Route::prefix('c')->name('company.')->group(function () {
        Route::get('/{id}/wastes.json', function ($id) {
            return new RifiutarioResource(Company::findOrFail($id));
        });
        Route::get('/{id}/user_types.json', function ($id) {
            return new UtenzeMetaResource(Company::findOrFail($id));
        });
        Route::get('/{id}/trash_types.json', function ($id) {
            return new TrashTypeResource(Company::findOrFail($id));
        });
        Route::get('/{id}/zones.geojson', function ($id) {
            return new ZoneConfiniResource(Company::findOrFail($id));
        });
        Route::get('/{id}/waste_collection_centers.geojson', function ($id) {
            return new CentriRaccoltaResource(Company::findOrFail($id));
        });
    });

    // AUTH
    Route::middleware('auth:sanctum')->get('/user', [UpdateUserController::class, 'get']);

    Route::middleware('auth:sanctum')->post('/user', [UpdateUserController::class, 'v1Update']);

    Route::middleware('auth:sanctum')->get('/delete', [UpdateUserController::class, 'delete']);

    // AUTH AND SIGNED WITH COMPANY
    Route::prefix('c')->name('company.')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::get('/{id}/config.json', function ($id) {
            return new CompanyResource(Company::findOrFail($id));
        });
        Route::post('/{id}/ticket', [TicketController::class, 'v1store']);
        Route::get('/{id}/calendar', [CalendarController::class, 'v1index']);
        Route::get('/{id}/tickets', [TicketController::class, 'index']);
        Route::get('/{id}/pushnotification', [PushNotificationController::class, 'v1index']);
    });
    Route::patch('/ticket/{ticket}', [TicketController::class, 'v1update']);
    Route::middleware('auth:sanctum')->get('/address/delete/{id}', [AddressController::class, 'destroy']);
    Route::middleware('auth:sanctum')->post('/address/update', [AddressController::class, 'update']);
    Route::middleware('auth:sanctum')->post('/address/create', [AddressController::class, 'create']);
    Route::middleware('auth:sanctum')->get('/address/index', [AddressController::class, 'index']);
    Route::get('email/resend', [VerificationController::class, 'resend']);
    Route::get('email/verify/{id}', [VerificationController::class, 'verify'])->middleware('signed');
});

Route::prefix('v2')->group(function () {
    Route::post('/register', [RegisterController::class, 'v2register']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::patch('/fcm-token', [LoginController::class, 'updateToken']);
    // NO MIDDLEWARES
    Route::prefix('c')->name('company.')->group(function () {
        Route::get('/{id}/wastes.json', function ($id) {
            return new RifiutarioResource(Company::findOrFail($id));
        });
        Route::get('/{id}/user_types.json', function ($id) {
            return new UtenzeMetaResource(Company::findOrFail($id));
        });
        Route::get('/{id}/trash_types.json', function ($id) {
            return new TrashTypeResource(Company::findOrFail($id));
        });
        Route::get('/{id}/zones.geojson', function ($id) {
            return new ZoneConfiniResource(Company::findOrFail($id));
        });
        Route::get('/{id}/waste_collection_centers.geojson', function ($id) {
            return new CentriRaccoltaResource(Company::findOrFail($id));
        });
        Route::get('/{id}/form_json', [CompanyController::class, 'formJson']);
        Route::get('/{id}/calendar/z/{zone_id}', [CalendarController::class, 'v1indexByZone']);
    });

    // AUTH
    Route::middleware('auth:sanctum')->get('/user', [UpdateUserController::class, 'get']);

    Route::middleware('auth:sanctum')->post('/user', [UpdateUserController::class, 'v2Update']);

    Route::middleware('auth:sanctum')->get('/delete', [UpdateUserController::class, 'delete']);

    // AUTH AND SIGNED WITH COMPANY
    Route::prefix('c')->name('company.')->middleware('auth:sanctum', 'verified')->group(function () {
        Route::get('/{id}/config.json', function ($id) {
            return new CompanyResource(Company::findOrFail($id));
        });
        Route::post('/{id}/ticket', [TicketController::class, 'v1store']);
        Route::get('/{id}/calendar', [CalendarController::class, 'v1index']);
        Route::get('/{id}/tickets', [TicketController::class, 'index']);
        Route::get('/{id}/pushnotification', [PushNotificationController::class, 'v1index']);
    });
    Route::patch('/ticket/{ticket}', [TicketController::class, 'v1update'])->name('ticket.v1update');
    Route::middleware('auth:sanctum')->get('/address/delete/{id}', [AddressController::class, 'destroy']);
    Route::middleware('auth:sanctum')->post('/address/update', [AddressController::class, 'update']);
    Route::middleware('auth:sanctum')->post('/address/create', [AddressController::class, 'create']);
    Route::middleware('auth:sanctum')->get('/address/index', [AddressController::class, 'index']);
    Route::get('email/resend', [VerificationController::class, 'resend']);
    Route::get('email/verify/{id}', [VerificationController::class, 'verify'])->middleware('signed')->name('verificationV2.verify');
});
