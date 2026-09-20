<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceInvitationService
{
    public function invite(
        Workspace $workspace,
        User $inviter,
        string $email
    ): WorkspaceInvitation {
        $email = Str::lower(trim($email));

        return DB::transaction(function () use (
            $workspace,
            $inviter,
            $email
        ) {
            $workspace = Workspace::query()
                ->whereKey($workspace->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($inviter)->authorize('invite', $workspace);

            $alreadyMember = $workspace->members()
                ->whereRaw('LOWER(users.email) = ?', [$email])
                ->exists();

            if ($alreadyMember) {
                throw ValidationException::withMessages([
                    'email' => 'Người này đã là thành viên của workspace.',
                ]);
            }

            $existingInvitation = WorkspaceInvitation::query()
                ->where('workspace_id', $workspace->id)
                ->where('email', $email)
                ->first();

            if (
                $existingInvitation
                && $existingInvitation->accepted_at === null
                && $existingInvitation->expires_at->isFuture()
            ) {
                throw ValidationException::withMessages([
                    'email' => 'Email này đã có lời mời còn hiệu lực.',
                ]);
            }

            $token = Str::random(64);
            $expiresAt = now()->addDays(7);

            $invitation = WorkspaceInvitation::updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'email' => $email,
                ],
                [
                    'invited_by' => $inviter->id,
                    'role' => 'member',
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => $expiresAt,
                    'accepted_at' => null,
                ]
            );

            $url = route('workspace-invitations.show', [
                'token' => $token,
            ]);

            Notification::route('mail', $email)->notify(
                new WorkspaceInvitationNotification(
                    workspaceName: $workspace->name,
                    invitationUrl: $url,
                    expiresAtText: $expiresAt
                        ->copy()
                        ->timezone('Asia/Ho_Chi_Minh')
                        ->format('d/m/Y H:i') . ' (giờ Việt Nam)',
                )
            );

            return $invitation;
        });
    }
}