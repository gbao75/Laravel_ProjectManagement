<?php

use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\WorkspaceReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:sanctum', 'verified', 'throttle:api'])
    ->group(function () {
        Route::get('/me', function (Request $request) {
            return response()->json([
                'data' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                ],
            ]);
        })->name('me');

        Route::get('/tasks', [
            TaskController::class,
            'index',
        ])->middleware('abilities:tasks:read')
            ->name('tasks.index');

        Route::get('/tasks/{task}', [
            TaskController::class,
            'show',
        ])->middleware('abilities:tasks:read')
            ->name('tasks.show');

        Route::patch('/tasks/{task}/status', [
            TaskController::class,
            'updateStatus',
        ])->middleware('abilities:tasks:write')
            ->name('tasks.status.update');

        Route::get('/workspaces/{workspace}/reports/tasks', [
            WorkspaceReportController::class,
            'data',
        ])->middleware('abilities:reports:read')
            ->name('reports.tasks');
    });