@props([
    'role',
    'size' => 'sm',
    'icon' => false,
])

@php
    $name = is_string($role) ? $role : $role->name;
    $presenter = \Kreetancraft\UserManagement\Support\RolePresenter::class;
@endphp

<flux:badge
    :size="$size"
    :color="$presenter::color($name)"
    :icon="$icon ? $presenter::icon($name) : null"
    {{ $attributes }}
>
    {{ $presenter::label($name) }}
</flux:badge>
