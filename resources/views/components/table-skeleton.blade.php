@props([
    'rows' => 5,
    'columns' => 4,
])

{{-- Shown while a filter or tab switch is in flight. Keeps the page height
     stable so the layout does not jump when results arrive. --}}
<div {{ $attributes->merge(['class' => 'space-y-3']) }} aria-hidden="true">
    @for ($row = 0; $row < (int) $rows; $row++)
        <div class="flex items-center gap-4">
            @for ($col = 0; $col < (int) $columns; $col++)
                <flux:skeleton class="h-4 flex-1" />
            @endfor
        </div>
    @endfor
</div>
