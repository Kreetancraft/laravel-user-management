<div class="space-y-8">
    {{-- Page Header --}}
    <div class="space-y-3">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('User Details') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ $user->name }}</flux:heading>
                <flux:subheading>
                    {{ $user->email }}
                    @if ($user->last_login_at)
                        · {{ __('Last seen') }} {{ $user->last_login_at->diffForHumans() }}
                    @else
                        · {{ __('Never signed in') }}
                    @endif
                </flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:button href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" variant="ghost" wire:navigate icon="chevron-left">
                    {{ __('Back') }}
                </flux:button>
                @can('update', $user)
                    <flux:button href="{{ route(config('user-management.routes.names.users.edit', 'admin.users.edit'), $user) }}" variant="primary" icon="pencil" wire:navigate>
                        {{ __('Edit User') }}
                    </flux:button>
                @endcan
            </div>
        </div>
    </div>

    <flux:separator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        {{-- Left Column: User Profile Summary --}}
        <div class="lg:col-span-1 space-y-12">
            <flux:card class="overflow-hidden p-0">
                <div class="p-6 border-b border-zinc-100 dark:border-zinc-800 flex flex-col items-center text-center space-y-4">
                    <flux:avatar
                        circle
                        size="xl"
                        :name="$user->name"
                        :initials="$user->initials()"
                        :src="$user->avatarUrl()"
                        class="h-24 w-24 text-2xl shadow-sm border-2 border-white dark:border-zinc-800"
                    />

                    <div>
                        <flux:heading size="lg">{{ $user->name }}</flux:heading>
                        <flux:text size="sm" variant="subtle">{{ $user->email }}</flux:text>
                    </div>

                    <div class="flex flex-wrap justify-center gap-1.5">
                        @forelse ($user->roles as $role)
                            <x-user-management::role-badge :role="$role" wire:key="role-{{ $role->id }}" />
                        @empty
                            <flux:text size="sm" variant="subtle">{{ __('No roles assigned') }}</flux:text>
                        @endforelse

                        @if ($user->is_active)
                            <flux:badge size="sm" color="green">{{ __('Active') }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">{{ __('Inactive') }}</flux:badge>
                        @endif
                    </div>
                </div>

                <div class="p-6 space-y-4 text-sm">
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle" class="font-medium">{{ __('Account Created') }}</flux:text>
                        <flux:text class="font-semibold">{{ $user->created_at->format('M d, Y') }}</flux:text>
                    </div>

                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle" class="font-medium">{{ __('Last Login IP') }}</flux:text>
                        <flux:text class="font-mono font-semibold">{{ $user->last_login_ip ?? __('N/A') }}</flux:text>
                    </div>

                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle" class="font-medium">{{ __('2FA Enabled') }}</flux:text>
                        @if ($user->hasEnabledTwoFactorAuthentication())
                            <flux:badge size="sm" color="green">{{ __('Yes') }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">{{ __('No') }}</flux:badge>
                        @endif
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Right Column: Login History --}}
        <div class="lg:col-span-2 space-y-12">
            <flux:card class="p-0">
                <div class="p-6 border-b border-zinc-100 dark:border-zinc-800">
                    <flux:heading size="lg">{{ __('Login History') }}</flux:heading>
                    <flux:text size="sm" variant="subtle" class="mt-1">
                        {{ __('A registry of recent successful login events for this account.') }}
                    </flux:text>
                </div>

                <div class="p-0">
                    @if ($history->isEmpty())
                        <div class="p-12 text-center">
                            <flux:text variant="subtle">{{ __('No login logs found for this user.') }}</flux:text>
                        </div>
                    @else
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>{{ __('Date & Time') }}</flux:table.column>
                                <flux:table.column>{{ __('IP Address') }}</flux:table.column>
                                <flux:table.column>{{ __('Location') }}</flux:table.column>
                                <flux:table.column>{{ __('Device & Browser') }}</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($history as $log)
                                    <flux:table.row :key="$log->id">
                                        <flux:table.cell class="whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span class="font-medium">
                                                    {{ $log->created_at->format('M d, Y H:i:s') }}
                                                </span>
                                                <span class="text-xs opacity-70">
                                                    {{ $log->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell class="font-mono font-medium">
                                            {{ $log->ip_address }}
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex items-center gap-2">
                                                <span class="text-lg" title="{{ $log->country_code ?? 'Unknown' }}">
                                                    {{ $log->country_flag }}
                                                </span>
                                                <span class="font-medium">
                                                    {{ $log->formatted_location }}
                                                </span>
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex flex-col text-sm">
                                                <span class="font-medium">
                                                    {{ $log->browser }}
                                                </span>
                                                <span class="text-xs opacity-70">
                                                    {{ $log->platform }}
                                                </span>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>

                        <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                            {{ $history->links() }}
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>
    </div>
</div>
