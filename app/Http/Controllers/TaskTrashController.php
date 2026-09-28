<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectBoardService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class TaskTrashController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $tasks = Task::onlyTrashed()
            ->with(['project.workspace'])
            ->where(function (Builder $query) use ($userId) {
                // Task cá nhân của chính người đăng nhập.
                $query->where(function (Builder $personal) use ($userId) {
                    $personal
                        ->whereNull('project_id')
                        ->where('created_by', $userId);
                });

                // Task dự án trong workspace mà người dùng là owner/admin.
                $query->orWhereHas(
                    'project.workspace',
                    function (Builder $workspace) use ($userId) {
                        $workspace->where(function (Builder $access) use ($userId) {
                            $access->where('owner_id', $userId)
                                ->orWhereHas(
                                    'members',
                                    function (Builder $members) use ($userId) {
                                        $members
                                            ->where('users.id', $userId)
                                            ->where('workspace_user.role', 'admin');
                                    }
                                );
                        });
                    }
                );
            })
            ->orderByDesc('deleted_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('tasks.trash', compact('tasks'));
    }

    public function restore(
        int $task,
        ProjectBoardService $boards
    ): RedirectResponse {
        $snapshot = Task::onlyTrashed()->findOrFail($task);

        Gate::authorize('restore', $snapshot);

        $this->change(
            $snapshot,
            $boards,
            function (Task $current) {
                Gate::authorize('restore', $current);

                if ($current->project_id !== null) {
                    // Người được giao có thể đã rời dự án/workspace.
                    $assignee = $current->assignee;

                    if (
                        $assignee === null
                        || ! Gate::forUser($assignee)->allows(
                            'view',
                            $current->project
                        )
                    ) {
                        $current->assigned_to = null;
                    }

                    // Đưa task được khôi phục xuống cuối cột Kanban.
                    $current->kanban_order = (
                        (int) $current->project->tasks()
                            ->where('status', $current->status)
                            ->max('kanban_order')
                    ) + 1;
                }

                $current->restore();
            }
        );

        return redirect()
            ->route('trash.tasks.index')
            ->with(
                'status',
                'Đã khôi phục công việc. '
                    . 'Nếu có lịch lặp, hãy kiểm tra và bật lại khi cần.'
            );
    }

    public function destroy(
        Request $request,
        int $task,
        ProjectBoardService $boards
    ): RedirectResponse {
        $snapshot = Task::onlyTrashed()->findOrFail($task);

        Gate::authorize('forceDelete', $snapshot);

        $request->validate([
            'confirm' => ['required', 'accepted'],
        ], [
            'confirm.required' => 'Vui lòng xác nhận xóa vĩnh viễn.',
            'confirm.accepted' => 'Vui lòng xác nhận xóa vĩnh viễn.',
        ]);

        $paths = $this->change(
            $snapshot,
            $boards,
            function (Task $current): array {
                Gate::authorize('forceDelete', $current);

                $paths = $current->attachments()
                    ->pluck('path')
                    ->all();

                $current->forceDelete();

                return $paths;
            }
        );

        // Chỉ xóa file vật lý sau khi transaction DB thành công.
        foreach ($paths as $path) {
            try {
                Storage::disk('task_attachments')->delete($path);
            } catch (Throwable $exception) {
                report($exception);

                // Lệnh dọn file mồ côi đã có sẽ xử lý lại file còn sót.
            }
        }

        return redirect()
            ->route('trash.tasks.index')
            ->with('status', 'Đã xóa vĩnh viễn công việc.');
    }

    private function change(
        Task $snapshot,
        ProjectBoardService $boards,
        Closure $callback
    ): mixed {
        if ($snapshot->project_id !== null) {
            $project = Project::query()
                ->findOrFail($snapshot->project_id);

            $result = $boards->write(
                $project,
                function (Project $lockedProject) use ($snapshot, $callback) {
                    $current = $lockedProject->tasks()
                        ->onlyTrashed()
                        ->whereKey($snapshot->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $current->setRelation('project', $lockedProject);

                    return $callback($current);
                }
            );

            return $result['result'];
        }

        return DB::transaction(function () use ($snapshot, $callback) {
            $current = Task::onlyTrashed()
                ->whereNull('project_id')
                ->whereKey($snapshot->id)
                ->lockForUpdate()
                ->firstOrFail();

            return $callback($current);
        }, 3);
    }
}