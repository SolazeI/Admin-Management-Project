<?php

use Illuminate\Support\Facades\Route;

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

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriverController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('login');
});

Route::post('/login', [AuthController::class, 'authenticate']);
Route::get('/admin', [DriverController::class, 'index'])->name('admin.dashboard');

// Driver Management Routes
Route::prefix('drivers')->group(function () {
    Route::get('/archived', [DriverController::class, 'archived']);
    Route::get('/search', [DriverController::class, 'search']);
    Route::post('/', [DriverController::class, 'store']);
    Route::get('/{id}', [DriverController::class, 'show']);
    Route::post('/{id}', [DriverController::class, 'update']); // Using POST for update with _method=PUT
    Route::post('/{id}/archive', [DriverController::class, 'archive']);
    Route::post('/{id}/unarchive', [DriverController::class, 'unarchive']);
});