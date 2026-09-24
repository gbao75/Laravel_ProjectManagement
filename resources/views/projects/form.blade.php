@extends('layouts.app')

@section('title', $project->exists ? 'Sửa dự án' : 'Tạo dự án')
@section('page-title', $project->exists ? 'Sửa dự án' : 'Tạo dự án')

@section('content')
    <div class="page-heading">
        <h1>{{ $project->exists ? 'Sửa dự án' : 'Tạo dự án' }}</h1>
        <p class="muted">{{ $workspace->name }}</p>
    </div>

    <section class="panel project-form-panel">
        <form
            method="POST"
            action="{{ $project->exists
                ? route('workspaces.projects.update', [
                    'workspace' => $workspace,
                    'project' => $project,
                ])
                : route('workspaces.projects.store', $workspace) }}"
            class="auth-form project-form"
        >
            @csrf

            @if ($project->exists)
                @method('PUT')
            @endif

            <div class="form-group">
                <label for="name">Tên dự án</label>

                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $project->name) }}"
                    maxlength="120"
                    required
                    autofocus
                >

                @error('name')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Mô tả</label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    maxlength="5000"
                >{{ old('description', $project->description) }}</textarea>

                @error('description')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="status">Trạng thái</label>

                <select id="status" name="status" required>
                    @foreach (\App\Models\Project::STATUSES as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(old('status', $project->status) === $value)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('status')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="project-date-fields">
                <div class="form-group">
                    <label for="start_date">Ngày bắt đầu</label>

                    <input
                        id="start_date"
                        name="start_date"
                        type="date"
                        value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"
                    >

                    @error('start_date')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="due_date">Hạn hoàn thành</label>

                    <input
                        id="due_date"
                        name="due_date"
                        type="date"
                        value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}"
                    >

                    @error('due_date')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button type="submit" class="primary-button">
                {{ $project->exists ? 'Lưu thay đổi' : 'Tạo dự án' }}
            </button>

            <a href="{{ route('workspaces.projects.index', $workspace) }} " class="primary-button">
                Quay lại danh sách
            </a>
        </form>
    </section>
@endsection