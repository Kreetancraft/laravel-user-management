<div class="py-16 lg:py-28 space-y-12">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ config('user-management.routes.home', '/') }}" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Roles') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8">
        <div class="space-y-3">
            <flux:heading size="2xl" class="leading-7 tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('Roles & Permissions') }}</flux:heading>
            <flux:subheading class="leading-6 text-zinc-500 dark:text-zinc-400">{{ __('Define and manage access levels and fine-grained permissions.') }}</flux:subheading>
        </div>
    </div>

    <div class="flex items-center gap-2 border-b border-border pb-3 mb-6">
        <flux:button size="sm" variant="{{ $tab === 'roles' ? 'filled' : 'ghost' }}" wire:click="setTab('roles')" icon="shield-check">
            {{ __('Roles') }}
        </flux:button>
        <flux:button size="sm" variant="{{ $tab === 'permissions' ? 'filled' : 'ghost' }}" wire:click="setTab('permissions')" icon="key">
            {{ __('Permissions') }}
        </flux:button>
    </div>

    @if ($tab === 'roles')
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="w-full sm:w-72">
                        <flux:input wire:model.live.debounce.300ms="searchRoles" placeholder="{{ __('Search roles...') }}" icon="magnifying-glass" />
                    </div>
                    <flux:button href="{{ route(config('user-management.routes.names.roles.create', 'admin.roles.create')) }}" icon="plus" variant="primary" wire:navigate>
                        {{ __('Create Role') }}
                    </flux:button>
                </div>

                <flux:table :paginate="$roles">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Role Name') }}</flux:table.column>
                        <flux:table.column>{{ __('Permissions') }}</flux:table.column>
                        <flux:table.column>{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($roles as $role)
                            <flux:table.row :key="$role->id">
                                <flux:table.cell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <flux:icon icon="shield-check" variant="mini" class="text-zinc-400 dark:text-zinc-500" />
                                        @php($enum = \Kreetancraft\UserManagement\Enums\UserRole::tryFrom($role->name))
                                        <span>{{ $enum?->label() ?? $role->name }}</span>
                                        @if (in_array($role->name, $systemRoles, true))
                                            <flux:badge size="sm" color="amber">{{ __('System') }}</flux:badge>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex flex-wrap gap-1 max-w-none">
                                        @forelse ($role->permissions as $perm)
                                            <flux:badge size="sm" color="zinc">{{ $perm->name }}</flux:badge>
                                        @empty
                                            <flux:text class="text-zinc-400 dark:text-zinc-500 text-xs">{{ __('No Permissions') }}</flux:text>
                                        @endforelse
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <flux:button href="{{ route(config('user-management.routes.names.roles.edit', 'admin.roles.edit'), $role) }}" variant="ghost" size="sm" icon="pencil" wire:navigate />

                                        @unless (in_array($role->name, $systemRoles, true))
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                icon="trash"
                                                class="text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
                                                wire:click="confirmDeleteRole({{ $role->id }})"
                                            />
                                        @endunless
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
    @else
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="w-full sm:w-72">
                        <flux:input wire:model.live.debounce.300ms="searchPermissions" placeholder="{{ __('Search permissions...') }}" icon="magnifying-glass" />
                    </div>
                    <flux:button icon="plus" variant="primary" wire:click="openCreatePermissionModal">
                        {{ __('Create Permission') }}
                    </flux:button>
                </div>

                <flux:table :paginate="$permissions">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Permission Name') }}</flux:table.column>
                        <flux:table.column>{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($permissions as $permission)
                            <flux:table.row :key="$permission->id">
                                <flux:table.cell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <flux:icon icon="key" variant="mini" class="text-zinc-400 dark:text-zinc-500" />
                                        <span>{{ $permission->name }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            class="text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
                                            wire:click="confirmDeletePermission({{ $permission->id }})"
                                        />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
    @endif

    {{-- Create Permission Modal --}}
    <flux:modal name="create-permission" class="md:w-96">
        <form wire:submit.prevent="savePermission" class="space-y-12">
            <div>
                <flux:heading size="lg">{{ __('Create Permission') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Specify a unique permission name.') }}</flux:text>
            </div>

            <flux:input
                wire:model="permissionName"
                label="{{ __('Permission Name') }}"
                placeholder="{{ __('e.g. edit-articles') }}"
                required
            />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:loading.class="opacity-60">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Confirm Delete Role Modal --}}
    <flux:modal name="confirm-delete-role" class="md:w-96">
        <div class="space-y-12">
            <div>
                <flux:heading size="lg">{{ __('Delete Role?') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Users with this role will lose its associated permissions. This action cannot be undone.') }}
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteRole" icon="trash">
                    {{ __('Delete Role') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Confirm Delete Permission Modal --}}
    <flux:modal name="confirm-delete-permission" class="md:w-96">
        <div class="space-y-12">
            <div>
                <flux:heading size="lg">{{ __('Delete Permission?') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('All roles using this permission will lose it. This action cannot be undone.') }}
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deletePermission" icon="trash">
                    {{ __('Delete Permission') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
