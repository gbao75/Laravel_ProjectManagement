<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('users.{userId}', function (
    User $user,
    int $userId
) {
    return $user->hasVerifiedEmail()
        && (int) $user->id === $userId;
});

Broadcast::channel('projects.{projectId}', function (
    User $user,
    int $projectId
) {
    $project = Project::query()->find($projectId);

    return $user->hasVerifiedEmail()
        && $project !== null
        && Gate::forUser($user)->allows('view', $project);
});