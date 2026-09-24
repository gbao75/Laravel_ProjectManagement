<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDeadlineReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class SendTaskDeadlineReminders extends Command
{
    protected $signature = 'tasks:send-deadline-reminders
                            {--task= : Chỉ kiểm tra một task ID}';

    protected $description = 'Gửi thông báo nhắc hạn và quá hạn công việc';

    public function handle(): int
    {
        $taskOption = $this->option('task');

        if (
            $taskOption !== null
            && (
                ! ctype_digit((string) $taskOption)
                || (int) $taskOption < 1
            )
        ) {
            $this->error('Task ID phải là số nguyên dương.');

            return self::FAILURE;
        }

        $timezone = config(
            'task_reminders.timezone',
            'Asia/Ho_Chi_Minh'
        );

        // Giữ cùng mốc ngày cho toàn bộ lần chạy.
        $today = CarbonImmutable::now($timezone)->startOfDay();

        $tomorrow = $today->addDay()->toDateString();

        $query = Task::query()
            ->select('id')
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $tomorrow)
            ->where('status', '!=', 'completed');

        if ($taskOption !== null) {
            $query->whereKey((int) $taskOption);
        }

        $sent = 0;
        $failed = 0;

        $query->chunkById(100, function ($tasks) use (
            $today,
            &$sent,
            &$failed
        ) {
            foreach ($tasks as $candidate) {
                try {
                    if ($this->sendForTask((int) $candidate->id, $today)) {
                        $sent++;
                    }
                } catch (Throwable $exception) {
                    $failed++;

                    report($exception);

                    $this->error(
                        'Không xử lý được task #'.$candidate->id
                        .'. Xem storage/logs/laravel.log.'
                    );
                }
            }
        });

        $this->info("Đã gửi {$sent} thông báo mới.");

        if ($failed > 0) {
            $this->error("Có {$failed} task xử lý thất bại.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function sendForTask(
        int $taskId,
        CarbonImmutable $today
    ): bool {
        return DB::transaction(function () use ($taskId, $today) {
            // Khóa task để hai lần chạy không cùng gửi cho một task.
            $task = Task::query()
                ->whereKey($taskId)
                ->lockForUpdate()
                ->first();

            if (
                ! $task
                || $task->status === 'completed'
                || $task->due_date === null
            ) {
                return false;
            }

            $dueDate = $task->due_date->toDateString();

            $todayDate = $today->toDateString();
            $tomorrowDate = $today->addDay()->toDateString();

            if ($dueDate > $tomorrowDate) {
                return false;
            }

            $kind = match (true) {
                $dueDate < $todayDate => 'overdue',
                $dueDate === $todayDate => 'due_today',
                default => 'due_tomorrow',
            };

            // Task cá nhân: nhắc người tạo.
            // Task dự án: nhắc người được giao.
            $recipientId = $task->project_id === null
                ? $task->created_by
                : $task->assigned_to;

            if ($recipientId === null) {
                return false;
            }

            $recipient = User::query()->find($recipientId);

            if (! $recipient || ! $recipient->hasVerifiedEmail()) {
                return false;
            }

            $preferenceKey = $kind === 'overdue'
                ? 'overdue'
                : 'deadlines';

            if (! $recipient->wantsTaskNotification($preferenceKey)) {
                return false;
            }

            $task->load('project.workspace');

            // Không gửi cho người đã mất quyền truy cập.
            if (Gate::forUser($recipient)->denies('view', $task)) {
                return false;
            }

            $identity = [
                'task_id' => $task->id,
                'user_id' => $recipient->id,
                'due_date' => $dueDate,
                'kind' => $kind,
            ];

            $alreadySent = DB::table('task_reminder_deliveries')
                ->where($identity)
                ->exists();

            if ($alreadySent) {
                return false;
            }

            DB::table('task_reminder_deliveries')->insert([
                ...$identity,
                'sent_at' => now(),
            ]);

            // Ghi notification trong cùng transaction.
            $recipient->notify(
                new TaskDeadlineReminder($task, $kind)
            );

            return true;
        }, 3);
    }
}