# PCSPC Menu ↔ Module Map

Single source of truth for sidebar links: [`config/navigation.php`](../config/navigation.php).

Aligned to:
- [`PROJECT_PLAN.md`](PROJECT_PLAN.md) §4 Module map
- Cursor canvas `hris-flowcharts.canvas.tsx` (Login · Delivery · Modules · Leave/OT)
- Enterprise API SPA rules (each feature later gets Service + Repository + `/api/v1/` + `resources/js/modules/{key}`)

## Navigation sections

| Section | Menu | Route | Phase | Req IDs | Flowchart |
|---------|------|-------|-------|---------|-----------|
| Main | Dashboard | `/dashboard` | P2 | HOME | login → Dashboard / RBAC menus |
| People | Employees | `/modules/employees` | P3 | EMP-001…006 | modules → Employee 201 |
| People | Departments | `/modules/departments` | P3 | ADM-001…002 | modules → Employee 201 / org |
| Time & Attendance | Timekeeping | `/modules/timekeeping` | P5 | TM-001…002 | modules → Timekeeping |
| Time & Attendance | Leave Management | `/modules/leave` | P4 | LEV-001…002 | leave approval flow |
| Time & Attendance | Overtime | `/modules/overtime` | P4 | OT-001 | leave/OT approval flow |
| Time & Attendance | Workflow Approvals | `/modules/workflow` | P4 | WF-001 | Approver 1 → 2 → HR |
| HR Records | Medical Records | `/modules/medical` | P6 | MED-001 | modules → Medical / Training / Perf |
| HR Records | Training | `/modules/training` | P6 | TRN-001 | modules → Medical / Training / Perf |
| HR Records | Performance | `/modules/performance` | P6 | PRF-001 | modules → Medical / Training / Perf |
| HR Records | Comp & Benefits | `/modules/compensation` | P6 | CMP-001 | modules → Medical / Training / Perf |
| HR Records | Documents | `/modules/documents` | P3 | DOC-001 | modules → Employee 201 · Documents |
| Payroll Support | Loans | `/modules/loans` | P7 | LON-001 | modules → Loans / Deduct / Earnings |
| Payroll Support | Deductions | `/modules/deductions` | P7 | DED-001 | modules → Loans / Deduct / Earnings |
| Payroll Support | Earnings | `/modules/earnings` | P7 | ERN-001 | modules → Loans / Deduct / Earnings |
| Payroll Support | Travel | `/modules/travel` | P7 | TVL-001 | modules → Travel |
| Insights | Reports & Analytics | `/modules/reports` | P7 | RPT-001 | modules → Reports & Analytics |
| System | Administration | `/modules/administration` | P2–P3 | ADM-001…010 | Platform + Security |
| System | Users & Security | `/modules/security` | P2–P3 | SEC-001…002 | login MFA/RBAC |
| System | Audit Log | `/modules/audit` | P2–P8 | AUD-001 | auth audit events |
| System | Notifications | `/modules/notifications` | P3–P4 | NOT-001 | leave email notify |
| System | Help & Docs | `/modules/help` | P8–P9 | DOC-PLAN | P8 docs · P9 training |

## Folder convention (per module)

```
resources/views/modules/{key}/index.blade.php
resources/js/modules/{key}/index.js
```

Future API pack (when phase starts):

```
app/Services/{Name}Service.php
app/Repositories/{Name}Repository.php
app/Http/Controllers/API/{Name}Controller.php
routes under /api/v1/{key}
```

## Status

| Area | Status |
|------|--------|
| Menus & web routes | ✅ Connected via `config/navigation.php` + RBAC filter |
| Auth / Security / Audit | ✅ SPA + `/api/v1` |
| Departments | ✅ SPA + `/api/v1/departments` |
| Employees 201 | ✅ SPA + `/api/v1/employees` (v1 fields; dependents/history/Excel later) |
| Other module pages | ⬜ Scaffolds until mapped delivery phase |

Legend: ✅ done · 🔶 partial · ⬜ not started — see [`PROJECT_PLAN.md`](PROJECT_PLAN.md) progress section.
