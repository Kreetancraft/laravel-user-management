<div class="py-16 lg:py-28 space-y-12">
    {{-- Breadcrumbs — 60 points --}}
    <flux:breadcrumbs class="leading-6">
        <flux:breadcrumbs.item href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Edit User') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Header — 120 points hero --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8">
        <div class="space-y-3">
            <flux:heading size="2xl" class="leading-7 tracking-tight text-white">{{ $user->name }}</flux:heading>
            <flux:text class="leading-6 text-white/60">
                {{ $user->email }}
                @if ($user->last_login_at)
                    <span class="text-white/40">· {{ __('Last seen :time', ['time' => $user->last_login_at->diffForHumans()]) }}</span>
                @else
                    <span class="text-white/40">· {{ __('Never signed in') }}</span>
                @endif
            </flux:text>
        </div>
        <flux:button href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" variant="ghost" class="leading-6 rounded-[var(--radius-md)]" wire:navigate>
            {{ __('Cancel') }}
        </flux:button>
    </div>

    <flux:separator class="bg-white/10" />

    <form wire:submit.prevent="save" class="space-y-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            {{-- Left — hero card, 120 points --}}
            <div class="lg:col-span-2">
                <div class="bg-[#0f0f0f] rounded-[var(--radius-lg)] border border-white/10 p-8 lg:p-12 space-y-8">
                    <div class="space-y-2">
                        <flux:heading size="lg" class="leading-7 text-white">{{ __('Account Details') }}</flux:heading>
                        <flux:text class="leading-6 text-white/60">{{ __('Basic information used to sign in.') }}</flux:text>
                    </div>

                    <flux:field class="space-y-2">
                        <flux:label class="leading-6 text-white">{{ __('Full Name') }}</flux:label>
                        <flux:input
                            wire:model.blur="name"
                            icon="user"
                            class="leading-6"
                            required
                        />
                        <flux:error name="name" class="leading-5" />
                    </flux:field>

                    <flux:field class="space-y-2">
                        <flux:label class="leading-6 text-white">{{ __('Email Address') }}</flux:label>
                        <flux:input
                            wire:model.blur="email"
                            type="email"
                            icon="envelope"
                            class="leading-6"
                            required
                        />
                        <flux:error name="email" class="leading-5" />
                    </flux:field>

                    <flux:field class="space-y-2">
                        <flux:label class="leading-6 text-white">{{ __('New Password') }}</flux:label>
                        <flux:input
                            wire:model.blur="password"
                            type="password"
                            icon="lock-closed"
                            placeholder="{{ __('Leave blank to keep current password') }}"
                            class="leading-6"
                            autocomplete="new-password"
                            viewable
                        />
                        <flux:text class="text-xs leading-5 text-white/40">{{ __('Only fill this if you want to reset the user\'s password.') }}</flux:text>
                        <flux:error name="password" class="leading-5" />
                    </flux:field>
                </div>
            </div>

            {{-- Right — 60 points, muted --}}
            <div class="lg:col-span-1">
                <div class="bg-[#0f0f0f] rounded-[var(--radius-lg)] border border-white/10 p-8 space-y-6">
                    <div class="space-y-2">
                        <flux:heading size="lg" class="leading-7 text-white">{{ __('Status') }}</flux:heading>
                        <flux:text class="leading-6 text-white/60">{{ __('Controls sign-in access.') }}</flux:text>
                    </div>

                    <flux:switch
                        wire:model="is_active"
                        label="{{ __('Account active') }}"
                        description="{{ __('Inactive users cannot sign in.') }}"
                        class="leading-6"
                    />

                    @if (auth()->user()->isSuperAdmin())
                        <div class="pt-6 border-t border-white/10">
                            <flux:switch
                                wire:model="enforce_2fa"
                                label="{{ __('Require two-factor authentication') }}"
                                description="{{ __('Force this user to enroll 2FA before they can use the panel.') }}"
                                class="leading-6"
                            />
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Roles — full width, secondary, 60 points --}}
        <div class="bg-[#0f0f0f] rounded-[var(--radius-lg)] border border-white/10 p-8 lg:p-12 space-y-6">
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-2">
                    <flux:heading size="lg" class="leading-7 text-white">{{ __('Roles') }}</flux:heading>
                    <flux:text class="leading-6 text-white/60">{{ __('User inherits all permissions of selected roles.') }}</flux:text>
                </div>
                @if (count($selectedRoles))
                    <span class="inline-flex items-center rounded-full bg-white text-black px-3 py-1 text-xs font-medium leading-none">{{ count($selectedRoles) }}</span>
                @endif
            </div>

            <flux:checkbox.group wire:model="selectedRoles" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($roles as $role)
                    @php($enum = \Kreetancraft\UserManagement\Enums\UserRole::tryFrom($role->name))
                    <label wire:key="role-{{ $role->id }}" class="flex items-start gap-4 p-6 rounded-[var(--radius-lg)] border border-white/10 hover:bg-white/[0.04] cursor-pointer transition-colors">
                        <flux:checkbox value="{{ $role->name }}" class="mt-1" />
                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center gap-3 flex-wrap">
                                <flux:text class="font-medium leading-6 text-white">
                                    {{ $enum?->label() ?? $role->name }}
                                </flux:text>
                                @if ($enum)
                                    <span class="inline-flex items-center rounded-full bg-white/10 text-white px-2 py-1 text-xs leading-none">{{ $enum->value }}</span>
                                @endif
                            </div>
                            @if ($enum?->description())
                                <flux:text class="text-sm leading-6 text-white/60">
                                    {{ $enum->description() }}
                                </flux:text>
                            @endif
                        </div>
                    </label>
                @endforeach
            </flux:checkbox.group>
        </div>

        <div class="flex items-center justify-end gap-4 pt-6 border-t border-white/10">
            <flux:button href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" variant="ghost" class="leading-6" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check" class="leading-6 rounded-[var(--radius-md)]" wire:loading.attr="disabled" wire:target="save" data-test="edit-user-submit">
                {{ __('Save Changes') }}
            </flux:button>
        </div>
    </form>
</div>
