<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrator accounts
    |--------------------------------------------------------------------------
    |
    | Initial passwords must come from the environment. Empty password values
    | are skipped so this file can be committed without credentials.
    |
    */

    'accounts' => array_values(array_filter([
        [
            'name' => env('ADMIN_NAME', 'KONSONTHEGO Admin'),
            'email' => env('ADMIN_EMAIL'),
            'password' => env('ADMIN_PASSWORD'),
        ],
        [
            'name' => env('ADMIN_DANE_NAME', 'Admin Dane - Konsonthego'),
            'email' => env('ADMIN_DANE_EMAIL', 'aerickadane@gmail.com'),
            'password' => env('ADMIN_DANE_PASSWORD'),
        ],
        [
            'name' => env('ADMIN_JD_NAME', 'Admin JD NEL - Konsonthego'),
            'email' => env('ADMIN_JD_EMAIL', 'jdawitan012799@gmail.com'),
            'password' => env('ADMIN_JD_PASSWORD'),
        ],
        [
            'name' => env('ADMIN_ISO_NAME', 'Admin Iso - Konsonthego'),
            'email' => env('ADMIN_ISO_EMAIL', 'isobelzara.aldeguer@gmail.com'),
            'password' => env('ADMIN_ISO_PASSWORD'),
        ],
        [
            'name' => env('ADMIN_ALI_NAME', 'Admin Ali - Konsonthego'),
            'email' => env('ADMIN_ALI_EMAIL'),
            'password' => env('ADMIN_ALI_PASSWORD'),
        ],
    ], fn (array $account): bool => filled($account['email']) && filled($account['password']))),

];
