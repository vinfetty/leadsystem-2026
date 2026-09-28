@props(['status'])

@php
    $colors = match ($status) {
        App\Enums\LeadStatus::New => 'bg-sky-50 text-sky-800 ring-sky-600/20',
        App\Enums\LeadStatus::Assigned => 'bg-violet-50 text-violet-800 ring-violet-600/20',
        App\Enums\LeadStatus::Contacted => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        App\Enums\LeadStatus::Scheduled => 'bg-brand-50 text-brand-800 ring-brand-600/20',
        App\Enums\LeadStatus::Routed => 'bg-green-50 text-green-800 ring-green-600/20',
        App\Enums\LeadStatus::Rejected => 'bg-rose-50 text-rose-800 ring-rose-600/20',
        App\Enums\LeadStatus::Dead => 'bg-stone-100 text-stone-600 ring-stone-500/20',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset', $colors]) }}>
    {{ $status->label() }}
</span>
