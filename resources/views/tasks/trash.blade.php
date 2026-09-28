@extends('layouts.app')

@section('title', 'Thùng rác')
@section('page-title', 'Thùng rác')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900">
            Thùng rác công việc
        </h1>

        <p class="mt-2 text-slate-500">
            Khôi phục công việc đã xóa hoặc xóa vĩnh viễn.
        </p>
    </div>

    @if (session('status'))
        <div role="status"
             class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert"
             class="mb-5 rounded-xl bg-red-50 px-4 py-3 text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        @forelse ($tasks as $task)
            <article
                class="flex flex-col gap-4 border-b border-slate-100 p-5
                       last:border-b-0 lg:flex-row lg:items-center
                       lg:justify-between"
            >
                <div class="min-w-0">
                    <h2 class="break-words text-lg font-semibold text-slate-900">
                        {{ $task->title }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        @if ($task->project)
                            {{ $task->project->workspace->name }}
                            / {{ $task->project->name }}
                        @else
                            Công việc cá nhân
                        @endif
                    </p>

                    <p class="mt-2 text-sm text-slate-500">
                        Đã xóa:
                        {{ $task->deleted_at->format('d/m/Y H:i') }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-col gap-3">
                    @can('restore', $task)
                        <form
                            method="POST"
                            action="{{ route('trash.tasks.restore', $task->id) }}"
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="w-full rounded-lg border border-indigo-200
                                       bg-indigo-50 px-4 py-2 font-medium
                                       text-indigo-700 hover:bg-indigo-100"
                            >
                                Khôi phục
                            </button>
                        </form>
                    @endcan

                    @can('forceDelete', $task)
                        <form
                            method="POST"
                            action="{{ route('trash.tasks.destroy', $task->id) }}"
                            onsubmit="return confirm('Xóa vĩnh viễn công việc và tệp đính kèm? Không thể khôi phục.');"
                        >
                            @csrf
                            @method('DELETE')

                            <label class="mb-2 flex items-center gap-2 text-sm text-slate-600">
                                <input
                                    type="checkbox"
                                    name="confirm"
                                    value="1"
                                    required
                                >

                                Tôi xác nhận xóa vĩnh viễn
                            </label>

                            <button
                                type="submit"
                                class="w-full rounded-lg border border-red-200
                                       bg-red-50 px-4 py-2 font-medium
                                       text-red-700 hover:bg-red-100"
                            >
                                Xóa vĩnh viễn
                            </button>
                        </form>
                    @endcan
                </div>
            </article>
        @empty
            <div class="p-10 text-center">
                <h2 class="text-lg font-semibold text-slate-800">
                    Thùng rác đang trống
                </h2>

                <p class="mt-2 text-slate-500">
                    Những công việc bạn có quyền quản lý sẽ xuất hiện ở đây
                    sau khi được xóa.
                </p>
            </div>
        @endforelse
    </div>

    <div class="mt-5">
        {{ $tasks->links() }}
    </div>
@endsection