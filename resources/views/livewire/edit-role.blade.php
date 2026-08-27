<div class="py-16 lg:py-28 space-y-12">
    {{-- Page header --}}
    <div class="space-y-3">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route(config('user-management.routes.names.roles.index', 'admin.roles')) }}" wire:navigate>{{ __('Roles') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Edit Role') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8">
            <div>
                <div class="flex items-center gap-2">
                    @php($enum = \Kreetancraft\UserManagement\Enums\UserRole::tryFrom($role->name))
                    <flux:heading size="xl" class="leading-7 tracking-tight text-zinc-900 dark:text-zinc-100">{{ $enum?->label() ?? $role->name }}</flux:heading>
                    @if ($isSystemRole)
                        <flux:badge size="sm" color="amber" icon="lock-closed">{{ __('System role') }}</flux:badge>
                    @endif
                </div>
                <flux:subheading>
                    {{ $enum?->description() ?? __('Update the permissions granted by this role.') }}
                </flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:button href="{{ route(config('user-management.routes.names.roles.index', 'admin.roles')) }}" variant="ghost" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>
    </div>

    <flux:separator />

    {{-- Form --}}
    <form wire:submit.prevent="save" class="space-y-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            {{-- Left column: Role Identity --}}
            <div class="lg:col-span-1 space-y-12">
                <flux:card>
                    <div class="p-6 border-b border-zinc-100 dark:border-zinc-800">
                        <flux:heading size="lg">{{ __('Role Identity') }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            @if ($isSystemRole)
                                {{ __('Built-in system role — name is locked.') }}
                            @else
                                {{ __('A short, lowercase identifier in kebab-case.') }}
                            @endif
                        </flux:text>
                    </div>

                    <div class="p-6">
                        <flux:field>
                            <flux:label required>{{ __('Role Name') }}</flux:label>
                            <flux:input
                                wire:model.blur="name"
                                icon="shield-check"
                                required
                                :disabled="$isSystemRole"
                            />
                            <flux:error name="name" />
                        </flux:field>
                    </div>
                </flux:card>

                @if ($isSystemRole)
                    <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 rounded-xl p-5">
                        <div class="flex gap-3">
                            <flux:icon icon="exclamation-triangle" class="text-amber-500 dark:text-amber-400 shrink-0 mt-0.5" />
                            <div class="space-y-1">
                                <flux:text class="text-sm font-medium text-amber-900 dark:text-amber-200">
                                    {{ __('System role') }}
                                </flux:text>
                                <flux:text class="text-xs text-amber-800 dark:text-amber-300">
                                    {{ __('This role is built into the application. Its name cannot be changed and it cannot be deleted, but you can adjust which permissions it grants.') }}
                                </flux:text>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right column: Permissions (2 cols on lg) --}}
            <div class="lg:col-span-2 space-y-12">
                <flux:card>
                    <div class="p-6 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                        <div>
                            <flux:heading size="lg">{{ __('Permissions') }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Users with this role gain access to every permission checked below.') }}
                            </flux:text>
                        </div>
                        <flux:badge size="sm" color="zinc">{{ count($selectedPermissions) }} / {{ $permissions->count() }}</flux:badge>
                    </div>

                    <div class="p-6">
                        <flux:checkbox.group wire:model="selectedPermissions" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($permissions as $permission)
                                <label wire:key="perm-{{ $permission->id }}" class="flex items-center gap-3 p-3 rounded-md border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer transition">
                                    <flux:checkbox value="{{ $permission->name }}" />
                                    <flux:icon icon="key" variant="mini" class="text-zinc-400 shrink-0" />
                                    <flux:text class="text-sm truncate">{{ $permission->name }}</flux:text>
                                </label>
                            @endforeach
                        </flux:checkbox.group>
                    </div>
                </flux:card>
            </div>
        </div>

        {{-- Form footer actions --}}
        <div class="flex items-center justify-end gap-2 pt-2">
            <flux:button href="{{ route(config('user-management.routes.names.roles.index', 'admin.roles')) }}" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save" data-test="edit-role-submit">
                {{ __('Save Changes') }}
            </flux:button>
        </div>
    </form>
</div>
