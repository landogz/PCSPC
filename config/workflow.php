<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow (P4c) — config-driven steps until Annex A matrices freeze
    |--------------------------------------------------------------------------
    */
    'default_overtime_code' => 'overtime',

    'seed_definitions' => [
        [
            'code' => 'overtime',
            'name' => 'Overtime / OT Meal',
            'description' => 'Approver → HR (provisional until Annex A department/rank matrices freeze).',
            'is_active' => true,
            'steps' => [
                ['step_order' => 1, 'label' => 'Approver', 'approver_permission' => 'ot.approve'],
                ['step_order' => 2, 'label' => 'HR', 'approver_permission' => 'ot.manage'],
            ],
        ],
        [
            'code' => 'leave',
            'name' => 'Leave',
            'description' => 'Approver → HR for standard leave types.',
            'is_active' => true,
            'steps' => [
                ['step_order' => 1, 'label' => 'Approver', 'approver_permission' => 'leave.approve'],
                ['step_order' => 2, 'label' => 'HR', 'approver_permission' => 'leave.manage'],
            ],
        ],
        [
            'code' => 'leave_hr',
            'name' => 'Leave (HR only)',
            'description' => 'Single HR step for leave types marked requires_hr.',
            'is_active' => true,
            'steps' => [
                ['step_order' => 1, 'label' => 'HR', 'approver_permission' => 'leave.manage'],
            ],
        ],
    ],

    'default_leave_code' => 'leave',
    'default_leave_hr_code' => 'leave_hr',

    'instance_statuses' => [
        'pending',
        'approved',
        'rejected',
        'cancelled',
    ],

    'ot_kinds' => [
        'ot',
        'ot_meal',
    ],

    'ot_max_hours' => 24,
    'ot_min_hours' => 0.25,
];
