<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Models\TaskTimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAutomationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTask(User $user): Task
    {
        $task = new Task();

        $task->project_id = null;
        $task->created_by = $user->id;
        $task->assigned_to = $user->id;
        $task->title = 'Task thử nghiệm';
        $task->status = 'todo';
        $task->priority = 'medium';

        $task->save();

        return $task;
    }

    public function test_user_cannot_start_two_timers(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $first = $this->makeTask($user);
        $second = $this->makeTask($user);

        $this->actingAs($user)
            ->post(route('tasks.timer.start', $first))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('tasks.timer.start', $second))
            ->assertSessionHasErrors('timer');

        $this->assertSame(
            1,
            TaskTimeEntry::whereNull('ended_at')->count()
        );
    }

    public function test_stop_stores_server_duration(): void
    {
        $this->travelTo(
            Carbon::parse('2026-09-23 10:00:00', 'Asia/Ho_Chi_Minh')
        );

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $task = $this->makeTask($user);

        $this->actingAs($user)
            ->post(route('tasks.timer.start', $task));

        $entry = TaskTimeEntry::firstOrFail();

        $this->travel(2)->minutes();

        $this->patch(route('time-entries.stop', $entry->id))
            ->assertRedirect();

        $this->assertSame(
            120,
            $entry->fresh()->duration_seconds
        );
    }

    public function test_same_recurrence_date_does_not_create_duplicate(): void
    {
        $this->travelTo(
            Carbon::parse('2026-09-23 10:00:00', 'Asia/Ho_Chi_Minh')
        );

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $task = $this->makeTask($user);

        $rule = new TaskRecurrence();

        $rule->task_id = $task->id;
        $rule->created_by = $user->id;
        $rule->frequency = 'daily';
        $rule->next_run_on = '2026-09-23';
        $rule->anchor_day = 23;
        $rule->due_after_days = 0;
        $rule->is_active = true;

        $rule->save();

        $this->artisan('tasks:generate-recurring')
            ->assertExitCode(0);

        $this->artisan('tasks:generate-recurring')
            ->assertExitCode(0);

        $this->assertDatabaseCount('tasks', 2);
        $this->assertDatabaseCount('task_recurrence_runs', 1);

        $this->assertSame(
            '2026-09-24',
            $rule->fresh()->next_run_on->toDateString()
        );
    }

    public function test_monthly_recurrence_keeps_original_day(): void
    {
        $rule = new TaskRecurrence();

        $rule->frequency = 'monthly';
        $rule->anchor_day = 31;
        $rule->next_run_on = '2027-01-31';

        $this->assertSame(
            '2027-02-28',
            $rule->nextDate()->toDateString()
        );

        $rule->next_run_on = $rule->nextDate();

        $this->assertSame(
            '2027-03-31',
            $rule->nextDate()->toDateString()
        );
    }
}