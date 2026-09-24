<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use App\Services\ProjectBoardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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
            ->with(['creator', 'assignee', 'project.workspace'])
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

        $assignees = $this->assignees($workspace, $project);

        return view(
            'tasks.create',
            compact('workspace', 'project', 'assignees')
        );
    }

    public function store(
        StoreTaskRequest $request,
        Workspace $workspace,
        Project $project,
        ProjectBoardService $board
    ): RedirectResponse {
        $data = $request->validated();

        $board->write($project, function (Project $lockedProject) use (
            $request,
            $data
        ) {
            Gate::authorize('create', [Task::class, $lockedProject]);

            $assigneeId = $this->validAssignee(
                $lockedProject,
                $data['assigned_to'] ?? null
            );

            $task = new Task([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'],
                'due_date' => $data['due_date'] ?? null,
            ]);

            $task->created_by = $request->user()->id;
            $task->assigned_to = $assigneeId;
            $task->status = 'todo';

            $task->kanban_order = $this->nextOrder(
                $lockedProject,
                'todo'
            );

            $lockedProject->tasks()->save($task);
        });

        return to_route('workspaces.projects.tasks.index', [
            'workspace' => $workspace,
            'project' => $project,
        ])->with('status', 'Đã tạo công việc.');
    }

    public function edit(
        Workspace $workspace,
        Project $project,
        Task $task
    ): View {
        Gate::authorize('update', $task);

        $task->load('assignee');

        $assignees = $this->assignees($workspace, $project);

        return view(
            'tasks.edit',
            compact('workspace', 'project', 'task', 'assignees')
        );
    }

    public function update(
        UpdateTaskRequest $request,
        Workspace $workspace,
        Project $project,
        Task $task,
        ProjectBoardService $board
    ): RedirectResponse {
        $data = $request->validated();

        $board->write($project, function (Project $lockedProject) use (
            $task,
            $data
        ) {
            $current = $lockedProject->tasks()
                ->whereKey($task->id)
                ->lockForUpdate()
                ->firstOrFail();

            $current->setRelation('project', $lockedProject);

            Gate::authorize('update', $current);

            $assigneeId = $this->validAssignee(
                $lockedProject,
                $data['assigned_to'] ?? null
            );

            $current->fill([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'],
                'due_date' => $data['due_date'] ?? null,
            ]);

            $current->assigned_to = $assigneeId;
            $current->save();
        });

        return to_route('workspaces.projects.tasks.index', [
            'workspace' => $workspace,
            'project' => $project,
        ])->with('status', 'Đã cập nhật công việc.');
    }

    public function updateStatus(
        Request $request,
        Workspace $workspace,
        Project $project,
        Task $task,
        ProjectBoardService $board
    ): RedirectResponse {
        Gate::authorize('updateStatus', $task);

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(array_keys(Task::STATUSES)),
            ],
        ], [
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ]);

        $board->write($project, function (Project $lockedProject) use (
            $task,
            $data
        ) {
            $current = $lockedProject->tasks()
                ->whereKey($task->id)
                ->lockForUpdate()
                ->firstOrFail();

            $current->setRelation('project', $lockedProject);

            Gate::authorize('updateStatus', $current);

            if ($current->status !== $data['status']) {
                $current->kanban_order = $this->nextOrder(
                    $lockedProject,
                    $data['status']
                );
            }

            $current->status = $data['status'];
            $current->save();
        });

        return to_route('workspaces.projects.tasks.index', [
            'workspace' => $workspace,
            'project' => $project,
        ])->with('status', 'Đã cập nhật trạng thái.');
    }

    public function destroy(
        Workspace $workspace,
        Project $project,
        Task $task,
        ProjectBoardService $board
    ): RedirectResponse {
        $board->write($project, function (Project $lockedProject) use ($task) {
            $current = $lockedProject->tasks()
                ->whereKey($task->id)
                ->lockForUpdate()
                ->firstOrFail();

            $current->setRelation('project', $lockedProject);

            Gate::authorize('delete', $current);

            $current->delete();
        });

        return to_route('workspaces.projects.tasks.index', [
            'workspace' => $workspace,
            'project' => $project,
        ])->with('status', 'Đã xóa công việc.');
    }

    private function assignees(Workspace $workspace, Project $project)
    {
        return $project->members()
            ->whereIn('users.id', function ($query) use ($workspace) {
                $query->select('user_id')
                    ->from('workspace_user')
                    ->where('workspace_id', $workspace->id);
            })
            ->orderBy('users.name')
            ->orderBy('users.id')
            ->get();
    }

    private function nextOrder(Project $project, string $status): int
    {
        return (int) $project->tasks()
            ->where('status', $status)
            ->max('kanban_order') + 1;
    }

    private function validAssignee(
        Project $project,
        mixed $assigneeId
    ): ?int {
        if ($assigneeId === null) {
            return null;
        }

        $workspaceMembership = DB::table('workspace_user')
            ->where('workspace_id', $project->workspace_id)
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

        return (int) $assigneeId;
    }
}