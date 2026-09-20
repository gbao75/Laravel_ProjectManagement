<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

//Profile
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

//Workspace
Route::middleware([
    'auth',
    'verified',
    'workspace.context',
])->group(function () {
    Route::get('/workspaces', [WorkspaceController::class, 'index'])
        ->name('workspaces.index');

    Route::get('/workspaces/create', [WorkspaceController::class, 'create'])
        ->name('workspaces.create');

    Route::post('/workspaces', [WorkspaceController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('workspaces.store');

    Route::post('/workspaces/switch', [WorkspaceController::class, 'switchWorkspace'])
        ->name('workspaces.switch');

    Route::get('/workspaces/{workspace}/edit', [WorkspaceController::class, 'edit'])
        ->name('workspaces.edit');

    Route::put('/workspaces/{workspace}', [WorkspaceController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('workspaces.update');

    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy'])
        ->middleware('throttle:5,1')
        ->name('workspaces.destroy');

    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show'])
        ->name('workspaces.show');
    
        //Invitation
    Route::get(
        '/workspaces/{workspace}/invitations/create',
        [WorkspaceInvitationController::class, 'create']
    )->name('workspaces.invitations.create');

    Route::post(
        '/workspaces/{workspace}/invitations',
        [WorkspaceInvitationController::class, 'store']
    )
        ->middleware('throttle:5,1')
        ->name('workspaces.invitations.store');
});

Route::get(
    '/workspace-invitations/{token}',
    [WorkspaceInvitationController::class, 'show']
)
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:30,1')
    ->name('workspace-invitations.show');