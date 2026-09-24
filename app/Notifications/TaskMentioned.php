<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\Notification;

class TaskMentioned extends Notification
{
    public function __construct(
        private Task $task,
        private User $actor
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'task_mentioned',

            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,

            'task_id' => $this->task->id,
            'task_title' => $this->task->title,

            'project_id' => $this->task->project_id,

            'message' => $this->actor->name
                .' đã nhắc bạn trong công việc “'
                .$this->task->title
                .'”.',
        ];
    }
}