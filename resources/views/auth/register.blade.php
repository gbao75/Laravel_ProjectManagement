@extends('layouts.guest')

@section('title', 'Đăng ký')

@section('content')
    <div class="auth-heading">
        <h1>Tạo tài khoản</h1>
        <p class="muted">
            Bắt đầu quản lý công việc của bạn với TaskFlow.
        </p>
    </div>

    <form method="POST" action="{{ url('/register') }}" class="auth-form">
        @csrf

        <div class="form-group">
            <label for="name">Họ và tên</label>

            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                maxlength="100"
                autocomplete="name"
                required
                autofocus
                aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                @error('name') aria-describedby="name-error" @enderror
            >

            @error('name')
                <p id="name-error" class="field-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email</label>

            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                maxlength="255"
                autocomplete="email"
                required
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
                minlength="8"
                autocomplete="new-password"
                required
                aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                aria-describedby="password-hint{{ $errors->has('password') ? ' password-error' : '' }}"
            >

            <p id="password-hint" class="field-hint">
                Ít nhất 8 ký tự, có chữ cái và chữ số.
            </p>

            @error('password')
                <p id="password-error" class="field-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">
                Xác nhận mật khẩu
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
            Tạo tài khoản
        </button>

        <p class="auth-switch">
            Đã có tài khoản?
            <a href="{{ route('login') }}">
                Đăng nhập
            </a>
        </p>
    </form>
@endsection