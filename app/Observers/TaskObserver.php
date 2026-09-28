<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\TaskActivityLogger;
use Illuminate\Validation\ValidationException;

class TaskObserver
{
    public function __construct(
        private TaskActivityLogger $logger
    ) {
    }

    public function created(Task $task): void
    {
        $this->logger->record($task, 'task.created');
    }

    public function updated(Task $task): void
    {
        $fields = [
            'title',
            'description',
            'status',
            'priority',
            'assigned_to',
            'due_date',
        ];

        $changes = [];

        foreach ($fields as $field) {
            if (! $task->wasChanged($field)) {
                continue;
            }

            $changes[$field] = [
                'old' => $task->getRawOriginal($field),
                'new' => $task->getAttributes()[$field] ?? null,
            ];
        }

        if ($changes !== []) {
            $this->logger->record(
                $task,
                'task.updated',
                $changes
            );
        }
    }

    public function deleting(Task $task): void
    {
        // Không để một timer đang chạy mất task của nó.
        if ($task->timeEntries()->whereNull('ended_at')->exists()) {
            throw ValidationException::withMessages([
                'task' => 'Công việc đang có bộ đếm thời gian chạy. '
                    . 'Hãy dừng bộ đếm trước khi xóa.',
            ]);
        }

        // Task vào thùng rác thì không tiếp tục sinh task mới.
        $task->recurrence()->update([
            'is_active' => false,
            'paused_reason' => 'Công việc đã được chuyển vào thùng rác.',
        ]);
    }

    public function deleted(Task $task): void
    {
        $permanent = $task->isForceDeleting();

        app(TaskActivityLogger::class)->record(
            $task,
            $permanent ? 'task.force_deleted' : 'task.trashed',
            [],
            $permanent
        );
    }

    public function restored(Task $task): void
    {
        app(TaskActivityLogger::class)->record(
            $task,
            'task.restored'
        );
    }
}