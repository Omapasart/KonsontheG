<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tournament Payment Details
    |--------------------------------------------------------------------------
    |
    | Administrators can update these values in the environment file without
    | changing the registration flow. Place a GCash QR image under public/
    | and point TOURNAMENT_QR_IMAGE at that relative path.
    |
    */

    'payment_method' => env('TOURNAMENT_PAYMENT_METHOD', 'GCash'),
    'account_name' => env('TOURNAMENT_GCASH_NAME', env('TOURNAMENT_ACCOUNT_NAME', 'KONSONTHEGO Tournament')),
    'gcash_number' => env('TOURNAMENT_GCASH_NUMBER', env('TOURNAMENT_ACCOUNT_NUMBER', '09XXXXXXXXX')),
    'payment_notes' => env('TOURNAMENT_PAYMENT_NOTES', 'Send via GCash and use your full name as the payment message/reference.'),
    'amount_label' => env('TOURNAMENT_AMOUNT_LABEL', 'Registration fee: TBA'),
    'qr_image' => env('TOURNAMENT_QR_IMAGE', 'images/payment-qr.svg'),

    'photo_max_kb' => (int) env('TOURNAMENT_PHOTO_MAX_KB', 2048),
    'proof_max_kb' => (int) env('TOURNAMENT_PROOF_MAX_KB', 5120),

    'confirmation_hours' => 28,
];
