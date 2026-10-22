<?php

declare(strict_types=1);

// The password is supplied only by the parent process environment, never by
// an argument or a file. This helper emits a hash only after Laravel verifies it.
$password = getenv('AI_SMART_CAMPUS_BOOTSTRAP_PASSWORD');
if (! is_string($password) || $password === '') {
    fwrite(STDERR, "Missing in-memory password.\n");
    exit(2);
}

$projectRoot = dirname(__DIR__);
require $projectRoot . '/backend/vendor/autoload.php';

$app = require $projectRoot . '/backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$hash = Illuminate\Support\Facades\Hash::make($password);
if (! Illuminate\Support\Facades\Hash::check($password, $hash)) {
    fwrite(STDERR, "Laravel hash verification failed.\n");
    exit(3);
}

echo $hash;
