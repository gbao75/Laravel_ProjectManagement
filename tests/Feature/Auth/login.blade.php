@extends('layouts.guest')

@section('title', 'Đăng nhập')

@section('content')
    <div class="auth-heading">
        <h1>Đăng nhập</h1>

        <p class="muted">
            Tiếp tục quản lý công việc của bạn.
        </p>
    </div>

    @if (session('status'))
        <p class="auth-notice" role="status">
            {{ session('status') }}
        </p>
    @endif

    <form method="POST" action="{{ url('/login') }}" class="auth-form">
        @csrf

        <div class="form-group">
            <label for="email">Email</label>

            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="username"
                required
                autofocus
                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                @error('email') aria-describedby="email-error" @enderror
            >

            @error('email')
                <p id="email-error" class="field-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu</label>

            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                @error('password') aria-describedby="password-error" @enderror
            >

            @error('password')
                <p id="password-error" class="field-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <label class="checkbox-row" for="remember">
            <input
                id="remember"
                name="remember"
                type="checkbox"
                value="1"
                @checked(old('remember'))
            >

            <span>Ghi nhớ đăng nhập</span>
        </label>

        <button type="submit" class="primary-button">
            Đăng nhập
        </button>

        <p class="auth-switch">
            Chưa có tài khoản?

            <a href="{{ route('register') }}">
                Đăng ký
            </a>
        </p>
    </form>
@endsection