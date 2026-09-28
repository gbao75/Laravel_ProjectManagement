@extends('layouts.app')

@section('title', 'Tổng quan')
@section('page-title', 'Tổng quan')

@section('content')
    @php
        $taskUrl = function ($task) {
            return $task->project_id === null
                ? route('personal-tasks.edit', $task)
                : route('workspaces.projects.tasks.show', [
                    'workspace' => $task->project->workspace,
                    'project' => $task->project,
                    'task' => $task,
                ]);
        };

        $cards = [
            ['label' => 'Dự án có thể truy cập', 'value' => $projectCount],
            ['label' => 'Công việc của tôi', 'value' => $totalTasks],
            ['label' => 'Đang thực hiện', 'value' => $inProgressTasks],
            ['label' => 'Đã hoàn thành', 'value' => $completedTasks],
            ['label' => 'Chưa xong và quá hạn', 'value' => $overdueTasks],
        ];
    @endphp

    <div class="space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">
                    Xin chào, {{ auth()->user()->name }}
                </h1>

                <p class="mt-2 text-slate-500">
                    Công việc cá nhân và công việc được giao trên các workspace.
                </p>
            </div>

            <a
                href="{{ route('calendar.index') }}"
                class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white"
            >
                Mở lịch công việc
            </a>
        </header>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($cards as $card)
                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500">
                        {{ $card['label'] }}
                    </p>

                    <p class="mt-4 text-3xl font-bold">
                        {{ $card['value'] }}
                    </p>
                </article>
            @endforeach
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="mb-3 flex justify-between gap-4">
                <h2 class="font-semibold">Tỷ lệ hoàn thành</h2>

                <span>{{ $completionPercent }}%</span>
            </div>

            <progress
                value="{{ $completedTasks }}"
                max="{{ max(1, $totalTasks) }}"
                class="h-4 w-full accent-indigo-600"
                aria-label="Tỷ lệ công việc đã hoàn thành"
            ></progress>

            <p class="mt-2 text-sm text-slate-500">
                {{ $completedTasks }} / {{ $totalTasks }} công việc.
            </p>
        </section>

        <div class="grid items-start gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="mb-5 text-xl font-bold">
                    Hạn công việc sắp tới
                </h2>

                <div class="space-y-4">
                    @forelse ($upcomingTasks as $task)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <a
                                href="{{ $taskUrl($task) }}"
                                class="break-words font-semibold text-indigo-600"
                            >
                                {{ $task->title }}
                            </a>

                            <p class="mt-2 text-sm text-slate-500">
                                {{ $task->project?->name ?? 'Công việc cá nhân' }}
                            </p>

                            <p class="mt-2 text-sm">
                                Hạn: {{ $task->due_date->format('d/m/Y') }}
                            </p>
                        </article>
                    @empty
                        <p class="text-slate-500">
                            Chưa có công việc chưa hoàn thành với hạn từ hôm nay.
                        </p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <div class="mb-5 flex justify-between gap-4">
                    <h2 class="text-xl font-bold">Cập nhật gần đây</h2>

                    <a href="{{ route('my-tasks.index') }}" class="text-indigo-600">
                        Xem tất cả
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse ($recentTasks as $task)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <a
                                href="{{ $taskUrl($task) }}"
                                class="break-words font-semibold text-indigo-600"
                            >
                                {{ $task->title }}
                            </a>

                            <p class="mt-2 text-sm text-slate-500">
                                {{ $task->project?->name ?? 'Công việc cá nhân' }}
                            </p>

                            <div class="mt-3 flex flex-wrap justify-between gap-2 text-sm">
                                <span>
                                    {{ \App\Models\Task::STATUSES[$task->status] ?? $task->status }}
                                </span>

                                <span class="text-slate-500">
                                    {{ $task->updated_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </article>
                    @empty
                        <p class="text-slate-500">Bạn chưa có công việc.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection