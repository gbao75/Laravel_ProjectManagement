<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MyWorkQuery
{
    public function tasks(User $user): Builder
    {
        return Task::query()
            ->where(function (Builder $query) use ($user) {
                $query->where(function (Builder $personal) use ($user) {
                    $personal->whereNull('project_id')
                        ->where('created_by', $user->id);
                })->orWhere(function (Builder $projectTasks) use ($user) {
                    $projectTasks->whereNotNull('project_id')
                        ->where('assigned_to', $user->id)
                        ->whereHas(
                            'project',
                            fn (Builder $project) =>
                                $project->accessibleTo($user)
                        );
                });
            });
    }
}