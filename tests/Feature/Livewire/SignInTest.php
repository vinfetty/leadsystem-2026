<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SignIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SignInTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_gets_the_sign_in_page(): void
    {
        $this->get(route('login'))->assertSeeLivewire(SignIn::class);
    }

    public function test_signed_in_user_is_sent_to_the_inbox_instead(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('leads.index'));
    }

    public function test_correct_credentials_sign_the_user_in_and_open_the_inbox(): void
    {
        $user = User::factory()->create();

        $form = Livewire::test(SignIn::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('signIn');

        $form->assertRedirect(route('leads.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_shows_an_error_and_keeps_the_visitor_signed_out(): void
    {
        $user = User::factory()->create();

        $form = Livewire::test(SignIn::class)
            ->set('email', $user->email)
            ->set('password', 'not-the-password')
            ->call('signIn');

        $form->assertSee('These credentials do not match our records.')->assertSet('password', '');
        $this->assertGuest();
    }

    public function test_unknown_email_gets_the_same_error_as_a_wrong_password(): void
    {
        $form = Livewire::test(SignIn::class)
            ->set('email', 'nobody@example.com')
            ->set('password', 'password')
            ->call('signIn');

        $form->assertSee('These credentials do not match our records.');
        $this->assertGuest();
    }

    public function test_refuses_even_the_correct_password_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();
        $form = Livewire::test(SignIn::class)->set('email', $user->email);
        foreach (range(1, 5) as $attempt) {
            $form->set('password', 'not-the-password')->call('signIn');
        }

        $form->set('password', 'password')->call('signIn');

        $form->assertSee('Too many login attempts.');
        $this->assertGuest();
    }

    public function test_requires_an_email_and_a_password(): void
    {
        $form = Livewire::test(SignIn::class)->call('signIn');

        $form->assertHasErrors(['email' => 'required', 'password' => 'required']);
        $this->assertGuest();
    }
}
