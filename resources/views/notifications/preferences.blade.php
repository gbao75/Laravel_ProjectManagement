@extends('layouts.app')

@section('title', 'Cài đặt thông báo')
@section('page-title', 'Cài đặt thông báo')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <header>
            <h1 class="text-3xl font-bold">Cài đặt thông báo</h1>

            <p class="mt-2 text-slate-500">
                Chọn những thông báo bạn muốn nhận trong website.
            </p>
        </header>

        @if (session('status'))
            <div role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl bg-red-50 p-4 text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('notifications.preferences.update') }}"
            class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6"
        >
            @csrf
            @method('PUT')

            @php
                $options = [
                    'mentions' => [
                        'title' => 'Nhắc tên',
                        'description' => 'Khi thành viên khác nhắc bạn trong công việc.',
                    ],
                    'deadlines' => [
                        'title' => 'Sắp đến hạn và đến hạn',
                        'description' => 'Nhắc trước một ngày và vào ngày đến hạn.',
                    ],
                    'overdue' => [
                        'title' => 'Công việc quá hạn',
                        'description' => 'Nhắc một lần khi công việc đã quá hạn.',
                    ],
                ];
            @endphp

            @foreach ($options as $key => $option)
                <label class="flex items-start gap-4 rounded-xl border border-slate-200 p-4">
                    <input type="hidden" name="{{ $key }}" value="0">

                    <input
                        type="checkbox"
                        name="{{ $key }}"
                        value="1"
                        class="mt-1 h-4 w-4"
                        @checked((bool) old($key, $preferences[$key]))
                    >

                    <span>
                        <strong class="block">{{ $option['title'] }}</strong>

                        <span class="mt-1 block text-sm text-slate-500">
                            {{ $option['description'] }}
                        </span>
                    </span>
                </label>
            @endforeach

            <div class="flex flex-wrap items-center gap-4">
                <button
                    type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white"
                >
                    Lưu cài đặt
                </button>

                <a href="{{ route('notifications.index') }}" class="text-indigo-600">
                    Quay lại thông báo
                </a>
            </div>
        </form>
    </div>
@endsection