<?php

namespace Kreetancraft\UserManagement\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreetancraft\UserManagement\Models\User;

class UserDeactivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public ?User $deactivatedBy = null,
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
        $by = $this->deactivatedBy?->name ?? __('an administrator');

        return (new MailMessage)
            ->subject(__('Your :app account has been deactivated', ['app' => config('app.name')]))
            ->greeting(__('Hello :name,', ['name' => $this->user->name]))
            ->line(__('Your account was deactivated by :by.', ['by' => $by]))
            ->line(__('If you believe this was a mistake, please contact your administrator.'));
    }
}
