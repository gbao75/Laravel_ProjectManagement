@extends('layouts.app')

@section('title', 'Sửa workspace')
@section('page-title', 'Sửa workspace')

@section('content')
    <div class="page-heading">
        <h1>Sửa workspace</h1>

        <p class="muted">
            Cập nhật thông tin của {{ $workspace->name }}.
        </p>
    </div>

    <section class="panel workspace-form-panel">
        <form
            method="POST"
            action="{{ route('workspaces.update', $workspace) }}"
            class="auth-form"
        >
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Tên workspace</label>

                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $workspace->name) }}"
                    maxlength="120"
                    required
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
                    maxlength="2000"
                >{{ old('description', $workspace->description) }}</textarea>

                @error('description')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="primary-button">
                Lưu thay đổi
            </button>

            <a href="{{ route('workspaces.show', $workspace) }}">
                Hủy và quay lại
            </a>
        </form>
    </section>
@endsection