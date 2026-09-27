<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignOutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_signs_the_user_out_and_returns_to_sign_in(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_sign_in(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
    }
}
