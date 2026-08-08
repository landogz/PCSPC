<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Leave policy (P4a) — provisional until HR confirms
    |--------------------------------------------------------------------------
    | See docs/LEAVE_AND_OT_POLICY.md
    */
    'vl_tiers' => [
        ['max_years' => 5, 'monthly_days' => 1.25],
        ['max_years' => 10, 'monthly_days' => 1.50],
        ['max_years' => null, 'monthly_days' => 1.66],
    ],

    'ledger_types' => [
        'earn',
        'use',
        'adjust',
        'carry',
        'forfeit',
        'monetize',
    ],

    'seed_types' => [
        ['code' => 'VL', 'name' => 'Vacation Leave', 'is_accruing' => true, 'requires_reason' => true, 'requires_hr' => false, 'is_active' => true, 'sort_order' => 10],
        ['code' => 'SL', 'name' => 'Sick Leave', 'is_accruing' => false, 'requires_reason' => true, 'requires_hr' => false, 'is_active' => true, 'sort_order' => 20],
        ['code' => 'EL', 'name' => 'Emergency Leave', 'is_accruing' => false, 'requires_reason' => true, 'requires_hr' => false, 'is_active' => false, 'sort_order' => 30],
        ['code' => 'BL', 'name' => 'Bereavement Leave', 'is_accruing' => false, 'requires_reason' => true, 'requires_hr' => true, 'is_active' => false, 'sort_order' => 40],
        ['code' => 'ML', 'name' => 'Maternity Leave', 'is_accruing' => false, 'requires_reason' => true, 'requires_hr' => true, 'is_active' => false, 'sort_order' => 50],
        ['code' => 'PL', 'name' => 'Paternity Leave', 'is_accruing' => false, 'requires_reason' => true, 'requires_hr' => true, 'is_active' => false, 'sort_order' => 60],
        ['code' => 'SPL', 'name' => 'Solo Parent Leave', 'is_accruing' => false, 'requires_reason' => true, 'requires_hr' => true, 'is_active' => false, 'sort_order' => 70],
        ['code' => 'VAWC', 'name' => 'VAWC Leave', 'is_accruing' => false, 'requires_reason' => true, 'requires_hr' => true, 'is_active' => false, 'sort_order' => 80],
    ],

    'request_statuses' => [
        'pending',
        'approved',
        'rejected',
        'cancelled',
    ],
];
