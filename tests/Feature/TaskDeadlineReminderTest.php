<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDeadlineReminderTest extends TestCase
{
    use RefreshDatabase;

    private function makePersonalTask(User $user): Task
    {
        $task = new Task();

        $task->project_id = null;
        $task->created_by = $user->id;
        $task->assigned_to = $user->id;

        $task->title = 'Task kiểm tra nhắc hạn';
        $task->status = 'todo';
        $task->priority = 'medium';
        $task->due_date = '2026-09-24';

        $task->save();

        return $task;
    }

    public function test_same_reminder_is_sent_only_once(): void
    {
        $this->travelTo(
            Carbon::parse('2026-09-23 10:00:00', 'Asia/Ho_Chi_Minh')
        );

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $task = $this->makePersonalTask($user);

        $this->artisan('tasks:send-deadline-reminders', [
            '--task' => $task->id,
        ])->assertExitCode(0);

        $this->artisan('tasks:send-deadline-reminders', [
            '--task' => $task->id,
        ])->assertExitCode(0);

        $this->assertSame(1, $user->notifications()->count());

        $this->assertDatabaseCount('task_reminder_deliveries', 1);
    }

    public function test_disabled_deadline_notifications_are_not_sent(): void
    {
        $this->travelTo(
            Carbon::parse('2026-09-23 10:00:00', 'Asia/Ho_Chi_Minh')
        );

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'notification_preferences' => [
                'mentions' => true,
                'deadlines' => false,
                'overdue' => true,
            ],
        ]);

        $task = $this->makePersonalTask($user);

        $this->artisan('tasks:send-deadline-reminders', [
            '--task' => $task->id,
        ])->assertExitCode(0);

        $this->assertSame(0, $user->notifications()->count());

        $this->assertDatabaseCount('task_reminder_deliveries', 0);
    }

    public function test_completed_task_does_not_receive_reminder(): void
    {
        $this->travelTo(
            Carbon::parse('2026-09-23 10:00:00', 'Asia/Ho_Chi_Minh')
        );

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $task = $this->makePersonalTask($user);

        $task->status = 'completed';
        $task->save();

        $this->artisan('tasks:send-deadline-reminders', [
            '--task' => $task->id,
        ])->assertExitCode(0);

        $this->assertSame(0, $user->notifications()->count());
    }
}