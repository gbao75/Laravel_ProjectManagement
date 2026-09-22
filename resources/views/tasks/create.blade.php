@extends('layouts.app')

@section('title', 'Tạo công việc')
@section('page-title', 'Tạo công việc')

@section('content')
    <div class="task-page">
        <div class="task-heading">
            <div>
                <h1>Tạo công việc</h1>
                <p>Dự án: {{ $project->name }}</p>
            </div>

            <a href="{{ route('workspaces.projects.tasks.index', [
                'workspace' => $workspace,
                'project' => $project,
            ]) }}" class="primary-button">
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
                action="{{ route('workspaces.projects.tasks.store', [
                    'workspace' => $workspace,
                    'project' => $project,
                ]) }}"
                class="task-form"
            >
                @csrf

                <div class="task-field">
                    <label for="title">Tiêu đề công việc *</label>

                    <input
                        id="title"
                        name="title"
                        type="text"
                        value="{{ old('title') }}"
                        maxlength="200"
                        required
                        placeholder="Ví dụ: Thiết kế trang đăng nhập"
                    >
                </div>

                <div class="task-field">
                    <label for="description">Mô tả</label>

                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        maxlength="10000"
                        placeholder="Mô tả yêu cầu và kết quả cần đạt..."
                    >{{ old('description') }}</textarea>
                </div>

                <div class="task-form-grid">
                    <div class="task-field">
                        <label for="priority">Độ ưu tiên *</label>

                        <select id="priority" name="priority" required>
                            @foreach (\App\Models\Task::PRIORITIES as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('priority', 'medium') === $value)
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
                            value="{{ old('due_date') }}"
                        >
                    </div>
                </div>

                <div class="task-field">
                    <label for="assigned_to">Người thực hiện</label>

                    <select id="assigned_to" name="assigned_to">
                        <option value="">Chưa giao</option>

                        @foreach ($assignees as $assignee)
                            <option
                                value="{{ $assignee->id }}"
                                @selected(
                                    (string) old('assigned_to')
                                    === (string) $assignee->id
                                )
                            >
                                {{ $assignee->name }} — {{ $assignee->email }}
                            </option>
                        @endforeach
                    </select>

                    <small>
                        Chỉ hiển thị thành viên của dự án.
                        Có thể để trống và giao việc sau.
                    </small>

                    @if ($assignees->isEmpty())
                        <a href="{{ route('workspaces.projects.members.index', [
                            'workspace' => $workspace,
                            'project' => $project,
                        ]) }}">
                            Thêm thành viên vào dự án →
                        </a>
                    @endif
                </div>

                <div class="task-actions">
                    <button type="submit" class="task-primary">
                        Tạo công việc
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