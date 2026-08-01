<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default system parameters (Annex A ADM-010)
    |--------------------------------------------------------------------------
    | Overridden by values stored in system_settings.key = system_parameters.
    */
    'defaults' => [
        'company_name' => 'Philippine Coastal Storage & Pipeline Corporation',
        'company_short_name' => 'PCSPC',
        'timezone' => 'Asia/Manila',
        'date_format' => 'Y-m-d',
        'currency_code' => 'PHP',
        'support_email' => 'hris@pcspc.local',
        'leave_year_start_month' => 1,
        'rest_day_holiday_paid_hours' => 8,
        'default_grace_minutes' => 0,
        'week_start' => 'monday',
        'logo_path' => null,
    ],

    'timezones' => [
        'Asia/Manila',
        'UTC',
        'Asia/Singapore',
        'Asia/Hong_Kong',
        'Asia/Tokyo',
    ],

    'date_formats' => [
        'Y-m-d',
        'd/m/Y',
        'm/d/Y',
        'd-M-Y',
    ],

    'week_starts' => [
        'monday',
        'sunday',
    ],
];
