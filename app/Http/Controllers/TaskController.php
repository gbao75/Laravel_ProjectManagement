<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(
        Workspace $workspace,
        Project $project
    ): View {
        Gate::authorize('viewAny', [Task::class, $project]);

        $tasks = $project->tasks()
            ->with(['creator', 'assignee'])
            ->latest('tasks.id')
            ->paginate(10);

        return view(
            'tasks.index',
            compact('workspace', 'project', 'tasks')
        );
    }

    public function create(
        Workspace $workspace,
        Project $project
    ): View {
        Gate::authorize('create', [Task::class, $project]);

        $assignees = $project->members()
            ->whereIn('users.id', function ($query) use ($workspace) {
                $query->select('user_id')
                    ->from('workspace_user')
                    ->where('workspace_id', $workspace->id);
            })
            ->orderBy('users.name')
            ->orderBy('users.id')
            ->get();

        return view(
            'tasks.create',
            compact('workspace', 'project', 'assignees')
        );
    }

    public function store(
        StoreTaskRequest $request,
        Workspace $workspace,
        Project $project
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(function () use ($request, $workspace, $project, $data) {
            $assigneeId = $data['assigned_to'] ?? null;

            if ($assigneeId !== null) {
                // Đọc lại membership lúc ghi dữ liệu.
                $workspaceMembership = DB::table('workspace_user')
                    ->where('workspace_id', $workspace->id)
                    ->where('user_id', $assigneeId)
                    ->lockForUpdate()
                    ->first();

                $projectMembership = DB::table('project_user')
                    ->where('project_id', $project->id)
                    ->where('user_id', $assigneeId)
                    ->lockForUpdate()
                    ->first();

                if (! $workspaceMembership || ! $projectMembership) {
                    throw ValidationException::withMessages([
                        'assigned_to' =>
                            'Người được chọn không còn thuộc dự án hoặc workspace.',
                    ]);
                }
            }

            $task = new Task([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'],
                'due_date' => $data['due_date'] ?? null,
            ]);

            $task->created_by = $request->user()->id;
            $task->assigned_to = $assigneeId;
            $task->status = 'todo';

            $project->tasks()->save($task);
        });

        return redirect()
            ->route('workspaces.projects.tasks.index', [
                'workspace' => $workspace,
                'project' => $project,
            ])
            ->with('status', 'Đã tạo công việc.');
    }
}