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

    public function accept(string $token, User $user): Workspace
    {
        $tokenHash = hash('sha256', $token);

        $invitation = WorkspaceInvitation::query()
            ->where('token_hash', $tokenHash)
            ->firstOrFail();

        return $this->acceptInvitation(
            $invitation->id,
            $user,
            $tokenHash
        );
    }

    public function acceptById(int $invitationId, User $user): Workspace
    {
        return $this->acceptInvitation($invitationId, $user);
    }

    private function acceptInvitation(
        int $invitationId,
        User $user,
        ?string $expectedTokenHash = null
    ): Workspace {
        $workspaceId = WorkspaceInvitation::query()
            ->findOrFail($invitationId)
            ->workspace_id;

        return DB::transaction(function () use (
            $invitationId,
            $workspaceId,
            $user,
            $expectedTokenHash
        ) {
            $workspace = Workspace::query()
                ->whereKey($workspaceId)
                ->lockForUpdate()
                ->firstOrFail();

            $invitation = WorkspaceInvitation::query()
                ->whereKey($invitationId)
                ->where('workspace_id', $workspace->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Với liên kết email, kiểm tra lại token sau khi khóa bản ghi.
            if ($expectedTokenHash !== null) {
                abort_unless(
                    hash_equals(
                        $invitation->token_hash,
                        $expectedTokenHash
                    ),
                    404
                );
            }

            $user = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $user->hasVerifiedEmail(),
                403,
                'Bạn cần xác minh email trước khi tham gia.'
            );

            abort_unless(
                Str::lower(trim($user->email)) === $invitation->email,
                403,
                'Tài khoản hiện tại không phải người nhận lời mời.'
            );

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

            abort_if(
                $workspace->is_personal,
                403,
                'Không thể tham gia workspace cá nhân.'
            );

            $alreadyMember = $workspace->members()
                ->whereKey($user->id)
                ->exists();

            if (! $alreadyMember) {
                $workspace->members()->attach($user->id, [
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
            }

            $invitation->update([
                'accepted_at' => now(),
            ]);

            return $workspace;
        });
    }
}