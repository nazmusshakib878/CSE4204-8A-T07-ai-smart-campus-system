<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckDatabaseTls extends Command
{
    protected $signature = 'database:startup-health';
    protected $description = 'Read-only database and TLS health check for protected startup logs.';

    public function handle(): int
    {
        try {
            $connected = DB::selectOne('SELECT 1 AS connected');
            $ssl = DB::selectOne("SHOW STATUS LIKE 'Ssl_cipher'");

            if (($connected?->connected) && ! empty($ssl?->Value)) {
                $this->line('DATABASE_TLS_CHECK=OK');
                return self::SUCCESS;
            }
        } catch (Throwable) {
            // Intentionally suppress driver, host, and credential details in startup logs.
        }

        $this->line('DATABASE_TLS_CHECK=FAIL');
        return self::FAILURE;
    }
}
