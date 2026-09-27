@props(['message'])

@if ($message)
    <div {{ $attributes->class('flex items-center justify-between gap-4 rounded-lg border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-800') }} role="status">
        <span>{{ $message }}</span>
        <button type="button" wire:click="$set('notice', null)" class="font-medium underline-offset-2 hover:underline">Dismiss</button>
    </div>
@endif
