<?php

use Illuminate\Support\Facades\Route;
use App\Jobs\TestJob;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('phpmyinfo', function () {
    phpinfo();
})->name('phpmyinfo');
Route::get('/test-horizon', function () {
    TestJob::dispatch();
    return 'Test job dispatched';
});
Route::get('/logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index'])->middleware('auth');
Route::get('/download-export/{fileName}', [\App\Http\Controllers\ExportDownloadController::class, 'download'])
    ->name('download.export')
    ->middleware('signed');
