<div class="space-y-8">
    {{-- Breadcrumbs — 60 points --}}
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Edit User') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Header — 120 points hero --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8">
        <div class="space-y-3">
            <flux:heading size="2xl" level="1">{{ $user->name }}</flux:heading>
            <flux:text variant="subtle">
                {{ $user->email }}
                @if ($user->last_login_at)
                    <span variant="subtle">· {{ __('Last seen :time', ['time' => $user->last_login_at->diffForHumans()]) }}</span>
                @else
                    <span variant="subtle">· {{ __('Never signed in') }}</span>
                @endif
            </flux:text>
        </div>
        <flux:button href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" variant="ghost" class="rounded-md" wire:navigate>
            {{ __('Cancel') }}
        </flux:button>
    </div>

    <flux:separator variant="subtle" />

    <form wire:submit.prevent="save" class="space-y-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            {{-- Left — hero card, 120 points --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 p-8 lg:p-12 space-y-8">
                    <div class="space-y-2">
                        <flux:heading size="lg">{{ __('Account Details') }}</flux:heading>
                        <flux:text variant="subtle">{{ __('Basic information used to sign in.') }}</flux:text>
                    </div>

                    <flux:field class="space-y-2">
                        <flux:label>{{ __('Full Name') }}</flux:label>
                        <flux:input
                            wire:model.blur="name"
                            icon="user"
                           
                            required
                        />
                        <flux:error name="name" class="leading-5" />
                    </flux:field>

                    <flux:field class="space-y-2">
                        <flux:label>{{ __('Email Address') }}</flux:label>
                        <flux:input
                            wire:model.blur="email"
                            type="email"
                            icon="envelope"
                           
                            required
                        />
                        <flux:error name="email" class="leading-5" />
                    </flux:field>

                    <flux:field class="space-y-2">
                        <flux:label>{{ __('New Password') }}</flux:label>
                        <flux:input
                            wire:model.blur="password"
                            type="password"
                            icon="lock-closed"
                            placeholder="{{ __('Leave blank to keep current password') }}"
                           
                            autocomplete="new-password"
                            viewable
                        />
                        <flux:text size="sm" variant="subtle">{{ __('Only fill this if you want to reset the user\'s password.') }}</flux:text>
                        <flux:error name="password" class="leading-5" />
                    </flux:field>
                </div>
            </div>

            {{-- Right — 60 points, muted --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 p-8 space-y-12">
                    <div class="space-y-2">
                        <flux:heading size="lg">{{ __('Status') }}</flux:heading>
                        <flux:text variant="subtle">{{ __('Controls sign-in access.') }}</flux:text>
                    </div>

                    <flux:switch
                        wire:model="is_active"
                        label="{{ __('Account active') }}"
                        description="{{ __('Inactive users cannot sign in.') }}"
                       
                    />

                    @if (auth()->user()->isSuperAdmin())
                        <div class="pt-6 border-t border-zinc-200 dark:border-zinc-800">
                            <flux:switch
                                wire:model="enforce_2fa"
                                label="{{ __('Require two-factor authentication') }}"
                                description="{{ __('Force this user to enroll 2FA before they can use the panel.') }}"
                               
                            />
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Roles — full width, secondary, 60 points --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 p-8 lg:p-12 space-y-12">
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-2">
                    <flux:heading size="lg">{{ __('Roles') }}</flux:heading>
                    <flux:text variant="subtle">{{ __('User inherits all permissions of selected roles.') }}</flux:text>
                </div>
                @if (count($selectedRoles))
                    <flux:badge size="sm" color="zinc">{{ count($selectedRoles) }}</flux:badge>
                @endif
            </div>

            <flux:checkbox.group wire:model="selectedRoles" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($roles as $role)
                    <label wire:key="role-{{ $role->id }}" class="flex items-start gap-4 p-6 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition-colors">
                        <flux:checkbox value="{{ $role->name }}" class="mt-1" />
                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center gap-3 flex-wrap">
                                <x-user-management::role-badge :role="$role" icon />
                            </div>
                            <flux:text size="sm" variant="subtle">
                                {{ trans_choice('{0}No permissions|{1}:count permission|[2,*]:count permissions', $role->permissions_count ?? 0, ['count' => $role->permissions_count ?? 0]) }}
                            </flux:text>
                        </div>
                    </label>
                @endforeach
            </flux:checkbox.group>
        </div>

        <div class="flex items-center justify-end gap-4 pt-6 border-t border-zinc-200 dark:border-zinc-800">
            <flux:button href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check" class="rounded-md" wire:loading.attr="disabled" wire:target="save" data-test="edit-user-submit">
                {{ __('Save Changes') }}
            </flux:button>
        </div>
    </form>
</div>
