<div class="space-y-6">
    {{-- Page header --}}
    <div class="space-y-3">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Edit User') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="leading-7 tracking-tight">{{ $user->name }}</flux:heading>
                <flux:subheading class="leading-6">
                    {{ $user->email }}
                    @if ($user->last_login_at)
                        · {{ __('Last seen') }} {{ $user->last_login_at->diffForHumans() }}
                    @else
                        · {{ __('Never signed in') }}
                    @endif
                </flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:button href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" variant="ghost" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>
    </div>

    <flux:separator />

    {{-- Avatar hook — media package can override User::avatarUrl() --}}

    {{-- Form --}}
    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left column: Account Details --}}
            <div class="lg:col-span-2 space-y-6">
                <x-form-card :title="__('Account Details')" :subtitle="__('Basic information used to sign in.')">
                    <flux:field class="leading-6">
                        <flux:label required class="leading-6">{{ __('Full Name') }}</flux:label>
                        <flux:input
                            wire:model.blur="name"
                            icon="user"
                            required
                        />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field class="leading-6">
                        <flux:label required class="leading-6">{{ __('Email Address') }}</flux:label>
                        <flux:input
                            wire:model.blur="email"
                            type="email"
                            icon="envelope"
                            required
                        />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field class="leading-6">
                        <flux:label class="leading-6">{{ __('New Password') }}</flux:label>
                        <flux:input
                            wire:model.blur="password"
                            type="password"
                            icon="lock-closed"
                            placeholder="{{ __('Leave blank to keep current password') }}"
                            description="{{ __('Only fill this if you want to reset the user\'s password.') }}"
                            autocomplete="new-password"
                            viewable
                        />
                        <flux:error name="password" />
                    </flux:field>
                </x-form-card>
            </div>

            {{-- Right column: Status --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Status card --}}
                <x-form-card :title="__('Status')" :subtitle="__('Controls sign-in access.')">
                    <flux:switch
                        wire:model="is_active"
                        label="{{ __('Account active') }}"
                        description="{{ __('Inactive users cannot sign in.') }}"
                    />
                    @if (auth()->user()->isSuperAdmin())
                        <div class="mt-4 border-t border-border pt-4">
                            <flux:switch
                                wire:model="enforce_2fa"
                                label="{{ __('Require two-factor authentication') }}"
                                description="{{ __('Force this user to enroll 2FA before they can use the panel.') }}"
                            />
                        </div>
                    @endif
                </x-form-card>
            </div>
        </div>

        {{-- Roles card (full width below user details) --}}
        <x-form-card :title="__('Roles')" :subtitle="__('User inherits all permissions of selected roles.')">
            @if (count($selectedRoles))
                <x-slot:actions>
                    <flux:badge size="sm" color="zinc">{{ count($selectedRoles) }}</flux:badge>
                </x-slot:actions>
            @endif
            <flux:checkbox.group wire:model="selectedRoles" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach ($roles as $role)
                    @php($enum = \Kreetancraft\UserManagement\Enums\UserRole::tryFrom($role->name))
                    <label wire:key="role-{{ $role->id }}" class="flex items-start gap-3 p-3 rounded-lg border border-border hover:bg-surface-muted cursor-pointer transition">
                        <flux:checkbox value="{{ $role->name }}" class="mt-0.5" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <flux:text class="font-medium leading-6 text-content">
                                    {{ $enum?->label() ?? $role->name }}
                                </flux:text>
                                @if ($enum)
                                    <flux:badge size="sm" color="{{ $enum->color() }}">{{ $enum->value }}</flux:badge>
                                @endif
                            </div>
                            @if ($enum?->description())
                                <flux:text class="text-xs leading-5 text-content-muted mt-1 block">
                                    {{ $enum->description() }}
                                </flux:text>
                            @endif
                        </div>
                    </label>
                @endforeach
            </flux:checkbox.group>
        </x-form-card>

        {{-- Form footer actions --}}
        <div class="flex items-center justify-end gap-2 pt-2">
            <flux:button href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save" data-test="edit-user-submit">
                {{ __('Save Changes') }}
            </flux:button>
        </div>
    </form>
</div>
