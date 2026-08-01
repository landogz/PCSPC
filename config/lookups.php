<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System lookup types (ADM-006)
    |--------------------------------------------------------------------------
    |
    | Seeded defaults ship with Auth/Lookup seeders. Admins can add custom
    | values; system-seeded rows cannot be deleted (label/sort/active only).
    |
    */
    'types' => [
        'gender' => [
            'label' => 'Gender',
            'description' => 'Employee and dependent gender options',
            'module' => 'employees',
        ],
        'civil_status' => [
            'label' => 'Civil status',
            'description' => 'Marital / civil status on Employee 201',
            'module' => 'employees',
        ],
        'employment_status' => [
            'label' => 'Employment status',
            'description' => 'Active, inactive, separated, on leave',
            'module' => 'employees',
        ],
        'employment_category' => [
            'label' => 'Employment category',
            'description' => 'Permanent, probationary, contractual, and other appointment types',
            'module' => 'employees',
        ],
        'dependent_relationship' => [
            'label' => 'Dependent relationship',
            'description' => 'Spouse, child, parent, and other relationships',
            'module' => 'employees',
        ],
        'education_level' => [
            'label' => 'Education level',
            'description' => 'Schooling / attainment levels',
            'module' => 'employees',
        ],
        'holiday_type' => [
            'label' => 'Holiday type',
            'description' => 'Regular, special, and company holiday types',
            'module' => 'holidays',
        ],
        'document_category' => [
            'label' => 'Document category',
            'description' => 'Document repository folder categories',
            'module' => 'documents',
        ],
    ],
];
