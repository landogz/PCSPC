# PCSPC HRIS Flowcharts

In-app view of the delivery and feature flows (aligned with Cursor canvas `hris-flowcharts.canvas.tsx`).

Related:
- [Project Plan](/docs/project-plan)
- [Menu ↔ Module Map](/docs/modules)
- [Public API docs](/api-docs)

---

## Login & session

Sanctum web SPA (cookie) / mobile (Bearer) · MFA for privileged roles · RBAC menus.

```mermaid
flowchart TD
  S[Open Web SPA or Mobile App] --> C[Enter credentials]
  C --> API["POST /api/v1/auth/login<br/>throttled FormRequest"]
  API --> CHK{Account active<br/>and not locked?}
  CHK -->|No| FAIL[Fail · audit log · attempts++]
  FAIL --> LOCK{Max attempts?}
  LOCK -->|Yes| LOCKED[Account locked]
  LOCK -->|No| C
  CHK -->|Yes| PWD{Password valid?}
  PWD -->|No| FAIL
  PWD -->|Yes| MFA{MFA required<br/>for role?}
  MFA -->|Yes| OTP[Enter MFA code]
  OTP -->|Fail| FAIL
  OTP -->|OK| TOK[Issue session / token]
  MFA -->|No| TOK
  TOK --> RBAC[Load roles & permissions]
  RBAC --> HOME[Dashboard / menus by RBAC]
  HOME --> OUT[Logout · revoke token<br/>optional logout other devices]
```

---

## Modules (feature build sequence)

SOW A.3 + Annex A Must Haves · parallel tracks after platform foundation.

```mermaid
flowchart TD
  F[Platform + Security<br/>ADM · SEC · API · Audit] --> E[Employee 201<br/>EMP · Documents]
  F --> W[Workflow Engine<br/>WF-001]
  E --> L[Leave + OT<br/>LEV · OT · Mobile]
  W --> L
  L --> T[Timekeeping<br/>Biometric · TM]
  E --> H[Medical / Training / Perf<br/>Comp & Benefits]
  T --> FIN[Loans / Deduct / Earnings<br/>Travel]
  H --> FIN
  FIN --> R[Reports & Analytics<br/>Excel exports]
  T --> R
```

---

## Leave / OT approval

Annex A matrices vary by department/rank · reason required on filing.

```mermaid
flowchart TD
  E[Employee files request<br/>Web / Mobile · reason required] --> A1[Approver 1]
  A1 --> A2[Approver 2 if required]
  A2 --> HR[HR Head / HR for special leave]
  HR --> OK[Approved]
  A1 --> REJ[Rejected / Returned]
  A2 --> REJ
  HR --> REJ
```

---

## Delivery lifecycle

TOR + SOW · P0–P10 · ~36 weeks build + 6-month warranty.

```mermaid
flowchart TB
  subgraph R1[" "]
    direction LR
    P0["P0 Procurement<br/>Pre-bid → Award"] --> P1["P1 Discovery<br/>& Design"]
    P1 --> P2["P2 Platform<br/>Foundation"]
    P2 --> P3["P3 Employee 201<br/>+ Admin"]
    P3 --> P4["P4 Leave / OT<br/>/ Workflow"]
  end
  subgraph R2[" "]
    direction LR
    P5["P5 Timekeeping<br/>+ Biometric"] --> P6["P6 Medical / Training<br/>/ Comp"]
    P6 --> P7["P7 Loans / Travel<br/>/ Reports"]
    P7 --> P8["P8 Hardening<br/>& Docs"]
    P8 --> P9["P9 UAT · Training<br/>· Go-Live"]
    P9 --> P10["P10 Warranty<br/>6 months"]
  end
  R1 --> R2
```

---

## CI/CD promotion

SOW B.1–B.4 · no direct deploy to Live.

```mermaid
flowchart LR
  Plan --> Code --> Build --> Test --> Dev --> UAT[Staging / UAT] --> Live --> Monitor
```
