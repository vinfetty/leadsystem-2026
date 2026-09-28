@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $leads = $this->leads;
    $summary = $this->summary;
    $pageIds = $leads->pluck('id')->all();
    $wholePageChosen = $pageIds !== [] && array_diff($pageIds, array_map('intval', $selected)) === [];
@endphp

<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Leads</h1>
            <p class="mt-1 text-sm text-stone-600">
                {{ $isAdmin ? 'Every lead from every source.' : 'Your leads and the unassigned pool.' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2" role="group" aria-label="Quick views">
            @foreach (['unassigned' => 'Unassigned', 'mine' => 'Mine', 'due' => 'Call-backs due', 'all' => 'All open'] as $view => $label)
                @php
                    $current = $status === 'open' && $search === '' && $source === '' && $state === ''
                        && ($view === 'due' ? $due && $owner === 'all' : ! $due && $owner === $view);
                @endphp
                <button type="button" wire:click="show('{{ $view }}')" wire:key="view-{{ $view }}"
                    @class([
                        'rounded-lg border px-3.5 py-2 text-left text-sm shadow-xs',
                        'border-brand-600 bg-brand-50 text-brand-800' => $current,
                        'border-stone-200 bg-white text-stone-700 hover:bg-stone-50' => ! $current,
                    ])
                    @if ($current) aria-pressed="true" @else aria-pressed="false" @endif>
                    <span class="block text-xs text-stone-500">{{ $label }}</span>
                    <span class="block text-lg font-semibold tabular-nums">{{ number_format($summary[$view === 'all' ? 'open' : $view]) }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <x-notice :message="$notice" class="mt-5" />

    <div class="mt-5 grid gap-3 rounded-xl border border-stone-200 bg-white p-3 shadow-xs sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))_auto]">
        <div>
            <label for="search" class="sr-only">Search by name, email or phone</label>
            <input wire:model.live.debounce.300ms="search" id="search" type="search" class="field" placeholder="Search name, email or phone">
        </div>

        <div>
            <label for="status" class="sr-only">Status</label>
            <select wire:model.live="status" id="status" class="field">
                <option value="open">Open leads</option>
                <option value="all">Every status</option>
                @foreach ($statuses as $option)
                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="source" class="sr-only">Source</label>
            <select wire:model.live="source" id="source" class="field">
                <option value="">Every source</option>
                @foreach ($this->sources as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="state" class="sr-only">State</label>
            <select wire:model.live="state" id="state" class="field">
                <option value="">Every state</option>
                @foreach ($states as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="owner" class="sr-only">Assigned to</label>
            <select wire:model.live="owner" id="owner" class="field">
                <option value="all">{{ $isAdmin ? 'Any broker' : 'Mine and unassigned' }}</option>
                <option value="mine">Assigned to me</option>
                <option value="unassigned">Unassigned</option>
            </select>
        </div>

        <button type="button" wire:click="clearFilters" class="button-quiet" @disabled(! $this->hasFilters())>Clear</button>
    </div>

    @if ($isAdmin && $selected !== [])
        <form wire:submit="assignSelected" class="mt-3 flex flex-wrap items-center gap-3 rounded-xl border border-brand-100 bg-brand-50 px-4 py-3">
            <p class="text-sm font-medium text-brand-800">
                {{ trans_choice('{1} 1 lead selected|[2,*] :count leads selected', count($selected)) }}
            </p>

            <div class="ml-auto flex flex-wrap items-center gap-2">
                <label for="assignTo" class="text-sm text-brand-800">Assign to</label>
                <select wire:model="assignTo" id="assignTo" class="field w-auto">
                    <option value="">Choose a broker</option>
                    @foreach ($this->brokers as $broker)
                        <option value="{{ $broker->id }}">{{ $broker->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="button">Assign</button>
                <button type="button" wire:click="$set('selected', [])" class="button-quiet">Cancel</button>
            </div>

            @error('assignTo')
                <p class="w-full text-sm text-red-700">{{ $message }}</p>
            @enderror
        </form>
    @endif

    <div class="mt-3 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-xs">
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search,status,source,state,owner,clearFilters,show,gotoPage,nextPage,previousPage">
            <table class="min-w-full divide-y divide-stone-200 text-sm lg:w-full lg:table-fixed">
                <caption class="sr-only">Leads, newest first</caption>
                <thead class="bg-stone-50 text-left text-xs font-medium tracking-wide text-stone-500 uppercase">
                    <tr>
                        @if ($isAdmin)
                            <th scope="col" class="w-10 py-3 pr-0 pl-4 lg:w-10">
                                <input type="checkbox" wire:click="togglePage" @checked($wholePageChosen)
                                    class="size-4 rounded border-stone-300 accent-brand-700" aria-label="Select every lead on this page">
                            </th>
                        @endif
                        <th scope="col" class="px-3 py-3">Lead</th>
                        <th scope="col" class="hidden px-3 py-3 sm:table-cell lg:w-44">Location</th>
                        <th scope="col" class="hidden px-3 py-3 md:table-cell lg:w-56">Loan</th>
                        <th scope="col" class="hidden px-3 py-3 xl:table-cell xl:w-40">Source</th>
                        <th scope="col" class="hidden px-3 py-3 sm:table-cell lg:w-32">Status</th>
                        <th scope="col" class="px-3 py-3 lg:w-40">Broker</th>
                        <th scope="col" class="hidden px-3 py-3 xl:table-cell xl:w-32">Received</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-stone-100">
                    @forelse ($leads as $lead)
                        @php $callable = $lead->isCallableNow(); @endphp
                        <tr wire:key="lead-{{ $lead->id }}" class="align-top hover:bg-stone-50/70">
                            @if ($isAdmin)
                                <td class="py-3 pr-0 pl-4">
                                    <input type="checkbox" wire:model.live="selected" value="{{ $lead->id }}"
                                        class="size-4 rounded border-stone-300 accent-brand-700" aria-label="Select {{ $lead->fullName() }}">
                                </td>
                            @endif

                            <td class="px-3 py-3">
                                <p class="max-w-36 truncate font-medium sm:max-w-52 lg:max-w-none">
                                    <a href="{{ route('leads.show', $lead) }}" class="text-stone-900 underline-offset-2 hover:text-brand-700 hover:underline">{{ $lead->fullName() }}</a>
                                </p>
                                <p class="max-w-36 truncate text-stone-500 sm:max-w-52 lg:max-w-none" title="{{ $lead->email }}">{{ $lead->email }}</p>
                                <p class="text-stone-500 tabular-nums">{{ $lead->formattedPhone() }}</p>
                                <x-status-badge :status="$lead->status" class="mt-1.5 sm:hidden" />
                            </td>

                            <td class="hidden px-3 py-3 sm:table-cell">
                                <p class="truncate text-stone-900">{{ $lead->city }}, {{ $lead->state }}</p>
                                <p class="flex items-center gap-1.5 whitespace-nowrap text-stone-500 tabular-nums">
                                    <span @class(['size-2 rounded-full', 'bg-green-600' => $callable, 'bg-stone-300' => ! $callable]) aria-hidden="true"></span>
                                    {{ $lead->localTime()->format('g:i a T') }}
                                    <span class="sr-only">{{ $callable ? 'Within calling hours' : 'Outside calling hours' }}</span>
                                </p>
                            </td>

                            <td class="hidden px-3 py-3 md:table-cell">
                                <p class="whitespace-nowrap text-stone-900 tabular-nums">${{ number_format($lead->loan_amount) }}</p>
                                <p class="truncate text-stone-500">
                                    {{ $lead->loan_type->label() }}@if ($lead->loanToValue() !== null), {{ $lead->loanToValue() }}% LTV @endif
                                </p>
                                <p class="truncate text-stone-500">{{ $lead->credit_rating->label() }} credit</p>
                            </td>

                            <td class="hidden truncate px-3 py-3 text-stone-700 xl:table-cell" title="{{ $lead->source->name }}">{{ $lead->source->name }}</td>

                            <td class="hidden px-3 py-3 sm:table-cell">
                                <x-status-badge :status="$lead->status" />
                                @if ($lead->isOpen() && $lead->follow_up_at)
                                    <p @class(['mt-1 truncate text-xs tabular-nums', 'font-medium text-red-700' => $lead->isOverdue(), 'text-stone-500' => ! $lead->isOverdue()])
                                        title="Call back {{ $lead->follow_up_at->setTimezone($lead->timezone)->format('D j M Y, g:i a T') }}">
                                        {{ $lead->follow_up_at->setTimezone($lead->timezone)->format('j M, g:i a') }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-3 py-3">
                                @if ($lead->broker)
                                    <p class="max-w-24 truncate text-stone-900 sm:max-w-40 lg:max-w-none" title="{{ $lead->broker->name }}">
                                        {{ $lead->broker->is($user) ? 'You' : $lead->broker->name }}
                                    </p>
                                @else
                                    <p class="truncate text-stone-500">Unassigned</p>
                                    @if (! $isAdmin)
                                        @can('claim', $lead)
                                            <button type="button" wire:click="claim({{ $lead->id }})" wire:loading.attr="disabled"
                                                wire:target="claim({{ $lead->id }})" class="button-quiet mt-1.5 px-2.5 py-1 whitespace-nowrap">
                                                Take lead
                                            </button>
                                        @endcan
                                    @endif
                                @endif
                            </td>

                            <td class="hidden truncate px-3 py-3 text-stone-500 xl:table-cell">
                                <time datetime="{{ $lead->created_at->toIso8601String() }}" title="{{ $lead->created_at->toDayDateTimeString() }}">
                                    {{ $lead->created_at->diffForHumans() }}
                                </time>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 8 : 7 }}" class="px-4 py-14 text-center">
                                <p class="font-medium text-stone-900">No leads match these filters.</p>
                                @if ($this->hasFilters())
                                    <button type="button" wire:click="clearFilters" class="mt-2 text-sm font-medium text-brand-700 underline-offset-2 hover:underline">
                                        Clear the filters
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($leads->hasPages())
            <div class="border-t border-stone-200 px-3 py-3">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>
