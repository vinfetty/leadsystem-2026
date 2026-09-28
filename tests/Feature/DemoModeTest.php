<?php

namespace Tests\Feature;

use App\Livewire\SignIn;
use App\Models\User;
use App\Support\Demo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['admin'])]
    #[TestWith(['processor'])]
    public function test_one_click_signs_the_visitor_in_as_a_demo_account(string $account): void
    {
        config(['leads.demo' => true]);
        $user = User::factory()->create(['email' => Demo::ACCOUNTS[$account]]);

        $form = Livewire::test(SignIn::class)->call('signInAs', $account);

        $form->assertRedirect(route('leads.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_returns_404_for_one_click_sign_in_when_demo_mode_is_off(): void
    {
        config(['leads.demo' => false]);
        User::factory()->admin()->create(['email' => Demo::ACCOUNTS['admin']]);

        $form = Livewire::test(SignIn::class)->call('signInAs', 'admin');

        $form->assertNotFound();
        $this->assertGuest();
    }

    public function test_returns_404_for_one_click_sign_in_as_anyone_but_a_demo_account(): void
    {
        config(['leads.demo' => true]);
        User::factory()->admin()->create(['email' => 'owner@example.com']);

        $form = Livewire::test(SignIn::class)->call('signInAs', 'owner@example.com');

        $form->assertNotFound();
        $this->assertGuest();
    }

    public function test_says_so_when_the_demo_accounts_have_not_been_seeded(): void
    {
        config(['leads.demo' => true]);

        $form = Livewire::test(SignIn::class)->call('signInAs', 'admin');

        $form->assertSee('The demo accounts have not been created yet.');
        $this->assertGuest();
    }

    public function test_offers_one_click_sign_in_only_in_demo_mode(): void
    {
        config(['leads.demo' => true]);
        $this->get(route('login'))->assertSee('Sign in as the admin');

        config(['leads.demo' => false]);
        $this->get(route('login'))->assertDontSee('Sign in as the admin');
    }

    public function test_tells_visitors_the_data_is_invented_only_in_demo_mode(): void
    {
        $user = User::factory()->create();

        config(['leads.demo' => true]);
        $this->actingAs($user)->get(route('leads.index'))->assertSee('Every name and number is invented');

        config(['leads.demo' => false]);
        $this->actingAs($user)->get(route('leads.index'))->assertDontSee('Every name and number is invented');
    }

    public function test_asks_search_engines_to_stay_away_only_in_demo_mode(): void
    {
        config(['leads.demo' => true]);
        $this->get(route('login'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/robots.txt')->assertContent("User-agent: *\nDisallow: /\n");

        config(['leads.demo' => false]);
        $this->get(route('login'))->assertHeaderMissing('X-Robots-Tag');
        $this->get('/robots.txt')->assertContent("User-agent: *\nDisallow:\n");
    }
}
