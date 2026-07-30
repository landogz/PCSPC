# PCSPC — HRIS

Laravel 13 API-based SPA foundation for the PCSPC Human Resource Information System.

## Stack

- Laravel 13 + PHP 8.3+
- MySQL (`pcspc`)
- Laravel Sanctum
- Tailwind CSS 4 + Vite
- Axios, SweetAlert2 (confirmations), toast for success/error

## Local setup (XAMPP / Artisan)

1. Start MySQL in XAMPP (database `pcspc`).
2. Prefer PHP 8.3+ CLI (Laravel 13):

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/PCSPC
composer install && npm install
cp .env.example .env   # if needed
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000/login`

### Demo accounts (from AuthSeeder)

| Login | Password | Notes |
|-------|----------|-------|
| `admin@pcspc.local` / `EMP-0001` | `Password1!` | HR Admin (no MFA) |
| `employee@pcspc.local` / `EMP-1001` | `Password1!` | Employee |
| `mfa@pcspc.local` / `EMP-0002` | `Password1!` | MFA challenge (OTP in `storage/logs/laravel.log` locally) |

### Auth API

- `POST /api/v1/auth/login` — SPA session, or pass `device_name` for mobile token
- `POST /api/v1/auth/mfa/verify` — complete MFA
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/logout`
- `POST /api/v1/auth/logout-others`

Health check: `GET /api/v1/health`

## Project documents

Bidding / TOR documents live in [`docs/hris-bidding/`](docs/hris-bidding/) (not publicly served).

## Architecture folders

- `app/Services/` — business logic
- `app/Repositories/` — data access
- `app/Http/Controllers/API/` — thin API controllers
- `resources/js/modules/` — feature JS modules
- `resources/js/utils/` — shared Axios / toast helpers

See `CLAUDE.md` and `.cursor/rules/` for binding engineering standards.
