# Product

<!-- impeccable:product-schema 1 -->

## Platform
web

## Stack
Laravel 13, PHP 8.4, React TypeScript through Inertia, Tailwind CSS, shadcn/ui, PostgreSQL Neon. No Docker, Next.js, or separate SPA.

## Users
Owner monitors all branches. Branch Manager manages only their assigned branch. Cashier processes sales in their branch. Desktop and tablet are primary; mobile remains usable.

## Product Purpose
VELTRIX connects coffee shop sales, ingredients, purchasing, employees, payroll, expenses, and analytics in one modular monolith.

## Operating Context
Initial branches: Depok, Jakarta, Bogor. Indonesian interface, IDR, Asia/Jakarta business timezone. PostgreSQL is authoritative. User supplied a Neon connection privately. Gemini configuration is deferred by the user.

## Capabilities and Constraints
Build vertically in the order in build.md. Enforce permissions and branch isolation in the backend. Never invent metrics or AI answers. Demo data must be separate from production setup. No public registration.

## Brand Commitments
VELTRIX, restrained emerald, white surfaces, clear Indonesian copy, modern readable sans serif.

## Evidence on Hand
build.md is the implementation instruction; prd.md defines requirements and acceptance criteria. There is no existing application or asset library.

## Product Principles
- Operational integrity before decorative breadth.
- Every action is authorized and validated on the server.
- Figures are derived from stored records.
- AI failure must not affect operations.
