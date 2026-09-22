<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

class ProjectPolicy
{
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $user->can('view', $workspace);
    }

    public function view(User $user, Project $project): bool
    {
        $workspace = $project->workspace;

        // Phải còn quyền truy cập workspace.
        if (! $user->can('view', $workspace)) {
            return false;
        }

        // Owner/Admin workspace được xem mọi dự án.
        if ($user->can('update', $workspace)) {
            return true;
        }

        // Member chỉ xem dự án được thêm vào.
        return $project->members()
            ->where('users.id', $user->id)
            ->exists();
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $user->can('update', $workspace);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('update', $project->workspace);
    }

    public function delete(User $user, Project $project): bool
    {
        return (int) $project->workspace->owner_id === (int) $user->id;
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $user->can('update', $project->workspace);
    }
}