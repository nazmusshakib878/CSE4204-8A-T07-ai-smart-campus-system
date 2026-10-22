<?php

declare(strict_types=1);

/*
 * A small dependency-free logical backup fallback for the remote database.
 * Credentials are supplied only by the guarded PowerShell wrapper as
 * process-scoped environment variables and are never printed or written.
 */

$required = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'MYSQL_ATTR_SSL_CA'];
foreach ($required as $name) {
    if ((string) getenv($name) === '') {
        fwrite(STDERR, "POST_MIGRATION_BACKUP=FAIL\n");
        exit(1);
    }
}

$output = $argv[1] ?? '';
if ($output === '') {
    fwrite(STDERR, "POST_MIGRATION_BACKUP=FAIL\n");
    exit(1);
}

$ca = (string) getenv('MYSQL_ATTR_SSL_CA');
if (! is_readable($ca)) {
    fwrite(STDERR, "POST_MIGRATION_BACKUP=FAIL\n");
    exit(1);
}

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_DATABASE'));
$pdo = new PDO($dsn, (string) getenv('DB_USERNAME'), (string) getenv('DB_PASSWORD'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_SSL_CA => $ca,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
]);

$tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
if ($tables === []) {
    fwrite(STDERR, "POST_MIGRATION_BACKUP=FAIL\n");
    exit(1);
}

$directory = dirname($output);
if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
    fwrite(STDERR, "POST_MIGRATION_BACKUP=FAIL\n");
    exit(1);
}

$handle = fopen($output, 'xb');
if ($handle === false) {
    fwrite(STDERR, "POST_MIGRATION_BACKUP=FAIL\n");
    exit(1);
}

try {
    fwrite($handle, "-- Aiven post-migration logical backup\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
    $rows = 0;
    foreach ($tables as $table) {
        $quotedTable = '`'.str_replace('`', '``', (string) $table).'`';
        $create = $pdo->query("SHOW CREATE TABLE {$quotedTable}")->fetch();
        fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n".$create['Create Table'].";\n\n");

        $statement = $pdo->query("SELECT * FROM {$quotedTable}");
        while ($record = $statement->fetch()) {
            $values = array_map(static fn ($value): string => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values($record));
            fwrite($handle, "INSERT INTO {$quotedTable} VALUES (".implode(', ', $values).");\n");
            $rows++;
        }
        fwrite($handle, "\n");
    }
    fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
} finally {
    fclose($handle);
}

$content = file_get_contents($output);
$valid = is_string($content) && filesize($output) > 0 && substr_count($content, 'CREATE TABLE') === count($tables);
if (! $valid) {
    @unlink($output);
    fwrite(STDERR, "POST_MIGRATION_BACKUP=FAIL\n");
    exit(1);
}

echo 'POST_MIGRATION_BACKUP=OK'.PHP_EOL;
echo 'POST_MIGRATION_BACKUP_TABLES='.count($tables).PHP_EOL;
echo 'POST_MIGRATION_BACKUP_ROWS='.$rows.PHP_EOL;
echo 'POST_MIGRATION_BACKUP_FILE='.basename($output).PHP_EOL;
