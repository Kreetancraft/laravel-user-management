@props([
    'title',
    'description' => null,
])

<div class="flex w-full flex-col gap-2">
    <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
    @if ($description)
        <flux:text variant="subtle">{{ $description }}</flux:text>
    @endif
</div>
