<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskTimeEntry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaskTimerController extends Controller
{
    public function index(Request $request): View
    {
        $query = TaskTimeEntry::query()
            ->where('user_id', $request->user()->id);

        $totalSeconds = (int) (clone $query)
            ->whereNotNull('ended_at')
            ->sum('duration_seconds');

        $entries = $query
            ->with('task.project')
            ->latest('id')
            ->paginate(20);

        return view('time-entries.index', compact(
            'entries',
            'totalSeconds'
        ));
    }

    public function start(
        Request $request,
        Task $task
    ): RedirectResponse {
        DB::transaction(function () use ($request, $task) {
            // Mọi thao tác start/stop của một người dùng
            // đều khóa cùng hàng user.
            User::query()
                ->whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentTask = Task::query()->findOrFail($task->id);

            Gate::authorize('trackTime', $currentTask);

            if ($currentTask->status === 'completed') {
                throw ValidationException::withMessages([
                    'timer' => 'Công việc đã hoàn thành, không thể bắt đầu bộ đếm.',
                ]);
            }

            $hasRunningTimer = TaskTimeEntry::query()
                ->where('user_id', $request->user()->id)
                ->whereNull('ended_at')
                ->exists();

            if ($hasRunningTimer) {
                throw ValidationException::withMessages([
                    'timer' => 'Bạn đang ghi thời gian cho một công việc. Hãy dừng trước.',
                ]);
            }

            $entry = new TaskTimeEntry();

            $entry->task_id = $currentTask->id;
            $entry->user_id = $request->user()->id;
            $entry->started_at = now();

            $entry->save();
        }, 3);

        return back()->with('status', 'Đã bắt đầu ghi thời gian.');
    }

    public function stop(
        Request $request,
        int $entry
    ): RedirectResponse {
        DB::transaction(function () use ($request, $entry) {
            User::query()
                ->whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $timer = TaskTimeEntry::query()
                ->where('user_id', $request->user()->id)
                ->whereKey($entry)
                ->lockForUpdate()
                ->firstOrFail();

            // Bấm dừng hai lần không sửa lại số giây.
            if ($timer->ended_at !== null) {
                return;
            }

            $end = now();

            $timer->ended_at = $end;

            $timer->duration_seconds = max(
                0,
                (int) $timer->started_at->diffInSeconds($end)
            );

            $timer->save();
        }, 3);

        return back()->with('status', 'Đã dừng và lưu thời gian.');
    }
}