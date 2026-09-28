<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-stone-50 font-sans text-stone-900 antialiased">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <div class="flex items-center gap-6">
                    <a href="{{ route('leads.index') }}" class="flex items-center gap-2.5 font-semibold tracking-tight">
                        <span class="grid size-7 place-items-center rounded-md bg-brand-700 text-xs text-white" aria-hidden="true">LS</span>
                        <span class="hidden sm:inline">{{ config('app.name') }}</span>
                    </a>

                    <nav class="flex items-center gap-1 text-sm font-medium" aria-label="Main">
                        <a href="{{ route('leads.index') }}" @class([
                            'rounded-md px-2.5 py-1.5',
                            'bg-stone-100 text-stone-900' => request()->routeIs('leads.*'),
                            'text-stone-600 hover:text-stone-900' => ! request()->routeIs('leads.*'),
                        ]) @if (request()->routeIs('leads.*')) aria-current="page" @endif>Leads</a>

                        @can('viewAny', App\Models\Buyer::class)
                            <a href="{{ route('buyers.index') }}" @class([
                                'rounded-md px-2.5 py-1.5',
                                'bg-stone-100 text-stone-900' => request()->routeIs('buyers.*'),
                                'text-stone-600 hover:text-stone-900' => ! request()->routeIs('buyers.*'),
                            ]) @if (request()->routeIs('buyers.*')) aria-current="page" @endif>Buyers</a>
                        @endcan
                    </nav>
                </div>

                <div class="flex items-center gap-3 text-sm">
                    <span class="hidden text-stone-600 sm:inline">{{ auth()->user()->name }}</span>
                    <span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-600">
                        {{ auth()->user()->role->label() }}
                    </span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="button-quiet">Sign out</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8">
            {{ $slot }}
        </main>
    </body>
</html>
