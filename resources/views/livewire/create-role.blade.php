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

    <x-user-management::form-errors />

    <form wire:submit.prevent="save" class="space-y-6">
        <x-user-management::form-section :title="__('Name')">
            <flux:field>
                <flux:label badge="{{ __('Required') }}">{{ __('Role name') }}</flux:label>
                <flux:input wire:model.blur="name" placeholder="{{ __('content-editor') }}" autofocus class="sm:max-w-md" />
                <flux:description>{{ __('Lowercase and hyphenated. Shown to admins as “Content Editor”.') }}</flux:description>
                <flux:error name="name" />
            </flux:field>
        </x-user-management::form-section>

        <x-user-management::form-section :title="__('Permissions')">
            @if ($permissionGroups->isEmpty())
                <x-user-management::empty-state
                    icon="key"
                    :heading="__('No permissions yet')"
                    :description="__('Run user-management:sync-permissions to generate them from your policies.')"
                />
            @else
                <x-user-management::permission-picker
                    :groups="$permissionGroups"
                    :selected="$selectedPermissions"
                />
                <flux:error name="selectedPermissions" />
            @endif
        </x-user-management::form-section>

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
