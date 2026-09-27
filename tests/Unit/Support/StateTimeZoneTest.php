<?php

namespace Tests\Unit\Support;

use App\Support\StateTimeZone;
use DateTimeZone;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class StateTimeZoneTest extends TestCase
{
    #[TestWith(['NY', 'America/New_York'])]
    #[TestWith(['TX', 'America/Chicago'])]
    #[TestWith(['AZ', 'America/Phoenix'])]
    #[TestWith(['HI', 'Pacific/Honolulu'])]
    #[TestWith([' wa ', 'America/Los_Angeles'])]
    public function test_returns_the_zone_for_a_state(string $state, string $zone): void
    {
        $this->assertSame($zone, StateTimeZone::for($state));
    }

    public function test_falls_back_to_eastern_for_an_unknown_state(): void
    {
        $this->assertSame('America/New_York', StateTimeZone::for('ZZ'));
    }

    public function test_every_state_maps_to_a_zone_php_recognises(): void
    {
        $known = DateTimeZone::listIdentifiers();

        foreach (StateTimeZone::states() as $state) {
            $this->assertContains(StateTimeZone::for($state), $known, $state);
        }
    }
}
