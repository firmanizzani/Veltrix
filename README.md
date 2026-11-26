# VELTRIX

VELTRIX is a multi-branch coffee shop operations application for owners, branch managers, and cashiers. Laravel and React communicate through Inertia in one modular monolith. The application interface is in Indonesian, with IDR amounts and the Asia/Jakarta business timezone.

## Stack

Laravel 13 · PHP 8.4 · React 19 · TypeScript · Inertia 3 · Tailwind 4 · shadcn/ui · Lucide · Recharts · PostgreSQL / Neon · Dompdf.

PostgreSQL is the only operational database. XAMPP, Apache, MySQL, and Docker are not required. PHP can run directly from a terminal, including the VS Code terminal.

## Local setup

Requirements: PHP 8.4+, Composer, Node.js 22.12+, and PostgreSQL. Enable the PHP extensions curl, fileinfo, mbstring, openssl, pdo_pgsql, pgsql, zip, intl, and dom. The fast test suite also requires pdo_sqlite.

```sh
composer install
npm ci
```

Copy `.env.example` to `.env` and configure your PostgreSQL credentials locally. Neon connections use `DB_SSLMODE=require` and `PGCHANNELBINDING=require`. Keep private credentials out of the repository.

```sh
php artisan key:generate
php artisan migrate
```

Run Laravel and Vite in separate terminals:

```sh
php artisan serve
```

```sh
npm run dev
```

Open [the local application](http://127.0.0.1:8000). To use compiled assets without a Vite development server, run `npm run build`, then run only the Laravel server.

This Windows workspace also has a local portable PHP installation, which Git ignores:

```powershell
& .\.tools\php\php.exe artisan serve
& .\.tools\php\php.exe .tools\composer.phar install
```

The application uses client-side React rendering. SSR is disabled until a real
SSR service is configured. Automatic Boost browser-log posting is also disabled
by default to avoid additional development-server requests.

The local portable PHP runtime enables OPcache for CLI with timestamp validation
and `opcache.revalidate_freq=0`. Restart PHP after changing `php.ini`. Vite still
provides live frontend updates. Warm navigation differs from an initial visit,
which must also load the development modules.

## Initial setup and demo data

For a normal installation, seed the three initial branches and create the first owner. This does not add sample transactions or a default production password.

```sh
php artisan db:seed
php artisan veltrix:owner owner@example.com --name=Owner
```

The owner command prompts for a hidden password and refuses to create a second owner.

For an empty development database, use the separate demo seeder instead of creating the initial owner:

```sh
php artisan db:seed --class=DemoSeeder
```

The demo seeder runs only in local/testing environments. It stops without overwriting data if users or products already exist. It supplies three branches, ten accounts, twenty menu items, recipes, stock ledger entries, suppliers, employees, schedules, draft payroll, and approximately three months of transactions.

| Role | Demo email |
| --- | --- |
| Owner | owner@veltrix.com |
| Depok manager | depok.manager@veltrix.com |
| Depok cashier | depok.kasir1@veltrix.com |

Development demo password: `demo1234`. Jakarta and Bogor manager/cashier accounts follow the same email pattern. Demo menu photos are generated images stored in `public/images/menu`; replace them with the shop's product photos for a real catalog.

## Available workflows

The business reports screen has Summary and Menu sales tabs. Menu sales lists the complete catalog with pagination, including inactive and zero-sale products. Filter by date, authorized branch, category, or product name/SKU; sort by net quantity, net sales, or name. Expand a menu for its branch breakdown. Summary figures cover the entire filtered result, and CSV exports include all matching pages. Managers can access only their assigned branch. Product figures share the same underlying sales/refund aggregation as the AI tools; approved refunds are recognized on their processing date.

- Login, logout, password recovery, profiles, account activation, role and branch assignment, and change auditing.
- Login lasts at most seven days from password authentication, even with continued activity. The login form has no remember-me option; legacy remember cookies cannot restart the seven-day limit. Existing active sessions receive their seven-day baseline on their first request after this update.
- Branches, users, categories, products, branch-specific pricing and availability, and price change approvals.
- POS search and categories, cart quantities, manually recorded payments, tendered cash and change, cashier shifts, transaction history, printable receipts, and full refund requests and approvals.
- Ingredients, versioned recipes, branch inventory, adjustments, stock counts, stock ledger, suppliers, purchasing, partial receiving, and stock transfers with approval, dispatch, and receipt stages.
- Employees separate from login accounts, work schedules, expenses with private evidence and approval, and draft → approved → paid payroll with printable payslips.
- Owner and manager dashboards, daily and hourly trends, equal-duration branch growth comparisons, bestselling products, management financial reports, expense category breakdowns, current inventory valuation, stock movement counts, and CSV/PDF exports. Owners see eight overview metrics; managers see four. Inventory valuation reflects current balances, not historical balances at the report's end date.
- Stock notifications, per-user read status, business settings, and receipt footer configuration.
- Installable PWA with a sidebar install button above the account profile, standalone app icons, iOS installation guidance, an offline fallback, and user-controlled updates. Installation requires HTTPS or localhost and browser support. The service worker caches only the public offline page and app icons; authenticated pages, reports, API responses, and checkout requests are never cached or queued offline. Run `npm run test:pwa` to verify these cache boundaries.

The owner-only Intelligence workspace provides Gemini chat, conversation history, supporting analytics evidence, and automatic business insights. Chat uses Laravel AI SDK and approved read-only aggregate tools; it cannot execute arbitrary SQL or change transactions. Insight detection runs locally using explicit thresholds and remains available without Gemini. Sales trends require at least 10 transactions in both seven-day periods; stock coverage is an estimate based on seven days of recorded recipe consumption. Insight review statuses and notifications are deduplicated. The “Explain with AI” action opens a grounded conversation and uses the same daily question allowance.

Product questions can search catalog and historical names, return per-branch or daily quantities, and compare equally long periods with backend-calculated changes. Products outside the top five are searchable; unknown names are distinguished from recorded products with zero sales. Percent changes are unavailable when the baseline is zero or negative. Dates and branches can be changed within the same conversation: each new message stores its scope, while previous answers and evidence retain their original period. Implicit follow-ups use the current selection; explicit date requests may use another validated period.

Set `AI_ENABLED=true`, `GEMINI_API_KEY`, and `GEMINI_MODEL` on the server to enable chat. The default model is `gemini-3.5-flash-lite`; use `php artisan veltrix:ai-check --models` to inspect available text models and `php artisan veltrix:ai-check` to verify the connection without sending business data. `php artisan veltrix:ai-check --business` explicitly verifies analytics tool calling with business aggregates. Keep keys outside Git and the browser. `AI_DAILY_LIMIT` defaults to 30 questions per owner per Jakarta calendar day, including failed attempts; token usage is recorded per answer. Provider pricing and quotas apply separately from the free Laravel AI SDK.

Run a persistent database queue worker (`php artisan queue:work database --tries=1 --timeout=150`) for chat and manually requested analysis, even when the default queue connection is synchronous. The UI polls only while a reply is pending and reports stalled requests after five minutes. Run `php artisan veltrix:insights` for an immediate deterministic analysis. The scheduler dispatches automatic analysis daily at 06:00 Asia/Jakarta. Serverless hosting requires an external persistent queue worker and scheduler; synchronous web execution is not used for AI requests. No live provider call is made by automated tests.

## Data integrity and accounting conventions

The backend determines checkout prices. Idempotency keys prevent repeated checkout and receiving operations. Ingredient deductions, orders, items, payments, ledger entries, and audit records are written within database transactions. Row locks protect concurrent access to the last available stock.

Inventory valuation uses a weighted average for each ingredient and branch. Sales item costs are frozen at checkout, so later ingredient price changes do not rewrite history. A purchase does not immediately become cost of goods sold or add stock before receipt. Transfers preserve a snapshot of the source cost.

Refunds in this release are always full refunds and do not return consumed ingredients to stock. Non-cash payments and refunds are recorded manually; there is no payment gateway. Approved/paid payroll is recognized on the first date of its monthly period. Profit reports are management estimates, not statutory accounting statements.

## Tests and formatting

```sh
php artisan test
php vendor/bin/phpunit -c phpunit.pgsql.xml
npm run build
npm run format:check
```

The test runner rejects the operational database. The fast suite uses an isolated in-memory SQLite database; the PostgreSQL suite uses the local `veltrix_test` database on `127.0.0.1:55432`. Concurrency tests run two PHP processes to exercise last-stock contention and checkout replay. Tests do not reset the application's Neon database.

The PostgreSQL suite requires a running local test cluster. In this workspace it resides at `.tools/pgsql-test`. TLS and channel binding are disabled only for that local suite; Neon connections retain TLS and channel binding.

```powershell
$postgresControl = 'C:/Program Files/PostgreSQL/18/bin/pg_ctl.exe'
& $postgresControl `
    -D .tools/pgsql-test `
    -l .tools/pgsql-test/server.log `
    -o '-p 55432 -h 127.0.0.1' `
    -w start

& ./.tools/php/php.exe vendor/bin/phpunit -c phpunit.pgsql.xml
```

Source uses vertical formatting with Prettier, a print width of 90, and the PHP plugin. Run `npm run format` to format source. Laravel Pint is also available for PHP style checks; the project's final formatting pass uses Prettier to wrap long lines.

Local browser QA screenshots belong in `.tasteskill/screenshots/`, which Git ignores. Do not store them in `docs/screenshots/`.

## Queue, scheduler, and email

```sh
php artisan queue:work --tries=3 --timeout=90
php artisan schedule:run
php artisan veltrix:notifications
```

Run the scheduler every minute. The stock notification job is scheduled hourly and avoids duplicate notifications. `MAIL_MAILER=log` is for development: password reset links will not reach inboxes until a real mail transport is configured.

## Deployment

Vercel deployment is configured from the repository root without Docker. Import this GitHub repository, leave **Root Directory** at `./`, select **Other** as the framework preset, and use Node.js **22.x**. The committed `vercel.json` sets the install command, build command, and output directory; keep those settings without dashboard overrides. `vercel-php@0.8.0` supplies PHP 8.4. This community runtime is archived and is not officially supported by Vercel.

Add these environment variables in Vercel before deploying:

| Variable | Value |
| --- | --- |
| `APP_KEY` | The existing Laravel application key, kept private. Reuse it when connecting to the existing database so encrypted data remains readable. |
| `APP_URL` | The full HTTPS URL of the deployment or custom domain. |
| `DB_HOST` | Your Neon pooled hostname. |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | Your Neon database name. |
| `DB_USERNAME` | Your Neon role name. |
| `DB_PASSWORD` | Your Neon password, kept private. |

The configuration enables production mode, HTTPS cookies, PostgreSQL sessions/cache, TLS for Neon, stderr logs, and synchronous queue execution. Configure SMTP separately for password reset emails. `npm run build:vercel` builds the frontend and copies only public assets into `.vercel-static`; it includes the generated menu photos and landing image, while excluding PHP entry points, the Vite hot file, private storage, and source files. Composer dependencies and package discovery are handled by the PHP runtime. The Laravel entry point creates writable view and framework cache directories in temporary storage. Local development continues using `public/index.php` as usual.

Run `php artisan migrate --force` from a trusted environment configured for the target database when migrations are needed. Builds deliberately do not migrate, seed, reset data, or generate a replacement application key. Do not run DemoSeeder or DemoAccountSeeder in production. The built-in `/up` route provides a deployment health check.

Vercel does not run this application's Artisan scheduler automatically. Run `php artisan schedule:run` from an external scheduler/worker environment for hourly operational notifications. Synchronous queue execution covers jobs dispatched by web requests; it does not process an existing database queue backlog. Uploads of expense receipts currently use private local storage, which is temporary on Vercel: permanent receipt storage must be configured before relying on this upload feature in production. The committed menu images are deployment assets and are available without external image storage.

For traditional PHP hosting, point the web document root to `public/`, install production dependencies, build Vite assets, configure HTTPS and the environment, run migrations, and run `php artisan optimize`. Manage the queue worker through a process supervisor and the scheduler through cron or an equivalent service.

Keep encrypted PostgreSQL backups outside the web root and repository. Test `pg_dump` and `pg_restore` against a separate recovery database before going live. Backup execution, a restore drill, public deployment, uptime monitoring, and production load testing have not been completed in this workspace.

Never commit `.env`, API keys, database dumps, dependencies, or local runtime directories. Keep Neon and Gemini credentials out of frontend source and Git.
