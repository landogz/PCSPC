<?php

/**
 * Sidebar / module map aligned to:
 * - docs/PROJECT_PLAN.md §4 Module map
 * - canvases/hris-flowcharts.canvas.tsx (modules + delivery flows)
 * - Enterprise Laravel API SPA rules (per-module Service/Repository/API later)
 *
 * Keys are stable route slugs under /modules/{key}.
 * `permission` (string|list) gates sidebar + module page access (any-of).
 */
return [

    'dashboard' => [
        'label' => 'Dashboard',
        'route' => 'dashboard',
        'icon' => 'ph-squares-four',
        'phase' => 'P2',
        'permission' => 'dashboard.view',
        'req_ids' => ['HOME'],
        'flowchart' => 'modules → Platform + Security',
        'summary' => 'Post-login home. Menus are filtered by RBAC after auth (login flowchart).',
    ],

    'sections' => [

        [
            'label' => 'Main',
            'items' => [
                [
                    'key' => 'dashboard',
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'icon' => 'ph-squares-four',
                    'phase' => 'P2',
                    'permission' => 'dashboard.view',
                    'req_ids' => ['HOME'],
                    'flowchart' => 'login → Dashboard / menus by RBAC',
                    'summary' => 'Operational overview after successful login and RBAC load.',
                ],
            ],
        ],

        [
            'label' => 'People',
            'items' => [
                [
                    'key' => 'employees',
                    'label' => 'Employees',
                    'route' => 'modules.show',
                    'icon' => 'ph-users-three',
                    'phase' => 'P3',
                    'permission' => 'employees.view',
                    'req_ids' => ['EMP-001', 'EMP-002', 'EMP-003', 'EMP-004', 'EMP-005', 'EMP-006'],
                    'flowchart' => 'modules → Employee 201',
                    'summary' => 'Employee 201 master: personal/statutory fields, dependents, history, Excel export.',
                ],
                [
                    'key' => 'departments',
                    'label' => 'Departments',
                    'route' => 'modules.show',
                    'icon' => 'ph-buildings',
                    'phase' => 'P3',
                    'permission' => 'departments.manage',
                    'req_ids' => ['ADM-001', 'ADM-002'],
                    'flowchart' => 'modules → Employee 201 · Admin org structure',
                    'summary' => 'Org structure, departments, and master data used by approvals and reporting.',
                ],
            ],
        ],

        [
            'label' => 'Time & Attendance',
            'items' => [
                [
                    'key' => 'timekeeping',
                    'label' => 'Timekeeping',
                    'route' => 'modules.show',
                    'icon' => 'ph-clock-user',
                    'phase' => 'P5',
                    'permission' => 'timekeeping.view',
                    'req_ids' => ['TM-001', 'TM-002'],
                    'flowchart' => 'modules → Timekeeping',
                    'summary' => 'Biometric sync, attendance computation, holiday/rest-day rules, manhours.',
                ],
                [
                    'key' => 'leave',
                    'label' => 'Leave Management',
                    'route' => 'modules.show',
                    'icon' => 'ph-airplane-takeoff',
                    'phase' => 'P4',
                    'permission' => ['leave.file', 'leave.approve'],
                    'req_ids' => ['LEV-001', 'LEV-002'],
                    'flowchart' => 'leave → Employee files request · modules → Leave + OT',
                    'summary' => 'Leave filing with mandatory reason; VL/SL/special leaves; multi-level approvals.',
                ],
                [
                    'key' => 'overtime',
                    'label' => 'Overtime',
                    'route' => 'modules.show',
                    'icon' => 'ph-timer',
                    'phase' => 'P4',
                    'permission' => ['leave.file', 'leave.approve'],
                    'req_ids' => ['OT-001'],
                    'flowchart' => 'leave → OT approval flow · modules → Leave + OT',
                    'summary' => 'OT and OT Meal filings with Annex A approval matrices.',
                ],
                [
                    'key' => 'workflow',
                    'label' => 'Workflow Approvals',
                    'route' => 'modules.show',
                    'icon' => 'ph-git-branch',
                    'phase' => 'P4',
                    'permission' => 'leave.approve',
                    'req_ids' => ['WF-001'],
                    'flowchart' => 'leave → Approver 1 → Approver 2 → HR',
                    'summary' => 'Configurable multi-level approval engine shared by Leave, OT, Travel, and related requests.',
                ],
            ],
        ],

        [
            'label' => 'HR Records',
            'items' => [
                [
                    'key' => 'medical',
                    'label' => 'Medical Records',
                    'route' => 'modules.show',
                    'icon' => 'ph-heartbeat',
                    'phase' => 'P6',
                    'permission' => 'employees.view',
                    'req_ids' => ['MED-001'],
                    'flowchart' => 'modules → Medical / Training / Perf',
                    'summary' => 'APE, checkup, vaccines, reimbursements; encrypted/masked sensitive fields (DPA 2012).',
                ],
                [
                    'key' => 'training',
                    'label' => 'Training',
                    'route' => 'modules.show',
                    'icon' => 'ph-graduation-cap',
                    'phase' => 'P6',
                    'permission' => 'employees.view',
                    'req_ids' => ['TRN-001'],
                    'flowchart' => 'modules → Medical / Training / Perf',
                    'summary' => 'Training records and confirmation tracking (SOW A.3).',
                ],
                [
                    'key' => 'performance',
                    'label' => 'Performance',
                    'route' => 'modules.show',
                    'icon' => 'ph-chart-line-up',
                    'phase' => 'P6',
                    'permission' => 'employees.view',
                    'req_ids' => ['PRF-001'],
                    'flowchart' => 'modules → Medical / Training / Perf',
                    'summary' => 'Performance records linked to employee 201 history.',
                ],
                [
                    'key' => 'compensation',
                    'label' => 'Comp & Benefits',
                    'route' => 'modules.show',
                    'icon' => 'ph-handshake',
                    'phase' => 'P6',
                    'permission' => 'employees.manage',
                    'req_ids' => ['CMP-001'],
                    'flowchart' => 'modules → Medical / Training / Perf',
                    'summary' => 'Compensation and benefits with historical tracking; sensitive fields encrypted.',
                ],
                [
                    'key' => 'documents',
                    'label' => 'Documents',
                    'route' => 'modules.show',
                    'icon' => 'ph-files',
                    'phase' => 'P3',
                    'permission' => 'employees.view',
                    'req_ids' => ['DOC-001'],
                    'flowchart' => 'modules → Employee 201 · Documents',
                    'summary' => 'Document repository for employee and HR files.',
                ],
            ],
        ],

        [
            'label' => 'Payroll Support',
            'items' => [
                [
                    'key' => 'loans',
                    'label' => 'Loans',
                    'route' => 'modules.show',
                    'icon' => 'ph-bank',
                    'phase' => 'P7',
                    'permission' => 'employees.manage',
                    'req_ids' => ['LON-001'],
                    'flowchart' => 'modules → Loans / Deduct / Earnings',
                    'summary' => 'Employee loans management (SOW A.3).',
                ],
                [
                    'key' => 'deductions',
                    'label' => 'Deductions',
                    'route' => 'modules.show',
                    'icon' => 'ph-minus-circle',
                    'phase' => 'P7',
                    'permission' => 'employees.manage',
                    'req_ids' => ['DED-001'],
                    'flowchart' => 'modules → Loans / Deduct / Earnings',
                    'summary' => 'Deductions management and reporting exports.',
                ],
                [
                    'key' => 'earnings',
                    'label' => 'Earnings',
                    'route' => 'modules.show',
                    'icon' => 'ph-plus-circle',
                    'phase' => 'P7',
                    'permission' => 'employees.manage',
                    'req_ids' => ['ERN-001'],
                    'flowchart' => 'modules → Loans / Deduct / Earnings',
                    'summary' => 'Earnings management and reporting exports.',
                ],
                [
                    'key' => 'travel',
                    'label' => 'Travel',
                    'route' => 'modules.show',
                    'icon' => 'ph-airplane-tilt',
                    'phase' => 'P7',
                    'permission' => ['leave.file', 'leave.approve'],
                    'req_ids' => ['TVL-001'],
                    'flowchart' => 'modules → Loans / Deduct / Earnings · Travel',
                    'summary' => 'Travel requests/history with workflow approvals.',
                ],
            ],
        ],

        [
            'label' => 'Insights',
            'items' => [
                [
                    'key' => 'reports',
                    'label' => 'Reports & Analytics',
                    'route' => 'modules.show',
                    'icon' => 'ph-chart-bar',
                    'phase' => 'P7',
                    'permission' => 'employees.view',
                    'req_ids' => ['RPT-001'],
                    'flowchart' => 'modules → Reports & Analytics',
                    'summary' => 'Employee, leave, OT, training, medical, attendance, manhours; Excel export.',
                ],
            ],
        ],

        [
            'label' => 'System',
            'items' => [
                [
                    'key' => 'administration',
                    'label' => 'Administration',
                    'route' => 'modules.show',
                    'icon' => 'ph-sliders-horizontal',
                    'phase' => 'P2–P3',
                    'permission' => 'administration.manage',
                    'req_ids' => ['ADM-001', 'ADM-002', 'ADM-003', 'ADM-004', 'ADM-005', 'ADM-006', 'ADM-007', 'ADM-008', 'ADM-009', 'ADM-010'],
                    'flowchart' => 'modules → Platform + Security · delivery P2–P3',
                    'summary' => 'Accounts, holidays, shifts, system parameters, password policy (ADM Must Haves).',
                ],
                [
                    'key' => 'security',
                    'label' => 'Users & Security',
                    'route' => 'modules.show',
                    'icon' => 'ph-shield-check',
                    'phase' => 'P2–P3',
                    'permission' => ['users.manage', 'roles.manage'],
                    'req_ids' => ['SEC-001', 'SEC-002'],
                    'flowchart' => 'login → RBAC · MFA · modules → Platform + Security',
                    'summary' => 'RBAC per module/screen/action; MFA for privileged roles.',
                ],
                [
                    'key' => 'audit',
                    'label' => 'Audit Log',
                    'route' => 'modules.show',
                    'icon' => 'ph-detective',
                    'phase' => 'P2–P8',
                    'permission' => 'audit.view',
                    'req_ids' => ['AUD-001'],
                    'flowchart' => 'login → audit events · modules → Platform + Security',
                    'summary' => 'Activity logging for auth, MFA, lockout, and sensitive actions.',
                ],
                [
                    'key' => 'notifications',
                    'label' => 'Notifications',
                    'route' => 'modules.show',
                    'icon' => 'ph-bell-ringing',
                    'phase' => 'P3–P4',
                    'permission' => 'dashboard.view',
                    'req_ids' => ['NOT-001'],
                    'flowchart' => 'leave → email notify · delivery P3–P4',
                    'summary' => 'Email/in-app notification skeleton for approvals and system events.',
                ],
                [
                    'key' => 'help',
                    'label' => 'Help & Docs',
                    'route' => 'modules.show',
                    'icon' => 'ph-lifebuoy',
                    'phase' => 'P8–P9',
                    'permission' => 'dashboard.view',
                    'req_ids' => ['DOC-PLAN'],
                    'flowchart' => 'delivery → P8 Hardening & Docs · P9 Training',
                    'summary' => 'Links to project plan, module map, bidding docs, and user manuals (as delivered).',
                ],
            ],
        ],

    ],

];
