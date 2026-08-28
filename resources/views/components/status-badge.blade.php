@props([
    'active' => true,
    'size' => 'sm',
])

@php($presenter = \Kreetancraft\UserManagement\Support\RolePresenter::class)

<flux:badge
    :size="$size"
    :color="$presenter::statusColor((bool) $active)"
    :icon="$active ? 'check-circle' : 'x-circle'"
    {{ $attributes }}
>
    {{ $active ? __('Active') : __('Inactive') }}
</flux:badge>
