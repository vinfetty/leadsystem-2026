<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Buyers</h1>
            <p class="mt-1 max-w-2xl text-sm text-stone-600">
                A verified lead is offered to these buyers from the top down. The first one whose rules it meets,
                and whose cap has room, takes it.
            </p>
        </div>

        @unless ($formIsOpen)
            <button type="button" wire:click="add" class="button">Add a buyer</button>
        @endunless
    </div>

    <x-notice :message="$notice" class="mt-5" />

    @if ($formIsOpen)
        <form wire:submit="save" class="mt-5 rounded-xl border border-stone-200 bg-white shadow-xs">
            <h2 class="border-b border-stone-100 px-5 py-3 text-sm font-semibold text-stone-900">
                {{ $form->buyerId ? 'Change '.$form->name : 'Add a buyer' }}
            </h2>

            <div class="grid gap-x-6 gap-y-5 px-5 py-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-stone-700">Name</label>
                    <input wire:model="form.name" id="name" type="text" class="field" @error('form.name') aria-invalid="true" @enderror>
                    @error('form.name') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tier" class="mb-1 block text-sm font-medium text-stone-700">Tier <span class="font-normal text-stone-500">(1 is offered first)</span></label>
                    <input wire:model="form.tier" id="tier" type="number" min="1" max="9" inputmode="numeric" class="field" @error('form.tier') aria-invalid="true" @enderror>
                    @error('form.tier') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="price" class="mb-1 block text-sm font-medium text-stone-700">Price per lead <span class="font-normal text-stone-500">($)</span></label>
                    <input wire:model="form.price" id="price" type="text" inputmode="decimal" class="field" @error('form.price') aria-invalid="true" @enderror>
                    @error('form.price') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="dailyCap" class="mb-1 block text-sm font-medium text-stone-700">Leads per day <span class="font-normal text-stone-500">(blank for no cap)</span></label>
                    <input wire:model="form.dailyCap" id="dailyCap" type="number" min="1" inputmode="numeric" class="field" @error('form.dailyCap') aria-invalid="true" @enderror>
                    @error('form.dailyCap') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="minLoanAmount" class="mb-1 block text-sm font-medium text-stone-700">Smallest loan <span class="font-normal text-stone-500">($)</span></label>
                    <input wire:model="form.minLoanAmount" id="minLoanAmount" type="number" min="0" step="1000" inputmode="numeric" class="field" @error('form.minLoanAmount') aria-invalid="true" @enderror>
                    @error('form.minLoanAmount') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="maxLoanAmount" class="mb-1 block text-sm font-medium text-stone-700">Largest loan <span class="font-normal text-stone-500">($)</span></label>
                    <input wire:model="form.maxLoanAmount" id="maxLoanAmount" type="number" min="0" step="1000" inputmode="numeric" class="field" @error('form.maxLoanAmount') aria-invalid="true" @enderror>
                    @error('form.maxLoanAmount') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="minCreditRating" class="mb-1 block text-sm font-medium text-stone-700">Credit</label>
                    <select wire:model="form.minCreditRating" id="minCreditRating" class="field">
                        <option value="">Any credit</option>
                        @foreach ($creditRatings as $rating)
                            <option value="{{ $rating->value }}">{{ $rating->label() }} or better</option>
                        @endforeach
                    </select>
                    @error('form.minCreditRating') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="states" class="mb-1 block text-sm font-medium text-stone-700">States <span class="font-normal text-stone-500">(blank for every state)</span></label>
                    <input wire:model="form.states" id="states" type="text" class="field" placeholder="OH, PA, NY" @error('form.states') aria-invalid="true" @enderror>
                    @error('form.states') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="zipPrefixes" class="mb-1 block text-sm font-medium text-stone-700">ZIP codes, or the start of them <span class="font-normal text-stone-500">(blank for any)</span></label>
                    <input wire:model="form.zipPrefixes" id="zipPrefixes" type="text" class="field" placeholder="432, 44101" @error('form.zipPrefixes') aria-invalid="true" @enderror>
                    @error('form.zipPrefixes') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <fieldset class="sm:col-span-2 lg:col-span-3">
                    <legend class="mb-1.5 text-sm font-medium text-stone-700">Loan types <span class="font-normal text-stone-500">(none ticked means every type)</span></legend>
                    <div class="flex flex-wrap gap-x-5 gap-y-2">
                        @foreach ($loanTypes as $type)
                            <label class="flex items-center gap-2 text-sm text-stone-700">
                                <input wire:model="form.loanTypes" type="checkbox" value="{{ $type->value }}" class="size-4 rounded border-stone-300 accent-brand-700">
                                {{ $type->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('form.loanTypes.*') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </fieldset>

                <label class="flex items-center gap-2 self-end text-sm text-stone-700">
                    <input wire:model="form.active" type="checkbox" class="size-4 rounded border-stone-300 accent-brand-700">
                    Buying leads now
                </label>
            </div>

            <div class="flex justify-end gap-2 border-t border-stone-100 px-5 py-3">
                <button type="button" wire:click="close" class="button-quiet">Cancel</button>
                <button type="submit" class="button" wire:loading.attr="disabled" wire:target="save">Save</button>
            </div>
        </form>
    @endif

    <div class="mt-5 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <caption class="sr-only">Buyers, in the order a lead is offered to them</caption>
                <thead class="bg-stone-50 text-left text-xs font-medium tracking-wide text-stone-500 uppercase">
                    <tr>
                        <th scope="col" class="px-4 py-3">Buyer</th>
                        <th scope="col" class="px-4 py-3">Tier</th>
                        <th scope="col" class="px-4 py-3">Pays</th>
                        <th scope="col" class="px-4 py-3">Today</th>
                        <th scope="col" class="hidden px-4 py-3 md:table-cell">Rules</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Change</span></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-stone-100">
                    @forelse ($this->buyers as $buyer)
                        @php
                            $today = $buyer->deliveredToday();
                            $full = $buyer->daily_cap !== null && $today >= $buyer->daily_cap;
                            $rules = $buyer->ruleSummary();
                        @endphp
                        <tr wire:key="buyer-{{ $buyer->id }}" class="align-top">
                            <td class="px-4 py-3">
                                <p class="font-medium text-stone-900">{{ $buyer->name }}</p>
                                @unless ($buyer->active)
                                    <p class="text-stone-500">Paused</p>
                                @endunless
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ $buyer->tier }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $buyer->price() }}</td>
                            <td @class(['px-4 py-3 whitespace-nowrap tabular-nums', 'font-medium text-red-700' => $full])>
                                {{ $today }}{{ $buyer->daily_cap === null ? '' : ' of '.$buyer->daily_cap }}
                                @if ($full) <span class="font-normal">(full)</span> @endif
                            </td>
                            <td class="hidden px-4 py-3 text-stone-700 md:table-cell">
                                @forelse ($rules as $rule)
                                    <p>{{ $rule }}</p>
                                @empty
                                    <p class="text-stone-500">Takes any lead</p>
                                @endforelse
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="edit({{ $buyer->id }})" class="button-quiet px-2.5 py-1">
                                    Change<span class="sr-only"> {{ $buyer->name }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-14 text-center text-stone-600">
                                No buyers yet. Until there is one, every verified lead is rejected.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
