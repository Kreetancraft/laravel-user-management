<div class="space-y-6">
    {{-- Page header --}}
    <div class="space-y-3">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.roles') }}" wire:navigate>{{ __('Roles') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Create Role') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Create Role') }}</flux:heading>
                <flux:subheading>{{ __('Define a new access level and the permissions it grants.') }}</flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:button href="{{ route('admin.roles') }}" variant="ghost" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>
    </div>

    <flux:separator />

    {{-- Form --}}
    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left column: Role Identity --}}
            <div class="lg:col-span-1 space-y-6">
                <x-form-card :title="__('Role Identity')" :subtitle="__('A short, lowercase identifier in kebab-case.')">
                    <flux:field>
                        <flux:label required>{{ __('Role Name') }}</flux:label>
                        <flux:input
                            wire:model.blur="name"
                            placeholder="{{ __('e.g. content-editor') }}"
                            icon="shield-check"
                            required
                            autofocus
                        />
                        <flux:error name="name" />
                    </flux:field>
                </x-form-card>

                {{-- Helpful tip card --}}
                <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 rounded-xl p-5">
                    <div class="flex gap-3">
                        <flux:icon icon="information-circle" class="text-blue-500 dark:text-blue-400 shrink-0 mt-0.5" />
                        <div class="space-y-1">
                            <flux:text class="text-sm font-medium text-blue-900 dark:text-blue-200">
                                {{ __('Choosing permissions') }}
                            </flux:text>
                            <flux:text class="text-xs text-blue-800 dark:text-blue-300">
                                {{ __('Grant only the permissions this role truly needs. You can always update them later.') }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right column: Permissions (2 cols on lg) --}}
            <div class="lg:col-span-2 space-y-6">
                <x-form-card :title="__('Permissions')" :subtitle="__('Users with this role gain access to every permission checked below.')">
                    <x-slot:actions>
                        <flux:badge size="sm" color="zinc">{{ count($selectedPermissions) }} / {{ $permissions->count() }}</flux:badge>
                    </x-slot:actions>
                    <flux:checkbox.group wire:model="selectedPermissions" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($permissions as $permission)
                            <label wire:key="perm-{{ $permission->id }}" class="flex items-center gap-3 p-3 rounded-md border border-border hover:bg-surface-muted cursor-pointer transition">
                                <flux:checkbox value="{{ $permission->name }}" />
                                <flux:icon icon="key" variant="mini" class="text-content-subtle shrink-0" />
                                <flux:text class="text-sm truncate">{{ $permission->name }}</flux:text>
                            </label>
                        @endforeach
                    </flux:checkbox.group>
                </x-form-card>
            </div>
        </div>

        {{-- Form footer actions --}}
        <div class="flex items-center justify-end gap-2 pt-2">
            <flux:button href="{{ route('admin.roles') }}" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save" data-test="create-role-submit">
                {{ __('Create Role') }}
            </flux:button>
        </div>
    </form>
</div>
