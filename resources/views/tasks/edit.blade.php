@extends('layouts.app')

@section('title', 'Sửa công việc')
@section('page-title', 'Sửa công việc')

@section('content')
    <div class="task-page">
        <div class="task-heading">
            <div>
                <h1>Sửa công việc</h1>
                <p>Dự án: {{ $project->name }}</p>
            </div>

            <a href="{{ route('workspaces.projects.tasks.index', [
                'workspace' => $workspace,
                'project' => $project,
            ]) }}">
                ← Danh sách công việc
            </a>
        </div>

        <section class="task-panel task-form-panel">
            @if ($errors->any())
                <div class="task-errors" role="alert">
                    <strong>Vui lòng kiểm tra thông tin:</strong>

                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('workspaces.projects.tasks.update', [
                    'workspace' => $workspace,
                    'project' => $project,
                    'task' => $task,
                ]) }}"
                class="task-form"
            >
                @csrf
                @method('PUT')

                <div class="task-field">
                    <label for="title">Tiêu đề công việc *</label>

                    <input
                        id="title"
                        name="title"
                        type="text"
                        value="{{ old('title', $task->title) }}"
                        maxlength="200"
                        required
                    >
                </div>

                <div class="task-field">
                    <label for="description">Mô tả</label>

                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        maxlength="10000"
                    >{{ old('description', $task->description) }}</textarea>
                </div>

                <div class="task-form-grid">
                    <div class="task-field">
                        <label for="priority">Độ ưu tiên *</label>

                        <select id="priority" name="priority" required>
                            @foreach (\App\Models\Task::PRIORITIES as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        old('priority', $task->priority) === $value
                                    )
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="task-field">
                        <label for="due_date">Hạn hoàn thành</label>

                        <input
                            id="due_date"
                            name="due_date"
                            type="date"
                            value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}"
                        >
                    </div>
                </div>

                @php
                    $selectedAssignee = old('assigned_to', $task->assigned_to);

                    $previousAssigneeUnavailable =
                        $task->assigned_to !== null
                        && ! $assignees->contains('id', $task->assigned_to);
                @endphp

                <div class="task-field">
                    <label for="assigned_to">Người thực hiện</label>

                    <select id="assigned_to" name="assigned_to">
                        <option
                            value=""
                            @selected($selectedAssignee === null || $selectedAssignee === '')
                        >
                            Chưa giao
                        </option>

                        @if ($previousAssigneeUnavailable)
                            <option
                                value="{{ $task->assigned_to }}"
                                @selected(
                                    (string) $selectedAssignee
                                    === (string) $task->assigned_to
                                )
                            >
                                {{ $task->assignee?->name ?? 'Người nhận cũ' }}
                                — không còn thuộc dự án
                            </option>
                        @endif

                        @foreach ($assignees as $assignee)
                            <option
                                value="{{ $assignee->id }}"
                                @selected(
                                    (string) $selectedAssignee
                                    === (string) $assignee->id
                                )
                            >
                                {{ $assignee->name }} — {{ $assignee->email }}
                            </option>
                        @endforeach
                    </select>

                    @if ($previousAssigneeUnavailable)
                        <small>
                            Người nhận cũ không còn hợp lệ.
                            Hãy chọn người khác hoặc “Chưa giao” trước khi lưu.
                        </small>
                    @else
                        <small>
                            Chỉ giao việc cho thành viên hiện tại của dự án.
                        </small>
                    @endif
                </div>

                <div class="task-actions">
                    <button type="submit" class="task-primary">
                        Lưu thay đổi
                    </button>

                    <a href="{{ route('workspaces.projects.tasks.index', [
                        'workspace' => $workspace,
                        'project' => $project,
                    ]) }}">
                        Hủy
                    </a>
                </div>
            </form>
        </section>
    </div>
@endsection