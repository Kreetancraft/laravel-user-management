{{-- Page-level summary. Per-field <flux:error> still does the precise work; this
     exists because on a long form the failing field can be off-screen, and a
     submit that appears to do nothing is the worst failure mode there is. --}}
@if ($errors->any())
    <flux:callout variant="danger" icon="exclamation-triangle" {{ $attributes }}>
        <flux:callout.heading>
            {{ trans_choice(
                '{1}There is a problem with one field|[2,*]There are problems with :count fields',
                $errors->count(),
                ['count' => $errors->count()]
            ) }}
        </flux:callout.heading>

        <flux:callout.text>
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </flux:callout.text>
    </flux:callout>
@endif
