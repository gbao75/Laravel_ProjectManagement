@extends('layouts.guest')

@section('title', 'Quên mật khẩu')

@section('content')
    <div class="auth-heading">
        <h1>Quên mật khẩu</h1>

        <p class="muted">
            Nhập email tài khoản để nhận liên kết đặt lại mật khẩu.
        </p>
    </div>

    @if (session('status'))
        <p class="auth-notice" role="status">
            {{ session('status') }}
        </p>
    @endif

    <form
        method="POST"
        action="{{ route('password.email') }}"
        class="auth-form"
    >
        @csrf

        <div class="form-group">
            <label for="email">Email</label>

            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
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

        <button type="submit" class="primary-button">
            Gửi liên kết đặt lại mật khẩu
        </button>

        <a href="{{ route('login') }}" class="auth-back-link">
            Quay lại đăng nhập
        </a>
    </form>
@endsection