<div class="space-y-6">
    {{-- Page header --}}
    <div class="space-y-3">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Create User') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Create User') }}</flux:heading>
                <flux:subheading>{{ __('Add a new staff member and assign their role.') }}</flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:button href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" variant="ghost" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>
    </div>

    <flux:separator />

    {{-- Form --}}
    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left column: Account Details (2 cols on lg) --}}
            <div class="lg:col-span-2 space-y-6">
                <x-form-card :title="__('Account Details')" :subtitle="__('An invitation email will be sent so they can set their own password.')">
                    <flux:field>
                        <flux:label required>{{ __('Full Name') }}</flux:label>
                        <flux:input
                            wire:model.blur="name"
                            placeholder="{{ __('Jane Doe') }}"
                            icon="user"
                            required
                            autofocus
                        />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label required>{{ __('Email Address') }}</flux:label>
                        <flux:input
                            wire:model.blur="email"
                            type="email"
                            placeholder="{{ __('jane@example.com') }}"
                            icon="envelope"
                            required
                        />
                        <flux:error name="email" />
                    </flux:field>
                </x-form-card>
            </div>

            {{-- Right column: Status & Roles --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Status card --}}
                <x-form-card :title="__('Status')" :subtitle="__('Controls sign-in access.')">
                    <flux:switch
                        wire:model="is_active"
                        label="{{ __('Account active') }}"
                        description="{{ __('Inactive users cannot sign in.') }}"
                    />
                </x-form-card>

                {{-- Roles card --}}
                <x-form-card :title="__('Roles')" :subtitle="__('User inherits all permissions of selected roles.')">
                    @if (count($selectedRoles))
                        <x-slot:actions>
                            <flux:badge size="sm" color="zinc">{{ count($selectedRoles) }}</flux:badge>
                        </x-slot:actions>
                    @endif
                    <flux:checkbox.group wire:model="selectedRoles" class="space-y-2">
                        @foreach ($roles as $role)
                            @php($enum = \Kreetancraft\UserManagement\Enums\UserRole::tryFrom($role->name))
                            <label wire:key="role-{{ $role->id }}" class="flex items-start gap-3 p-3 rounded-lg border border-border hover:bg-surface-muted cursor-pointer transition">
                                <flux:checkbox value="{{ $role->name }}" class="mt-0.5" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <flux:text class="font-medium text-content">
                                            {{ $enum?->label() ?? $role->name }}
                                        </flux:text>
                                        @if ($enum)
                                            <flux:badge size="sm" color="{{ $enum->color() }}">{{ $enum->value }}</flux:badge>
                                        @endif
                                    </div>
                                    @if ($enum?->description())
                                        <flux:text class="text-xs text-content-muted mt-1 block">
                                            {{ $enum->description() }}
                                        </flux:text>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </flux:checkbox.group>
                </x-form-card>
            </div>
        </div>

        {{-- Form footer actions --}}
        <div class="flex items-center justify-end gap-2 pt-2">
            <flux:button href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save" data-test="create-user-submit">
                {{ __('Send Invitation') }}
            </flux:button>
        </div>
    </form>
</div>
