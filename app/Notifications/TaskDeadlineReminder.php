<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskDeadlineReminder extends Notification
{
    public function __construct(
        private Task $task,
        private string $kind
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $deadlineText = match ($this->kind) {
            'due_tomorrow' => 'sẽ đến hạn vào ngày mai',
            'due_today' => 'đến hạn hôm nay',
            'overdue' => 'đã quá hạn',
        };

        return [
            'kind' => 'task_deadline',
            'reminder_kind' => $this->kind,

            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'project_id' => $this->task->project_id,

            'due_date' => $this->task->due_date->toDateString(),

            'message' => 'Công việc “'
                .$this->task->title
                .'” '
                .$deadlineText
                .'.',
        ];
    }
}