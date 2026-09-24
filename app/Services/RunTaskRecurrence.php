<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RunTaskRecurrence
{
    public function run(int $ruleId): bool
    {
        $snapshot = TaskRecurrence::query()
            ->with('task')
            ->find($ruleId);

        if (! $snapshot || ! $snapshot->task) {
            return false;
        }

        $projectId = $snapshot->task->project_id;

        return DB::transaction(function () use ($ruleId, $projectId) {
            $project = null;

            // Khóa cùng hàng project mà ProjectBoardService sử dụng.
            if ($projectId !== null) {
                $project = Project::query()
                    ->whereKey($projectId)
                    ->lockForUpdate()
                    ->first();

                if (! $project) {
                    return false;
                }
            }

            $rule = TaskRecurrence::query()
                ->whereKey($ruleId)
                ->lockForUpdate()
                ->first();

            if (! $rule || ! $rule->is_active) {
                return false;
            }

            $today = CarbonImmutable::now(
                config('task_reminders.timezone', 'Asia/Ho_Chi_Minh')
            )->toDateString();

            if ($rule->next_run_on->toDateString() > $today) {
                return false;
            }

            $source = Task::query()
                ->whereKey($rule->task_id)
                ->lockForUpdate()
                ->first();

            if (! $source) {
                return false;
            }

            if ($project) {
                $source->setRelation('project', $project);
            }

            $actor = User::query()->find($rule->created_by);

            if (
                ! $actor
                || ! $actor->hasVerifiedEmail()
                || Gate::forUser($actor)->denies('update', $source)
                || (
                    $project
                    && Gate::forUser($actor)->denies(
                        'create',
                        [Task::class, $project]
                    )
                )
            ) {
                $rule->is_active = false;

                $rule->paused_reason =
                    'Người thiết lập lịch không còn đủ quyền. Hãy lưu lại lịch bằng tài khoản có quyền.';

                $rule->save();

                return false;
            }

            $scheduledOn = $rule->next_run_on->toDateString();

            $alreadyCreated = DB::table('task_recurrence_runs')
                ->where('task_recurrence_id', $rule->id)
                ->where('scheduled_on', $scheduledOn)
                ->exists();

            if ($alreadyCreated) {
                $rule->next_run_on = $rule->nextDate();
                $rule->save();

                return false;
            }

            $task = new Task();

            $task->project_id = $source->project_id;
            $task->created_by = $actor->id;

            $task->title = $source->title;
            $task->description = $source->description;
            $task->priority = $source->priority;

            $task->status = 'todo';

            $task->due_date = $rule->next_run_on
                ->addDays($rule->due_after_days)
                ->toDateString();

            if ($project === null) {
                $task->assigned_to = $actor->id;
                $task->kanban_order = 0;
            } else {
                $task->assigned_to = $this->validAssignee(
                    $project,
                    $source->assigned_to
                );

                $task->kanban_order = (
                    (int) $project->tasks()
                        ->where('status', 'todo')
                        ->max('kanban_order')
                ) + 1;
            }

            $task->save();

            DB::table('task_recurrence_runs')->insert([
                'task_recurrence_id' => $rule->id,
                'scheduled_on' => $scheduledOn,
                'generated_task_id' => $task->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rule->next_run_on = $rule->nextDate();
            $rule->save();

            if ($project) {
                // Báo cho Kanban rằng dữ liệu bảng đã thay đổi.
                $project->increment('board_version');
            }

            return true;
        }, 3);
    }

    private function validAssignee(
        Project $project,
        ?int $userId
    ): ?int {
        if ($userId === null) {
            return null;
        }

        $inWorkspace = DB::table('workspace_user')
            ->where('workspace_id', $project->workspace_id)
            ->where('user_id', $userId)
            ->exists();

        $inProject = DB::table('project_user')
            ->where('project_id', $project->id)
            ->where('user_id', $userId)
            ->exists();

        return $inWorkspace && $inProject ? $userId : null;
    }
}