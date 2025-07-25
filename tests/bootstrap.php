<?php

declare(strict_types=1);

/**
 * Load environment variables from .env file into the environment.
 */
function loadEnvironmentVariables(): void
{
    $envFilePath = dirname(__DIR__).'/.env';

    if (! is_readable($envFilePath)) {
        return;
    }

    $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');

        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        if ($key !== '') {
            putenv("{$key}={$value}");
        }
    }
}

$loadEnvFile = $_ENV['TEST_LOAD_ENV_FILE'] ?? getenv('TEST_LOAD_ENV_FILE') ?: false;

if (filter_var($loadEnvFile, FILTER_VALIDATE_BOOLEAN)) {
    loadEnvironmentVariables();
}
