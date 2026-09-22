<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectMemberController extends Controller
{
    public function index(
        Workspace $workspace,
        Project $project
    ): View {
        Gate::authorize('manageMembers', $project);

        $members = $project->members()
            ->orderBy('users.name')
            ->get();

        // Chỉ lấy người thuộc workspace
        // và chưa có trong danh sách thành viên dự án.
        $candidates = $workspace->members()
            ->whereNotIn('users.id', function ($query) use ($project) {
                $query->select('user_id')
                    ->from('project_user')
                    ->where('project_id', $project->id);
            })
            ->orderBy('users.name')
            ->get();

        return view('projects.members.index', compact(
            'workspace',
            'project',
            'members',
            'candidates',
        ));
    }

    public function store(
        Request $request,
        Workspace $workspace,
        Project $project
    ): RedirectResponse {
        Gate::authorize('manageMembers', $project);

        $data = $request->validate(
            [
                'user_id' => ['required', 'integer'],
            ],
            [
                'user_id.required' => 'Vui lòng chọn thành viên.',
                'user_id.integer' => 'Thành viên không hợp lệ.',
            ],
        );

        DB::transaction(function () use ($workspace, $project, $data) {
            // Kiểm tra lại membership trong workspace.
            // Khóa dòng để phối hợp với thao tác xóa ở bước 8.
            $membership = DB::table('workspace_user')
                ->where('workspace_id', $workspace->id)
                ->where('user_id', $data['user_id'])
                ->lockForUpdate()
                ->first();

            if (! $membership) {
                throw ValidationException::withMessages([
                    'user_id' => 'Người này không còn thuộc workspace.',
                ]);
            }

            $project->members()->syncWithoutDetaching([
                (int) $data['user_id'],
            ]);
        });

        return to_route('workspaces.projects.members.index', [
            'workspace' => $workspace,
            'project' => $project,
        ])->with('status', 'Đã cập nhật thành viên dự án.');
    }

    public function destroy(
        Workspace $workspace,
        Project $project,
        string $member
    ): RedirectResponse {
        Gate::authorize('manageMembers', $project);

        // Tìm trong thành viên của đúng dự án.
        $target = $project->members()
            ->where('users.id', $member)
            ->firstOrFail();

        $project->members()->detach($target->id);

        return to_route('workspaces.projects.members.index', [
            'workspace' => $workspace,
            'project' => $project,
        ])->with('status', 'Đã xóa thành viên khỏi dự án.');
    }
}