<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php($appName = config('app.name', 'e-BERKAT JANM'))
        @php($pageTitle = trim($__env->yieldContent('title')))

        <title inertia>{{ $pageTitle !== '' ? $pageTitle.' | '.$appName : $appName }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-berkat.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
