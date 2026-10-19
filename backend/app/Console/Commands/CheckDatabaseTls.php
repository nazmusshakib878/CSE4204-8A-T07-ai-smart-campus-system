<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CheckDatabaseTls extends Command
{
    protected $signature = 'database:startup-health {--table-count : Also report the current database table count} {--config-check : Verify effective MySQL connection settings without revealing values} {--critical-schema : Verify the required API tables and columns without exposing schema details}';
    protected $description = 'Read-only database and TLS health check for protected startup logs.';

    public function handle(): int
    {
        try {
            if ($this->option('config-check') && ! $this->hasExpectedMysqlConfiguration()) {
                $this->line('DATABASE_CONFIG_CHECK=FAIL');
                return self::FAILURE;
            }
            if ($this->option('config-check')) {
                $this->line('DATABASE_CONFIG_CHECK=OK');
            }
            $connected = DB::selectOne('SELECT 1 AS connected');
            $ssl = DB::selectOne("SHOW STATUS LIKE 'Ssl_cipher'");

            if (($connected?->connected) && ! empty($ssl?->Value)) {
                if ($this->option('critical-schema') && ! $this->hasCriticalSchema()) {
                    $this->line('DATABASE_CRITICAL_SCHEMA=FAIL');
                    return self::FAILURE;
                }

                $this->line('DATABASE_TLS_CHECK=OK');
                if ($this->option('critical-schema')) {
                    $this->line('DATABASE_CRITICAL_SCHEMA=OK');
                }
                if ($this->option('table-count')) {
                    $count = DB::scalar('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()');
                    $this->line('DATABASE_TABLE_COUNT='.(int) $count);
                }
                return self::SUCCESS;
            }
        } catch (Throwable) {
            // Intentionally suppress driver, host, and credential details in startup logs.
        }

        $this->line('DATABASE_TLS_CHECK=FAIL');
        return self::FAILURE;
    }

    private function hasExpectedMysqlConfiguration(): bool
    {
        $connection = config('database.connections.mysql', []);

        return config('database.default') === 'mysql'
            && ($connection['driver'] ?? null) === 'mysql'
            && ($connection['host'] ?? null) === getenv('AIVEN_EXPECTED_DB_HOST')
            && (string) ($connection['port'] ?? '') === getenv('AIVEN_EXPECTED_DB_PORT')
            && ($connection['database'] ?? null) === getenv('AIVEN_EXPECTED_DB_DATABASE')
            && ($connection['username'] ?? null) === getenv('AIVEN_EXPECTED_DB_USERNAME')
            && ! empty($connection['password'])
            && empty($connection['unix_socket'])
            && is_string($connection['options'][\PDO::MYSQL_ATTR_SSL_CA] ?? null)
            && is_readable($connection['options'][\PDO::MYSQL_ATTR_SSL_CA] ?? '')
            && ($connection['options'][\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] ?? false) === true;
    }

    private function hasCriticalSchema(): bool
    {
        $required = [
            'users' => ['id', 'role', 'approval_status'],
            'personal_access_tokens' => ['id', 'tokenable_id', 'token'],
            'departments' => ['id', 'name', 'code', 'is_active'],
            'notices' => ['id', 'user_id', 'publish_date', 'category', 'audience', 'target_department', 'target_role', 'target_semester', 'expires_at', 'archived_at'],
            'notice_reads' => ['id', 'notice_id', 'user_id', 'read_at'],
            'students' => ['id', 'user_id', 'current_semester', 'section'],
        ];

        foreach ($required as $table => $columns) {
            if (! Schema::hasTable($table) || ! Schema::hasColumns($table, $columns)) {
                return false;
            }
        }

        return true;
    }
}
