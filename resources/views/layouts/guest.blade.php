<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-full flex-col bg-stone-50 font-sans text-stone-900 antialiased">
        <x-demo-banner />

        <div class="grid grow place-items-center px-4 py-10">
            <main class="w-full max-w-sm">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
