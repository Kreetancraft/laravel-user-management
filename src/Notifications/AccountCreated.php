<?php

namespace Kreetancraft\UserManagement\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreetancraft\UserManagement\Models\User;

class AccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
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
        return (new MailMessage)
            ->subject(__('Welcome to :app — Your account is active', ['app' => config('app.name')]))
            ->greeting(__('Hello :name!', ['name' => $this->user->name]))
            ->line(__('Your account has been activated and is ready to use.'))
            ->action(__('Sign In'), route(config('user-management.routes.names.login', 'login')))
            ->line(__('For security, consider enabling two-factor authentication under Settings > Security.'));
    }
}
