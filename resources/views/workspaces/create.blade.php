@extends('layouts.app')

@section('title', 'Tạo workspace')
@section('page-title', 'Tạo workspace')

@section('content')
    <div class="page-heading">
        <h1>Tạo workspace</h1>

        <p class="muted">
            Bạn sẽ trở thành chủ sở hữu của workspace này.
        </p>
    </div>

    <section class="panel workspace-form-panel">
        <form
            method="POST"
            action="{{ route('workspaces.store') }}"
            class="auth-form"
        >
            @csrf

            <div class="form-group">
                <label for="name">Tên workspace</label>

                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    maxlength="120"
                    placeholder="Ví dụ: Đội phát triển website"
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
                    maxlength="2000"
                    placeholder="Workspace này được dùng để làm gì?"
                >{{ old('description') }}</textarea>

                @error('description')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="primary-button">
                Tạo workspace
            </button>

            <a href="{{ route('workspaces.index') }}">
                Quay lại danh sách
            </a>
        </form>
    </section>
@endsection