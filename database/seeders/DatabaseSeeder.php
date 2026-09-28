<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Demo accounts, all with the factory's default password ("password"):
 * admin@example.com sees and assigns every lead, processor@example.com
 * sees their own leads and the unassigned pool.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Demo Admin', 'email' => 'admin@example.com']);
        User::factory()->create(['name' => 'Demo Processor', 'email' => 'processor@example.com']);
        User::factory()->count(2)->create();

        $this->call(LeadSeeder::class);
    }
}
