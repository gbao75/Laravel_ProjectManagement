<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePersonalTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PersonalTaskController extends Controller
{
    public function create(): View
    {
        $task = new Task([
            'priority' => 'medium',
        ]);

        $task->status = 'todo';

        return view('personal-tasks.form', compact('task'));
    }

    public function store(
        SavePersonalTaskRequest $request
    ): RedirectResponse {
        $task = new Task();

        $this->fillFields($task, $request->validated());

        $task->project_id = null;
        $task->created_by = $request->user()->id;
        $task->assigned_to = $request->user()->id;

        $task->save();

        return to_route('my-tasks.index', ['scope' => 'personal'])
            ->with('status', 'Đã tạo công việc cá nhân.');
    }

    public function edit(Task $task): View
    {
        abort_unless($task->project_id === null, 404);

        Gate::authorize('update', $task);

        return view('personal-tasks.form', compact('task'));
    }

    public function update(
        SavePersonalTaskRequest $request,
        Task $task
    ): RedirectResponse {
        abort_unless($task->project_id === null, 404);

        Gate::authorize('update', $task);

        $this->fillFields($task, $request->validated());
        $task->save();

        return to_route('my-tasks.index', ['scope' => 'personal'])
            ->with('status', 'Đã cập nhật công việc cá nhân.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        abort_unless($task->project_id === null, 404);

        Gate::authorize('delete', $task);

        $task->delete();

        return to_route('my-tasks.index', ['scope' => 'personal'])
            ->with('status', 'Đã xóa công việc cá nhân.');
    }

    private function fillFields(Task $task, array $data): void
    {
        $task->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'],
            'due_date' => $data['due_date'] ?? null,
        ]);

        $task->status = $data['status'];
    }
}