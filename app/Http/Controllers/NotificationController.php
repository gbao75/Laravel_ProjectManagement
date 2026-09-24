<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Notifications\TaskMentioned;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Notifications\TaskDeadlineReminder;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest('created_at')
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function open(
        Request $request,
        string $notification
    ): RedirectResponse {
        $entry = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $supportedTypes = [
            TaskMentioned::class,
            TaskDeadlineReminder::class,
        ];

        if (! in_array($entry->type, $supportedTypes, true)) {
            return back()->with(
                'status',
                'Thông báo này chưa hỗ trợ mở nội dung.'
            );
        }

        $task = Task::query()
            ->with('project.workspace')
            ->whereKey($entry->data['task_id'] ?? 0)
            ->where('project_id', $entry->data['project_id'] ?? null)
            ->first();

        if (! $task) {
            $entry->markAsRead();

            return redirect()
                ->route('notifications.index')
                ->with('status', 'Công việc không còn tồn tại.');
        }

        if (Gate::denies('view', $task)) {
            $entry->markAsRead();

            return redirect()
                ->route('notifications.index')
                ->with('status', 'Bạn không còn quyền truy cập công việc này.');
        }

        if ($task->project_id === null) {
            $entry->markAsRead();

            // Task cá nhân hiện dùng trang chỉnh sửa để xem/làm việc.
            return redirect()->route('personal-tasks.edit', $task);
        }

        if (! $task->project || ! $task->project->workspace) {
            $entry->markAsRead();

            return redirect()
                ->route('notifications.index')
                ->with('status', 'Dự án không còn tồn tại.');
        }

        $entry->markAsRead();

        return redirect()->route('workspaces.projects.tasks.show', [
            'workspace' => $task->project->workspace,
            'project' => $task->project,
            'task' => $task,
        ]);
    }

    public function read(
        Request $request,
        string $notification
    ): RedirectResponse {
        $entry = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $entry->markAsRead();

        return back()->with('status', 'Đã đánh dấu thông báo là đã đọc.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return back()->with('status', 'Đã đọc tất cả thông báo.');
    }
}