{{--
    Stand-in for the HOST application's admin layout.

    This package ships no layouts by design: it renders into yours and inherits
    your Tailwind/Flux theme. The suite therefore has to play the host, and this
    file is that stub — it is test scaffolding, never published.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? config('app.name') }}</title>
    @fluxAppearance
</head>
<body>
    {{ $slot }}
    @fluxScripts
</body>
</html>
