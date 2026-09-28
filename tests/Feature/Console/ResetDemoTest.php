<?php

namespace Tests\Feature\Console;

use App\Models\Lead;
use App\Support\Demo;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * Rebuilding the schema cannot happen inside the transaction that
 * RefreshDatabase wraps around a test, so this class migrates instead.
 */
class ResetDemoTest extends TestCase
{
    use DatabaseMigrations;

    public function test_refuses_and_leaves_the_data_alone_when_demo_mode_is_off(): void
    {
        config(['leads.demo' => false]);
        $lead = Lead::factory()->create();

        $this->artisan('demo:reset')
            ->expectsOutputToContain('Demo mode is off, so nothing was reset.')
            ->assertFailed();

        $this->assertModelExists($lead);
    }

    public function test_replaces_whatever_visitors_did_with_fresh_demo_data(): void
    {
        config(['leads.demo' => true]);
        $left = Lead::factory()->create(['email' => 'left.by.a.visitor@example.com']);

        $this->artisan('demo:reset')->assertSuccessful();

        $this->assertDatabaseMissing('leads', ['email' => $left->email]);
        $this->assertDatabaseHas('users', ['email' => Demo::ACCOUNTS['admin']]);
        $this->assertDatabaseHas('users', ['email' => Demo::ACCOUNTS['processor']]);
        $this->assertDatabaseHas('buyers', ['name' => 'Elmstead Funding']);
        $this->assertGreaterThan(0, Lead::query()->count());
    }
}
