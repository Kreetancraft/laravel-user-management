<div class="space-y-6">
    <x-user-management::page-header :title="$user->name" :subtitle="$user->email">
        <x-slot:breadcrumbs>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Edit') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </x-slot:breadcrumbs>

        <x-slot:meta>
            <x-user-management::status-badge :active="$user->is_active" />
            @foreach ($user->roles as $role)
                <x-user-management::role-badge :role="$role" wire:key="hdr-role-{{ $role->id }}" />
            @endforeach
            <flux:text size="sm" variant="subtle">
                {{ $user->last_login_at
                    ? __('Last seen :time', ['time' => $user->last_login_at->diffForHumans()])
                    : __('Never signed in') }}
            </flux:text>
        </x-slot:meta>

        <x-slot:actions>
            <flux:button
                href="{{ route(config('user-management.routes.names.users.show', 'admin.users.show'), $user) }}"
                variant="ghost"
                icon="eye"
                wire:navigate
            >{{ __('View') }}</flux:button>
        </x-slot:actions>
    </x-user-management::page-header>

    <flux:separator variant="subtle" />

    <x-user-management::form-errors />

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <x-user-management::form-section
                    :title="__('Account')"
                    :subtitle="__('Used to sign in.')"
                >
                    <flux:field>
                        <flux:label badge="{{ __('Required') }}">{{ __('Full name') }}</flux:label>
                        <flux:input wire:model.blur="name" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label badge="{{ __('Required') }}">{{ __('Email address') }}</flux:label>
                        <flux:input wire:model.blur="email" type="email" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label badge="{{ __('Optional') }}">{{ __('New password') }}</flux:label>
                        <flux:input
                            wire:model.blur="password"
                            type="password"
                            placeholder="{{ __('Leave blank to keep the current one') }}"
                            autocomplete="new-password"
                            viewable
                        />
                        <flux:description>{{ __('Setting this replaces their password immediately.') }}</flux:description>
                        <flux:error name="password" />
                    </flux:field>
                </x-user-management::form-section>
            </div>

            <div class="space-y-6">
                <x-user-management::form-section :title="__('Access')">
                    <flux:switch
                        wire:model="is_active"
                        :label="__('Account active')"
                        :description="__('Inactive users cannot sign in.')"
                    />

                    @if (auth()->user()->isSuperAdmin())
                        <flux:separator variant="subtle" />

                        <flux:switch
                            wire:model="enforce_2fa"
                            :label="__('Require two-factor')"
                            :description="__('They must enrol 2FA before using the panel.')"
                        />
                    @endif
                </x-user-management::form-section>

                {{-- Renders nothing unless an avatar resolver and a picker view
                     are both configured, so a form on an install without a
                     media package is exactly as it was. --}}
                <x-user-management::form-section :title="__('Avatar')">
                    <x-user-management::avatar-field :items="$avatarMedia" :label="null" />
                </x-user-management::form-section>

                <x-user-management::form-section :title="__('Roles')">
                    @if ($roles->isEmpty())
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            {{ __('No roles exist yet.') }}
                        </flux:callout>
                    @else
                        <flux:checkbox.group wire:model="selectedRoles" class="space-y-2">
                            @foreach ($roles as $role)
                                <flux:checkbox
                                    wire:key="role-{{ $role->id }}"
                                    value="{{ $role->name }}"
                                    :label="\Kreetancraft\UserManagement\Support\RolePresenter::label($role->name)"
                                    :description="trans_choice('{0}No permissions|{1}:count permission|[2,*]:count permissions', $role->permissions_count ?? 0, ['count' => $role->permissions_count ?? 0])"
                                />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="selectedRoles" />
                    @endif
                </x-user-management::form-section>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <flux:button
                href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}"
                variant="ghost"
                wire:navigate
            >{{ __('Cancel') }}</flux:button>

            <flux:button
                type="submit"
                variant="primary"
                icon="check"
                wire:loading.attr="disabled"
                wire:target="save"
                data-test="edit-user-submit"
            >{{ __('Save changes') }}</flux:button>
        </div>
    </form>
</div>
