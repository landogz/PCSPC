# PCSPC — HRIS

Laravel 13 API-based SPA foundation for the PCSPC Human Resource Information System.

## Stack

- Laravel 13 + PHP 8.3+
- **PostgreSQL** (`pcspc`) — target DB per SOW; local Homebrew or GCP
- Laravel Sanctum (SPA session + bearer token for mobile)
- Tailwind CSS 4 + Vite
- Axios, SweetAlert2 (confirmations only), toast for success/error
- DataTables-style listings with actions + context menus

## Local setup

1. Start PostgreSQL (Homebrew: `brew services start postgresql@18`).
2. Create the database if needed: `createdb pcspc`
3. Prefer PHP 8.3+ CLI with `pdo_pgsql`:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/PCSPC
composer install && npm install
cp .env.example .env   # if needed
php artisan key:generate
# .env: DB_CONNECTION=pgsql, DB_PORT=5432, DB_DATABASE=pcspc, DB_USERNAME=<your OS user or postgres>
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8002
```

Open `http://127.0.0.1:8002/login` (or the port you chose).

PHPUnit uses in-memory SQLite (`phpunit.xml`) and does not require Postgres.

### Demo accounts (from AuthSeeder)

| Login | Password | Notes |
|-------|----------|-------|
| `admin@pcspc.local` / `EMP-0001` | `Password1!` | HR Admin (no MFA) |
| `employee@pcspc.local` / `EMP-1001` | `Password1!` | Employee |
| `mfa@pcspc.local` / `EMP-0002` (or `MFA_DEMO_EMAIL`) | `Password1!` | MFA challenge — OTP emailed; local also logs OTP |

### Auth API

- `POST /api/v1/auth/login` — SPA session, or pass `device_name` for mobile token
- `POST /api/v1/auth/mfa/verify` — complete MFA
- `GET /api/v1/auth/me`
- `GET|PUT /api/v1/auth/profile` — self-service profile
- `POST|DELETE /api/v1/auth/profile/avatar` — profile photo
- `POST /api/v1/auth/password` — change password
- `POST /api/v1/auth/logout`
- `POST /api/v1/auth/logout-others`

Health check: `GET /api/v1/health`

## Public & in-app surfaces

| Surface | Path | Notes |
|---------|------|--------|
| Public API reference | [`/api-docs`](http://127.0.0.1:8002/api-docs) | Live `/api/v1` catalog, Modules sidebar, multi-language examples |
| API catalog JSON | `/api-docs.json` | Throttled machine-readable catalog |
| Global search | Topbar / `⌘K` | Modules + people; deep-links employees with `?highlight=` |
| Notifications | `/modules/notifications` + topbar bell | In-app inbox; dual-channel with email where wired |
| Project plan | `/docs/project-plan` | Live phase checklist (`docs/PROJECT_PLAN.md`) |

## Project documents

- Live plan / modules: [`docs/PROJECT_PLAN.md`](docs/PROJECT_PLAN.md), [`docs/MODULES.md`](docs/MODULES.md)
- Bidding / TOR: [`docs/hris-bidding/`](docs/hris-bidding/) (not publicly served)

## Architecture folders

- `app/Services/` — business logic
- `app/Repositories/` — data access
- `app/Http/Controllers/API/` — thin API controllers
- `resources/js/modules/` — feature JS modules (Axios SPA)
- `resources/js/utils/` — shared Axios / toast / modal helpers
- `config/api_docs.php` — API docs group labels + endpoint summaries
