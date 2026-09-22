<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\MyWorkspaceInvitationController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\TaskController;

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
    
    
    //Member
    Route::get(
        '/workspaces/{workspace}/members',
        [WorkspaceMemberController::class, 'index']
    )->name('workspaces.members.index');

    Route::put(
        '/workspaces/{workspace}/members/{member}',
        [WorkspaceMemberController::class, 'update']
    )
        ->whereNumber('member')
        ->middleware('throttle:20,1')
        ->name('workspaces.members.update');

    Route::delete(
        '/workspaces/{workspace}/members/{member}',
        [WorkspaceMemberController::class, 'destroy']
    )
        ->whereNumber('member')
        ->middleware('throttle:10,1')
        ->name('workspaces.members.destroy');
});

Route::get(
    '/workspace-invitations/{token}',
    [WorkspaceInvitationController::class, 'show']
)
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:30,1')
    ->name('workspace-invitations.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get(
        '/workspace-invitations/{token}/continue',
        [WorkspaceInvitationController::class, 'continueInvitation']
    )
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('workspace-invitations.continue');

    Route::post(
        '/workspace-invitations/{token}/accept',
        [WorkspaceInvitationController::class, 'accept']
    )
        ->where('token', '[A-Za-z0-9]{64}')
        ->middleware('throttle:10,1')
        ->name('workspace-invitations.accept');
});

//MyInvivation
Route::middleware(['auth', 'verified', 'workspace.context'])
    ->group(function () {
        Route::get(
            '/my-invitations',
            [MyWorkspaceInvitationController::class, 'index']
        )->name('my-invitations.index');

        Route::post(
            '/my-invitations/{invitation}/accept',
            [MyWorkspaceInvitationController::class, 'accept']
        )
            ->whereNumber('invitation')
            ->middleware('throttle:10,1')
            ->name('my-invitations.accept');
    });

//Project
Route::middleware(['auth', 'verified', 'workspace.context'])
    ->prefix('workspaces/{workspace}/projects')
    ->name('workspaces.projects.')
    ->scopeBindings()
    ->group(function () {
        Route::get('/', [ProjectController::class, 'index'])
            ->name('index');

        Route::get('/create', [ProjectController::class, 'create'])
            ->name('create');

        Route::post('/', [ProjectController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('store');

        Route::get('/{project}/edit', [ProjectController::class, 'edit'])
            ->whereNumber('project')
            ->name('edit');

        Route::put('/{project}', [ProjectController::class, 'update'])
            ->whereNumber('project')
            ->middleware('throttle:20,1')
            ->name('update');

        Route::delete('/{project}', [ProjectController::class, 'destroy'])
            ->whereNumber('project')
            ->middleware('throttle:10,1')
            ->name('destroy');

        Route::get('/{project}', [ProjectController::class, 'show'])
            ->whereNumber('project')
            ->name('show');
    });

Route::middleware(['auth', 'verified', 'workspace.context'])
    ->prefix('workspaces/{workspace}/projects/{project}/members')
    ->name('workspaces.projects.members.')
    ->scopeBindings()
    ->group(function () {
        Route::get('/', [ProjectMemberController::class, 'index'])
            ->name('index');

        Route::post('/', [ProjectMemberController::class, 'store'])
            ->name('store');

        Route::delete('/{member}', [ProjectMemberController::class, 'destroy'])
            ->whereNumber('member')
            ->name('destroy');
    });

Route::middleware(['auth', 'verified', 'workspace.context'])
    ->prefix('workspaces/{workspace}/projects/{project}/tasks')
    ->name('workspaces.projects.tasks.')
    ->scopeBindings()
    ->group(function () {
        Route::get('/', [TaskController::class, 'index'])
            ->name('index');

        Route::get('/create', [TaskController::class, 'create'])
            ->name('create');

        Route::post('/', [TaskController::class, 'store'])
            ->name('store');
    });