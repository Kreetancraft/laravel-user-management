@can('view-users')
    <flux:navlist.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users*')" wire:navigate>{{ __('Users') }}</flux:navlist.item>
@endcan
@can('manage-roles')
    <flux:navlist.item icon="shield-check" :href="route('admin.roles')" :current="request()->routeIs('admin.roles*')" wire:navigate>{{ __('Roles') }}</flux:navlist.item>
@endcan
