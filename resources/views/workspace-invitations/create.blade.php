@extends('layouts.app')

@section('title', 'Mời thành viên')
@section('page-title', 'Mời thành viên')

@section('content')
    <div class="page-heading">
        <h1>Mời thành viên</h1>

        <p class="muted">
            Mời người khác tham gia {{ $workspace->name }}.
        </p>
    </div>

    <section class="panel workspace-form-panel">
        <form
            method="POST"
            action="{{ route('workspaces.invitations.store', $workspace) }}"
            class="auth-form"
        >
            @csrf

            <div class="form-group">
                <label for="email">Email người được mời</label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Ví dụ: thanhvien@example.com"
                    maxlength="255"
                    required
                    autofocus
                >

                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror

                <p class="field-hint">
                    Người nhận chưa có tài khoản vẫn có thể được mời.
                </p>
            </div>

            <div class="invitation-summary">
                <p><strong>Vai trò:</strong> Thành viên</p>
                <p><strong>Thời hạn:</strong> 7 ngày</p>
            </div>

            <button type="submit" class="primary-button">
                Gửi lời mời
            </button>

            <a href="{{ route('workspaces.show', $workspace) }}">
                Quay lại workspace
            </a>
        </form>
    </section>
@endsection