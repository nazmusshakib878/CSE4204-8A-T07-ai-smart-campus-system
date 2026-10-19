<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Production must never recreate synthetic exhibition accounts. Explicit
        // local demo seeding remains available through its dedicated command.
        if (app()->environment('production')) {
            return;
        }

        $this->call(LiveDemoAdminSeeder::class);
    }
}
