@props([
    'title',
    'subtitle' => null,
])

{{-- One page shell for every screen: crumbs, then the title, then the one
     primary action. Everything else is subordinate by construction. --}}
<div class="space-y-4">
    @isset($breadcrumbs)
        {{ $breadcrumbs }}
    @endisset

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" level="1">{{ $title }}</flux:heading>

            @if ($subtitle)
                <flux:subheading class="max-w-xl">{{ $subtitle }}</flux:subheading>
            @endif

            @isset($meta)
                <div class="flex flex-wrap items-center gap-2 pt-2">{{ $meta }}</div>
            @endisset
        </div>

        @isset($actions)
            <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
