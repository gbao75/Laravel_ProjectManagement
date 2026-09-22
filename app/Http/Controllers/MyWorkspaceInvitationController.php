<?php

namespace App\Http\Controllers;

use App\Models\WorkspaceInvitation;
use App\Services\WorkspaceInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MyWorkspaceInvitationController extends Controller
{
    public function index(Request $request): View
    {
        $email = Str::lower(trim($request->user()->email));

        $invitations = WorkspaceInvitation::query()
            ->with(['workspace', 'inviter'])
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->paginate(10);

        return view(
            'my-invitations.index',
            compact('invitations')
        );
    }

    public function accept(
        Request $request,
        int $invitation,
        WorkspaceInvitationService $service
    ): RedirectResponse {
        $workspace = $service->acceptById(
            $invitation,
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