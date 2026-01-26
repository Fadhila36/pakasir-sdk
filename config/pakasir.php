<?php

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
];
