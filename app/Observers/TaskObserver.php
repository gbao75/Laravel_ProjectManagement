<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\TaskActivityLogger;

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

    public function deleted(Task $task): void
    {
        $this->logger->record(
            $task,
            'task.deleted',
            deleted: true
        );
    }
}