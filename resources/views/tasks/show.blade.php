@extends('layouts.app')

@section('title', $task->title)
@section('page-title', 'Chi tiết công việc')

@section('content')
    @php
        $routeParams = [
            'workspace' => $workspace,
            'project' => $project,
            'task' => $task,
        ];

        $buttonClass = 'inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700';
    @endphp
    @include('tasks.partials.time-tracking')
    @include('tasks.partials.recurrence')
    <div class="space-y-6">
        @if (session('status'))
            <div role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl bg-red-50 p-4 text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="mb-2 text-sm text-slate-500">
                    {{ $project->name }}
                </p>

                <h1 class="break-words text-3xl font-bold">
                    {{ $task->title }}
                </h1>
            </div>

            <div class="flex flex-wrap gap-4">
                <a
                    class="text-indigo-600"
                    href="{{ route('workspaces.projects.tasks.index', [$workspace, $project]) }}"
                >
                    Danh sách
                </a>

                <a
                    class="text-indigo-600"
                    href="{{ route('workspaces.projects.board.index', [$workspace, $project]) }}"
                >
                    Kanban
                </a>

                @can('update', $task)
                    <a
                        class="text-indigo-600"
                        href="{{ route('workspaces.projects.tasks.edit', $routeParams) }}"
                    >
                        Sửa công việc
                    </a>
                @endcan
            </div>
        </header>

        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="mb-4 text-xl font-bold">Thông tin công việc</h2>

            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-sm text-slate-500">Trạng thái</dt>
                    <dd>
                        {{ \App\Models\Task::STATUSES[$task->status] ?? $task->status }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-slate-500">Ưu tiên</dt>
                    <dd>
                        {{ \App\Models\Task::PRIORITIES[$task->priority] ?? $task->priority }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-slate-500">Người được giao</dt>
                    <dd>{{ $task->assignee?->name ?? 'Chưa giao' }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-slate-500">Hạn hoàn thành</dt>
                    <dd>{{ $task->due_date?->format('d/m/Y') ?? 'Chưa đặt' }}</dd>
                </div>
            </dl>

            <p class="mt-4 text-sm text-slate-500">
                Tạo bởi: {{ $task->creator?->name ?? 'Tài khoản đã xóa' }}
            </p>

            <div class="mt-5 whitespace-pre-wrap break-words text-slate-700">{{ $task->description ?: 'Chưa có mô tả.' }}</div>
        </section>

        <div class="grid items-start gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-xl font-bold">Nhắc tên thành viên</h2>

                <p class="mt-2 text-sm text-slate-500">
                    Người được chọn sẽ nhận thông báo kèm liên kết đến công việc này.
                </p>

                @if ($mentionableUsers->isNotEmpty())
                    <form
                        method="POST"
                        action="{{ route('workspaces.projects.tasks.mentions.store', $routeParams) }}"
                        class="mt-5 space-y-4"
                    >
                        @csrf

                        <fieldset>
                            <legend class="mb-3 font-semibold">
                                Chọn người cần nhắc
                            </legend>

                            <div class="max-h-64 space-y-3 overflow-y-auto rounded-xl border border-slate-200 p-4">
                                @foreach ($mentionableUsers as $person)
                                    <label class="flex items-start gap-3">
                                        <input
                                            type="checkbox"
                                            name="user_ids[]"
                                            value="{{ $person->id }}"
                                            class="mt-1"
                                            @checked(in_array(
                                                $person->id,
                                                (array) old('user_ids', [])
                                            ))
                                        >

                                        <span class="min-w-0">
                                            <span class="block font-medium">
                                                {{ $person->name }}
                                            </span>

                                            <span class="block break-words text-sm text-slate-500">
                                                {{ $person->email }}
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <button type="submit" class="{{ $buttonClass }}">
                            Nhắc tên
                        </button>
                    </form>
                @else
                    <p class="mt-4 text-slate-500">
                        Chưa có thành viên khác để nhắc tên.
                    </p>
                @endif
            </section>

            <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-xl font-bold">Tệp đính kèm</h2>

                <form
                    method="POST"
                    action="{{ route('workspaces.projects.tasks.attachments.store', $routeParams) }}"
                    enctype="multipart/form-data"
                    class="space-y-3"
                >
                    @csrf

                    <label for="task-file" class="block text-sm font-semibold">
                        Chọn tệp
                    </label>

                    <input
                        id="task-file"
                        type="file"
                        name="file"
                        required
                        accept=".jpg,.jpeg,.png,.webp,.pdf,.txt"
                        class="block w-full min-w-0 text-sm"
                    >

                    <p class="text-sm text-slate-500">
                        JPG, PNG, WebP, PDF, TXT. Tối đa 2 MB.
                    </p>

                    <button type="submit" class="{{ $buttonClass }}">
                        Tải tệp lên
                    </button>
                </form>

                <div class="space-y-4">
                    @forelse ($attachments as $attachment)
                        @php
                            $attachmentParams = array_merge(
                                $routeParams,
                                ['attachment' => $attachment->id]
                            );
                        @endphp

                        <article class="rounded-xl border border-slate-200 p-4">
                            <a
                                class="break-words font-semibold text-indigo-600"
                                href="{{ route('workspaces.projects.tasks.attachments.download', $attachmentParams) }}"
                            >
                                {{ $attachment->original_name }}
                            </a>

                            <p class="mt-2 text-sm text-slate-500">
                                {{ number_format($attachment->size / 1024, 1) }} KB
                                · {{ $attachment->uploader?->name ?? 'Tài khoản đã xóa' }}
                            </p>

                            @if (
                                (int) $attachment->user_id === (int) auth()->id()
                                || auth()->user()->can('update', $task)
                            )
                                <form
                                    method="POST"
                                    action="{{ route('workspaces.projects.tasks.attachments.destroy', $attachmentParams) }}"
                                    class="mt-3"
                                    onsubmit="return confirm('Xóa tệp này?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="text-sm text-red-600">
                                        Xóa tệp
                                    </button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="text-slate-500">Chưa có tệp đính kèm.</p>
                    @endforelse
                </div>

                {{ $attachments->links() }}
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="mb-5 text-xl font-bold">Lịch sử công việc</h2>

            @include('tasks.partials.activity-list')
        </section>
    </div>
@endsection