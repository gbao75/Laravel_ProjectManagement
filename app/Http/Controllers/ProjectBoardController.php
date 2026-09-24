<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use App\Services\ProjectBoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectBoardController extends Controller
{
    public function index(
        Workspace $workspace,
        Project $project
    ): View {
        // Đọc phiên bản và task dưới cùng khóa project.
        [$project, $tasks] = DB::transaction(function () use ($project) {
            $current = Project::query()
                ->whereKey($project->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('view', $current);

            $tasks = $current->tasks()
                ->with('assignee')
                ->orderBy('kanban_order')
                ->orderBy('id')
                ->get();

            foreach ($tasks as $task) {
                $task->setRelation('project', $current);
            }

            return [$current, $tasks];
        });

        return view(
            'projects.board',
            compact('workspace', 'project', 'tasks')
        );
    }

    public function move(
        Request $request,
        Workspace $workspace,
        Project $project,
        ProjectBoardService $board
    ): JsonResponse {
        Gate::authorize('view', $project);

        $data = $request->validate([
            'task_id' => ['required', 'integer'],
            'status' => [
                'required',
                Rule::in(array_keys(Task::STATUSES)),
            ],
            'before_id' => ['nullable', 'integer'],
            'version' => ['required', 'integer', 'min:0'],
        ]);

        $result = $board->write(
            $project,
            function (Project $lockedProject) use ($data) {
                $task = $lockedProject->tasks()
                    ->whereKey($data['task_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $task->setRelation('project', $lockedProject);

                Gate::authorize('updateStatus', $task);

                $oldStatus = $task->status;
                $newStatus = $data['status'];

                // Danh sách cột đích, loại chính task đang di chuyển.
                $targetIds = $lockedProject->tasks()
                    ->where('status', $newStatus)
                    ->where('id', '<>', $task->id)
                    ->orderBy('kanban_order')
                    ->orderBy('id')
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $beforeId = $data['before_id'] ?? null;
                $insertAt = count($targetIds);

                if ($beforeId !== null) {
                    $insertAt = array_search(
                        (int) $beforeId,
                        $targetIds,
                        true
                    );

                    if ($insertAt === false) {
                        throw ValidationException::withMessages([
                            'before_id' =>
                                'Vị trí đích không hợp lệ. Vui lòng tải lại bảng.',
                        ]);
                    }
                }

                array_splice(
                    $targetIds,
                    $insertAt,
                    0,
                    [(int) $task->id]
                );

                $task->status = $newStatus;
                $task->save();

                $this->renumber($lockedProject, $targetIds);

                if ($oldStatus !== $newStatus) {
                    $sourceIds = $lockedProject->tasks()
                        ->where('status', $oldStatus)
                        ->orderBy('kanban_order')
                        ->orderBy('id')
                        ->pluck('id')
                        ->all();

                    $this->renumber($lockedProject, $sourceIds);
                }
            },
            (int) $data['version']
        );

        return response()->json([
            'message' => 'Đã lưu vị trí công việc.',
            'version' => $result['version'],
        ]);
    }

    private function renumber(Project $project, array $ids): void
    {
        foreach ($ids as $index => $id) {
            $project->tasks()
                ->whereKey($id)
                ->update([
                    'kanban_order' => $index + 1,
                ]);
        }
    }
}