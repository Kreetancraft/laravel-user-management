<div class="space-y-6">
    <x-user-management::page-header :title="$user->name" :subtitle="$user->email">
        <x-slot:breadcrumbs>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route(config('user-management.routes.names.users.index', 'admin.users')) }}" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $user->name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </x-slot:breadcrumbs>

        <x-slot:meta>
            <x-user-management::status-badge :active="$user->is_active" />
            @forelse ($user->roles as $role)
                <x-user-management::role-badge :role="$role" wire:key="role-{{ $role->id }}" />
            @empty
                <flux:text size="sm" variant="subtle">{{ __('No roles assigned') }}</flux:text>
            @endforelse
        </x-slot:meta>

        <x-slot:actions>
            @can('update', $user)
                <flux:button
                    href="{{ route(config('user-management.routes.names.users.edit', 'admin.users.edit'), $user) }}"
                    variant="primary"
                    icon="pencil-square"
                    wire:navigate
                >{{ __('Edit') }}</flux:button>
            @endcan
        </x-slot:actions>
    </x-user-management::page-header>

    <flux:separator variant="subtle" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div>
            <flux:card class="space-y-6">
                <div class="flex items-center gap-4">
                    <flux:avatar
                        circle
                        size="lg"
                        :name="$user->name"
                        :initials="$user->initials()"
                        :src="$user->avatarUrl()"
                    />
                    <div class="min-w-0">
                        <flux:heading size="lg" class="truncate">{{ $user->name }}</flux:heading>
                        <flux:text size="sm" variant="subtle" class="truncate">{{ $user->email }}</flux:text>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                {{-- Label left, value right, one line each: a spec sheet, not a form. --}}
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt><flux:text size="sm" variant="subtle">{{ __('Joined') }}</flux:text></dt>
                        <dd class="font-medium tabular-nums">{{ $user->created_at->format('M j, Y') }}</dd>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <dt><flux:text size="sm" variant="subtle">{{ __('Last seen') }}</flux:text></dt>
                        <dd class="font-medium">
                            @if ($user->last_login_at)
                                <flux:tooltip :content="$user->last_login_at->toDayDateTimeString()">
                                    <span>{{ $user->last_login_at->diffForHumans() }}</span>
                                </flux:tooltip>
                            @else
                                <flux:text size="sm" variant="subtle">{{ __('Never') }}</flux:text>
                            @endif
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <dt><flux:text size="sm" variant="subtle">{{ __('Last IP') }}</flux:text></dt>
                        <dd class="font-mono text-xs tabular-nums">{{ $user->last_login_ip ?? '—' }}</dd>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <dt><flux:text size="sm" variant="subtle">{{ __('Two-factor') }}</flux:text></dt>
                        <dd>
                            @if ($user->hasEnabledTwoFactorAuthentication())
                                <flux:badge size="sm" color="emerald" icon="lock-closed">{{ __('On') }}</flux:badge>
                            @elseif ($user->enforce_2fa)
                                <flux:badge size="sm" color="amber" icon="exclamation-triangle">{{ __('Required') }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">{{ __('Off') }}</flux:badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </flux:card>
        </div>

        <div class="lg:col-span-2 space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Sign-in history') }}</flux:heading>
                <flux:text size="sm" variant="subtle">{{ __('Where and when this account has been used.') }}</flux:text>
            </div>

            @if ($history->isEmpty())
                <flux:card>
                    <x-user-management::empty-state
                        icon="clock"
                        :heading="__('No sign-ins recorded')"
                        :description="__('History appears here once the user signs in.')"
                    />
                </flux:card>
            @else
                <flux:table :paginate="$history">
                    <flux:table.columns>
                        <flux:table.column>{{ __('When') }}</flux:table.column>
                        <flux:table.column>{{ __('Where') }}</flux:table.column>
                        <flux:table.column>{{ __('Device') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($history as $log)
                            <flux:table.row :key="$log->id">
                                <flux:table.cell class="whitespace-nowrap">
                                    <flux:tooltip :content="$log->created_at->toDayDateTimeString()">
                                        <span class="font-medium">{{ $log->created_at->diffForHumans() }}</span>
                                    </flux:tooltip>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ $log->formatted_location }}</span>
                                        <flux:text size="sm" variant="subtle" class="font-mono tabular-nums">
                                            {{ $log->ip_address }}
                                        </flux:text>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ $log->browser }}</span>
                                        <flux:text size="sm" variant="subtle">{{ $log->platform }}</flux:text>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </div>
    </div>
</div>
