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
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\MaintenanceRecordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TripTicketController;
use App\Http\Controllers\TruckController;

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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
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

    // Fleet Management
    Route::get('/fleet', [TruckController::class, 'index'])->name('fleet.index');
    Route::post('/fleet', [TruckController::class, 'store'])->name('fleet.store');
    Route::post('/fleet/{truck}', [TruckController::class, 'update'])->name('fleet.update');
    Route::post('/fleet/{truck}/delete', [TruckController::class, 'destroy'])->name('fleet.destroy');

    // Trip Tickets
    Route::get('/trips', [TripTicketController::class, 'index'])->name('trips.index');
    Route::post('/trips', [TripTicketController::class, 'store'])->name('trips.store');
    Route::post('/trips/{trip}', [TripTicketController::class, 'update'])->name('trips.update');
    Route::post('/trips/{trip}/delete', [TripTicketController::class, 'destroy'])->name('trips.destroy');

    // Maintenance
    Route::get('/maintenance', [MaintenanceRecordController::class, 'index'])->name('maintenance.index');
    Route::post('/maintenance', [MaintenanceRecordController::class, 'store'])->name('maintenance.store');
    Route::post('/maintenance/{record}', [MaintenanceRecordController::class, 'update'])->name('maintenance.update');
    Route::post('/maintenance/{record}/delete', [MaintenanceRecordController::class, 'destroy'])->name('maintenance.destroy');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});