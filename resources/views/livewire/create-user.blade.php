<div class="space-y-6">
    <x-user-management::page-header
        :title="__('Invite user')"
        :subtitle="__('They receive an email and set their own password — you never handle it.')"
    >
        <x-slot:breadcrumbs>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Invite') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </x-slot:breadcrumbs>
    </x-user-management::page-header>

    <flux:separator variant="subtle" />

    <x-user-management::form-errors />

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- The form is the protagonist: it gets two thirds and the focus. --}}
            <div class="lg:col-span-2">
                <x-user-management::form-section
                    :title="__('Account')"
                    :subtitle="__('Name and email are all that is needed to send the invitation.')"
                >
                    <flux:field>
                        <flux:label badge="{{ __('Required') }}">{{ __('Full name') }}</flux:label>
                        <flux:input wire:model.blur="name" placeholder="{{ __('Jane Doe') }}" autofocus />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label badge="{{ __('Required') }}">{{ __('Email address') }}</flux:label>
                        <flux:input wire:model.blur="email" type="email" placeholder="{{ __('jane@example.com') }}" />
                        <flux:description>{{ __('The invitation link is sent here and expires.') }}</flux:description>
                        <flux:error name="email" />
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
                </x-user-management::form-section>

                <x-user-management::form-section :title="__('Roles')">
                    @if ($roles->isEmpty())
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            {{ __('No roles exist yet. The user can be invited now and assigned a role later.') }}
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
                icon="paper-airplane"
                wire:loading.attr="disabled"
                wire:target="save"
                data-test="create-user-submit"
            >
                <span wire:loading.remove wire:target="save">{{ __('Send invitation') }}</span>
                <span wire:loading wire:target="save">{{ __('Sending…') }}</span>
            </flux:button>
        </div>
    </form>
</div>
