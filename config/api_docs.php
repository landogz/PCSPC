<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public API docs catalog
    |--------------------------------------------------------------------------
    |
    | The /api-docs page is built from live Laravel routes under api/v1.
    | Use this config to label groups and document notable endpoints.
    | When you add a NEW API group (e.g. leave), add a groups[] entry here.
    | When you add an important endpoint, add an endpoints[] summary.
    |
    */
    'title' => 'PCSPC API Reference',
    'subtitle' => 'Versioned REST API under /api/v1 — Sanctum session or bearer token.',
    'base_path' => '/api/v1',
    'updated_note' => 'This catalog is generated from registered routes. New /api/v1 endpoints appear automatically.',

    'groups' => [
        'auth' => [
            'label' => 'Authentication',
            'description' => 'Login, MFA, session/token auth, password change, logout.',
            'order' => 10,
        ],
        'dashboard' => [
            'label' => 'Dashboard',
            'description' => 'HR command-center KPIs and activity widgets.',
            'order' => 20,
        ],
        'administration' => [
            'label' => 'Administration',
            'description' => 'System parameters, company logo, and platform settings.',
            'order' => 30,
        ],
        'security' => [
            'label' => 'Users & Security',
            'description' => 'Users, roles, RBAC permissions, password policy.',
            'order' => 40,
        ],
        'audit' => [
            'label' => 'Audit',
            'description' => 'Auth and domain activity logs.',
            'order' => 50,
        ],
        'departments' => [
            'label' => 'Departments',
            'description' => 'Org units (ADM-007).',
            'order' => 60,
        ],
        'lookups' => [
            'label' => 'Lookups',
            'description' => 'Configurable master-data tables (ADM-006).',
            'order' => 70,
        ],
        'holidays' => [
            'label' => 'Holidays',
            'description' => 'Holiday calendar (ADM-008).',
            'order' => 80,
        ],
        'shifts' => [
            'label' => 'Shifts',
            'description' => 'Shift templates (ADM-009).',
            'order' => 90,
        ],
        'schedules' => [
            'label' => 'Schedules',
            'description' => 'Employee/department shift assignments and landscape print report (ADM-009).',
            'order' => 100,
        ],
        'documents' => [
            'label' => 'Documents',
            'description' => 'Employee document repository (DOC-001).',
            'order' => 110,
        ],
        'employees' => [
            'label' => 'Employees',
            'description' => 'Employee 201 master file, nested dependents, education, employment history, career history.',
            'order' => 120,
        ],
        'up' => [
            'label' => 'Health',
            'description' => 'Application health / uptime probe.',
            'order' => 900,
        ],
        'health' => [
            'label' => 'Health',
            'description' => 'Application health / uptime probe.',
            'order' => 900,
        ],
    ],

    /*
    | Key format: "{METHOD} {uri}" e.g. "GET api/v1/schedules/print"
    | Uri must match Laravel route uri() (no leading slash).
    */
    'endpoints' => [
        'POST api/v1/auth/login' => [
            'summary' => 'Sign in with email/employee number + password. May return MFA challenge or Sanctum token when device_name is sent.',
        ],
        'POST api/v1/auth/mfa/verify' => [
            'summary' => 'Complete MFA with emailed OTP.',
        ],
        'GET api/v1/auth/me' => [
            'summary' => 'Current authenticated user profile and permissions.',
        ],
        'GET api/v1/dashboard/stats' => [
            'summary' => 'Dashboard KPIs, charts data, attention items, recent activity.',
        ],
        'GET api/v1/schedules/print' => [
            'summary' => 'Landscape printable schedule report grouped per employee or per department.',
        ],
        'GET api/v1/employees/export' => [
            'summary' => 'Excel export of employee 201 list.',
        ],
        'GET api/v1/lookups/options' => [
            'summary' => 'Active lookup options for forms (any authenticated user).',
        ],
        'GET api/v1/documents/stats' => [
            'summary' => 'Document storage meter and category/expiry counts.',
        ],
    ],

    'conventions' => [
        'Success envelope' => '{ "status": true, "message": "...", "data": {} }',
        'Error envelope' => '{ "status": false, "message": "...", "errors": {} }',
        'Auth' => 'Cookie/session (SPA) or Bearer Sanctum token (mobile). CSRF required for cookie SPA mutating calls.',
        'IDs' => 'Public route keys are UUIDs (not incremental IDs).',
        'Permissions' => 'Route middleware permission:{name} — UI hiding is not authorization.',
    ],
];
