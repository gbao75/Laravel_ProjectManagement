@extends('layouts.app')

@section('title', 'API token')
@section('page-title', 'API token')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <header>
            <h1 class="text-3xl font-bold">API token</h1>
            <p class="mt-2 text-slate-500">
                Cấp quyền truy cập API cho công cụ hoặc ứng dụng của bạn.
            </p>
        </header>

        @if (session('status'))
            <div role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if (session('plain_token'))
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="font-semibold">Sao chép token ngay</p>

                <p class="mt-1 text-sm">
                    Token đầy đủ chỉ hiển thị lần này. Không đưa token vào Git.
                </p>

                <textarea
                    readonly
                    rows="3"
                    aria-label="Token vừa tạo"
                    class="mt-3 w-full rounded-lg border border-amber-300 bg-white p-3 font-mono text-sm"
                >{{ session('plain_token') }}</textarea>
            </section>
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
            action="{{ route('api-tokens.store') }}"
            class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6"
        >
            @csrf

            <div>
                <label for="token-name" class="block font-semibold">
                    Tên token
                </label>

                <input
                    id="token-name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Ví dụ: Postman cá nhân"
                    required
                    maxlength="80"
                    class="mt-2 w-full rounded-lg border border-slate-300 p-3"
                >
            </div>

            <fieldset class="space-y-2">
                <legend class="mb-2 font-semibold">Quyền</legend>

                @foreach ($abilities as $value => $label)
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="abilities[]"
                            value="{{ $value }}"
                            @checked(in_array(
                                $value,
                                (array) old('abilities', ['tasks:read'])
                            ))
                        >

                        {{ $label }}
                    </label>
                @endforeach
            </fieldset>

            <div>
                <label for="token-password" class="block font-semibold">
                    Mật khẩu hiện tại
                </label>

                <input
                    id="token-password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="mt-2 w-full rounded-lg border border-slate-300 p-3"
                >
            </div>

            <button
                type="submit"
                class="rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white"
            >
                Tạo token
            </button>
        </form>

        <section class="space-y-4">
            <h2 class="text-xl font-bold">Token đã tạo</h2>

            @forelse ($tokens as $token)
                <article class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex flex-wrap justify-between gap-4">
                        <div>
                            <strong>{{ $token->name }}</strong>

                            <p class="mt-2 text-sm text-slate-500">
                                {{ implode(', ', $token->abilities ?? []) }}
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Hết hạn:
                                {{ $token->expires_at?->format('d/m/Y H:i') ?? 'Không đặt' }}
                            </p>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('api-tokens.destroy', $token->id) }}"
                            onsubmit="return confirm('Thu hồi token này?')"
                        >
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="text-red-600">
                                Thu hồi
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="text-slate-500">Chưa có token.</p>
            @endforelse

            {{ $tokens->links() }}
        </section>
    </div>
@endsection