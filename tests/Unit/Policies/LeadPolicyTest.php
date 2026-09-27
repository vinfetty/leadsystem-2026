<?php

namespace Tests\Unit\Policies;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use App\Policies\LeadPolicy;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LeadPolicyTest extends TestCase
{
    private const BROKER = 1;

    private const COLLEAGUE = 2;

    #[TestWith([null, true])]
    #[TestWith([self::BROKER, true])]
    #[TestWith([self::COLLEAGUE, false])]
    public function test_broker_may_view_own_and_unassigned_leads_only(?int $assignedTo, bool $allowed): void
    {
        $lead = $this->lead($assignedTo);

        $this->assertSame($allowed, (new LeadPolicy)->view($this->broker(), $lead));
    }

    public function test_admin_may_view_a_lead_assigned_to_anyone(): void
    {
        $lead = $this->lead(self::COLLEAGUE);

        $this->assertTrue((new LeadPolicy)->view($this->admin(), $lead));
    }

    public function test_only_an_admin_may_assign_a_lead_to_someone(): void
    {
        $lead = $this->lead(null);

        $this->assertTrue((new LeadPolicy)->assign($this->admin(), $lead));
        $this->assertFalse((new LeadPolicy)->assign($this->broker(), $lead));
    }

    #[TestWith([LeadStatus::New, null, true])]
    #[TestWith([LeadStatus::Contacted, null, true])]
    #[TestWith([LeadStatus::New, self::COLLEAGUE, false])]
    #[TestWith([LeadStatus::New, self::BROKER, false])]
    #[TestWith([LeadStatus::Closed, null, false])]
    #[TestWith([LeadStatus::Dead, null, false])]
    public function test_a_lead_may_be_taken_only_while_open_and_unassigned(LeadStatus $status, ?int $assignedTo, bool $allowed): void
    {
        $lead = $this->lead($assignedTo, $status);

        $this->assertSame($allowed, (new LeadPolicy)->claim($this->broker(), $lead));
    }

    private function broker(): User
    {
        return User::factory()->make(['id' => self::BROKER]);
    }

    private function admin(): User
    {
        return User::factory()->admin()->make(['id' => 99]);
    }

    private function lead(?int $assignedTo, LeadStatus $status = LeadStatus::New): Lead
    {
        return Lead::factory()->withStatus($status)->make(['lead_source_id' => null, 'assigned_to' => $assignedTo]);
    }
}
