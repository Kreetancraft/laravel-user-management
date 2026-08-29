@props([
    'user' => null,
    'items' => null,
    'group' => 'user-avatar',
    'label' => null,
])

{{--
    The avatar picker, for the user forms and for your own profile page:

        <x-user-management::avatar-field :user="$user" />

    It renders nothing unless both halves of the seam are configured — a
    resolver that can store an avatar, and a picker view to choose one:

        // config/user-management.php
        'avatar_resolver'   => \Kreetancraft\Media\Support\MediaAvatarResolver::class,
        'media_picker_view' => 'media::picker-field',

    Without them the forms are exactly as they were, rather than showing a
    control with nothing behind it.

    On a Livewire form, bind it to a property so the choice survives until save:
    pass :items="$avatarMedia" and listen for `media-picked`. On a plain page,
    pass only :user and it shows what is stored.
--}}
@php
    $enabled = \Kreetancraft\UserManagement\Support\Avatar::enabled();
    $picker = \Kreetancraft\UserManagement\Support\Avatar::pickerView();

    // $items when the form is holding an unsaved choice; otherwise whatever is
    // stored, so the field is right on first render as well as after picking.
    $current = $items ?? ($user ? \Kreetancraft\UserManagement\Support\Avatar::list($user) : []);
@endphp

@if ($enabled && $picker)
    @includeIf($picker, [
        'items' => $current,
        'group' => $group,
        'multiple' => false,
        'label' => $label ?? __('Avatar'),
        'emptyLabel' => __('No avatar chosen — initials are shown instead.'),
        'icon' => 'user',
    ])
@endif
