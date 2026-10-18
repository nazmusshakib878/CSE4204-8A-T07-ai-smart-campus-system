<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedOnlineDemo extends Command
{
    protected $signature = 'demo:seed-online {--confirmation= : The exact private ONLINE_DEMO_SEED_CONFIRMATION value}';
    protected $description = 'Manually and idempotently provision the Spring 2026 online demo accounts; never used at service startup.';

    public function handle(): int
    {
        if (app()->environment('production') && env('ONLINE_DEMO_SEED_ENABLED') !== 'true') {
            $this->error('Online demo seeding is disabled. Set ONLINE_DEMO_SEED_ENABLED=true only for the one approved run.');
            return self::FAILURE;
        }

        $expected = (string) env('ONLINE_DEMO_SEED_CONFIRMATION', '');
        if (strlen($expected) < 24 || ! hash_equals($expected, (string) $this->option('confirmation'))) {
            $this->error('A valid private confirmation token is required; no seed was run.');
            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => 'Database\\Seeders\\LocalPresentationDemoSeeder', '--force' => true]);
        $this->info('Online Spring 2026 demo seed completed idempotently. Disable ONLINE_DEMO_SEED_ENABLED now.');
        return self::SUCCESS;
    }
}
