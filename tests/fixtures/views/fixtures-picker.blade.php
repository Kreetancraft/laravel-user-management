@props(['items' => [], 'group' => 'default', 'multiple' => false, 'label' => null, 'emptyLabel' => null, 'icon' => null])

{{-- Stands in for a host's picker view (media::picker-field in practice). --}}
<div data-picker="{{ $group }}">{{ count($items) }}</div>
