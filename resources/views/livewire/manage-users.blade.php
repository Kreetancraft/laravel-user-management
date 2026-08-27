<div class="space-y-6">
    <x-compact-table-styles />

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ config('user-management.routes.home', '/') }}" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Users') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-page-header :title="__('User Management')" :subtitle="__('Manage your application users and roles here.')">
        @can('create', Kreetancraft\UserManagement\Models\User::class)
            <flux:button href="{{ route('admin.users.create') }}" icon="plus" variant="primary" size="sm" wire:navigate>
                {{ __('Create User') }}
            </flux:button>
        @endcan
    </x-page-header>

    <div class="flex flex-wrap items-center gap-2">
        <flux:badge size="sm" color="zinc">{{ __(':count total', ['count' => $users->total()]) }}</flux:badge>
        <flux:badge size="sm" color="green">{{ __(':count active', ['count' => $activeCount]) }}</flux:badge>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="w-full sm:w-72 relative">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search users...') }}"
                icon="magnifying-glass"
                size="sm"
            />
            <div wire:loading wire:target="search, roleFilter, statusFilter, sort" class="absolute right-3 top-1/2 -translate-y-1/2">
                <flux:icon icon="arrow-path" class="animate-spin size-3.5 text-zinc-400 dark:text-zinc-500" />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="roleFilter" class="w-40 shadow-xs" size="sm">
                <flux:select.option value="">{{ __('All roles') }}</flux:select.option>
                @foreach ($roleOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="statusFilter" class="w-40 shadow-xs" size="sm">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    @if ($users->isEmpty())
        <x-empty-state icon="users" :title="__('No users found')">
            @if ($search || $roleFilter || $statusFilter)
                {{ __('No users match your current filters.') }}
                <x-slot:action>
                    <flux:button variant="ghost" size="xs" wire:click="$set('search', '')">{{ __('Clear filters') }}</flux:button>
                </x-slot:action>
            @else
                {{ __('Start by creating a new user account.') }}
            @endif
        </x-empty-state>
    @else
        <flux:table :paginate="$users" class="compact-table transition-opacity duration-200" wire:loading.class="opacity-60">
            <flux:table.columns>
                <flux:table.column class="w-12"></flux:table.column>
                <flux:table.column sortable :sorted="$sort === 'name'" wire:click="$set('sort', 'name')">
                    {{ __('Name') }}
                </flux:table.column>
                <flux:table.column sortable :sorted="$sort === 'email'" wire:click="$set('sort', 'email')">
                    {{ __('Email') }}
                </flux:table.column>
                <flux:table.column>{{ __('Roles') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sort === 'last_login_at'" wire:click="$set('sort', 'last_login_at')">
                    {{ __('Last Login') }}
                </flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>
                            <flux:avatar
                                circle
                                size="sm"
                                :name="$user->name"
                                :initials="$user->initials()"
                                :src="$user->avatarUrl()"
                            />
                        </flux:table.cell>
                        <flux:table.cell class="font-medium">
                            @can('view', $user)
                                <flux:link href="{{ route('admin.users.show', $user) }}" wire:navigate><x-highlight-text :text="$user->name" :search="$search" /></flux:link>
                            @else
                                <x-highlight-text :text="$user->name" :search="$search" />
                            @endcan
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text class="text-xs text-zinc-500"><x-highlight-text :text="$user->email" :search="$search" /></flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->roles as $role)
                                    @php($enum = \Kreetancraft\UserManagement\Enums\UserRole::tryFrom($role->name))
                                    <flux:badge size="sm" color="{{ $enum?->color() ?? 'zinc' }}">
                                        {{ $enum?->label() ?? $role->name }}
                                    </flux:badge>
                                @empty
                                    <flux:text class="text-zinc-400 dark:text-zinc-500 text-xs">{{ __('No Roles') }}</flux:text>
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($user->is_active)
                                <flux:badge size="sm" color="green">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($user->last_login_at)
                                <flux:text class="text-xs text-zinc-500">{{ $user->last_login_at->diffForHumans() }}</flux:text>
                            @else
                                <flux:text class="text-xs text-zinc-400">{{ __('Never') }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button icon="ellipsis-vertical" variant="ghost" size="sm" />

                                <flux:menu>
                                    @can('view', $user)
                                        <flux:tooltip content="{{ __('View user') }}">
                                            <flux:menu.item href="{{ route('admin.users.show', $user) }}" icon="eye" wire:navigate>{{ __('View') }}</flux:menu.item>
                                        </flux:tooltip>
                                    @endcan
                                    @can('update', $user)
                                        <flux:tooltip content="{{ __('Edit user') }}">
                                            <flux:menu.item href="{{ route('admin.users.edit', $user) }}" icon="pencil" wire:navigate>{{ __('Edit') }}</flux:menu.item>
                                        </flux:tooltip>
                                    @endcan
                                    @can('delete', $user)
                                        <flux:menu.separator />
                                        <flux:tooltip content="{{ __('Delete user') }}">
                                            <flux:menu.item wire:click="confirmDelete({{ $user->id }})" icon="trash" variant="danger" data-test="delete-user-{{ $user->id }}">{{ __('Delete') }}</flux:menu.item>
                                        </flux:tooltip>
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
                <flux:heading size="lg">{{ __('Delete User?') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('This action cannot be undone. The user will be permanently removed from the system.') }}
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete" icon="trash" wire:loading.attr="disabled">
                    {{ __('Delete User') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
