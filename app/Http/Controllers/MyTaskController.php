<?php

namespace App\Http\Controllers;

use App\Http\Requests\MyTaskFilterRequest;
use App\Models\SavedTaskFilter;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class MyTaskController extends Controller
{
    public function index(MyTaskFilterRequest $request): View
    {
        $filters = $request->filters();
        $user = $request->user();

        $query = Task::query()
            ->where(function (Builder $visible) use ($user) {
                // Task cá nhân do chính mình tạo.
                $visible->where(function (Builder $personal) use ($user) {
                    $personal->whereNull('project_id')
                        ->where('created_by', $user->id);
                });

                // Hoặc task dự án được giao cho mình và còn quyền xem.
                $visible->orWhere(function (Builder $projectTasks) use ($user) {
                    $projectTasks->whereNotNull('project_id')
                        ->where('assigned_to', $user->id)
                        ->whereHas(
                            'project',
                            function (Builder $project) use ($user) {
                                $project->accessibleTo($user);
                            }
                        );
                });
            })
            ->with(['project.workspace', 'creator']);

        if ($filters['scope'] === 'personal') {
            $query->whereNull('project_id');
        } elseif ($filters['scope'] === 'project') {
            $query->whereNotNull('project_id');
        }

        if ($filters['q'] !== '') {
            $query->where('title', 'like', '%' . $filters['q'] . '%');
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['priority'] !== '') {
            $query->where('priority', $filters['priority']);
        }

        $tasks = $query
            ->latest('tasks.id')
            ->paginate(10)
            ->appends($filters);

        $savedFilters = SavedTaskFilter::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return view(
            'my-tasks.index',
            compact('tasks', 'filters', 'savedFilters')
        );
    }
}