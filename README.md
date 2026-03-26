# e-BERKAT Smart Concierge (Prototype)

Laravel + Inertia Vue prototype for JANM BERKAT aid workflow modernization.

## Stack

- Laravel 13 (web routing, auth, business logic)
- Inertia.js + Vue 3 (dynamic UI)
- Tailwind CSS (styling)

## Implemented Foundation

- RBAC baseline with `applicant` and `admin` roles.
- Document Wallet with persistent uploads and reuse across applications.
- Unified application creation flow with dynamic smart stepper behavior.
- Applicant dashboard with status timeline view.
- Admin dashboard and approval queue with pre-scoring priority tags.
- Reporting page for category distribution and branch volume.

## Quick Start

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run dev
php artisan serve
```

## Demo Credentials

- Applicant: `applicant@e-berkat.test` / `password`
- Admin: `admin@e-berkat.test` / `password`

## Main Routes

- `/dashboard`
- `/wallet-documents` (applicant)
- `/applications` (applicant)
- `/admin/approvals` (admin)
- `/admin/reports` (admin)

## Notes

- Real-time behavior is currently MVP polling-ready (not WebSocket push).
- File storage uses Laravel default local disk in this prototype.
