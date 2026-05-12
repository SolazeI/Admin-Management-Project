<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\MaintenanceRecordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TripTicketController;
use App\Http\Controllers\TruckController;

Route::redirect('/', '/admin/login');
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

    Route::prefix('drivers')->group(function () {
        Route::get('/archived', [DriverController::class, 'archived']);
        Route::get('/search', [DriverController::class, 'search']);
        Route::get('/filter-status', [DriverController::class, 'filterByStatus']);
        Route::post('/', [DriverController::class, 'store']);
        Route::get('/{id}', [DriverController::class, 'show']);
        Route::post('/{id}', [DriverController::class, 'update']);
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
    Route::post('/trips/{trip}/transition', [TripTicketController::class, 'transition'])->name('trips.transition');
    Route::post('/trips/{trip}', [TripTicketController::class, 'update'])->name('trips.update');
    Route::post('/trips/{trip}/delete', [TripTicketController::class, 'destroy'])->name('trips.destroy');

    // Maintenance
    Route::get('/maintenance', [MaintenanceRecordController::class, 'index'])->name('maintenance.index');
    Route::post('/maintenance', [MaintenanceRecordController::class, 'store'])->name('maintenance.store');
    Route::post('/maintenance/{record}/transition', [MaintenanceRecordController::class, 'transition'])->name('maintenance.transition');
    Route::post('/maintenance/{record}', [MaintenanceRecordController::class, 'update'])->name('maintenance.update');
    Route::post('/maintenance/{record}/delete', [MaintenanceRecordController::class, 'destroy'])->name('maintenance.destroy');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/compile', [ReportController::class, 'compile'])->name('reports.compile');
    Route::get('/reports/{compilation}/download', [ReportController::class, 'download'])->name('reports.download');
});