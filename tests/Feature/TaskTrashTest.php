<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTrashTest extends TestCase
{
    use RefreshDatabase;

    private function personalTask(User $owner): Task
    {
        $task = new Task();

        $task->project_id = null;
        $task->created_by = $owner->id;
        $task->assigned_to = $owner->id;
        $task->title = 'Công việc kiểm thử thùng rác';
        $task->status = 'todo';
        $task->priority = 'medium';
        $task->kanban_order = 0;

        $task->save();

        return $task;
    }

    public function test_owner_can_trash_and_restore_personal_task(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $task = $this->personalTask($owner);

        $this->actingAs($owner)
            ->delete(route('personal-tasks.destroy', $task))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);

        $this->assertNull(Task::find($task->id));

        $this->patch(route('trash.tasks.restore', $task->id))
            ->assertRedirect(route('trash.tasks.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'deleted_at' => null,
        ]);
    }

    public function test_other_user_cannot_manage_personal_trash(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $other = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $task = $this->personalTask($owner);

        $this->actingAs($owner)
            ->delete(route('personal-tasks.destroy', $task))
            ->assertSessionHasNoErrors();

        $this->actingAs($other)
            ->get(route('trash.tasks.index'))
            ->assertOk()
            ->assertDontSee($task->title);

        $this->patch(route('trash.tasks.restore', $task->id))
            ->assertForbidden();

        $this->delete(route('trash.tasks.destroy', $task->id), [
            'confirm' => '1',
        ])->assertForbidden();

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_permanent_deletion_requires_confirmation(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $task = $this->personalTask($owner);

        $this->actingAs($owner)
            ->delete(route('personal-tasks.destroy', $task))
            ->assertSessionHasNoErrors();

        $this->delete(route('trash.tasks.destroy', $task->id))
            ->assertSessionHasErrors('confirm');

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);

        $this->delete(route('trash.tasks.destroy', $task->id), [
            'confirm' => '1',
        ])
            ->assertRedirect(route('trash.tasks.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_running_timer_prevents_deletion(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $task = $this->personalTask($owner);

        $entry = $task->timeEntries()->make();

        $entry->user_id = $owner->id;
        $entry->started_at = now();
        $entry->save();

        $this->actingAs($owner)
            ->delete(route('personal-tasks.destroy', $task))
            ->assertSessionHasErrors('task');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'deleted_at' => null,
        ]);
    }
}