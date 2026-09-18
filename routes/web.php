<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::put('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('profile.update');

    Route::put('/profile/password', [
        ProfileController::class,
        'updatePassword',
    ])
        ->middleware('throttle:5,1')
        ->name('profile.password.update');

    Route::post('/profile/avatar', [
        ProfileController::class,
        'updateAvatar',
    ])
        ->middleware('throttle:10,1')
        ->name('profile.avatar.update');
});