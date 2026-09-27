@props(['title'])

<section {{ $attributes->class('rounded-xl border border-stone-200 bg-white shadow-xs') }}>
    <h2 class="border-b border-stone-100 px-5 py-3 text-sm font-semibold text-stone-900">{{ $title }}</h2>

    <div class="px-5 py-4">
        {{ $slot }}
    </div>
</section>
