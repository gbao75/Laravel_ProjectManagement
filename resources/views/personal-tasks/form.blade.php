@extends('layouts.app')

@section('title', $task->exists ? 'Sửa việc cá nhân' : 'Tạo việc cá nhân')
@section('page-title', 'Công việc cá nhân')

@section('content')
    <div class="task-page">
        <div class="task-heading">
            <div>
                <h1>
                    {{ $task->exists ? 'Sửa công việc cá nhân' : 'Tạo công việc cá nhân' }}
                </h1>
                <p>Chỉ bạn có quyền truy cập công việc này.</p>
            </div>

            <a href="{{ route('my-tasks.index', ['scope' => 'personal']) }}">
                ← Công việc của tôi
            </a>
        </div>

        <section class="task-panel task-form-panel">
            @if ($errors->any())
                <div class="task-errors" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ $task->exists
                    ? route('personal-tasks.update', $task)
                    : route('personal-tasks.store') }}"
                class="task-form"
            >
                @csrf

                @if ($task->exists)
                    @method('PUT')
                @endif

                <div class="task-field">
                    <label for="title">Tiêu đề *</label>
                    <input
                        id="title"
                        name="title"
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
                        <label for="priority">Độ ưu tiên</label>
                        <select id="priority" name="priority" required>
                            @foreach (\App\Models\Task::PRIORITIES as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('priority', $task->priority) === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="task-field">
                        <label for="status">Trạng thái</label>
                        <select id="status" name="status" required>
                            @foreach (\App\Models\Task::STATUSES as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('status', $task->status) === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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

                <div class="task-actions">
                    <button type="submit" class="task-primary">
                        {{ $task->exists ? 'Lưu thay đổi' : 'Tạo công việc' }}
                    </button>

                    <a href="{{ route('my-tasks.index', ['scope' => 'personal']) }}">
                        Hủy
                    </a>
                </div>
            </form>
            @if ($task->exists)
                @include('tasks.partials.time-tracking')

                @include('tasks.partials.recurrence')
            @endif
        </section>
    </div>
@endsection