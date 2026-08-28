<div class="space-y-6">
    <x-user-management::page-header
        :title="__('New role')"
        :subtitle="__('A role is a named bundle of permissions. Grant only what it needs.')"
    >
        <x-slot:breadcrumbs>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route(config('user-management.routes.names.roles.index', 'admin.roles')) }}" wire:navigate>{{ __('Roles') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('New') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </x-slot:breadcrumbs>
    </x-user-management::page-header>

    <flux:separator variant="subtle" />

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <x-user-management::form-section :title="__('Name')">
                    <flux:field>
                        <flux:label badge="{{ __('Required') }}">{{ __('Role name') }}</flux:label>
                        <flux:input wire:model.blur="name" placeholder="{{ __('content-editor') }}" autofocus />
                        <flux:description>{{ __('Lowercase and hyphenated. Shown to admins as “Content Editor”.') }}</flux:description>
                        <flux:error name="name" />
                    </flux:field>
                </x-user-management::form-section>
            </div>

            {{-- Permissions carry the weight here: two thirds, grouped by resource
                 so a long flat list becomes something you can actually scan. --}}
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
                data-test="create-role-submit"
            >{{ __('Create role') }}</flux:button>
        </div>
    </form>
</div>
