<?php

namespace App\Livewire;

use App\Enums\CreditRating;
use App\Enums\LoanType;
use App\Livewire\Forms\BuyerForm;
use App\Models\Buyer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The buyers, what each pays, and the rules a lead must meet to reach them.
 *
 * In 2005 a change to a buyer's requirements meant editing code. Here an
 * admin changes a field and the next lead is routed by the new rule.
 */
#[Title('Buyers')]
class BuyerDirectory extends Component
{
    public BuyerForm $form;

    public bool $formIsOpen = false;

    public ?string $notice = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Buyer::class);
    }

    public function add(): void
    {
        $this->authorize('create', Buyer::class);

        $this->form->reset();
        $this->resetValidation();
        $this->formIsOpen = true;
    }

    public function edit(int $buyerId): void
    {
        $buyer = Buyer::query()->findOrFail($buyerId);
        $this->authorize('update', $buyer);

        $this->form->fillFrom($buyer);
        $this->resetValidation();
        $this->formIsOpen = true;
    }

    public function save(): void
    {
        $this->form->buyerId === null
            ? $this->authorize('create', Buyer::class)
            : $this->authorize('update', Buyer::query()->findOrFail($this->form->buyerId));

        $buyer = $this->form->save();

        $this->notice = "{$buyer->name} saved. The next lead is routed by these rules.";
        $this->close();
    }

    public function close(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->formIsOpen = false;
    }

    /**
     * @return Collection<int, Buyer>
     */
    #[Computed]
    public function buyers(): Collection
    {
        return Buyer::query()->inRoutingOrder()->get();
    }

    public function render(): View
    {
        return view('livewire.buyer-directory', [
            'loanTypes' => LoanType::cases(),
            'creditRatings' => array_reverse(CreditRating::cases()),
        ]);
    }
}
