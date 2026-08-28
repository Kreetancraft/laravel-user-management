<div class="space-y-6">
    <x-user-management::page-header
        :title="\Kreetancraft\UserManagement\Support\RolePresenter::label($role->name)"
        :subtitle="__('Choose what this role can do. Changes apply to everyone holding it.')"
    >
        <x-slot:breadcrumbs>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route(config('user-management.routes.names.roles.index', 'admin.roles')) }}" wire:navigate>{{ __('Roles') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Edit') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </x-slot:breadcrumbs>

        <x-slot:meta>
            <x-user-management::role-badge :role="$role" icon />
            @if ($isSystemRole)
                <flux:badge size="sm" color="amber" icon="lock-closed">{{ __('System role') }}</flux:badge>
            @endif
        </x-slot:meta>
    </x-user-management::page-header>

    <flux:separator variant="subtle" />

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <x-user-management::form-section :title="__('Name')">
                    <flux:field>
                        <flux:label>{{ __('Role name') }}</flux:label>
                        <flux:input wire:model.blur="name" :disabled="$isSystemRole" />

                        @if ($isSystemRole)
                            {{-- Say it once, where the disabled field is — not in a
                                 separate callout repeating the same fact. --}}
                            <flux:description>
                                {{ __('Built in. The name is locked and the role cannot be deleted, but its permissions are yours to change.') }}
                            </flux:description>
                        @else
                            <flux:description>{{ __('Lowercase and hyphenated.') }}</flux:description>
                        @endif

                        <flux:error name="name" />
                    </flux:field>
                </x-user-management::form-section>
            </div>

            <div class="lg:col-span-2">
                <x-user-management::form-section :title="__('Permissions')">
                    <x-slot:subtitle>
                        {{ trans_choice('{0}Nothing selected|{1}:count of :total selected|[2,*]:count of :total selected', count($selectedPermissions), ['count' => count($selectedPermissions), 'total' => $permissions->count()]) }}
                    </x-slot:subtitle>

                    @if ($permissions->isEmpty())
                        <x-user-management::empty-state
                            icon="key"
                            :heading="__('No permissions yet')"
                            :description="__('Run user-management:sync-permissions to generate them from your policies.')"
                        />
                    @else
                        <flux:checkbox.group wire:model="selectedPermissions" class="space-y-5">
                            @foreach ($permissions->groupBy(fn ($p) => \Illuminate\Support\Str::afterLast($p->name, '-')) as $group => $items)
                                <div wire:key="group-{{ $group }}" class="space-y-2">
                                    <flux:text size="sm" variant="subtle" class="font-medium uppercase tracking-wide">
                                        {{ \Illuminate\Support\Str::headline($group) }}
                                    </flux:text>

                                    <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                                        @foreach ($items as $permission)
                                            <flux:checkbox
                                                wire:key="perm-{{ $permission->id }}"
                                                value="{{ $permission->name }}"
                                                :label="$permission->name"
                                            />
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="selectedPermissions" />
                    @endif
                </x-user-management::form-section>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <flux:button
                href="{{ route(config('user-management.routes.names.roles.index', 'admin.roles')) }}"
                variant="ghost"
                wire:navigate
            >{{ __('Cancel') }}</flux:button>

            <flux:button
                type="submit"
                variant="primary"
                icon="check"
                wire:loading.attr="disabled"
                wire:target="save"
                data-test="edit-role-submit"
            >{{ __('Save changes') }}</flux:button>
        </div>
    </form>
</div>
