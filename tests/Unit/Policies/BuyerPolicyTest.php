<?php

namespace Tests\Unit\Policies;

use App\Models\Buyer;
use App\Models\User;
use App\Policies\BuyerPolicy;
use Tests\TestCase;

class BuyerPolicyTest extends TestCase
{
    public function test_admin_may_see_add_and_change_buyers(): void
    {
        $admin = User::factory()->admin()->make();

        $this->assertTrue((new BuyerPolicy)->viewAny($admin));
        $this->assertTrue((new BuyerPolicy)->create($admin));
        $this->assertTrue((new BuyerPolicy)->update($admin, Buyer::factory()->make()));
    }

    public function test_processor_may_not_see_add_or_change_buyers(): void
    {
        $processor = User::factory()->make();

        $this->assertFalse((new BuyerPolicy)->viewAny($processor));
        $this->assertFalse((new BuyerPolicy)->create($processor));
        $this->assertFalse((new BuyerPolicy)->update($processor, Buyer::factory()->make()));
    }
}
