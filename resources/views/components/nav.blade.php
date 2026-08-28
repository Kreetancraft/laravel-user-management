{{--
    Every admin sidebar link, from this package and from any other package that
    contributed one through Navigation::TAG. Include it once:

        <x-user-management::nav />

    Nothing here names a package. Adding a screen — here or elsewhere — does not
    touch this file.
--}}
@foreach (app(\Kreetancraft\UserManagement\Navigation::class)->items() as $item)
    <flux:navlist.item
        :icon="$item['icon']"
        :href="$item['href']"
        :current="$item['active']"
        wire:navigate
    >{{ $item['label'] }}</flux:navlist.item>
@endforeach
