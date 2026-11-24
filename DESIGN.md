---
name: VELTRIX
description: A restrained emerald workspace for coffee shop operations.
colors:
  primary: "#166348"
  primary-hover: "#104f38"
  ground: "#f3f6f4"
  surface: "#fff"
  raised: "#f8faf9"
  active: "#e7eeea"
  border: "#e5eae7"
  rule: "#edf0ee"
  control: "#ccd5cf"
  text: "#202a24"
  muted: "#626d66"
  danger: "#a52c32"
  focus: "#278061"
  nav-selected: "#e0e9e3"
  nav-selected-text: "#145b40"
  status-positive-bg: "#e8f3ec"
  status-positive-text: "#1c653e"
  status-pending-bg: "#fff5dc"
  status-pending-text: "#83601c"
  status-negative-bg: "#fbecea"
  status-negative-text: "#963b36"
  status-open-bg: "#e7eef3"
  status-open-text: "#3e6277"
typography:
  headline:
    fontFamily: "Inter Variable, sans-serif"
    fontSize: "26px"
    fontWeight: 650
    lineHeight: 1.25
    letterSpacing: "-0.035em"
  title:
    fontFamily: "Inter Variable, sans-serif"
    fontSize: "16px"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "-0.015em"
  body:
    fontFamily: "Inter Variable, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.5
  control:
    fontFamily: "Inter Variable, sans-serif"
    fontSize: "13px"
    fontWeight: 500
    lineHeight: "18px"
  metric:
    fontFamily: "Inter Variable, sans-serif"
    fontSize: "24px"
    fontWeight: 550
    lineHeight: 1.5
    letterSpacing: "-0.04em"
rounded:
  status: "5px"
  control: "7px"
  product: "10px"
  panel: "12px"
  dialog: "14px"
  sheet: "16px"
spacing:
  compact: "8px"
  control-inline: "14px"
  panel: "20px"
  dialog: "24px"
  sheet: "28px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.surface}"
    typography: "{typography.control}"
    rounded: "{rounded.control}"
    padding: "8px 14px"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
  button-outline:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.control}"
    padding: "8px 14px"
  field:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.control}"
    padding: "8px 10px"
  status-positive:
    backgroundColor: "{colors.status-positive-bg}"
    textColor: "{colors.status-positive-text}"
    rounded: "{rounded.status}"
    padding: "2px 7px"
  nav-selected:
    backgroundColor: "{colors.nav-selected}"
    textColor: "{colors.nav-selected-text}"
    rounded: "{rounded.control}"
    padding: "8px 12px"
---

# Design System: VELTRIX

## Overview

**Creative North Star: "The Framed Operations Sheet"**

VELTRIX presents dense operational information on a white inset sheet surrounded by pale green-grey workspace chrome. Restrained emerald identifies actions, selected context, and meaningful business data. Typography, alignment, and fine rules establish hierarchy without decorative effects.

The interface serves owners overseeing revenue and branches, managers resolving branch followups, and cashiers completing accurate checkout. Keep information scoped and task-oriented. Indonesian labels and IDR figures are the working language. This document records the implemented system in `resources/css/app.css`; it is a scan of the current interface, not a pixel-match claim for a reference image.

Key characteristics:

- Quiet chrome with a clearly bounded working surface.
- Readable compact tables, tabular numbers, and explicit status labels.
- Grouped navigation and clear branch context.
- Brief state transitions and visible keyboard focus.

## Colors

The primary palette combines deep emerald with green-tinted neutrals. Warm amber, muted red, and slate blue communicate operational status only.

**Primary.** Emerald fills the main action and appears in links, chart accents, and checkout controls. The darker hover token signals interaction without motion or glow.

**Neutral.** Ground belongs to the surrounding shell; surface belongs to the inset sheet and controls. Raised distinguishes contextual or supporting areas. Border defines containers, rule separates rows, and control makes field boundaries stronger than passive dividers. Text and muted establish information hierarchy.

**Status.** Positive covers approved, completed, paid, and received states. Pending covers ordered and partial states. Negative covers refunded, rejected, and cancelled states. Open/shipped uses the slate pair. Each state always includes readable text.

**The Operational Accent Rule.** Use emerald for action and relevant information. Do not spread accent fills across decorative containers.

## Typography

Local Inter Variable supplies the entire interface through `@fontsource-variable/inter`; use the sans-serif fallback. Use the headline, title, body, control, and metric roles defined above. The variable font supports intermediate weights already present in the implementation.

Page headlines tighten tracking and reduce to (22px) on small screens. Dialog titles use (19px), weight (650). Table content uses (13px), navigation uses (14px), and supporting annotations range from (11px) to (12px). Product names and prices use (14px).

The three primary summary metrics use the metric role with (14px) labels and (12px) supporting text. Numeric table cells align right and use tabular numerals. Keep formatters shared for currency, counts, and dates rather than manually formatting labels.

**The Comparable Figures Rule.** Numeric alignment and consistent formatting take priority over decorative number treatments.

## Layout

The desktop shell has a fixed sidebar (228px) beside an open white workspace, separated by a fine vertical rule. A single page header carries the title, relevant actions, and a discreet notification link. Do not restore the redundant breadcrumb, top avatar, or rounded outer sheet. The main content uses (32px 32px 40px) padding. Dashboard branch selection sits beside the report action; date filters remain in the toolbar below. The content area flexes with the viewport rather than imposing a marketing-page maximum width.

Sidebar groups are Penjualan, Inventori, Operasional, Keuangan, Intelligence, and Administrasi. Empty role-filtered groups disappear. Owner sees cross-branch tools; manager sees branch operations; cashier sees sales tools. Preserve the workspace and user context at the top and bottom of the sidebar.

The dashboard has a ruled metric strip and a two-column content grid, with a wider revenue area. Both roles see three primary metrics in one desktop row: net sales, transactions, and operating profit for owners or gross profit for managers. Three supporting statistics align with the primary columns in a compact second row. Detailed financial calculations live on the separate Reports page; Dashboard prioritizes a compact trend, live pending tasks, low stock, top three menus, and three recent transactions. Secondary statistics retain labels above clearly sized values; panel padding stays (14–16px), and the trend is (176px) high. Panels fit their content instead of stretching shorter lists; full records remain accessible through links. The deferred AI status remains in its dedicated navigation destination. Pending tasks reflect current status regardless of the sales date range. Manager views never expose payroll or operating profit. Mobile stacks the metrics and profit explanation in reading order. Management screens put title and action above a wrapping search/filter toolbar and horizontally scrollable table. Toolbar date inputs use (150px) width and (36px) height on desktop; select filters size automatically up to (200px) wide with the same height. Mobile inputs and selects flex with (120px) minimum width and (44px) height. Forms use two columns inside dialogs and one column on mobile.

Reports preserves the previously approved overview: period and branch controls, six financial figures, a disclosure for the profit calculation, the full trend and branch ranking, product and stock summaries, recent transactions, and detailed comparison/expense/inventory tables. Dashboard uses the compact operational variant with live pending tasks and previews limited to three rows. Detailed exports and accounting caveats remain in Reports.

Checkout keeps catalog and active order side by side on desktop. The order column is sticky, aligns with the catalog, and has a bounded scrolling item list. Its default width is (348px); responsive overrides adapt it to the available space. Product cards use a three-column default grid. Mobile stacks catalog and order and exposes a fixed cart shortcut when appropriate.

Responsive behavior follows the existing cascade:

- At (1600px) and wider, sheet padding expands and the catalog gains four columns; checkout order width becomes (370px).
- At (1250px) and below, sidebar narrows to (216px), catalog becomes two columns, and checkout order width becomes (320px).
- At (1100px) and below, the sidebar becomes a (240px) off-canvas drawer with backdrop; main content takes full width. Catalog uses three columns and order width becomes (340px).
- At (900px) and below, catalog returns to two columns and order width becomes (320px); secondary chrome is reduced.
- At (700px) and below, the sheet loses its frame, the navigation trigger moves beside the page title, padding becomes (23px 18px 36px), grids stack, metrics stack in one column, and key controls reach at least (44px) touch height.

There is also a desktop short-height adjustment at heights up to (820px), reducing cart item height so payment remains reachable. Preserve horizontal table scrolling and the sticky identity cell on mobile.

## Elevation & Depth

Use tonal layering and borders for resting surfaces. Dashboard panels, product cards, and the main sheet do not carry shadows. Dialogs alone use the structural shadow `0 18px 55px #10231824`, with a dark transparent overlay. The mobile navigation drawer uses its own backdrop.

State transitions are brief: button background/border changes use (0.13s), product borders use (0.12s), and drawer translation uses (0.18s ease-out). The reduced-motion query removes transitions and animation. Do not add ambient motion.

## Shapes

Corners follow component purpose: compact rounded controls, slightly softer products and panels, with an unframed main workspace. Status chips are compact rounded rectangles. Avatars alone use circular silhouettes. Fine borders and clipping preserve the clean sheet edge; do not turn every row or metric into an isolated card.

## Components

**Buttons.** Reuse the shared Button component. Default is emerald with white text; outline is white with a control-strength border; ghost is transparent with a pale hover fill; destructive uses danger. Default controls are (36px) minimum height, with small and icon variants. Disabled buttons use opacity (0.55). Keyboard focus uses a (2px) focus outline with (3px) offset. Mobile default buttons have (44px) minimum height.

**Fields.** Visible labels sit above white inputs with control-strength borders and gently curved corners. Field gaps are (7px); field groups have (17px) bottom spacing. Preserve error text and focusable error summaries. Search fields include a leading icon and sufficient internal padding.

**Navigation.** Use consistent line icons, regular casing, and muted group labels. Selected items have a pale emerald surface and darker green text; hover changes the background. Keep `aria-current` and named menu controls. Desktop navigation remains an ordinary sidebar. At the drawer breakpoint, reuse the installed Radix Dialog primitive with a named trigger, portaled overlay and modal content, a screen-reader title, and an explicit close control. The closed drawer is unmounted. Preserve the primitive's focus trap, Escape dismissal, trigger focus return, and background isolation. The close icon occupies (32px) at top (17px), right (8px).

**Navigation feedback.** Skip progress feedback for requests completed within
700ms. Longer navigation shows one thin emerald line without a glowing peg or
spinner. Preserve `aria-busy`, form processing labels, and disabled submission
buttons. Menu links prefetch on hover or press with a three-second response
lifetime; writes clear this cache. Background prefetch does not mark the current
page busy.

**Role navigation.** Owner shortcuts are Ringkasan, Laporan bisnis, and Transaksi.
Manager shortcuts are Ringkasan, Persediaan cabang, and Laporan bisnis. Remaining
menus use an inline accordion with one group open at a time; clicking its header
again closes it. AI & insight bisnis follows Administrasi in the owner's menu
list. Preserve access to long lists through scrolling when an expanded group
cannot fit the available height. Compact laptop spacing keeps ordinary closed
and short-group navigation within the viewport without reducing body text below
13px; drawer controls retain a 44px minimum height.

**Status chips.** Use the shared Status component and label mapping. Chips use small text (11px), a minimum height of (22px), and semantic color pairs. These are state labels, not primary actions.

**Tables.** Fine bottom rules separate rows; hover is nearly white. Identity cells carry stronger text, secondary details sit below, and row actions align right. Preserve pagination, empty states, and search controls instead of substituting decorative summary cards.

**Panels and metrics.** Panels have a fine border, clipped corners, and a header with (20px) inset. Metrics share a ruled strip with vertical dividers. Use stored operational values and preserve meaningful empty states.

**Report details.** Reuse horizontally scrollable tables for equal-duration branch comparisons and current inventory valuation. Use simple key-value rows for expense categories and stock movement counts. Show the comparison period and explicitly label unavailable growth percentages. Distinguish current inventory valuation from the selected report period; owner-only payroll and operating profit columns remain role-aware.

**Products and checkout.** Product cards use photos when available, a coffee icon fallback, and border changes for hover and selection. Keep prices readable and unavailable items visibly distinct. Quantity buttons become (44px) square on mobile. Payment and total remain within the active order panel.

**Dialogs and receipts.** Dialogs are centered, scroll within the viewport, and constrain width to (640px) or viewport minus (32px). Mobile padding reduces to (20px). Receipt printing removes shell, actions, overlays, and mobile cart chrome; preserve the narrow receipt hierarchy.


## Public landing page

The public root is separate from the operational workspace. Its hero uses a solid forest-emerald field, a natural two-line sentence without clipped slogans or end-of-line periods, an obvious login action, and a large upright POS preview. A short photograph of coffee craft below the preview and real menu photography provide cafe warmth. No image remains pinned beside scrolling content. Use Inter Variable, emerald (#173f32), a light ground (#f7f8f4), and sage (#e5e8dc) for the illustrative app frame.

The POS preview is a semantic interactive component, not a screenshot background. Search and category controls filter four illustrative menu items; selecting products updates bounded quantities and totals. A sample receipt stays in local state and explicitly does not save an operational transaction. At 1100px the hero copy and preview reflow; below 900px the preview drops its decorative sidebar, and below 600px catalog and order stack with readable controls. Prefer full sentences and ordinary feature headings throughout the page.

The hero direction was calibrated against Square's coffee-shop product page and Shopify POS. The working image target is a 1487 × 1058 desktop composition, retained in ignored .tasteskill/landing-hero-target.png. The implementation uses actual local menu assets, the existing V mark, and real controls; it intentionally keeps a flat emerald field rather than reproducing generated texture. Source image provenance remains in public/images/landing/provenance.json.

Primary actions lead to login for guests and the workspace for authenticated users. Accounts remain assigned by the business, with no public registration. Direct section links are restored after the client mounts. The header disclosure supports Escape, the role selector supports arrow/Home/End keys, and reduced motion disables decoration. Landing styles stay isolated from operational screens.

## Do's and Don'ts

- Do reuse the shared tokens and existing components before introducing variants.
- Do keep operational scope, status, errors, and actionable next steps explicit.
- Do preserve visible keyboard focus, reduced motion, and mobile touch sizing.
- Do keep source and JSON readable with vertical formatting.
- Don't introduce gradients, vanity metrics, fabricated business figures, or simulated AI output.
- Don't replace the restrained sheet composition with a wall of raised cards.
- Don't use color alone to communicate state or hide essential actions at smaller widths.
- Don't treat the generated reference image as evidence of implemented measurements.
