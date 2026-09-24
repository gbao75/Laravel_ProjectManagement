<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskActivity;
use Illuminate\Support\Facades\Auth;

class TaskActivityLogger
{
    public function record(
        Task $task,
        string $event,
        array $changes = [],
        bool $deleted = false
    ): void {
        // Chỉ áp dụng cho task thuộc dự án.
        if ($task->project_id === null) {
            return;
        }

        $activity = new TaskActivity();

        $activity->project_id = $task->project_id;
        $activity->task_id = $deleted ? null : $task->id;
        $activity->user_id = Auth::id();

        $activity->task_title = $task->title;
        $activity->event = $event;
        $activity->changes = $changes ?: null;

        $activity->save();
    }
}