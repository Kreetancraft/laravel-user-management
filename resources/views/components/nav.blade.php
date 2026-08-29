{{--
    Every admin sidebar link, from this package and from any other package that
    contributed one through Navigation::TAG. Include it once:

        <x-user-management::nav />

    Nothing here names a package. Adding a screen — here or elsewhere — does not
    touch this file.

    Items carrying a `group` are rendered under a heading; loose ones sit at the
    top level and come first. A package with six screens groups them so it owns
    one line of your sidebar rather than six.
--}}
@php($sections = app(\Kreetancraft\UserManagement\Navigation::class)->grouped())

@foreach ($sections as $heading => $items)
    @if ($heading === '')
        @foreach ($items as $item)
            <flux:navlist.item
                :icon="$item['icon']"
                :href="$item['href']"
                :current="$item['active']"
                wire:navigate
            >{{ $item['label'] }}</flux:navlist.item>
        @endforeach
    @else
        <flux:navlist.group :heading="$heading" expandable :expanded="collect($items)->contains('active', true)">
            @foreach ($items as $item)
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
