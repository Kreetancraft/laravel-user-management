<div class="space-y-8">
    {{-- Breadcrumbs — subtle, 60 points --}}
    <flux:breadcrumbs class="leading-6">
        <flux:breadcrumbs.item href="{{ config('user-management.routes.home', '/') }}" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Users') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Hero — 120 points: heading + CTA --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8">
        <div class="space-y-3">
            <flux:heading size="2xl" class="leading-7 tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('User Management') }}</flux:heading>
            <flux:subheading class="leading-6 text-zinc-900 dark:text-zinc-100 max-w-xl">{{ __('Manage your application users and roles here.') }}</flux:subheading>
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <span class="inline-flex items-center rounded-full bg-white text-black px-3 py-1 text-xs font-medium leading-none">{{ __(':count total', ['count' => $users->total()]) }}</span>
                <span class="inline-flex items-center rounded-full bg-emerald-500 text-zinc-900 dark:text-zinc-100 px-3 py-1 text-xs font-medium leading-none">{{ __(':count active', ['count' => $activeCount]) }}</span>
            </div>
        </div>
        @can('create', Kreetancraft\UserManagement\Models\User::class)
            <flux:button href="{{ route(config('user-management.routes.names.users.create', 'admin.users.create')) }}" icon="plus" variant="primary" size="sm" class="rounded-[var(--radius-md)] leading-6" wire:navigate>
                {{ __('Create User') }}
            </flux:button>
        @endcan
    </div>

    {{-- Filters — 60 points, generous whitespace, no decoration --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 py-6 border-y border-zinc-200 dark:border-zinc-800">
        <div class="w-full sm:w-[420px] relative">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search users...') }}"
                icon="magnifying-glass"
                class="leading-6"
            />
            <div wire:loading wire:target="search, roleFilter, statusFilter, sort" class="absolute right-3 top-1/2 -translate-y-1/2">
                <flux:icon icon="arrow-path" class="animate-spin size-3.5 text-zinc-900 dark:text-zinc-100/40" />
            </div>
        </div>

        <div class="flex items-center gap-6">
            <flux:select wire:model.live="roleFilter" class="w-44 leading-6" placeholder="{{ __('All roles') }}">
                <flux:select.option value="">{{ __('All roles') }}</flux:select.option>
                @foreach ($roleOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="statusFilter" class="w-44 leading-6" placeholder="{{ __('All statuses') }}">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    @if ($users->isEmpty())
        <div class="bg-white dark:bg-zinc-900 rounded-[var(--radius-lg)] border border-zinc-200 dark:border-zinc-800 p-16 lg:p-24 text-center">
            <flux:heading size="lg" class="leading-7 text-zinc-900 dark:text-zinc-100">{{ __('No users found') }}</flux:heading>
            <flux:text class="mt-3 leading-6 text-zinc-900 dark:text-zinc-100/60">
                @if ($search || $roleFilter || $statusFilter)
                    {{ __('No users match your current filters.') }}
                @else
                    {{ __('Start by creating a new user account.') }}
                @endif
            </flux:text>
            @if ($search || $roleFilter || $statusFilter)
                <flux:button variant="ghost" size="sm" wire:click="$set('search', '')" class="mt-6 leading-6">{{ __('Clear filters') }}</flux:button>
            @endif
        </div>
    @else
        {{-- Premium table — hero, 70-85% visual weight --}}
        <div class="bg-white dark:bg-zinc-900 rounded-[var(--radius-lg)] overflow-hidden border border-zinc-200 dark:border-zinc-800">
            <flux:table :paginate="$users" class="w-full">
                <flux:table.columns class="bg-zinc-50 dark:bg-zinc-900">
                    <flux:table.column class="w-16 leading-6"></flux:table.column>
                    <flux:table.column class="leading-6 tracking-tight" sortable :sorted="$sort === 'name'" wire:click="$set('sort', 'name')">{{ __('Name') }}</flux:table.column>
                    <flux:table.column class="leading-6">{{ __('Email') }}</flux:table.column>
                    <flux:table.column class="leading-6">{{ __('Roles') }}</flux:table.column>
                    <flux:table.column class="leading-6">{{ __('Status') }}</flux:table.column>
                    <flux:table.column class="leading-6" sortable :sorted="$sort === 'last_login_at'" wire:click="$set('sort', 'last_login_at')">{{ __('Last Login') }}</flux:table.column>
                    <flux:table.column class="leading-6 text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($users as $user)
                        <flux:table.row :key="$user->id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            <flux:table.cell>
                                <flux:avatar
                                    circle
                                    size="lg"
                                    :name="$user->name"
                                    :initials="$user->initials()"
                                    :src="$user->avatarUrl()"
                                    class="h-12 w-12 text-sm leading-none ring-1 ring-zinc-200 dark:ring-zinc-700"
                                />
                            </flux:table.cell>
                            <flux:table.cell class="font-medium leading-6">
                                @can('view', $user)
                                    <flux:link href="{{ route(config('user-management.routes.names.users.show', 'admin.users.show'), $user) }}" wire:navigate class="leading-6 text-zinc-900 dark:text-zinc-100 hover:text-zinc-900 dark:text-zinc-100">{{ $user->name }}</flux:link>
                                @else
                                    <span class="leading-6 text-zinc-900 dark:text-zinc-100">{{ $user->name }}</span>
                                @endcan
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm leading-6 text-zinc-900 dark:text-zinc-100/60">{{ $user->email }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($user->roles as $role)
                                        @php($enum = \Kreetancraft\UserManagement\Enums\UserRole::tryFrom($role->name))
                                        <span class="inline-flex items-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 px-2.5 py-1 text-xs font-medium leading-none ring-1 ring-zinc-200 dark:ring-zinc-700">
                                            {{ $enum?->label() ?? $role->name }}
                                        </span>
                                    @empty
                                        <flux:text class="text-xs leading-5 text-zinc-900 dark:text-zinc-100/40">{{ __('No Roles') }}</flux:text>
                                    @endforelse
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($user->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 dark:text-emerald-400 ring-emerald-500/20 px-2.5 py-1 text-xs font-medium leading-none ring-1 ring-emerald-500/20">{{ __('Active') }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100/60 px-2.5 py-1 text-xs font-medium leading-none">{{ __('Inactive') }}</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($user->last_login_at)
                                    <flux:text class="text-sm leading-6 text-zinc-900 dark:text-zinc-100/60">{{ $user->last_login_at->diffForHumans() }}</flux:text>
                                @else
                                    <flux:text class="text-sm leading-6 text-zinc-900 dark:text-zinc-100/40">{{ __('Never') }}</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:dropdown>
                                    <flux:button icon="ellipsis-vertical" variant="ghost" size="sm" class="leading-6" />

                                    <flux:menu class="min-w-[200px] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-[var(--radius-lg)]">
                                        <div class="flex items-center gap-2 p-2">
                                            @can('view', $user)
                                                <flux:menu.item class="flex-1 justify-center leading-5" href="{{ route(config('user-management.routes.names.users.show', 'admin.users.show'), $user) }}" icon="eye" wire:navigate>{{ __('View') }}</flux:menu.item>
                                            @endcan
                                            @can('update', $user)
                                                <flux:menu.item class="flex-1 justify-center leading-5" href="{{ route(config('user-management.routes.names.users.edit', 'admin.users.edit'), $user) }}" icon="pencil" wire:navigate>{{ __('Edit') }}</flux:menu.item>
                                            @endcan
                                        </div>
                                        @can('delete', $user)
                                            <flux:menu.separator class="bg-zinc-100 dark:bg-zinc-800" />
                                            <flux:menu.item class="leading-5 text-red-600 dark:text-red-400 hover:text-red-700" wire:click="confirmDelete({{ $user->id }})" icon="trash" variant="danger" data-test="delete-user-{{ $user->id }}">{{ __('Delete') }}</flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif

    <flux:modal name="confirm-delete-user" class="md:w-96">
        <div class="space-y-6 p-2">
            <flux:heading size="lg" class="leading-7 text-zinc-900 dark:text-zinc-100">{{ __('Delete User?') }}</flux:heading>
            <flux:text class="leading-6 text-zinc-900 dark:text-zinc-100/60">{{ __('This action cannot be undone. The user will be permanently removed from the system.') }}</flux:text>
            <div class="flex gap-3 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" class="leading-6">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete" icon="trash" class="leading-6 rounded-[var(--radius-md)]">{{ __('Delete User') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
