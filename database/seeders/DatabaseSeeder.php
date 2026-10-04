<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([ReferenceDataSeeder::class, PlatformSeeder::class, TopicFactsSeeder::class, ProfessionSeeder::class, HealthcareCoursesSeeder::class]);
        // Local previews (Codespaces, ops/qa scripts) need the documented demo logins; never seeded anywhere else.
        if (app()->environment('local')) {
            $this->call(DemoAccountsSeeder::class);
        }
    }
}
