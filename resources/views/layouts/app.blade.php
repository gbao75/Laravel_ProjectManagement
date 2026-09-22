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

                {{-- @if (isset($currentWorkspace))
                    <a
                        href="{{ route('workspaces.members.index', $currentWorkspace) }}"
                        class="nav-item {{ request()->routeIs('workspaces.members.*') ? 'is-active' : '' }}"
                        @if (request()->routeIs('workspaces.members.*'))
                            aria-current="page"
                        @endif
                    >
                        Thành viên
                    </a>
                @else
                    <span class="nav-item is-disabled" aria-disabled="true">
                        Thành viên
                        <small>Chọn workspace</small>
                    </span>
                @endif --}}

                <span class="nav-item is-disabled" aria-disabled="true">
                    Công việc của tôi
                    <small>Sắp có</small>
                </span>

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
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>