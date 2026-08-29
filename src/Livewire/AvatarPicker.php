<?php

namespace Kreetancraft\UserManagement\Livewire;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Kreetancraft\UserManagement\Support\Avatar;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * A self-contained avatar picker, for a page that has no form of its own.
 *
 *     <livewire:user-management.avatar />                  {{-- the signed-in user --}}
 *     <livewire:user-management.avatar :user="$someone" />
 *
 * The Blade field (`x-user-management::avatar-field`) only renders a picker: it
 * relies on the surrounding Livewire component to hear `media-picked` and save,
 * which the user forms do. A profile page has a form about name and email and
 * nothing that knows about images, so dropping the field there would show a
 * picker that quietly did nothing. This listens and saves for itself.
 *
 * Saving happens on pick rather than behind a submit button: there is no form
 * here to submit, and an avatar needing a separate save after choosing it is
 * the kind of thing people miss.
 *
 * The key is held, not the model. This package does not own the user class —
 * it resolves through `auth.providers.users.model` — and a property typed on
 * the abstract Model makes Livewire try to instantiate it on hydration.
 */
class AvatarPicker extends Component
{
    #[Locked]
    public int|string|null $userId = null;

    /**
     * @var array<int, array{id: int|string, url: ?string, name: ?string}>
     */
    public array $items = [];

    /**
     * @param  Model|int|string|null  $user  a model, its key, or null for the
     *                                       signed-in user
     */
    public function mount($user = null): void
    {
        $this->userId = match (true) {
            $user instanceof Model => $user->getKey(),
            $user !== null => $user,
            default => Auth::user()?->getKey(),
        };

        abort_if($this->userId === null, 403);

        $model = $this->user();

        abort_if($model === null, 404);

        // Anyone may set their own; changing someone else's is an update.
        if ($model->getKey() !== Auth::user()?->getKey()) {
            $this->authorize('update', $model);
        }

        $this->items = Avatar::list($model);
    }

    /**
     * The picker group, scoped to the user.
     *
     * Two of these on one page — your own avatar beside someone else's — would
     * otherwise share a modal name and overwrite each other.
     */
    public function group(): string
    {
        return 'user-avatar-'.$this->userId;
    }

    #[On('media-picked')]
    public function onMediaPicked(array $ids, string $group, array $items = []): void
    {
        if ($group !== $this->group()) {
            return;
        }

        $model = $this->user();

        if ($model === null) {
            return;
        }

        Avatar::sync($model, array_map(fn ($m) => $m['id'], $items));

        $this->items = Avatar::list($model);

        Flux::toast(variant: 'success', text: __('Avatar updated.'));
    }

    public function render(): View
    {
        return view('user-management::livewire.avatar');
    }

    /**
     * The user this picker is for, for the view.
     *
     * Public because the uploader component needs the model handed to it, and
     * the view is where that happens.
     */
    public function subject(): ?Model
    {
        return $this->user();
    }

    /**
     * Resolved fresh rather than held: this package does not own the user
     * class, and a model on a public property has to survive hydration.
     */
    private function user(): ?Model
    {
        /** @var class-string<Model> $class */
        $class = config('auth.providers.users.model');

        return $class::find($this->userId);
    }
}
