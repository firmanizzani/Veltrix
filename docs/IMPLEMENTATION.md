# Implementation status

Updated: 26 November 2026. This document records implemented behavior and explicit
boundaries against build.md and prd.md.

## Operational application

| Area | Implemented behavior |
| --- | --- |
| Foundation | Laravel 13, React TypeScript, Inertia 3, Neon PostgreSQL, three roles, branch isolation, active accounts, session invalidation, recovery and profile. |
| Administration | Branches, staff accounts, business settings, receipt footer and redacted audit history. |
| Catalog | Categories, products, branch prices/availability, price approval, ingredients and immutable recipe versions. |
| POS | Search/category filters, cart, manual payments, change, shifts, receipts, history and full-refund approval. |
| Inventory | Recipe consumption, branch stock, adjustments, counts, weighted costs, ledger and low-stock notifications. |
| Purchasing | Suppliers, orders, pre-receipt cancellation, partial receiving, receipt records and idempotent receiving. |
| Transfers | Request, owner approval/shipment, destination receipt, source cost snapshot and both branch ledgers. |
| People | Employees separate from accounts, branch assignments, daily schedules, monthly payroll components, approval, payment recording and payslips. |
| Expenses | Categories, branch, private documents, approval/rejection, audit, and date/category/branch filters. |
| Analytics | Gross/net sales, refunds, count, average order value, net units, COGS, gross/operating profit, expenses, payroll and active branches. |
| Reports | Summary and complete menu-sales tabs; date, branch, category and name/SKU filters; zero-sale and inactive menu inclusion; per-menu branch details; complete filtered CSV; daily/hourly trends, payment composition, branch comparisons, expense categories, stock valuation, CSV and PDF summaries. |
| Interface | Grouped navigation for each role, desktop/tablet/mobile layouts, validation and loading/empty/error states. |

## Integrity and access

- The application uses Neon PostgreSQL. SQLite exists only in the temporary,
  isolated in-memory test configuration.
- Checkout locks actor, shift and stock, validates server prices, snapshots cost,
  and commits sale/payment/ledger/audit together. Replayed keys cannot duplicate
  sales or change the original payload.
- Receipt keys are bound to the original purchase and quantities.
- Stock cannot become negative and all changes create ledger entries.
- Ingredient units cannot change after operational use. New recipe versions and
  later prices do not change historical sales costs.
- Backend branch isolation covers manipulated parameters, direct URLs and exports.
  Managers do not receive payroll or operating-profit data in payloads or exports.
- Cashiers access their own sales/shifts, with no business reports, payroll or
  account administration. Expense documents require authorized downloads.
- Tests reject the operational database before reset traits execute.

## Workflow boundaries

- Refunds cover the full order; item-level partial refunds are not supported.
  Consumed ingredients are not automatically returned to stock.
- Non-cash payment records are manual. There is no payment gateway or offline POS.
- Purchases are ordered on save; a separate draft purchase editor is not provided.
- Transfers contain one ingredient and are received in full after shipment.
- Schedules represent one same-day shift per employee/date, without attendance or
  overnight shift calculation.
- Expense categories are text on expense records. All expenses need owner
  approval; a configurable automatic approval threshold is not implemented.
- Payroll is entered per employee/month using base pay, allowance, bonus and
  deduction, without automatic attendance or statutory tax calculation. Approved
  or paid payroll is recognized on the first date of its monthly period.
- Inventory report valuation is the current stock snapshot, clearly labeled as
  such. The report date range filters business activity, not this snapshot.
- Growth compares the preceding period of equal duration; a zero or negative
  baseline produces no percentage. It is not a forecasting model.
- Profit is a managerial estimate excluding depreciation, financing and tax.
- The demo seeder runs only in local/testing and refuses existing users/products.
  Reports query persisted records, not hardcoded frontend numbers.

## Business intelligence

The owner workspace uses Laravel AI SDK 1.4 with Gemini, approved aggregate
analytics tools, stored conversations, supporting evidence, and per-answer token
usage. Rate limits and a daily allowance bound requests, including failed attempts.
Requests are processed through a database queue with explicit unavailable, pending,
failed, and expired states. Credentials remain server-side. No arbitrary SQL or
operational mutation tools are available to the model.

Named-product analysis searches the catalog and historical product names instead
of treating the top-five list as a complete catalog. It returns per-product,
per-branch, or daily counts, including zeros and approved refunds processed during
the requested period. Optional equal-duration comparisons calculate deltas in the
backend; percentage changes remain unavailable for zero or negative baselines.
Unknown products are distinguished from known products with no sales.

Dates and branch selections can change within a conversation. Each new message
captures its own scope; earlier answers and evidence remain unchanged. Implicit
follow-ups use the current selection, while explicit date expressions can request
another bounded period. The model is instructed to answer counts directly and to
separate evidence from hypotheses when discussing possible causes.

Daily deterministic analysis compares complete seven-day periods, enforces minimum
transaction counts, detects sales and product changes, expense increases, branch
differences, and current stock risk. Stock coverage uses recorded recipe usage and
states its assumptions. Findings retain evidence and review status, are deduplicated,
and notify owners. Optional Gemini explanations use the same chat budget. Core
operations and rule-based insights remain independent of Gemini availability.

## Deployment boundary

Local build and verification are complete for the implemented operational scope.
Public hosting, production SMTP, managed worker/scheduler installation, backup
retention, production-like restore rehearsal, load testing and uptime monitoring
have not been performed. README.md contains setup and deployment instructions.
This is not a claim of production availability or of independently audited AI accuracy.

## Verification

The fast suite uses an isolated temporary database. PostgreSQL integration tests
also run two independent PHP processes to race last-stock checkout and same-key
replay. Coverage includes permissions, branch isolation, finance reconciliation,
stock costing, receiving, payroll, notifications and audit redaction.

Browser checks cover POS payment/receipt/shift, management forms, owner and manager
dashboards, responsive navigation and report tables. Local screenshots belong in
ignored `.tasteskill/screenshots/`, never in docs or Git history.

Latest verification: 45 PostgreSQL tests passed with 309 assertions. The fast
suite passed 44 tests with 298 assertions and skipped the two PostgreSQL-only
concurrency cases. TypeScript/Vite production build and format checks passed.
The PDF export test also passed after vertically formatting its Blade template.

An independent visual review inspected 13 desktop/tablet/mobile captures. Its
required keyboard-navigation correction was implemented with Radix modal
navigation and verified with bidirectional Tab wrapping, Escape dismissal and
focus restoration. The reviewer returned `ship` for that required correction.
Owner mobile metric density and representative demo photography remain optional
visual refinements, not claims of a fresh full-surface approval after the fix.
The automatic design detector could not run because its engine cache was
unavailable; visual assessment was manual.

Navigation performance was optimized through selective data loading:
dashboard requests skip full report breakdown queries, management pages load
only required option sources, and small dashboard lists use scoped joins.
Tests reconcile summary/report metrics and enforce a reduction of at least ten
queries for summary mode. Read-only owner transaction options require one query.
Menu responses prefetch on hover/press for up to three seconds. Every write visit
clears this client cache. Checkout prices, availability and stock are still
validated server-side. Background prefetch does not mark the page busy. One plain
progress line appears only after 700ms; quick requests skip it entirely.
Unconfigured SSR attempts and automatic Boost browser-log posting are disabled
by default. Local PHP OPcache was enabled with timestamp validation for immediate
source updates. Temporary request profiling was removed after diagnosis.
