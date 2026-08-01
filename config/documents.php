<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Soft storage budget for the document repository meter
    |--------------------------------------------------------------------------
    */
    'storage_limit_bytes' => (int) env('DOCUMENTS_STORAGE_LIMIT_BYTES', 5 * 1024 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Days before expiry considered “expiring soon”
    |--------------------------------------------------------------------------
    */
    'expiring_within_days' => (int) env('DOCUMENTS_EXPIRING_WITHIN_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Categories treated as higher sensitivity (access indicator)
    |--------------------------------------------------------------------------
    */
    'restricted_categories' => [
        'government_id',
        'clearance',
    ],
];
