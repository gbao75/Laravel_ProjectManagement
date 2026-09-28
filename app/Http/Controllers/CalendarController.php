<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\MyWorkQuery;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        return view('calendar.index');
    }

    public function events(
        Request $request,
        MyWorkQuery $work
    ): JsonResponse {
        $data = $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],

            'end' => [
                'required',
                'date_format:Y-m-d',
                'after:start',
            ],

            'status' => [
                'nullable',
                Rule::in(array_keys(Task::STATUSES)),
            ],
        ]);

        $start = CarbonImmutable::parse($data['start']);
        $end = CarbonImmutable::parse($data['end']);

        if ($start->diffInDays($end) > 62) {
            throw ValidationException::withMessages([
                'end' => 'Chỉ được xem tối đa 62 ngày mỗi lần.',
            ]);
        }

        $query = $work->tasks($request->user())
            ->with('project.workspace')
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $data['start'])
            ->where('due_date', '<', $data['end']);

        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        $tasks = $query
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit(1001)
            ->get();

        if ($tasks->count() > 1000) {
            throw ValidationException::withMessages([
                'calendar' => 'Có quá nhiều công việc. Hãy chuyển sang xem tuần hoặc lọc trạng thái.',
            ]);
        }

        $colors = [
            'todo' => '#6366f1',
            'in_progress' => '#d97706',
            'completed' => '#059669',
        ];

        $events = $tasks->map(function (Task $task) use ($colors) {
            $url = $task->project_id === null
                ? route('personal-tasks.edit', $task)
                : route('workspaces.projects.tasks.show', [
                    'workspace' => $task->project->workspace,
                    'project' => $task->project,
                    'task' => $task,
                ]);

            $color = $colors[$task->status] ?? '#64748b';

            return [
                'id' => (string) $task->id,
                'title' => $task->title,
                'start' => $task->due_date->toDateString(),
                'allDay' => true,
                'url' => $url,
                'backgroundColor' => $color,
                'borderColor' => $color,
            ];
        })->values();

        return response()
            ->json($events)
            ->header('Cache-Control', 'no-store');
    }
}