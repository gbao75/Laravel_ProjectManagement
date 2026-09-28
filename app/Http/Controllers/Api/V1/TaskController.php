<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\MyWorkQuery;
use App\Services\ProjectBoardService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(
        Request $request,
        MyWorkQuery $work
    ): AnonymousResourceCollection {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],

            'status' => [
                'nullable',
                Rule::in(array_keys(Task::STATUSES)),
            ],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $work->tasks($request->user())
            ->with(['project', 'assignee']);

        if (! empty($filters['q'])) {
            $query->where(
                'title',
                'like',
                '%'.$filters['q'].'%'
            );
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $tasks = $query
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return TaskResource::collection($tasks);
    }

    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        $task->load(['project', 'assignee']);

        return new TaskResource($task);
    }

    public function updateStatus(
        Request $request,
        Task $task,
        ProjectBoardService $board
    ): TaskResource {
        Gate::authorize('updateStatus', $task);

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(array_keys(Task::STATUSES)),
            ],

            'board_version' => [
                Rule::requiredIf($task->project_id !== null),
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        if ($task->project_id === null) {
            DB::transaction(function () use ($task, $data) {
                $current = Task::query()
                    ->whereKey($task->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                Gate::authorize('updateStatus', $current);

                $current->status = $data['status'];
                $current->save();
            }, 3);
        } else {
            $board->write(
                $task->project,
                function (Project $project) use ($task, $data) {
                    $current = $project->tasks()
                        ->whereKey($task->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $current->setRelation('project', $project);

                    Gate::authorize('updateStatus', $current);

                    if ($current->status !== $data['status']) {
                        $current->kanban_order = (
                            (int) $project->tasks()
                                ->where('status', $data['status'])
                                ->max('kanban_order')
                        ) + 1;
                    }

                    $current->status = $data['status'];
                    $current->save();
                },
                (int) $data['board_version']
            );
        }

        $task->refresh()->load(['project', 'assignee']);

        return new TaskResource($task);
    }
}