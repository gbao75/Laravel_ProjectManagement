<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}?v=2">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ProjectManagement') | ProjectManagement</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a href="{{ route('dashboard') }}" class="brand">
                <span class="brand-icon" aria-hidden="true">PM</span>
                <span>ProjMgmt</span>
            </a>

            <p class="sidebar-label">KHÔNG GIAN LÀM VIỆC</p>

            @auth
                @include('workspaces.partials.switcher')
            @endauth

            <nav class="navigation" aria-label="Điều hướng chính">
                <a
                    href="{{ route('dashboard') }}"
                    class="nav-item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
                    @if (request()->routeIs('dashboard')) aria-current="page" @endif
                >
                    Tổng quan
                </a>

                <a
                    href="{{ route('workspaces.index') }}"
                    class="nav-item {{
                        request()->routeIs('workspaces.*')
                        && ! request()->routeIs('workspaces.members.*')
                        && ! request()->routeIs('workspaces.projects.*')
                            ? 'is-active'
                            : ''
                    }}"
                    @if (
                        request()->routeIs('workspaces.*')
                        && ! request()->routeIs('workspaces.members.*')
                        && ! request()->routeIs('workspaces.projects.*')
                    )
                        aria-current="page"
                    @endif
                >
                    Workspace
                </a>            
                

                @if (isset($currentWorkspace))
                    <a
                        href="{{ route('workspaces.projects.index', $currentWorkspace) }}"
                        class="nav-item {{ request()->routeIs('workspaces.projects.*') ? 'is-active' : '' }}"
                    >
                        Dự án
                    </a>
                @else
                    <span class="nav-item is-disabled" aria-disabled="true">
                        Dự án
                        <small>Chọn workspace</small>
                    </span>
                @endif

             
                <a
                    href="{{ route('my-tasks.index') }}"
                    class="nav-item {{
                        request()->routeIs('my-tasks.*', 'personal-tasks.*')
                            ? 'is-active'
                            : ''
                    }}"
                    @if (request()->routeIs('my-tasks.*', 'personal-tasks.*'))
                        aria-current="page"
                    @endif
                >
                    Công việc của tôi
                </a>

                <a
                    href="{{ route('time-entries.index') }}"
                    class="nav-item {{ request()->routeIs('time-entries.*') ? 'is-active' : '' }}"
                    @if (request()->routeIs('time-entries.*')) aria-current="page" @endif
                >
                    Thời gian làm việc
                </a>

                <a
                    href="{{ route('my-invitations.index') }}"
                    class="nav-item {{ request()->routeIs('my-invitations.*') ? 'is-active' : '' }}"
                    @if (request()->routeIs('my-invitations.*'))
                        aria-current="page"
                    @endif
                >
                    Lời mời của tôi
                </a>
            </nav>

            <p class="sidebar-footer">
                Sắp xếp công việc, theo dõi tiến độ.
            </p>
        </aside>

        <div class="main-column">
            <header class="topbar">
                <div class="topbar-left">
                    <button
                        type="button"
                        class="menu-button"
                        id="menu-toggle"
                        aria-controls="sidebar"
                        aria-expanded="false"
                    >
                        Menu
                    </button>

                    <span class="breadcrumb">
                        Workspace / @yield('page-title', 'Tổng quan')
                    </span>
                </div>

                <div class="account-actions">
                    @auth
                        @php
                            $unreadNotificationCount = auth()->user()
                                ->unreadNotifications()
                                ->count();
                        @endphp

                        <a
                            href="{{ route('notifications.index') }}"
                            class="notification-bell"
                            aria-label="Thông báo, {{ $unreadNotificationCount }} chưa đọc"
                            title="Thông báo"
                        >
                            <svg
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                                <path d="M10 21h4"/>
                            </svg>

                            @if ($unreadNotificationCount > 0)
                                <span class="notification-badge">
                                    {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                                </span>
                            @endif
                        </a>

                        <a
                            href="{{ route('profile.edit') }}"
                            class="topbar-avatar {{ request()->routeIs('profile.*') ? 'is-active' : '' }}"
                            title="Hồ sơ cá nhân — {{ auth()->user()->name }}"
                            aria-label="Mở hồ sơ cá nhân của {{ auth()->user()->name }}"
                            @if (request()->routeIs('profile.*')) aria-current="page" @endif
                        >
                            @if (auth()->user()->avatar_path)
                                <img
                                    src="{{ asset('storage/' . auth()->user()->avatar_path) }}"
                                    alt=""
                                    width="40"
                                    height="40"
                                >
                            @else
                                <span aria-hidden="true">
                                    {{ \Illuminate\Support\Str::upper(
                                        \Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)
                                    ) }}
                                </span>
                            @endif
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit" class="logout-button">
                                Đăng xuất
                            </button>
                        </form>
                    @endauth

        @guest
            <a href="{{ route('login') }}" class="account-placeholder">
                Đăng nhập
            </a>
        @endguest
    </div>
            </header>

            <main class="page-content" id="main-content">
                @auth
                    @php
                        $activeTimer = \App\Models\TaskTimeEntry::query()
                            ->where('user_id', auth()->id())
                            ->whereNull('ended_at')
                            ->with('task')
                            ->first();
                    @endphp

                    @if ($activeTimer)
                        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                            <div class="min-w-0">
                                <p class="text-sm text-indigo-700">Đang ghi thời gian</p>

                                <p class="break-words font-semibold">
                                    {{ $activeTimer->task?->title ?? 'Công việc' }}
                                </p>

                                <span
                                    class="font-mono text-indigo-700"
                                    data-task-timer
                                    data-started-at="{{ $activeTimer->started_at->toIso8601String() }}"
                                >
                                    Đang tính…
                                </span>
                            </div>

                            <form
                                method="POST"
                                action="{{ route('time-entries.stop', $activeTimer->id) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="rounded-lg bg-rose-600 px-4 py-2 font-semibold text-white"
                                >
                                    Dừng và lưu
                                </button>
                            </form>
                        </div>
                    @endif
                @endauth

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>