<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->members()
            ->whereKey($user->id)
            ->exists();
    }

    public function update(User $user, Workspace $workspace): bool
    {
        if ($workspace->owner_id === $user->id) {
            return true;
        }

        return $workspace->members()
            ->whereKey($user->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id
            && ! $workspace->is_personal;
    }

    public function invite(User $user, Workspace $workspace): bool
    {
        return ! $workspace->is_personal
            && $this->update($user, $workspace);
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id;
    }
}