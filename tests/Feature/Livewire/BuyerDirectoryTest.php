<?php

namespace Tests\Feature\Livewire;

use App\Enums\CreditRating;
use App\Livewire\BuyerDirectory;
use App\Models\Buyer;
use App\Models\RoutingAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BuyerDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_sign_in(): void
    {
        $this->get(route('buyers.index'))->assertRedirect(route('login'));
    }

    public function test_processor_is_forbidden_from_the_buyers_page(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('buyers.index'));

        $response->assertForbidden();
    }

    public function test_processor_is_forbidden_from_loading_the_buyers_component(): void
    {
        $page = Livewire::actingAs(User::factory()->create())->test(BuyerDirectory::class);

        $page->assertForbidden();
        $this->assertDatabaseCount('buyers', 0);
    }

    public function test_admin_sees_the_buyers_in_the_order_a_lead_is_offered_to_them(): void
    {
        Buyer::factory()->tier(2)->create(['name' => 'Second Lender']);
        Buyer::factory()->tier(1)->payingCents(7000)->create(['name' => 'Cheaper Bank']);
        Buyer::factory()->tier(1)->payingCents(8500)->create(['name' => 'Dearer Bank']);

        $page = Livewire::actingAs(User::factory()->admin()->create())->test(BuyerDirectory::class);

        $page->assertSeeInOrder(['Dearer Bank', 'Cheaper Bank', 'Second Lender']);
    }

    public function test_shows_how_much_of_a_buyers_cap_is_used_today(): void
    {
        $buyer = Buyer::factory()->cappedAt(2)->create();
        RoutingAttempt::factory()->for($buyer)->count(2)->create();

        $page = Livewire::actingAs(User::factory()->admin()->create())->test(BuyerDirectory::class);

        $page->assertSeeInOrder(['2 of 2', '(full)']);
    }

    public function test_admin_adds_a_buyer_with_rules(): void
    {
        $page = Livewire::actingAs(User::factory()->admin()->create())
            ->test(BuyerDirectory::class)
            ->call('add')
            ->set('form.name', 'Alder National Bank')
            ->set('form.tier', '1')
            ->set('form.price', '85.50')
            ->set('form.dailyCap', '6')
            ->set('form.states', 'oh, pa  NY')
            ->set('form.zipPrefixes', '432, 44101')
            ->set('form.loanTypes', ['refinance', 'purchase'])
            ->set('form.minLoanAmount', '200000')
            ->set('form.maxLoanAmount', '750000')
            ->set('form.minCreditRating', 'good')
            ->call('save');

        $page->assertHasNoErrors()->assertSet('formIsOpen', false)->assertSee('Alder National Bank saved.');
        $buyer = Buyer::query()->sole();
        $this->assertSame('Alder National Bank', $buyer->name);
        $this->assertSame(1, $buyer->tier);
        $this->assertSame(8550, $buyer->price_cents);
        $this->assertSame(6, $buyer->daily_cap);
        $this->assertTrue($buyer->active);
        $this->assertSame(['OH', 'PA', 'NY'], $buyer->states);
        $this->assertSame(['432', '44101'], $buyer->zip_prefixes);
        $this->assertSame(['refinance', 'purchase'], $buyer->loan_types);
        $this->assertSame(200000, $buyer->min_loan_amount);
        $this->assertSame(750000, $buyer->max_loan_amount);
        $this->assertSame(CreditRating::Good, $buyer->min_credit_rating);
    }

    public function test_a_buyer_saved_with_blank_rules_takes_any_lead(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(BuyerDirectory::class)
            ->call('add')
            ->set('form.name', 'Elmstead Funding')
            ->set('form.price', '15')
            ->call('save');

        $buyer = Buyer::query()->sole();
        $this->assertSame(1500, $buyer->price_cents);
        $this->assertNull($buyer->daily_cap);
        $this->assertNull($buyer->states);
        $this->assertNull($buyer->zip_prefixes);
        $this->assertNull($buyer->loan_types);
        $this->assertNull($buyer->min_loan_amount);
        $this->assertNull($buyer->max_loan_amount);
        $this->assertNull($buyer->min_credit_rating);
    }

    public function test_admin_changes_a_buyer_without_creating_another(): void
    {
        $buyer = Buyer::factory()->servingStates('OH')->cappedAt(6)->create(['name' => 'Alder National Bank']);

        $page = Livewire::actingAs(User::factory()->admin()->create())
            ->test(BuyerDirectory::class)
            ->call('edit', $buyer->id)
            ->assertSet('form.name', 'Alder National Bank')
            ->assertSet('form.states', 'OH')
            ->assertSet('form.dailyCap', '6')
            ->set('form.states', 'OH, PA')
            ->set('form.dailyCap', '')
            ->set('form.active', false)
            ->call('save');

        $page->assertHasNoErrors();
        $this->assertDatabaseCount('buyers', 1);
        $buyer->refresh();
        $this->assertSame(['OH', 'PA'], $buyer->states);
        $this->assertNull($buyer->daily_cap);
        $this->assertFalse($buyer->active);
    }

    #[TestWith(['form.name', '', 'Give the buyer a name.'])]
    #[TestWith(['form.tier', '0', 'The tier must be between 1 and 9.'])]
    #[TestWith(['form.price', '', 'Enter the price the buyer pays for a lead.'])]
    #[TestWith(['form.price', '45.505', 'Enter the price in dollars and cents, such as 45 or 45.50.'])]
    #[TestWith(['form.dailyCap', '0', 'A cap must be at least 1. Leave it blank for no cap.'])]
    #[TestWith(['form.states', 'OH, ZZ', 'ZZ is not a state. Use two-letter codes such as OH, PA.'])]
    #[TestWith(['form.zipPrefixes', '432, 4321a', '4321A is not a ZIP code or the start of one.'])]
    #[TestWith(['form.maxLoanAmount', '100000', 'The largest loan cannot be smaller than the smallest.'])]
    public function test_refuses_a_buyer_with_an_invalid_field(string $field, string $value, string $message): void
    {
        $page = Livewire::actingAs(User::factory()->admin()->create())
            ->test(BuyerDirectory::class)
            ->call('add')
            ->set('form.name', 'Alder National Bank')
            ->set('form.price', '85')
            ->set('form.minLoanAmount', '200000')
            ->set($field, $value)
            ->call('save');

        $page->assertHasErrors($field)->assertSee($message);
        $this->assertDatabaseCount('buyers', 0);
    }

    public function test_refuses_a_loan_type_the_form_does_not_offer(): void
    {
        $page = Livewire::actingAs(User::factory()->admin()->create())
            ->test(BuyerDirectory::class)
            ->call('add')
            ->set('form.name', 'Alder National Bank')
            ->set('form.price', '85')
            ->set('form.loanTypes', ['payday'])
            ->call('save');

        $page->assertHasErrors('form.loanTypes.0');
        $this->assertDatabaseCount('buyers', 0);
    }

    public function test_escapes_buyer_names_in_the_list(): void
    {
        Buyer::factory()->create(['name' => '<script>alert("x")</script>']);

        $page = Livewire::actingAs(User::factory()->admin()->create())->test(BuyerDirectory::class);

        $page->assertSee('&lt;script&gt;', escape: false)->assertDontSee('<script>alert("x")</script>', escape: false);
    }
}
