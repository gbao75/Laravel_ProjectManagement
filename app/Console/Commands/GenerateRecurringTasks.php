<?php

namespace App\Console\Commands;

use App\Models\TaskRecurrence;
use App\Services\RunTaskRecurrence;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class GenerateRecurringTasks extends Command
{
    protected $signature = 'tasks:generate-recurring
                            {--rule= : Chỉ chạy một lịch lặp}';

    protected $description = 'Tạo công việc cho các lịch lặp đã đến ngày';

    public function handle(RunTaskRecurrence $runner): int
    {
        $ruleOption = $this->option('rule');

        if (
            $ruleOption !== null
            && (
                ! ctype_digit((string) $ruleOption)
                || (int) $ruleOption < 1
            )
        ) {
            $this->error('Rule ID phải là số nguyên dương.');

            return self::FAILURE;
        }

        $today = CarbonImmutable::now(
            config('task_reminders.timezone', 'Asia/Ho_Chi_Minh')
        )->toDateString();

        $query = TaskRecurrence::query()
            ->where('is_active', true)
            ->where('next_run_on', '<=', $today);

        if ($ruleOption !== null) {
            $query->whereKey((int) $ruleOption);
        }

        $created = 0;
        $failed = 0;

        $query->chunkById(100, function ($rules) use (
            $runner,
            &$created,
            &$failed
        ) {
            foreach ($rules as $rule) {
                try {
                    if ($runner->run((int) $rule->id)) {
                        $created++;
                    }
                } catch (Throwable $exception) {
                    $failed++;

                    report($exception);

                    $this->error(
                        'Lịch #'.$rule->id.' thất bại. Xem laravel.log.'
                    );
                }
            }
        });

        $this->info("Đã tạo {$created} công việc.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}