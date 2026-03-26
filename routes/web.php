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

Route::redirect('/', '/admin/login');

// Backward-compatible login URL (redirects to admin login)
Route::redirect('/login', '/admin/login');

// Admin login
Route::get('/admin/login', function () {
    return view('login');
})->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'authenticate'])->name('admin.login.submit');

Route::middleware('admin')->group(function () {
    Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');
    Route::get('/admin', [DriverController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/password', [AuthController::class, 'showChangePassword'])->name('admin.password');
    Route::post('/admin/password', [AuthController::class, 'changePassword'])->name('admin.password.update');

    // Driver Management Routes
    Route::prefix('drivers')->group(function () {
        Route::get('/archived', [DriverController::class, 'archived']);
        Route::get('/search', [DriverController::class, 'search']);
        Route::post('/', [DriverController::class, 'store']);
        Route::get('/{id}', [DriverController::class, 'show']);
        Route::post('/{id}', [DriverController::class, 'update']); // Using POST for update
        Route::post('/{id}/archive', [DriverController::class, 'archive']);
        Route::post('/{id}/unarchive', [DriverController::class, 'unarchive']);
    });
});