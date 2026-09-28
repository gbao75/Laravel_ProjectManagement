@extends('layouts.app')

@section('title', 'Lịch công việc')
@section('page-title', 'Lịch công việc')

@section('content')
    <div class="space-y-6">
        <header>
            <h1 class="text-3xl font-bold">Lịch công việc của tôi</h1>

            <p class="mt-2 text-slate-500">
                Hạn của công việc cá nhân và công việc được giao cho bạn.
            </p>
        </header>

        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label for="calendar-status" class="mb-2 block font-semibold">
                    Trạng thái
                </label>

                <select
                    id="calendar-status"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2"
                >
                    <option value="">Tất cả trạng thái</option>

                    @foreach (\App\Models\Task::STATUSES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button
                type="button"
                id="calendar-refresh"
                class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white"
            >
                Làm mới
            </button>
        </div>

        <p
            id="calendar-error"
            hidden
            role="alert"
            class="rounded-xl bg-red-50 p-4 text-red-700"
        ></p>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
            <div
                id="task-calendar"
                data-events-url="{{ route('calendar.events') }}"
            ></div>
        </div>
    </div>
@endsection