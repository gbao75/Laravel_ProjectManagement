@extends('layouts.app')

@section('title', 'Thành viên dự án')
@section('page-title', 'Thành viên dự án')

@section('content')
    <div class="project-members-page">
        <div class="pm-heading">
            <div>
                <h1>Thành viên dự án</h1>
                <p>{{ $project->name }}</p>
            </div>

            <a href="{{ route('workspaces.projects.show', [
                'workspace' => $workspace,
                'project' => $project,
            ]) }}">
                ← Quay lại dự án
            </a>
        </div>

        @if (session('status'))
            <div class="pm-notice" role="status">
                {{ session('status') }}
            </div>
        @endif

        <section class="pm-panel">
            <h2>Thêm thành viên</h2>

            <p class="pm-muted">
                Chỉ có thể thêm người đang thuộc workspace này.
                Owner và Admin workspace luôn có quyền quản lý dự án.
            </p>

            @if ($candidates->isNotEmpty())
                <form
                    method="POST"
                    action="{{ route('workspaces.projects.members.store', [
                        'workspace' => $workspace,
                        'project' => $project,
                    ]) }}"
                    class="pm-add-form"
                >
                    @csrf

                    <div class="pm-field">
                        <label for="project-member">Thành viên workspace</label>

                        <select
                            id="project-member"
                            name="user_id"
                            required
                            @error('user_id')
                                aria-invalid="true"
                                aria-describedby="member-error"
                            @enderror
                        >
                            <option value="">Chọn thành viên</option>

                            @foreach ($candidates as $candidate)
                                <option
                                    value="{{ $candidate->id }}"
                                    @selected(
                                        (string) old('user_id')
                                        === (string) $candidate->id
                                    )
                                >
                                    {{ $candidate->name }} — {{ $candidate->email }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="pm-primary">
                        Thêm vào dự án
                    </button>
                </form>
            @else
                <p>Không còn thành viên workspace nào để thêm.</p>
            @endif

            @error('user_id')
                <p id="member-error" class="pm-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </section>

        <section class="pm-panel">
            <h2>Danh sách thành viên ({{ $members->count() }})</h2>

            <div class="pm-table-scroll">
                <table class="pm-table">
                    <thead>
                        <tr>
                            <th>Thành viên</th>
                            <th>Email</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($members as $member)
                            <tr>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->email }}</td>
                                <td>
                                    <form
                                        method="POST"
                                        action="{{ route('workspaces.projects.members.destroy', [
                                            'workspace' => $workspace,
                                            'project' => $project,
                                            'member' => $member->id,
                                        ]) }}"
                                        onsubmit="return confirm('Xóa người này khỏi danh sách thành viên dự án? Owner/Admin vẫn giữ quyền quản lý từ workspace.');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="pm-danger">
                                            Xóa khỏi dự án
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="pm-muted">
                                    Chưa thêm thành viên.
                                    Owner/Admin vẫn quản lý được dự án này.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection