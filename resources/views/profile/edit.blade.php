@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')
@section('page-title', 'Hồ sơ cá nhân')

@section('content')
    <section class="page-heading">
        <h1>Hồ sơ cá nhân</h1>
        <p class="muted">
            Quản lý thông tin tài khoản và mật khẩu.
        </p>
    </section>

    @if (session('status') === 'profile-updated')
        <p class="auth-notice" role="status">
            Đã cập nhật thông tin cá nhân.
        </p>
    @elseif (session('status') === 'avatar-updated')
        <p class="auth-notice" role="status">
            Đã cập nhật ảnh đại diện.
        </p>
    @elseif (session('status') === 'password-updated')
        <p class="auth-notice" role="status">
            Đã đổi mật khẩu.
        </p>
    @endif

    <div class="profile-grid">
        <section class="panel profile-section">
            <h2>Ảnh đại diện</h2>

            @if ($user->avatar_path)
                <img
                    class="profile-avatar"
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) }}"
                    alt="Ảnh đại diện của {{ $user->name }}"
                    width="96"
                    height="96"
                >
            @else
                <div class="profile-avatar avatar-placeholder">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
            @endif

            @if ($errors->getBag('avatar')->any())
                <div class="field-error" role="alert">
                    @foreach ($errors->getBag('avatar')->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('profile.avatar.update') }}"
                enctype="multipart/form-data"
                class="auth-form"
            >
                @csrf

                <div class="form-group">
                    <label for="avatar">Chọn ảnh mới</label>

                    <input
                        id="avatar"
                        name="avatar"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp"
                        required
                    >

                    <p class="field-hint">
                        JPG, PNG hoặc WebP. Tối đa 2 MB và 2048 × 2048 px.
                    </p>
                </div>

                <button type="submit" class="primary-button">
                    Lưu ảnh đại diện
                </button>
            </form>
        </section>

        <section class="panel profile-section">
            <h2>Thông tin cá nhân</h2>

            @if (! $user->hasVerifiedEmail())
                <p class="field-hint">
                    Email hiện tại chưa được xác minh.
                    <a href="{{ route('verification.notice') }}">
                        Đi đến trang xác minh
                    </a>
                </p>
            @endif

            @if ($errors->getBag('profile')->any())
                <div class="field-error" role="alert">
                    @foreach ($errors->getBag('profile')->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('profile.update') }}"
                class="auth-form"
            >
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Họ tên</label>

                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $user->name) }}"
                        maxlength="100"
                        autocomplete="name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', $user->email) }}"
                        maxlength="255"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="profile-current-password">
                        Mật khẩu hiện tại
                    </label>

                    <input
                        id="profile-current-password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                    >

                    <p class="field-hint">
                        Chỉ bắt buộc khi đổi email.
                    </p>
                </div>

                <button type="submit" class="primary-button">
                    Lưu thông tin
                </button>
            </form>
        </section>

        <section class="panel profile-section profile-password">
            <h2>Đổi mật khẩu</h2>

            @if ($errors->getBag('password')->any())
                <div class="field-error" role="alert">
                    @foreach ($errors->getBag('password')->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('profile.password.update') }}"
                class="auth-form"
            >
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="current-password">
                        Mật khẩu hiện tại
                    </label>

                    <input
                        id="current-password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="new-password">Mật khẩu mới</label>

                    <input
                        id="new-password"
                        name="password"
                        type="password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <p class="field-hint">
                        Ít nhất 8 ký tự, có chữ cái và chữ số.
                    </p>
                </div>

                <div class="form-group">
                    <label for="password-confirmation">
                        Xác nhận mật khẩu mới
                    </label>

                    <input
                        id="password-confirmation"
                        name="password_confirmation"
                        type="password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" class="primary-button">
                    Đổi mật khẩu
                </button>
            </form>
        </section>
    </div>
@endsection