{{--
    Every admin sidebar link — this package's, and any other package's that
    contributed one through Navigation::TAG. Include it once:

        <x-user-management::nav />

    Nothing here names a package. Adding a screen, here or elsewhere, does not
    touch this file.

    $sections arrives already grouped and already filtered by policy, from
    Kreetancraft\UserManagement\View\Components\Nav. Items carrying a `group`
    render under a heading; loose ones sit at the top level and come first, so a
    package cannot push your own links below its heading.

    PUBLISHING THIS FILE MEANS YOU OWN IT. It has its own tag
    (`user-management-nav`) and is deliberately excluded from
    `user-management-views`, because a copy frozen before a feature existed
    drops that feature with nothing to explain it.

    No wire:key on the items: wire:key on a slotted Flux component inside a loop
    makes Livewire's compiled wire-keys emit an unbalanced endif. Livewire 4
    keys loops itself (smart_wire_keys), and this list is static per request.
--}}
@php
    // Resolved here when nothing hands it in. This view is reached two ways —
    // through View\Components\Nav, and as a plain anonymous component when a
    // published copy is rendered directly — and 0.11.0 shipped assuming only
    // the first, which meant an undefined $sections and a 500 on every page
    // with a sidebar. Depending on the caller for data was the mistake.
    $sections ??= app(\Kreetancraft\UserManagement\Navigation::class)->grouped();
@endphp

@foreach ($sections as $heading => $sectionItems)
    @if ($heading === '')
        @foreach ($sectionItems as $item)
            <flux:navlist.item
                :icon="$item['icon']"
                :href="$item['href']"
                :current="$item['active']"
                wire:navigate
            >{{ $item['label'] }}</flux:navlist.item>
        @endforeach
    @else
        <flux:navlist.group
            :heading="$heading"
            expandable
            :expanded="collect($sectionItems)->contains('active', true)"
        >
            @foreach ($sectionItems as $item)
                <flux:navlist.item
                    :icon="$item['icon']"
                    :href="$item['href']"
                    :current="$item['active']"
                    wire:navigate
                >{{ $item['label'] }}</flux:navlist.item>
            @endforeach
        </flux:navlist.group>
    @endif
@endforeach
