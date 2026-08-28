@props([
    'groups',
    'selected' => [],
])

@php
    $total = $groups->flatten()->count();
    $chosen = count($selected);
@endphp

{{-- Shield's shape: one card per resource, actions inside it, a select-all on
     each card and one for everything. A flat list of forty checkboxes is not
     something anyone can audit; forty checkboxes in eight labelled cards is. --}}
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:text size="sm" variant="subtle" class="tabular-nums">
            {{ trans_choice('{0}Nothing selected|{1}:count of :total selected|[2,*]:count of :total selected', $chosen, ['count' => $chosen, 'total' => $total]) }}
        </flux:text>

        <flux:button size="sm" variant="subtle" wire:click="toggleAllPermissions" type="button">
            {{ $chosen === $total && $total > 0 ? __('Clear all') : __('Select all') }}
        </flux:button>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($groups as $group => $permissions)
            @php
                $names = $permissions->pluck('name')->all();
                $groupChosen = count(array_intersect($names, $selected));
                $allOn = $groupChosen === count($names);
            @endphp

            <div wire:key="group-{{ $group }}">
            <flux:card class="space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <flux:heading size="sm" class="truncate">
                            {{ \Illuminate\Support\Str::headline($group) }}
                        </flux:heading>
                        <flux:text size="sm" variant="subtle" class="tabular-nums">
                            {{ $groupChosen }}/{{ count($names) }}
                        </flux:text>
                    </div>

                    <flux:switch
                        :checked="$allOn"
                        wire:click="toggleGroup('{{ $group }}')"
                        :aria-label="$group"
                    />
                </div>

                <flux:separator variant="subtle" />

                <flux:checkbox.group wire:model.live="selectedPermissions" class="space-y-2">
                    @foreach ($permissions as $permission)
                        @php($action = \Illuminate\Support\Str::headline(\Illuminate\Support\Str::beforeLast($permission->name, '-')))

                        <flux:checkbox
                            wire:key="perm-{{ $permission->id }}"
                            value="{{ $permission->name }}"
                            :label="$action"
                        />
                    @endforeach
                </flux:checkbox.group>
            </flux:card>
            </div>
        @endforeach
    </div>
</div>
