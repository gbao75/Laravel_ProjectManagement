@extends('layouts.guest')

@section('title', 'Đặt lại mật khẩu')

@section('content')
    <div class="auth-heading">
        <h1>Đặt lại mật khẩu</h1>

        <p class="muted">
            Mật khẩu mới cần ít nhất 8 ký tự, có chữ cái và chữ số.
        </p>
    </div>

    @if ($errors->any())
        <div class="field-error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form
        method="POST"
        action="{{ url('/reset-password') }}"
        class="auth-form"
    >
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <label for="email">Email</label>

            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $email) }}"
                autocomplete="email"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu mới</label>

            <input
                id="password"
                name="password"
                type="password"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="form-group">
            <label for="password_confirmation">
                Xác nhận mật khẩu mới
            </label>

            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <button type="submit" class="primary-button">
            Lưu mật khẩu mới
        </button>

        <a href="{{ route('password.request') }}" class="auth-back-link">
            Yêu cầu liên kết mới
        </a>
    </form>
@endsection