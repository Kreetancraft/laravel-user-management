<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 flex items-center justify-center">
    <div class="w-full max-w-md p-6">
        {{ $slot }}
    </div>
</body>
</html>
