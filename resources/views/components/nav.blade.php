@can('view-users')
    <flux:navlist.item icon="users" :href="route(config('user-management.routes.names.users.index', 'admin.users'))" :current="request()->routeIs(config('user-management.routes.names.users.index', 'admin.users').'*')" wire:navigate>{{ __('Users') }}</flux:navlist.item>
@endcan
@can('manage-roles')
    <flux:navlist.item icon="shield-check" :href="route(config('user-management.routes.names.roles.index', 'admin.roles'))" :current="request()->routeIs(config('user-management.routes.names.roles.index', 'admin.roles').'*')" wire:navigate>{{ __('Roles') }}</flux:navlist.item>
@endcan
