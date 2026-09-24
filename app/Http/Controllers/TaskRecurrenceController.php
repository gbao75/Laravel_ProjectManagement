<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskRecurrence;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TaskRecurrenceController extends Controller
{
    public function save(
        Request $request,
        Task $task
    ): RedirectResponse {
        Gate::authorize('update', $task);

        $timezone = config(
            'task_reminders.timezone',
            'Asia/Ho_Chi_Minh'
        );

        $today = CarbonImmutable::now($timezone)->toDateString();

        $data = $request->validate([
            'frequency' => [
                'required',
                Rule::in(array_keys(TaskRecurrence::FREQUENCIES)),
            ],

            'next_run_on' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$today,
            ],

            'due_after_days' => [
                'required',
                'integer',
                'min:0',
                'max:365',
            ],
        ], [
            'next_run_on.after_or_equal' =>
                'Ngày tạo tiếp theo phải từ hôm nay trở đi.',
            'due_after_days.max' =>
                'Hạn hoàn thành tối đa 365 ngày sau ngày tạo theo lịch.',
        ]);

        DB::transaction(function () use ($request, $task, $data) {
            // Cùng thứ tự khóa với luồng tạo task của Kanban.
            if ($task->project_id !== null) {
                Project::query()
                    ->whereKey($task->project_id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $source = Task::query()
                ->whereKey($task->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('update', $source);

            $rule = $source->recurrence()
                ->lockForUpdate()
                ->first();

            if (! $rule) {
                $rule = new TaskRecurrence();
                $rule->task_id = $source->id;
            }

            $rule->created_by = $request->user()->id;
            $rule->frequency = $data['frequency'];
            $rule->next_run_on = $data['next_run_on'];

            $rule->anchor_day = (int) substr(
                $data['next_run_on'],
                8,
                2
            );

            $rule->due_after_days = (int) $data['due_after_days'];
            $rule->is_active = true;
            $rule->paused_reason = null;

            $rule->save();
        }, 3);

        return back()->with('status', 'Đã lưu và bật lịch lặp.');
    }

    public function pause(Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        DB::transaction(function () use ($task) {
            $rule = $task->recurrence()
                ->lockForUpdate()
                ->firstOrFail();

            $rule->is_active = false;
            $rule->paused_reason = 'Đã tạm dừng thủ công.';

            $rule->save();
        }, 3);

        return back()->with('status', 'Đã tạm dừng lịch lặp.');
    }
}