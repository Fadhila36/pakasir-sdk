<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Pakasir Project Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify your Pakasir project credentials.
    | You can find these details in your Pakasir project dashboard.
    |
    */

    'project' => env('PAKASIR_PROJECT'), // Project Slug or ID

    'api_key' => env('PAKASIR_API_KEY'), // Project API Key

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Pakasir API.
    |
    */

    'base_url' => env('PAKASIR_BASE_URL', 'https://app.pakasir.com/api'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Connection Settings
    |--------------------------------------------------------------------------
    |
    | Configure connection behaviors like timeout and automated retries.
    | Timeout is in seconds. Retry delay is in milliseconds.
    |
    */

    'timeout' => (int) env('PAKASIR_TIMEOUT', 30),

    'retry_attempts' => (int) env('PAKASIR_RETRY_ATTEMPTS', 3),

    'retry_delay' => (int) env('PAKASIR_RETRY_DELAY', 100),

    /*
    |--------------------------------------------------------------------------
    | Logging Support
    |--------------------------------------------------------------------------
    |
    | When enabled, API request and response data will be logged.
    | Useful for debugging integration issues.
    |
    */

    'logging_enabled' => (bool) env('PAKASIR_LOGGING_ENABLED', false),
];
