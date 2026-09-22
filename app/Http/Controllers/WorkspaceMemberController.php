<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkspaceMemberController extends Controller
{
    public function index(Workspace $workspace): View
    {
        Gate::authorize('view', $workspace);

        $members = $workspace->members()
            ->orderBy('users.name')
            ->orderBy('users.id')
            ->paginate(15);

        return view(
            'workspaces.members.index',
            compact('workspace', 'members')
        );
    }

    public function update(
        Request $request,
        Workspace $workspace,
        int $member
    ): RedirectResponse {
        DB::transaction(function () use (
            $request,
            $workspace,
            $member
        ) {
            $workspace = Workspace::query()
                ->whereKey($workspace->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('manageMembers', $workspace);

            $target = $workspace->members()
                ->whereKey($member)
                ->firstOrFail();

            abort_if(
                $target->id === $workspace->owner_id,
                403,
                'Không thể thay đổi vai trò của chủ sở hữu.'
            );

            $data = $request->validate([
                'role' => ['required', Rule::in(['member', 'admin'])],
            ], [
                'role.required' => 'Vui lòng chọn vai trò.',
                'role.in' => 'Vai trò không hợp lệ.',
            ]);

            $workspace->members()->updateExistingPivot(
                $target->id,
                ['role' => $data['role']]
            );
        });

        return redirect()
            ->route('workspaces.members.index', $workspace)
            ->with('status', 'Đã cập nhật vai trò thành viên.');
    }

    public function destroy(
        Workspace $workspace,
        int $member
    ): RedirectResponse {
        DB::transaction(function () use ($workspace, $member) {
            $workspace = Workspace::query()
                ->whereKey($workspace->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('manageMembers', $workspace);

            $target = $workspace->members()
                ->whereKey($member)
                ->firstOrFail();

            abort_if(
                (int) $target->id === (int) $workspace->owner_id,
                403,
                'Không thể xóa chủ sở hữu khỏi workspace.'
            );

            // Khóa membership, phối hợp với thao tác
            // thêm thành viên vào dự án ở buổi 11.
            $membership = DB::table('workspace_user')
                ->where('workspace_id', $workspace->id)
                ->where('user_id', $target->id)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $membership,
                404,
                'Thành viên không còn thuộc workspace.'
            );

            // Xóa liên kết của người này với các dự án
            // thuộc đúng workspace đang xử lý.
            DB::table('project_user')
                ->where('user_id', $target->id)
                ->whereIn('project_id', function ($query) use ($workspace) {
                    $query->select('id')
                        ->from('projects')
                        ->where('workspace_id', $workspace->id);
                })
                ->delete();

            // Sau đó xóa liên kết với workspace.
            $workspace->members()->detach($target->id);
        });

        return redirect()
            ->route('workspaces.members.index', $workspace)
            ->with('status', 'Đã xóa thành viên khỏi workspace.');
    }
}