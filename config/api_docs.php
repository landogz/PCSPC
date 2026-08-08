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
            'description' => 'Employee 201 master file, nested dependents, education, employment history, career history, leave balances.',
            'order' => 120,
        ],
        'leave' => [
            'label' => 'Leave',
            'description' => 'Leave types, balances/ledger, filing, Approver→HR workflow, VL accrual (P4a–P4c).',
            'order' => 125,
        ],
        'overtime' => [
            'label' => 'Overtime',
            'description' => 'OT / OT Meal filings with multi-level Approver → HR workflow (P4c).',
            'order' => 126,
        ],
        'workflow' => [
            'label' => 'Workflow',
            'description' => 'Configurable multi-level approval definitions and inbox (P4c).',
            'order' => 127,
        ],
        'notifications' => [
            'label' => 'Notifications',
            'description' => 'In-app notification inbox, unread counts, and mark-read actions.',
            'order' => 130,
        ],
        'search' => [
            'label' => 'Search',
            'description' => 'Global command-palette search across modules and people.',
            'order' => 140,
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
        'GET api/v1/auth/profile' => [
            'summary' => 'Editable self-service profile (name, avatar).',
        ],
        'PUT api/v1/auth/profile' => [
            'summary' => 'Update display name for the authenticated user.',
        ],
        'POST api/v1/auth/profile/avatar' => [
            'summary' => 'Upload or replace profile photo (JPG/PNG/WebP, max 2 MB).',
        ],
        'DELETE api/v1/auth/profile/avatar' => [
            'summary' => 'Remove the authenticated user profile photo.',
        ],
        'POST api/v1/auth/password' => [
            'summary' => 'Change password (current password + new password meeting policy).',
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
        'GET api/v1/leave/types' => [
            'summary' => 'List leave types (VL/SL/special stubs). Pass all=1 to include inactive.',
        ],
        'POST api/v1/leave/types' => [
            'summary' => 'Create a leave type (leave.manage).',
        ],
        'PUT api/v1/leave/types/{leaveType}' => [
            'summary' => 'Update a leave type (leave.manage).',
        ],
        'DELETE api/v1/leave/types/{leaveType}' => [
            'summary' => 'Delete unused leave type; types with balances/requests must be deactivated instead.',
        ],
        'GET api/v1/leave/balances' => [
            'summary' => 'HR paginated leave balances (search, leave_year, leave_type).',
        ],
        'POST api/v1/leave/balances/adjust' => [
            'summary' => 'HR manual balance adjustment with reason; writes audited ledger entry.',
        ],
        'POST api/v1/leave/accruals/run' => [
            'summary' => 'Run monthly VL accrual for YYYY-MM (idempotent per employee/period).',
        ],
        'GET api/v1/leave/requests/mine' => [
            'summary' => 'Current employee leave requests (file permission).',
        ],
        'GET api/v1/leave/requests' => [
            'summary' => 'Approver queue of leave requests (defaults to pending).',
        ],
        'POST api/v1/leave/requests' => [
            'summary' => 'File a leave request (mandatory reason; checks balance + overlap).',
        ],
        'POST api/v1/leave/requests/{leaveRequest}/approve' => [
            'summary' => 'Approve current leave workflow step; final step deducts USED and writes ledger use entry.',
        ],
        'POST api/v1/leave/requests/{leaveRequest}/reject' => [
            'summary' => 'Reject at current workflow step with optional notes (no balance change).',
        ],
        'POST api/v1/leave/requests/{leaveRequest}/cancel' => [
            'summary' => 'Cancel a pending leave request (owner or leave.manage); cancels workflow instance.',
        ],
        'GET api/v1/employees/{employee}/leave-balances' => [
            'summary' => 'Leave balances for one employee (UUID).',
        ],
        'GET api/v1/overtime/requests/mine' => [
            'summary' => 'Current employee OT / OT Meal requests (ot.file).',
        ],
        'GET api/v1/overtime/requests' => [
            'summary' => 'Approver queue of overtime requests (defaults to pending).',
        ],
        'POST api/v1/overtime/requests' => [
            'summary' => 'File OT or OT Meal; starts Approver → HR workflow instance.',
        ],
        'POST api/v1/overtime/requests/{overtimeRequest}/approve' => [
            'summary' => 'Approve current workflow step; final step marks OT approved.',
        ],
        'POST api/v1/overtime/requests/{overtimeRequest}/reject' => [
            'summary' => 'Reject at current step (no further approvals).',
        ],
        'POST api/v1/overtime/requests/{overtimeRequest}/cancel' => [
            'summary' => 'Cancel pending OT (owner or ot.manage); cancels workflow instance.',
        ],
        'GET api/v1/workflow/definitions' => [
            'summary' => 'List active workflow definitions and steps.',
        ],
        'GET api/v1/workflow/inbox' => [
            'summary' => 'Pending instances where the user matches the current step permission.',
        ],
        'GET api/v1/workflow/instances' => [
            'summary' => 'Paginated workflow instances (filter by status / definition).',
        ],
        'POST api/v1/workflow/instances/{instance}/approve' => [
            'summary' => 'Approve current step (syncs OT subject status when applicable).',
        ],
        'POST api/v1/workflow/instances/{instance}/reject' => [
            'summary' => 'Reject instance (syncs OT subject status when applicable).',
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
