# Implementation plan

The project follows build.md and prd.md. Work is delivered as vertical modules; an unfinished module must never be presented as working.

## 1. Foundation — in progress
Laravel + Inertia React, PostgreSQL, secure sessions, three roles, active-account enforcement, branch management, account management, password recovery, profile, audit trail, shared design system. Verify authentication, owner-only actions, branch isolation and account deactivation.

## 2. Products and POS — pending
Categories, catalog, branch price/availability, cashier shifts, idempotent checkout, manual payments, receipt and refund approval.

## 3. Inventory and purchasing — pending
Ingredient units, versioned recipes, branch stock ledger, locks, adjustments, suppliers, purchase orders, partial receiving and transfers. Integrate recipe consumption atomically with checkout.

## 4. Business operations — pending
Employees separate from login accounts, schedules, operational expenses and payroll approval/payment lifecycle.

## 5. Analytics — pending
One analytics module calculates sales, refunds, COGS snapshots, expenses and payroll. All charts, exports and later AI tools consume these definitions.

## 6–7. AI — deferred configuration
Laravel AI SDK + Gemini, authorized read-only analytics tools, stored conversations, provider usage and failure handling, deterministic insight detection and scheduled jobs. No mock AI responses.

## 8. Quality and deployment — ongoing / pending production
Feature and permission tests per vertical module, PostgreSQL concurrency checks for stock/checkout, browser verification, production worker/scheduler configuration, backup/restore rehearsal.

## Module structure
Controllers adapt HTTP/Inertia requests. Form Requests validate and authorize writes. Models represent persistence. Policies enforce resource access. Modules under app/Services own transactional business behavior. Analytics and AI will have separate modules when implemented; do not create pass-through abstractions in advance.

## Database design
Foundation: branches, users, password_reset_tokens, sessions, jobs, cache, audit_logs. Non-owner users require a branch. Branch codes and emails are unique. Branches/users are deactivated rather than deleted. Audit changes exclude secrets. Owner cannot deactivate or demote themselves through account management.

Next: categories → products → branch_products → recipe_versions → recipe_items; ingredients → inventories → inventory_movements; cashier_shifts → orders → order_items → payments → refunds/refund_items; suppliers → purchase_orders → purchase_order_items → stock_receipts/items; stock_transfers/items; employees → schedules/compensations → payroll_periods/items/adjustments; expense_categories → expenses; AI conversations/messages/tool executions, business_insights, notifications and settings.

Use foreign keys with restricted destructive cascades, unique branch/product and branch/ingredient pairs, integer rupiah money, decimal stock with consistent base units, immutable historical snapshots, ledger references and unique idempotency keys. Migrations are additive; never reset the supplied Neon database.
