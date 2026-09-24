@extends('layouts.app')

@section('title', 'Lịch sử dự án')
@section('page-title', 'Lịch sử dự án')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">Lịch sử dự án</h1>

                <p class="mt-2 text-slate-500">
                    {{ $project->name }}
                </p>
            </div>

            <a
                class="text-indigo-600"
                href="{{ route('workspaces.projects.show', [$workspace, $project]) }}"
                class="primary-button">
                ← Quay lại dự án
            </a>
        </header>

        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            @include('tasks.partials.activity-list')
        </section>
    </div>
@endsection