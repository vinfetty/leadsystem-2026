<?php

namespace App\Livewire;

use App\Actions\AssignLead;
use App\Actions\ClaimLead;
use App\Actions\LogLeadAction;
use App\Actions\ScheduleCallback;
use App\Enums\LeadActionType;
use App\Models\Lead;
use App\Models\User;
use App\Rules\CallableTime;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * One lead: who they are, what has happened, and what to do next.
 *
 * Stands in for the 2005 action pages (actionviewmort and its
 * per-lender copies) and the separate schedule pages.
 */
class LeadDetail extends Component
{
    public Lead $lead;

    public string $actionType = '';

    public string $note = '';

    /** The lead's own local time, as a datetime-local input writes it. */
    public string $callbackAt = '';

    public ?int $assignTo = null;

    public ?string $notice = null;

    private bool $leadIsGone = false;

    /**
     * A lead the person may not see answers 404, so its existence is not confirmed.
     */
    public function mount(Lead $lead): void
    {
        abort_unless(Gate::allows('view', $lead), 404);

        $this->lead = $lead;
        $this->actionType = $this->loggableTypes()[0]->value;
    }

    /**
     * The lead may have been handed to a colleague while the page sat open.
     * Whatever was pressed is dropped and the person returns to the inbox.
     */
    public function hydrate(): void
    {
        if (Gate::denies('view', $this->lead)) {
            $this->leaveForTheInbox();
        }
    }

    public function logAction(LogLeadAction $logLeadAction): void
    {
        if ($this->leadIsGone) {
            return;
        }

        $this->authorize('update', $this->lead);

        $type = LeadActionType::tryFrom($this->actionType);

        $this->validate([
            'actionType' => ['required', Rule::in(array_column($this->loggableTypes(), 'value'))],
            'note' => [Rule::requiredIf((bool) $type?->requiresNote()), 'string', 'max:2000'],
        ], [
            'actionType.in' => 'Choose what happened.',
            'note.required' => 'Add a note to explain.',
        ]);

        $logLeadAction->handle($this->lead, Auth::user(), $type, $this->note);

        $this->notice = 'Saved to the history.';
        $this->reset('note');
        $this->actionType = $this->loggableTypes()[0]->value;
    }

    public function scheduleCallback(ScheduleCallback $scheduleCallback): void
    {
        if ($this->leadIsGone) {
            return;
        }

        $this->authorize('update', $this->lead);

        if (! $this->lead->isOpen()) {
            $this->addError('callbackAt', 'Reopen the lead before scheduling a call-back.');

            return;
        }

        $this->validate(
            ['callbackAt' => ['bail', 'required', new CallableTime($this->lead)]],
            ['callbackAt.required' => 'Choose a date and time.'],
        );

        $at = CallableTime::parse($this->callbackAt, $this->lead);
        $scheduleCallback->handle($this->lead, Auth::user(), $at);

        $this->notice = 'Call-back scheduled for '.$at->format('D j M, g:i a T').'.';
        $this->reset('callbackAt');
        $this->actionType = $this->loggableTypes()[0]->value;
    }

    public function claim(ClaimLead $claimLead): void
    {
        if ($this->leadIsGone) {
            return;
        }

        if (Gate::allows('claim', $this->lead) && $claimLead->handle($this->lead, Auth::user())) {
            $this->notice = 'This lead is now yours.';

            return;
        }

        $this->leaveForTheInbox();
    }

    public function assign(AssignLead $assignLead): void
    {
        if ($this->leadIsGone) {
            return;
        }

        $this->authorize('assign', $this->lead);

        $this->validate(
            ['assignTo' => ['required', Rule::exists('users', 'id')]],
            ['assignTo.required' => 'Choose who to assign the lead to.'],
        );

        $processor = User::query()->findOrFail($this->assignTo);
        $assignLead->handle($this->lead, $processor, Auth::user());

        $this->notice = 'Assigned to '.$processor->name.'.';
        $this->reset('assignTo');
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function processors(): Collection
    {
        return User::query()->processors()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return array<int, LeadActionType>
     */
    public function loggableTypes(): array
    {
        return LeadActionType::loggableFor($this->lead->status);
    }

    public function render(): View
    {
        $this->lead->refresh()->load(['source', 'processor', 'actions.user']);

        return view('livewire.lead-detail', [
            'loggable' => $this->loggableTypes(),
        ])->title($this->lead->fullName());
    }

    private function leaveForTheInbox(): void
    {
        $this->leadIsGone = true;

        session()->flash('notice', 'That lead is no longer available to you.');

        $this->redirectRoute('leads.index');
        $this->skipRender();
    }
}
