<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\TaskMentioned;
use App\Services\TaskActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskDetailController extends Controller
{
    public function show(
        Request $request,
        Workspace $workspace,
        Project $project,
        Task $task
    ): View {
        Gate::authorize('view', $task);

        $task->load(['creator', 'assignee']);

        $attachments = $task->attachments()
            ->with('uploader')
            ->latest('id')
            ->paginate(8, ['*'], 'files_page')
            ->withQueryString();

        $activities = $task->activities()
            ->with('actor')
            ->latest('id')
            ->paginate(10, ['*'], 'activity_page')
            ->withQueryString();

        $mentionableUsers = $this->mentionableUsers(
            $workspace,
            $project
        )->reject(
            fn (User $user) =>
                (int) $user->id === (int) $request->user()->id
        )->values();

        return view('tasks.show', compact(
            'workspace',
            'project',
            'task',
            'attachments',
            'activities',
            'mentionableUsers'
        ));
    }

    public function mention(
        Request $request,
        Workspace $workspace,
        Project $project,
        Task $task,
        TaskActivityLogger $logger
    ): RedirectResponse {
        Gate::authorize('view', $task);

        $eligibleUsers = $this->mentionableUsers(
            $workspace,
            $project
        )->reject(
            fn (User $user) =>
                (int) $user->id === (int) $request->user()->id
        );

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:20'],

            'user_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::in($eligibleUsers->pluck('id')->all()),
            ],
        ], [
            'user_ids.required' => 'Vui lòng chọn người cần nhắc.',
            'user_ids.min' => 'Vui lòng chọn ít nhất một người.',
            'user_ids.max' => 'Mỗi lần chỉ nhắc tối đa 20 người.',
            'user_ids.*.in' =>
                'Người được chọn không có quyền xem task hoặc là chính bạn.',
        ]);

        $selectedIds = array_map('intval', $data['user_ids']);

        $recipients = $eligibleUsers->filter(
            fn (User $user) => in_array(
                (int) $user->id,
                $selectedIds,
                true
            )
        );

        $receivingUsers = $recipients
    ->filter(
        fn (User $user) => $user->wantsTaskNotification('mentions')
    )
    ->values();

        $skippedCount = $recipients->count() - $receivingUsers->count();

        if ($receivingUsers->isEmpty()) {
            return back()->with(
                'status',
                'Những người được chọn đang tắt thông báo nhắc tên.'
            );
        }

        DB::transaction(function () use (
            $request,
            $task,
            $receivingUsers,
            $logger
        ) {
            foreach ($receivingUsers as $recipient) {
                Gate::forUser($recipient)->authorize('view', $task);

                $recipient->notify(
                    new TaskMentioned($task, $request->user())
                );
            }

            $logger->record($task, 'task.mentioned', [
                'recipient_ids' => $receivingUsers->pluck('id')->all(),
                'recipient_names' => $receivingUsers->pluck('name')->all(),
            ]);
        });

        $message = 'Đã gửi thông báo nhắc tên cho '
            .$receivingUsers->count()
            .' người.';

        if ($skippedCount > 0) {
            $message .= ' '.$skippedCount.' người đang tắt thông báo nhắc tên.';
        }

        return back()->with('status', $message);
    }

    public function activity(
        Workspace $workspace,
        Project $project
    ): View {
        Gate::authorize('view', $project);

        $activities = TaskActivity::query()
            ->where('project_id', $project->id)
            ->with('actor')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('projects.activity', compact(
            'workspace',
            'project',
            'activities'
        ));
    }

    private function mentionableUsers(
        Workspace $workspace,
        Project $project
    ): Collection {
        // Người có thể xem dự án:
        // owner, admin workspace, hoặc thành viên dự án
        // vẫn còn là thành viên workspace.
        return User::query()
            ->where(function ($query) use ($workspace, $project) {
                $query->where('users.id', $workspace->owner_id)
                    ->orWhereIn(
                        'users.id',
                        DB::table('workspace_user')
                            ->select('user_id')
                            ->where('workspace_id', $workspace->id)
                            ->where(function ($membership) use ($project) {
                                $membership->where('role', 'admin')
                                    ->orWhereIn(
                                        'user_id',
                                        DB::table('project_user')
                                            ->select('user_id')
                                            ->where('project_id', $project->id)
                                    );
                            })
                    );
            })
            ->orderBy('name')
            ->get();
    }
}