<div {{ $attributes }} class='p-6 border rounded'>
@if(isset($title))<h4>{{ $title }}</h4>@endif
@if(isset($subtitle))<p>{{ $subtitle }}</p>@endif
{{ $slot }}
</div>