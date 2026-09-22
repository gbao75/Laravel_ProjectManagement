<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveProjectRequest;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(
        Request $request,
        Workspace $workspace
    ): View {
        Gate::authorize('view', $workspace);

        // Kiểm tra bộ lọc trước khi đưa vào truy vấn.
        $filters = $request->validate([
            'status' => [
                'nullable',
                'string',
                Rule::in(array_keys(Project::STATUSES)),
            ],
        ]);

        $status = $filters['status'] ?? null;
        $user = $request->user();

        // Bắt đầu truy vấn trong đúng workspace.
        $query = $workspace->projects()
            ->with('creator');

        // Member chỉ thấy dự án mình tham gia.
        // Owner/Admin được xem toàn bộ dự án trong workspace.
        if (! $user->can('update', $workspace)) {
            $query->visibleTo($user);
        }

        // Lọc trạng thái, sắp xếp rồi phân trang.
        $projects = $query
            ->when(
                $status,
                fn ($query) => $query->where('status', $status)
            )
            ->latest('projects.id')
            ->paginate(9)
            ->withQueryString();

        return view(
            'projects.index',
            compact('workspace', 'projects', 'status')
        );
    }

    public function create(Workspace $workspace): View
    {
        Gate::authorize('create', [Project::class, $workspace]);

        $project = new Project([
            'status' => 'planning',
        ]);

        return view(
            'projects.form',
            compact('workspace', 'project')
        );
    }

    public function store(
        SaveProjectRequest $request,
        Workspace $workspace
    ): RedirectResponse {
        Gate::authorize('create', [Project::class, $workspace]);

        $project = new Project($request->validated());

        // Người tạo lấy từ tài khoản đang đăng nhập.
        $project->created_by = $request->user()->id;

        // Quan hệ tự gán workspace_id.
        $workspace->projects()->save($project);

        return redirect()
            ->route('workspaces.projects.show', [
                'workspace' => $workspace,
                'project' => $project,
            ])
            ->with('status', 'Đã tạo dự án.');
    }

    public function show(
        Workspace $workspace,
        Project $project
    ): View {
        Gate::authorize('view', $project);

        $project->load('creator');

        return view(
            'projects.show',
            compact('workspace', 'project')
        );
    }

    public function edit(
        Workspace $workspace,
        Project $project
    ): View {
        Gate::authorize('update', $project);

        return view(
            'projects.form',
            compact('workspace', 'project')
        );
    }

    public function update(
        SaveProjectRequest $request,
        Workspace $workspace,
        Project $project
    ): RedirectResponse {
        // Kiểm tra quyền trước khi ghi dữ liệu.
        Gate::authorize('update', $project);

        $project->update($request->validated());

        return redirect()
            ->route('workspaces.projects.show', [
                'workspace' => $workspace,
                'project' => $project,
            ])
            ->with('status', 'Đã cập nhật dự án.');
    }

    public function destroy(
        Workspace $workspace,
        Project $project
    ): RedirectResponse {
        Gate::authorize('delete', $project);

        $project->delete();

        return redirect()
            ->route('workspaces.projects.index', $workspace)
            ->with('status', 'Đã xóa dự án.');
    }
}