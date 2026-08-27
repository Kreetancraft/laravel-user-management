<div {{ $attributes }}>
@if(isset($title))<h3>{{ $title }}</h3>@endif
{{ $slot }}
@if(isset($action))<div>{{ $action }}</div>@endif
</div>