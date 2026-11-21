# VELTRIX

Multi-branch coffee shop operations built with Laravel 13, PHP 8.4+, React 19, TypeScript, Inertia 3, Tailwind 4, shadcn/ui and PostgreSQL.

Implementation follows build.md and prd.md. See docs/IMPLEMENTATION.md for the phased roadmap. This is an active implementation; the initial backend checkpoint is not a finished product.

## Local development

No XAMPP or Docker is required. Install PHP 8.4+ with curl, fileinfo, mbstring, openssl, pdo_pgsql, pgsql, zip and intl, Composer, and Node.js 22+.

1. Run `composer install` and `npm ci`.
2. Copy `.env.example` to `.env`, then configure PostgreSQL credentials privately.
3. Run `php artisan key:generate` and `php artisan migrate`.
4. Run `npm run dev` and `php artisan serve` in separate terminals.
5. Run `php artisan test` for isolated tests and `npm run build` for frontend validation.

On this Windows workspace, a project-local PHP runtime exists at `.tools/php/php.exe` and Composer at `.tools/composer.phar`; both are ignored by Git. They can be invoked from the VS Code terminal directly.

Neon uses TLS. The PostgreSQL connection enables emulated prepared statements for transaction-pooler compatibility. Tests default to SQLite memory and must never reset the supplied Neon database.

Public registration is disabled. Initial owner provisioning and demonstration seeders are separate from production setup. Gemini integration is deferred; there are no fabricated AI replies.

## Operations

Run `php artisan queue:work --tries=3 --timeout=90` for background work, and schedule `php artisan schedule:run` every minute on the deployment host. Production requires HTTPS, `APP_DEBUG=false`, secure cookies, a real mail transport, supervised workers and database backups. Deployment is not performed by this repository checkpoint.

Never commit `.env`, credentials, database dumps, dependency directories or local runtimes.
