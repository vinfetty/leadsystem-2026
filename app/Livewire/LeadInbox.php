<?php

namespace App\Livewire;

use App\Actions\AssignLead;
use App\Actions\ClaimLead;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use App\Support\StateTimeZone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Every lead a person may see, filtered and assigned from one screen.
 *
 * Stands in for the 2005 list pages, which were copied once per view and
 * again for each lender: viewleadsmort, vleadsmall, mortgageleads,
 * mortgageassigned and their per-lender copies.
 */
#[Title('Leads')]
class LeadInbox extends Component
{
    use WithPagination;

    public const PER_PAGE = 25;

    private const FILTERS = ['search', 'status', 'source', 'state', 'owner', 'due'];

    #[Url(except: '')]
    public string $search = '';

    /** "open", "all", or a LeadStatus value. */
    #[Url(except: 'open')]
    public string $status = 'open';

    #[Url(except: '')]
    public string $source = '';

    #[Url(except: '')]
    public string $state = '';

    /** "all", "mine" or "unassigned". */
    #[Url(except: 'all')]
    public string $owner = 'all';

    /** Only leads whose call-back time has arrived. */
    #[Url(except: false)]
    public bool $due = false;

    /** @var array<int, int|string> */
    public array $selected = [];

    public ?int $assignTo = null;

    public ?string $notice = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Lead::class);

        $this->notice = session('notice');
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::FILTERS, true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function clearFilters(): void
    {
        $this->reset([...self::FILTERS, 'selected']);
        $this->resetPage();
    }

    /**
     * Jump to one of the quick views: "unassigned", "mine", "due" or "all".
     */
    public function show(string $view): void
    {
        $this->clearFilters();

        match ($view) {
            'unassigned', 'mine' => $this->owner = $view,
            'due' => $this->due = true,
            default => null,
        };
    }

    public function togglePage(): void
    {
        $onPage = $this->leads->pluck('id')->all();
        $chosen = array_map(intval(...), $this->selected);

        $this->selected = array_diff($onPage, $chosen) === []
            ? array_values(array_diff($chosen, $onPage))
            : array_values(array_unique([...$chosen, ...$onPage]));
    }

    public function claim(int $leadId, ClaimLead $claimLead): void
    {
        $lead = Lead::query()->findOrFail($leadId);

        $taken = Gate::allows('claim', $lead) && $claimLead->handle($lead, Auth::user());

        $this->notice = $taken
            ? $lead->fullName().' is now yours.'
            : 'That lead has already been taken.';

        unset($this->leads, $this->summary);
    }

    public function assignSelected(AssignLead $assignLead): void
    {
        $this->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer'],
            'assignTo' => ['required', Rule::exists('users', 'id')],
        ], [
            'selected.required' => 'Choose at least one lead.',
            'assignTo.required' => 'Choose who to assign the leads to.',
        ]);

        $leads = Lead::query()->whereKey($this->selected)->get();
        $leads->each(fn (Lead $lead) => $this->authorize('assign', $lead));

        $broker = User::query()->findOrFail($this->assignTo);
        $leads->each(fn (Lead $lead) => $assignLead->handle($lead, $broker, Auth::user()));

        $this->notice = trans_choice('{1} 1 lead assigned to :name.|[2,*] :count leads assigned to :name.', $leads->count(), ['name' => $broker->name]);
        $this->reset('selected', 'assignTo');

        unset($this->leads, $this->summary);
    }

    /**
     * @return LengthAwarePaginator<int, Lead>
     */
    #[Computed]
    public function leads(): LengthAwarePaginator
    {
        return $this->filteredLeads()
            ->with(['source', 'broker'])
            ->when($this->due, fn (Builder $query) => $query->oldest('follow_up_at'))
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE);
    }

    /**
     * @return array{unassigned: int, mine: int, due: int, open: int}
     */
    #[Computed]
    public function summary(): array
    {
        $open = fn (): Builder => Lead::query()->visibleTo(Auth::user())->open();

        return [
            'unassigned' => $open()->whereNull('assigned_to')->count(),
            'mine' => $open()->where('assigned_to', Auth::id())->count(),
            'due' => $open()->due()->count(),
            'open' => $open()->count(),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function brokers(): Collection
    {
        return User::query()->brokers()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return Collection<int, LeadSource>
     */
    #[Computed]
    public function sources(): Collection
    {
        return LeadSource::query()->orderBy('name')->get(['id', 'name']);
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->status !== 'open' || $this->source !== ''
            || $this->state !== '' || $this->owner !== 'all' || $this->due;
    }

    public function render(): View
    {
        return view('livewire.lead-inbox', [
            'statuses' => LeadStatus::cases(),
            'states' => StateTimeZone::states(),
        ]);
    }

    /**
     * Filter values arrive from the query string, so anything unrecognised is ignored.
     *
     * @return Builder<Lead>
     */
    private function filteredLeads(): Builder
    {
        $user = Auth::user();
        $status = LeadStatus::tryFrom($this->status);

        return Lead::query()
            ->visibleTo($user)
            ->search($this->search)
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($status === null && $this->status !== 'all', fn (Builder $query) => $query->open())
            ->when(ctype_digit($this->source), fn (Builder $query) => $query->where('lead_source_id', (int) $this->source))
            ->when(in_array($this->state, StateTimeZone::states(), true), fn (Builder $query) => $query->where('state', $this->state))
            ->when($this->owner === 'mine', fn (Builder $query) => $query->where('assigned_to', $user->id))
            ->when($this->owner === 'unassigned', fn (Builder $query) => $query->whereNull('assigned_to'))
            ->when($this->due, fn (Builder $query) => $query->due());
    }
}
