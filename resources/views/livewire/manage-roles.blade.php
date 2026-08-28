<div class="space-y-6">
    <x-user-management::page-header
        :title="__('Roles')"
        :subtitle="__('A role bundles permissions. Assign roles to people, not permissions.')"
    >
        <x-slot:breadcrumbs>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ config('user-management.routes.home', '/') }}" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Roles') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if ($tab === 'roles')
                <flux:button
                    href="{{ route(config('user-management.routes.names.roles.create', 'admin.roles.create')) }}"
                    icon="plus"
                    variant="primary"
                    wire:navigate
                >{{ __('New role') }}</flux:button>
            @else
                <flux:button icon="plus" variant="primary" wire:click="openCreatePermissionModal">
                    {{ __('New permission') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-user-management::page-header>

    {{-- Two datasets, one screen: tabs rather than stacking both. --}}
    <div class="flex items-center gap-1 border-b border-zinc-200 dark:border-zinc-700">
        <flux:button
            size="sm"
            :variant="$tab === 'roles' ? 'filled' : 'ghost'"
            wire:click="setTab('roles')"
            icon="shield-check"
        >{{ __('Roles') }}</flux:button>

        <flux:button
            size="sm"
            :variant="$tab === 'permissions' ? 'filled' : 'ghost'"
            wire:click="setTab('permissions')"
            icon="key"
        >{{ __('Permissions') }}</flux:button>
    </div>

    @if ($tab === 'roles')
        <div class="space-y-4">
            <flux:input
                wire:model.live.debounce.300ms="searchRoles"
                placeholder="{{ __('Search roles…') }}"
                icon="magnifying-glass"
                class="sm:max-w-xs"
            />

            <div wire:loading.class="opacity-50" wire:target="searchRoles, setTab">
                @if ($roles->isEmpty())
                    <flux:card>
                        <x-user-management::empty-state
                            icon="shield-check"
                            :heading="__('No roles found')"
                            :description="$searchRoles ? __('Nothing matches that search.') : __('Create a role, then assign permissions to it.')"
                        />
                    </flux:card>
                @else
                    <flux:table :paginate="$roles">
                        <flux:table.columns>
                            <flux:table.column>{{ __('Role') }}</flux:table.column>
                            <flux:table.column>{{ __('Permissions') }}</flux:table.column>
                            <flux:table.column />
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($roles as $role)
                                <flux:table.row :key="$role->id">
                                    <flux:table.cell>
                                        <div class="flex items-center gap-2">
                                            <x-user-management::role-badge :role="$role" icon />
                                            @if (in_array($role->name, $systemRoles, true))
                                                <flux:badge size="sm" color="amber" icon="lock-closed">{{ __('System') }}</flux:badge>
                                            @endif
                                        </div>
                                    </flux:table.cell>

                                    {{-- Listing every permission made the row unreadable at
                                         a glance. Show the count, reveal the detail on hover. --}}
                                    <flux:table.cell>
                                        @if ($role->permissions->isEmpty())
                                            <flux:text size="sm" variant="subtle">{{ __('None') }}</flux:text>
                                        @else
                                            <flux:tooltip :content="$role->permissions->pluck('name')->join(', ')">
                                                <flux:badge size="sm" color="zinc" icon="key" class="tabular-nums">
                                                    {{ $role->permissions->count() }}
                                                </flux:badge>
                                            </flux:tooltip>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell align="end">
                                        <div class="flex items-center justify-end gap-1">
                                            <flux:button
                                                href="{{ route(config('user-management.routes.names.roles.edit', 'admin.roles.edit'), $role) }}"
                                                variant="subtle"
                                                size="sm"
                                                icon="pencil-square"
                                                wire:navigate
                                            />

                                            @unless (in_array($role->name, $systemRoles, true))
                                                <flux:button
                                                    variant="subtle"
                                                    size="sm"
                                                    icon="trash"
                                                    wire:click="confirmDeleteRole({{ $role->id }})"
                                                    data-test="delete-role-{{ $role->id }}"
                                                />
                                            @endunless
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </div>
        </div>
    @else
        <div class="space-y-4">
            <flux:input
                wire:model.live.debounce.300ms="searchPermissions"
                placeholder="{{ __('Search permissions…') }}"
                icon="magnifying-glass"
                class="sm:max-w-xs"
            />

            <div wire:loading.class="opacity-50" wire:target="searchPermissions, setTab">
                @if ($permissions->isEmpty())
                    <flux:card>
                        <x-user-management::empty-state
                            icon="key"
                            :heading="__('No permissions found')"
                            :description="$searchPermissions ? __('Nothing matches that search.') : __('Run user-management:sync-permissions to generate them from your policies.')"
                        />
                    </flux:card>
                @else
                    <flux:table :paginate="$permissions">
                        <flux:table.columns>
                            <flux:table.column>{{ __('Permission') }}</flux:table.column>
                            <flux:table.column />
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($permissions as $permission)
                                <flux:table.row :key="$permission->id">
                                    <flux:table.cell>
                                        <div class="flex items-center gap-2">
                                            <flux:icon icon="key" variant="mini" class="opacity-50" />
                                            <span class="font-medium">{{ $permission->name }}</span>
                                        </div>
                                    </flux:table.cell>

                                    <flux:table.cell align="end">
                                        <flux:button
                                            variant="subtle"
                                            size="sm"
                                            icon="trash"
                                            wire:click="confirmDeletePermission({{ $permission->id }})"
                                            data-test="delete-permission-{{ $permission->id }}"
                                        />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </div>
        </div>
    @endif

    <flux:modal name="create-permission" class="md:w-96">
        <form wire:submit.prevent="savePermission" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('New permission') }}</flux:heading>
                <flux:text variant="subtle" class="mt-2">
                    {{ __('Most permissions are generated from your policies. Add one here only if it has no policy behind it.') }}
                </flux:text>
            </div>

            <flux:field>
                <flux:label badge="{{ __('Required') }}">{{ __('Permission name') }}</flux:label>
                <flux:input wire:model="permissionName" placeholder="{{ __('edit-articles') }}" />
                <flux:error name="permissionName" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                    {{ __('Create') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-delete-role" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete role?') }}</flux:heading>
                <flux:text variant="subtle" class="mt-2">
                    {{ __('Anyone holding it loses its permissions immediately.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteRole" icon="trash">{{ __('Delete role') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="confirm-delete-permission" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete permission?') }}</flux:heading>
                <flux:text variant="subtle" class="mt-2">
                    {{ __('Every role using it loses it. Running sync-permissions may recreate it.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deletePermission" icon="trash">{{ __('Delete permission') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
