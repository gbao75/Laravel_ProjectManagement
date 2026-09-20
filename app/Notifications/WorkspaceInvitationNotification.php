<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $workspaceName,
        public string $invitationUrl,
        public string $expiresAtText,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Lời mời tham gia workspace')
            ->greeting('Xin chào!')
            ->line(
                'Bạn được mời tham gia workspace: '
                . $this->workspaceName
            )
            ->line('Vai trò được mời: Thành viên.')
            ->action('Xem lời mời', $this->invitationUrl)
            ->line('Lời mời có hiệu lực đến ' . $this->expiresAtText . '.')
            ->line(
                'Nếu bạn không mong đợi lời mời này, '
                . 'bạn có thể bỏ qua email.'
            )
            ->salutation('ProjectManagement');
    }
}