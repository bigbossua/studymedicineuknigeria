<?php

/*
 * Prints the requested .env keys as shell assignments (KEY='value'), parsed by phpdotenv exactly as
 * Laravel parses them, so deploy scripts never mis-read quoted or special-character values.
 * Usage (from a release directory): eval "$(php ops/env-shell.php DB_DATABASE DB_PASSWORD)"
 * Prints nothing for itself; exits 1 when .env is missing or unparsable.
 */

require __DIR__.'/../vendor/autoload.php';

$dir = getcwd();
if (! is_file($dir.'/.env')) {
    fwrite(STDERR, ".env not found in $dir\n");
    exit(1);
}

try {
    $env = Dotenv\Dotenv::createArrayBacked($dir)->load();
} catch (Throwable $e) {
    fwrite(STDERR, '.env could not be parsed: '.$e->getMessage()."\n");
    exit(1);
}

foreach (array_slice($argv, 1) as $key) {
    if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
        fwrite(STDERR, "invalid key $key\n");
        exit(1);
    }
    echo $key, '=', escapeshellarg((string) ($env[$key] ?? '')), "\n";
}
