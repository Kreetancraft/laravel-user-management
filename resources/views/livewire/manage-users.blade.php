<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ config('user-management.routes.home', '/') }}" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Users') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-2">
            <flux:heading size="xl" level="1">{{ __('Users') }}</flux:heading>
            <flux:subheading class="max-w-xl">{{ __('Invite people, assign roles, and control who can sign in.') }}</flux:subheading>

            <div class="flex flex-wrap items-center gap-2 pt-1">
                <flux:badge size="sm" color="zinc" icon="users">
                    {{ __(':count total', ['count' => $users->total()]) }}
                </flux:badge>
                <flux:badge size="sm" :color="\Kreetancraft\UserManagement\Support\RolePresenter::statusColor(true)" icon="check-circle">
                    {{ __(':count active', ['count' => $activeCount]) }}
                </flux:badge>
            </div>
        </div>

        @can('create', Kreetancraft\UserManagement\Models\User::class)
            <flux:button
                href="{{ route(config('user-management.routes.names.users.create', 'admin.users.create')) }}"
                icon="plus"
                variant="primary"
                wire:navigate
            >
                {{ __('Invite user') }}
            </flux:button>
        @endcan
    </div>

    <flux:separator variant="subtle" />

    {{-- Filters --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative w-full sm:max-w-xs">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search name or email…') }}"
                icon="magnifying-glass"
            />
            <div
                wire:loading.flex
                wire:target="search, roleFilter, statusFilter, sort"
                class="absolute inset-y-0 right-3 items-center"
            >
                <flux:icon icon="arrow-path" variant="mini" class="animate-spin opacity-50" />
            </div>
        </div>

        <flux:select wire:model.live="roleFilter" class="sm:w-44">
            <flux:select.option value="">{{ __('All roles') }}</flux:select.option>
            @foreach ($roleOptions as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="statusFilter" class="sm:w-44">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
        </flux:select>

        @if ($search || $roleFilter || $statusFilter)
            <flux:button variant="subtle" size="sm" icon="x-mark" wire:click="clearFilters">
                {{ __('Clear') }}
            </flux:button>
        @endif
    </div>

    @if ($users->isEmpty())
        <flux:card>
            <x-user-management::empty-state
                icon="users"
                :heading="__('No users found')"
                :description="($search || $roleFilter || $statusFilter) ? __('No users match your current filters.') : __('Invite someone to get started — they set their own password.')"
            >
                @if ($search || $roleFilter || $statusFilter)
                    <flux:button variant="subtle" size="sm" icon="x-mark" wire:click="clearFilters">
                        {{ __('Clear filters') }}
                    </flux:button>
                @else
                    @can('create', Kreetancraft\UserManagement\Models\User::class)
                        <flux:button
                            href="{{ route(config('user-management.routes.names.users.create', 'admin.users.create')) }}"
                            icon="plus"
                            variant="primary"
                            size="sm"
                            wire:navigate
                        >
                            {{ __('Invite user') }}
                        </flux:button>
                    @endcan
                @endif
            </x-user-management::empty-state>
        </flux:card>
    @else
        <flux:table :paginate="$users">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sort === 'name'" wire:click="$set('sort', 'name')">{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Roles') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sort === 'last_login_at'" wire:click="$set('sort', 'last_login_at')">{{ __('Last login') }}</flux:table.column>
                <flux:table.column />
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($users as $user)
                    <flux:table.row :key="$user->id">
                        {{-- Avatar, name and email in one cell: one identity, one column. --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar
                                    circle
                                    size="sm"
                                    :name="$user->name"
                                    :initials="$user->initials()"
                                    :src="$user->avatarUrl()"
                                />
                                <div class="min-w-0">
                                    @can('view', $user)
                                        <flux:link
                                            href="{{ route(config('user-management.routes.names.users.show', 'admin.users.show'), $user) }}"
                                            wire:navigate
                                            class="block truncate font-medium"
                                        >{{ $user->name }}</flux:link>
                                    @else
                                        <span class="block truncate font-medium">{{ $user->name }}</span>
                                    @endcan
                                    <flux:text size="sm" variant="subtle" class="block truncate">{{ $user->email }}</flux:text>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->roles as $role)
                                    <x-user-management::role-badge :role="$role" wire:key="role-{{ $user->id }}-{{ $role->id }}" />
                                @empty
                                    <flux:text size="sm" variant="subtle">{{ __('No roles') }}</flux:text>
                                @endforelse
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <x-user-management::status-badge :active="$user->is_active" />
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($user->last_login_at)
                                <flux:tooltip :content="$user->last_login_at->toDayDateTimeString()">
                                    <flux:text size="sm" variant="subtle">{{ $user->last_login_at->diffForHumans() }}</flux:text>
                                </flux:tooltip>
                            @else
                                <flux:text size="sm" variant="subtle">{{ __('Never') }}</flux:text>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button icon="ellipsis-horizontal" variant="subtle" size="sm" />

                                <flux:menu>
                                    @can('view', $user)
                                        <flux:menu.item
                                            href="{{ route(config('user-management.routes.names.users.show', 'admin.users.show'), $user) }}"
                                            icon="eye"
                                            wire:navigate
                                        >{{ __('View') }}</flux:menu.item>
                                    @endcan
                                    @can('update', $user)
                                        <flux:menu.item
                                            href="{{ route(config('user-management.routes.names.users.edit', 'admin.users.edit'), $user) }}"
                                            icon="pencil-square"
                                            wire:navigate
                                        >{{ __('Edit') }}</flux:menu.item>
                                    @endcan
                                    @can('delete', $user)
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            wire:click="confirmDelete({{ $user->id }})"
                                            icon="trash"
                                            variant="danger"
                                            data-test="delete-user-{{ $user->id }}"
                                        >{{ __('Delete') }}</flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal name="confirm-delete-user" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete user?') }}</flux:heading>
                <flux:text variant="subtle" class="mt-2">
                    {{ __('They will lose access immediately. This cannot be undone from the interface.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete" icon="trash">{{ __('Delete user') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
