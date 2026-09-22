<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkspaceInvitationRequest;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\WorkspaceInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Illuminate\Http\Request;

class WorkspaceInvitationController extends Controller
{
    public function create(Workspace $workspace): View
    {
        Gate::authorize('invite', $workspace);

        return view(
            'workspace-invitations.create',
            compact('workspace')
        );
    }

    public function store(
        StoreWorkspaceInvitationRequest $request,
        Workspace $workspace,
        WorkspaceInvitationService $service
    ): RedirectResponse {
        $service->invite(
            $workspace,
            $request->user(),
            $request->validated()['email']
        );

        return redirect()
            ->route('workspaces.show', $workspace)
            ->with(
                'status',
                'Đã tạo lời mời và đưa email vào hàng đợi.'
            );
    }

    public function show(string $token): View
    {
        $invitation = WorkspaceInvitation::query()
            ->with('workspace')
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        abort_if(
            $invitation->accepted_at !== null,
            410,
            'Lời mời đã được sử dụng.'
        );

        abort_if(
            $invitation->expires_at->lessThanOrEqualTo(now()),
            410,
            'Lời mời đã hết hạn.'
        );

        return view(
            'workspace-invitations.show',
            compact('invitation', 'token')
        );
    }

    public function continueInvitation(string $token): RedirectResponse
    {
        return redirect()->route('workspace-invitations.show', [
            'token' => $token,
        ]);
    }

    public function accept(
        Request $request,
        string $token,
        WorkspaceInvitationService $service
    ): RedirectResponse {
        $workspace = $service->accept(
            $token,
            $request->user()
        );

        $request->session()->put(
            'current_workspace_id',
            $workspace->id
        );

        return redirect()
            ->route('workspaces.show', $workspace)
            ->with('status', 'Bạn đã tham gia workspace thành công.');
    }
}