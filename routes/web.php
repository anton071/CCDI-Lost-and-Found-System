<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// ---- Public -------------------------------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/items', [ItemController::class, 'index'])->name('items.index');
Route::get('/reports/{report}', [ReportController::class, 'show'])->whereNumber('report')->name('reports.show');
Route::get('/images/{image}', [ImageController::class, 'show'])->whereNumber('image')->name('images.show');

// ---- Guests -------------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');
});

// ---- Signed-in members --------------------------------------------------------------------
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/reports/new/{type}', [ReportController::class, 'create'])->whereIn('type', ['lost', 'found'])->name('reports.create');
    Route::post('/reports/new/{type}', [ReportController::class, 'store'])->whereIn('type', ['lost', 'found'])->middleware('throttle:submissions')->name('reports.store');
    Route::get('/my/reports', [ReportController::class, 'mine'])->name('reports.mine');

    Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
    Route::post('/reports/{report}/claims', [ClaimController::class, 'store'])->whereNumber('report')->middleware('throttle:claims')->name('claims.store');
    Route::get('/claims/{claim}', [ClaimController::class, 'show'])->whereNumber('claim')->name('claims.show');
    Route::post('/claims/{claim}/cancel', [ClaimController::class, 'cancel'])->whereNumber('claim')->name('claims.cancel');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
});

// ---- Administration (server-side role check) ------------------------------------------------
Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [Admin\ReportController::class, 'show'])->whereNumber('report')->name('reports.show');
    Route::post('/reports/{report}/status', [Admin\ReportController::class, 'status'])->whereNumber('report')->name('reports.status');
    Route::post('/reports/{report}/rematch', [Admin\ReportController::class, 'rematch'])->whereNumber('report')->name('reports.rematch');

    Route::get('/matches', [Admin\MatchController::class, 'index'])->name('matches.index');
    Route::post('/matches/{match}', [Admin\MatchController::class, 'update'])->whereNumber('match')->name('matches.update');

    Route::get('/claims', [Admin\ClaimController::class, 'index'])->name('claims.index');
    Route::get('/claims/{claim}', [Admin\ClaimController::class, 'show'])->whereNumber('claim')->name('claims.show');
    Route::post('/claims/{claim}/under-review', [Admin\ClaimController::class, 'underReview'])->whereNumber('claim')->name('claims.under-review');
    Route::post('/claims/{claim}/approve', [Admin\ClaimController::class, 'approve'])->whereNumber('claim')->name('claims.approve');
    Route::post('/claims/{claim}/reject', [Admin\ClaimController::class, 'reject'])->whereNumber('claim')->name('claims.reject');
    Route::post('/claims/{claim}/release', [Admin\ClaimController::class, 'release'])->whereNumber('claim')->name('claims.release');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}', [Admin\UserController::class, 'update'])->whereNumber('user')->name('users.update');

    Route::get('/activity', [Admin\ActivityController::class, 'index'])->name('activity.index');
});
