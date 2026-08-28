<?php

namespace Kreetancraft\UserManagement\Livewire;

use Kreetancraft\UserManagement\Layout;
use Kreetancraft\UserManagement\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class ShowUser extends Component
{
    use WithPagination;

    /**
     * The user model to show.
     */
    public User $user;

    /**
     * Mount the component.
     */
    public function mount(User $user): void
    {
        $this->authorize('view', $user);

        $this->user = $user;
    }

    /**
     * Render the view.
     */
    #[Title('User Details - Admin')]
    public function render()
    {
        $history = $this->user->loginHistories()->paginate(10);

        return view('user-management::livewire.show-user', [
            'history' => $history,
        ])->layout(Layout::admin());
    }
}
