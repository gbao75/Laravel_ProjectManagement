<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    private function ownsPersonalTask(User $user, Task $task): bool
    {
        return $task->project_id === null
            && (int) $task->created_by === (int) $user->id;
    }

    public function viewAny(User $user, Project $project): bool
    {
        return $user->can('view', $project);
    }

    public function view(User $user, Task $task): bool
    {
        if ($task->project_id === null) {
            return $this->ownsPersonalTask($user, $task);
        }

        return $user->can('view', $task->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->can('update', $project);
    }

    public function update(User $user, Task $task): bool
    {
        if ($task->project_id === null) {
            return $this->ownsPersonalTask($user, $task);
        }

        return $user->can('update', $task->project);
    }

    public function updateStatus(User $user, Task $task): bool
    {
        if ($task->project_id === null) {
            return $this->ownsPersonalTask($user, $task);
        }

        if (! $user->can('view', $task->project)) {
            return false;
        }

        if ($user->can('update', $task->project)) {
            return true;
        }

        return $task->assigned_to !== null
            && (int) $task->assigned_to === (int) $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    public function trackTime(User $user, Task $task): bool
    {
        return $this->updateStatus($user, $task);
    }
}