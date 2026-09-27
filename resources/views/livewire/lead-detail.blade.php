@php
    $user = auth()->user();
    $canUpdate = $user->can('update', $lead);
    $callable = $lead->isCallableNow();
    $callback = $lead->isOpen() ? $lead->follow_up_at?->setTimezone($lead->timezone) : null;
    $zone = $lead->localTime()->format('T');
@endphp

<div>
    <a href="{{ route('leads.index') }}" class="text-sm font-medium text-brand-700 underline-offset-2 hover:underline">&larr; All leads</a>

    <div class="mt-3">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-semibold tracking-tight break-words">{{ $lead->fullName() }}</h1>
            <x-status-badge :status="$lead->status" />
        </div>
        <p class="mt-1 text-sm text-stone-600">
            {{ $lead->loan_type->label() }}, ${{ number_format($lead->loan_amount) }}.
            From {{ $lead->source->name }}, received
            <time datetime="{{ $lead->created_at->toIso8601String() }}" title="{{ $lead->created_at->toDayDateTimeString() }}">{{ $lead->created_at->diffForHumans() }}</time>.
        </p>
    </div>

    <x-notice :message="$notice" class="mt-5" />

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Contact">
                <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-stone-500">Phone</dt>
                        <dd class="mt-0.5 font-medium tabular-nums">
                            <a href="tel:+1{{ $lead->phone }}" class="text-brand-700 underline-offset-2 hover:underline">{{ $lead->formattedPhone() }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Their time now</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5 font-medium tabular-nums">
                            <span @class(['size-2 rounded-full', 'bg-green-600' => $callable, 'bg-stone-300' => ! $callable]) aria-hidden="true"></span>
                            {{ $lead->localTime()->format('g:i a T') }}
                            <span class="font-normal text-stone-500">{{ $callable ? 'OK to call' : 'Outside calling hours' }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Email</dt>
                        <dd class="mt-0.5 font-medium break-all">
                            <a href="mailto:{{ $lead->email }}" class="text-brand-700 underline-offset-2 hover:underline">{{ $lead->email }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Best time to call</dt>
                        <dd class="mt-0.5 font-medium">{{ $lead->best_time_to_call ?? 'Not given' }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Address</dt>
                        <dd class="mt-0.5 font-medium">{{ $lead->address }}<br>{{ $lead->city }}, {{ $lead->state }} {{ $lead->zip }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Consent to contact</dt>
                        <dd class="mt-0.5 font-medium">
                            {{ $lead->consent_at->format('j M Y, g:i a') }} UTC
                            <span class="block font-normal text-stone-500">from {{ $lead->consent_ip }}</span>
                        </dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Loan">
                <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-stone-500">Loan amount</dt>
                        <dd class="mt-0.5 font-medium tabular-nums">${{ number_format($lead->loan_amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Property value</dt>
                        <dd class="mt-0.5 font-medium tabular-nums">${{ number_format($lead->property_value) }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Loan to value</dt>
                        <dd class="mt-0.5 font-medium tabular-nums">{{ $lead->loanToValue() === null ? 'Unknown' : $lead->loanToValue().'%' }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Loan type</dt>
                        <dd class="mt-0.5 font-medium">{{ $lead->loan_type->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Credit</dt>
                        <dd class="mt-0.5 font-medium">{{ $lead->credit_rating->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Yearly income</dt>
                        <dd class="mt-0.5 font-medium tabular-nums">{{ $lead->yearly_income === null ? 'Not given' : '$'.number_format($lead->yearly_income) }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="History">
                @if ($lead->actions->isEmpty())
                    <p class="text-sm text-stone-500">Nothing recorded yet.</p>
                @endif

                <ol class="space-y-5">
                    @foreach ($lead->actions as $action)
                        <li wire:key="action-{{ $action->id }}" class="flex gap-3">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-600" aria-hidden="true"></span>
                            <div class="min-w-0 text-sm">
                                <p>
                                    <span class="font-medium text-stone-900">{{ $action->type->label() }}</span>
                                    <span class="text-stone-500">
                                        by {{ $action->user?->name ?? 'the system' }},
                                        <time datetime="{{ $action->created_at->toIso8601String() }}" title="{{ $action->created_at->toDayDateTimeString() }}">{{ $action->created_at->diffForHumans() }}</time>
                                    </span>
                                </p>
                                @if ($action->note)
                                    <p class="mt-0.5 break-words whitespace-pre-line text-stone-700">{{ $action->note }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Assigned to">
                <p class="text-sm font-medium">
                    @if ($lead->broker)
                        {{ $lead->broker->is($user) ? 'You' : $lead->broker->name }}
                    @else
                        <span class="text-stone-500">Nobody yet</span>
                    @endif
                </p>

                @if (! $lead->broker && ! $user->isAdmin())
                    @can('claim', $lead)
                        <button type="button" wire:click="claim" class="button mt-3 w-full">Take lead</button>
                    @endcan
                @endif

                @can('assign', $lead)
                    <form wire:submit="assign" class="mt-3 flex gap-2">
                        <label for="assignTo" class="sr-only">Assign to</label>
                        <select wire:model="assignTo" id="assignTo" class="field">
                            <option value="">Choose a broker</option>
                            @foreach ($this->brokers as $broker)
                                <option value="{{ $broker->id }}" @disabled($lead->assigned_to === $broker->id)>{{ $broker->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="button-quiet shrink-0">Assign</button>
                    </form>
                    @error('assignTo')
                        <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                @endcan
            </x-card>

            @if ($canUpdate)
                <x-card title="Call-back">
                    @if ($callback)
                        <p @class(['text-sm font-medium', 'text-red-700' => $lead->isOverdue()])>
                            {{ $callback->format('D j M, g:i a T') }}
                            <span class="font-normal {{ $lead->isOverdue() ? '' : 'text-stone-500' }}">
                                ({{ $lead->isOverdue() ? 'due '.$callback->diffForHumans() : $callback->diffForHumans() }})
                            </span>
                        </p>
                    @elseif ($lead->isOpen())
                        <p class="text-sm text-stone-500">None scheduled.</p>
                    @else
                        <p class="text-sm text-stone-500">This lead is {{ strtolower($lead->status->label()) }}. Reopen it to schedule a call-back.</p>
                    @endif

                    @if ($lead->isOpen())
                        <form wire:submit="scheduleCallback" class="mt-3 space-y-2">
                            <label for="callbackAt" class="block text-sm font-medium text-stone-700">
                                {{ $callback ? 'Move it to' : 'Call back at' }}
                                <span class="font-normal text-stone-500">(their time, {{ $zone }})</span>
                            </label>
                            <input wire:model="callbackAt" id="callbackAt" type="datetime-local" class="field"
                                min="{{ $lead->localTime()->format(App\Rules\CallableTime::FORMAT) }}"
                                @error('callbackAt') aria-invalid="true" aria-describedby="callbackAt-error" @enderror>
                            @error('callbackAt')
                                <p id="callbackAt-error" class="text-sm text-red-700">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="button-quiet w-full">Schedule</button>
                        </form>
                    @endif
                </x-card>

                <x-card title="Record what happened">
                    <form wire:submit="logAction" class="space-y-3">
                        <div>
                            <label for="actionType" class="sr-only">What happened</label>
                            <select wire:model="actionType" id="actionType" class="field">
                                @foreach ($loggable as $type)
                                    <option value="{{ $type->value }}">{{ $type->prompt() }}</option>
                                @endforeach
                            </select>
                            @error('actionType')
                                <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="note" class="sr-only">Note</label>
                            <textarea wire:model="note" id="note" rows="4" maxlength="2000" class="field" placeholder="Add a note"
                                @error('note') aria-invalid="true" aria-describedby="note-error" @enderror></textarea>
                            @error('note')
                                <p id="note-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="button w-full" wire:loading.attr="disabled" wire:target="logAction">Save</button>
                    </form>
                </x-card>
            @elseif ($lead->broker === null)
                <p class="rounded-xl border border-dashed border-stone-300 px-5 py-4 text-sm text-stone-600">
                    Take this lead to log calls and schedule a call-back.
                </p>
            @endif
        </aside>
    </div>
</div>
