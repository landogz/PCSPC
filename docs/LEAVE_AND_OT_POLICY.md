# Leave & Overtime Policy (P4 foundation)

**Status:** Provisional — inferred from PCSPC `Timekeeping and Leave.xlsx` samples until HR confirms Annex A / CBA.  
**Last updated:** 2026-08-08  
**Implements in app:** P4a leave types, balances, ledger, monthly VL accrual.

---

## 1. Leave year

- Leave year follows `leave_year_start_month` in system parameters (default **January** = calendar year).
- Each employee has one **balance row per leave type per leave year**.
- **Beginning** = carry-in from prior year (or opening balance). Monetize / forfeit are reserved for later slices.

## 2. Vacation leave (VL) accrual

| Years of service | Earn rate (days / month) | Approx annual |
|------------------|--------------------------|---------------|
| 0–5 | **1.25** | 15 |
| 6–10 | **1.50** | 18 |
| 11+ | **1.66** | ~20 |

### Rules

- Accrue **once per calendar month** (not every semi-monthly payroll cut-off).
- Tenure uses whole years from `date_hired` as of the accrual month end (sample-sheet approximation).
- **No accrual** until the employee is regularized (`date_regularized` is set and not after the accrual month). Probation / not-yet-regular earn **0**.
- Separated / inactive employees are skipped.
- Accrual is **idempotent** per employee + leave type + year-month (`earn` ledger key).

### Balance formula

```
ending = beginning + earned + adjusted - used
```

Ledger entry types: `earn`, `use`, `adjust`, `carry`, `forfeit`, `monetize`.

## 3. Leave types (seeded)

| Code | Name | Accruing | Active (P4a) |
|------|------|----------|--------------|
| VL | Vacation Leave | Yes | Yes |
| SL | Sick Leave | No (caps later) | Yes |
| EL | Emergency Leave | No | Inactive stub |
| BL | Bereavement Leave | No | Inactive stub |
| ML | Maternity Leave | No | Inactive stub |
| PL | Paternity Leave | No | Inactive stub |
| SPL | Solo Parent Leave | No | Inactive stub |
| VAWC | VAWC Leave | No | Inactive stub |

Filing, special-leave credit customization, and SL caps land in **P4b+**.
- **P4b (shipped):** employees file with mandatory reason; simple approve/reject; USED deducted on approve; dual email + in-app notify.
- **P4c:** multi-level Annex A workflow + OT filings.

## 4. Overtime (reference only — not P4a)

Spreadsheet premium multipliers (for **P5–P7 payroll**, not leave balances):

| Label | Multiplier |
|-------|------------|
| Regular OT | 1.50 |
| S2 Regular OT | 1.65 |
| S3 Regular OT | 1.80 |
| Holiday OT | 1.60 |
| S2 Holiday OT | 1.86 |
| S3 Holiday OT | 2.12 |
| Rest Day OT | 1.95 |
| S2 Rest Day OT | 2.15 |
| S3 Rest Day OT | 2.34 |
| Holiday Pay | 1.00 |
| S2 Differential | 0.10 |
| S3 Differential | 0.20 |

Daily rate ≈ monthly basic ÷ **21.75**; hourly ≈ daily ÷ **8** (from career-history basic salary).

## 5. Open HR confirmations

1. Exact YOS cutovers and whether 1.66 is exact or rounded.
2. Catch-up accrual on regularization mid-year.
3. Max carry / cash conversion / forfeit at year-end.
4. SL annual cap and whether SL accrues.
