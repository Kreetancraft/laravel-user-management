<?php

namespace Kreetancraft\UserManagement\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreetancraft\UserManagement\Models\User;

class Invitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $token,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('user.invitation.set-password', ['token' => $this->token]);

        return (new MailMessage)
            ->subject(__('You have been invited to :app', ['app' => config('app.name')]))
            ->greeting(__('Hello :name!', ['name' => $this->user->name]))
            ->line(__('An administrator has invited you to join :app.', ['app' => config('app.name')]))
            ->line(__('Set your password to activate your account. This link expires in 72 hours.'))
            ->action(__('Set Password'), $url)
            ->line(__('If you did not expect this invitation, no further action is required.'));
    }
}
