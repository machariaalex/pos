# Agrovet POS — notes for continuing this project

This file is loaded automatically by Claude Code. It exists so a session on a
different machine can pick up exactly where the last one left off — read it
fully before making changes, especially the "in-flight work" and "do not"
sections below.

## What this is

A Laravel 12 + Livewire 3 + Tailwind v4 point-of-sale system for a
single-shop Kenyan agrovet (agricultural + veterinary supplies). Single
developer/maintainer (the user) will own this code long-term, so keep
solutions boring, conventional, and readable over clever. Money is stored as
integer cents throughout. SQLite for local dev, MySQL-compatible migrations
for production. Timezone `Africa/Nairobi`.

The full functional spec, PIN-approval matrix, and deployment walkthrough
are in `README.md` — read that for domain/feature detail, this file is about
process and current state.

## Setting up on a fresh clone

`vendor/`, `node_modules/`, `public/build`, and the SQLite database file are
all gitignored, so a fresh `git clone` has none of them. To get running:

```
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=UserSeeder      # demo logins, see below
npm run build                                # production-style Vite build;
                                              # there is no persistent dev
                                              # server/HMR workflow here
php artisan serve                            # add --host=0.0.0.0 if you
                                              # need it reachable on the LAN
```

**Do not run the full `php artisan db:seed` or `migrate:fresh --seed` on the
working database without checking with the user first.** `DatabaseSeeder`
also runs `CategorySeeder` and `ProductSeeder`, which would repopulate demo
products/categories — the user has twice deliberately wiped all
products/categories/batches/sales (see "Database state" below) specifically
so they could enter their own real catalog. Seed `UserSeeder` (and
`CustomerSeeder` if customer test data is needed) individually instead.

Demo logins (password `password` for all): `owner@agrovet.test` (PIN 1234),
`manager@agrovet.test` (PIN 5678), `attendant@agrovet.test`.

## Current status

Core build (6 milestones — schema/auth, inventory, sales/receipts,
customers/credit, reports/dashboard/cash-up, polish) is **done and
feature-complete**, 243 tests passing (`php artisan test`).

Since then, two threads of work have been happening:

### Thread 1: visual redesign — IN PROGRESS, screen 3 of 6

The user asked for a full visual redesign to a modern dashboard aesthetic —
**styling and layout only, zero functional changes**. These rules are still
in force for any remaining screens:

- Do not rename routes, Livewire components, public methods, events, or
  `wire:model` bindings. Do not alter validation, permissions, queries, or
  business logic. If a visual change would require touching logic, stop and
  ask first.
- Work one screen at a time, in the order below. After each screen: run the
  full test suite, then give a manual smoke-test checklist and **wait for
  explicit approval** before starting the next screen.
- Preserve: keyboard/barcode-scanner input on the Sell screen, role-based
  visibility (attendants must never see profit/buying prices by default —
  see the permissions system below for how that can now be overridden per
  user), pagination, filters, flash messages.
- Don't touch the thermal receipt print template's structure, typography
  only.
- Tailwind only, no new heavy UI frameworks. Chart.js for any charts.
- Design language: deep agricultural green primary, warm off-white/stone
  surface, amber/red/blue semantic accents, self-hosted Inter font (no
  runtime Google Fonts CDN), tabular figures for money, rounded-xl cards,
  dark-green collapsible sidebar nav, Heroicons throughout.

**Design system** (built in step 1, reuse for every remaining screen):
`resources/css/app.css` (design tokens via Tailwind v4 `@theme`),
`resources/views/components/{card,stat-card,badge,button,table,alert-row,modal,empty-state,nav-item}.blade.php`,
`resources/views/components/layouts/app.blade.php` (sidebar shell).

**Progress:**
1. Dashboard — ✅ done, approved.
2. Sell screen — ✅ done. Note: the first pass over-scoped this into a
   product-grid-browsing + category-filter-chip layout: the user explicitly
   rejected that ("let it be the way it was before, just work on the UI")
   and it was reverted back to the original search-box-with-dropdown
   interaction pattern, restyled only. That reverted version is what's
   live now and was approved.
3. Products & Inventory — ✅ built and tested (243 tests passing at the
   time), smoke-test checklist was handed to the user, **but the user got
   pulled into other work (see below) before explicitly confirming it looks
   right or saying "move to screen 4."** Don't assume this is approved —
   confirm with the user before starting screen 4, and don't be surprised
   if there's feedback on it first.
4. Customers & credit — not started.
5. Reports — not started.
6. Cash-up / Stock takes / Audit log / Users / Login — not started.

Known, deliberately-unfixed issue found during the Sell screen work: a fast
scan (`fill()` + immediate Enter) races the search input's
`wire:model.live.debounce.150ms` against `wire:keydown.enter="addFromSearch"`
and can leave stale text in the search box after the item is correctly
added. Pre-existing bug, not introduced by the redesign. User's decision:
leave it — do not fix unless asked.

Final step once all 6 screens are done (per the user's original ask): run
the full suite, then a one-page summary of every file changed confirming no
routes/component names/bindings were modified.

### Thread 2: features added mid-redesign — separate from the above, already shipped

These are real functional changes the user asked for outside the redesign's
"styling only" scope, fully built, tested, and merged — not pending, not
part of the screen-by-screen approval flow above:

- **Product categories are now manageable from the UI**: an inline
  "+ New category" quick-add inside the product create/edit form, plus a
  dedicated screen at `/inventory/categories`
  (`app/Livewire/Inventory/Categories/Index.php`). Delete is blocked (UI +
  server-side) while any product still references the category.
- **A global "Receive Stock" screen** at `/inventory/receive-stock`
  (`app/Livewire/Inventory/ReceiveStock/Index.php`) replaced the old
  per-product "Receive stock" modal: search any product, then enter batch
  number (optional), expiry (optional), quantity, buying price
  (pre-filled from the product's last cost), received date. The
  per-product page's "Receive stock" button now links here with
  `?product={id}` to preselect it. Reuses the existing
  `App\Actions\Inventory\ReceiveBatch` action, no logic changes there.
- **Owner-configurable per-user permissions**, layered additively on top of
  the existing role system (see `app/Models/User.php` `PERMISSIONS` const
  and `hasPermission()`, and `app/Providers/AppServiceProvider.php` gate
  definitions). An Owner can grant a Manager or Attendant any of 9
  capabilities (add/edit products, view buying price, view profit, apply
  discounts, adjust stock, set credit limits, view reports, view audit log,
  manage users) beyond their role's defaults, via a checklist in the Users
  screen edit modal. Every gate is `roleDefault() || hasPermission('key')`
  — nobody's access changes unless the Owner explicitly grants something,
  so this required zero changes to any pre-existing test. Owners always
  have full access and are not shown the checklist. `manage-users` is
  itself grantable (an Owner could delegate user management to a Manager)
  — flagged to the user as a deliberate but noteworthy design choice.
  While building this, closed a related gap: the buying-price *input* in
  the product form wasn't gated by `view-buying-price` (only the read-only
  list/show views were) — now it is, and a product created by someone
  without that permission defaults its cost to 0 until someone with
  visibility sets it.

Full test suite is at 243 passing after both threads (see
`tests/Feature/Inventory/CategoryManagementTest.php`,
`ReceiveStockTest.php`, `tests/Feature/Admin/UserPermissionsTest.php`).

## Database state

The user has twice asked to wipe all products/categories/batches/sales (and
their dependents — stock takes, customer ledger entries, customer payments,
resetting customer balances to zero) so they could re-enter their real
catalog and stock themselves. **An empty product catalog on the working
database is the expected, intentional current state, not a bug or
regression.** Users, customer contact records (names/phones), and audit log
history were preserved through both wipes.

If products/batches/sales need clearing again, the FK delete order that
works (see migrations under `database/migrations/` for the full dependency
graph) is: `sale_return_lines` → `sale_returns` → `customer_ledger_entries`
→ `customer_payments` → `sales` (cascades `sale_lines`/`payments`) →
`stock_takes` (cascades `stock_take_lines`) → `stock_adjustments` →
`batches` → `products` → `categories`, then reset any nonzero
`customers.balance_cents` to 0. Always confirm scope with the user first —
the blast radius here is easy to underestimate (this exact sequence was
worked out live after discovering `restrictOnDelete()` on most of these FKs
blocks a naive `Product::delete()`).

There was also a real, now-fixed data-entry bug: a batch of "maize germs"
was received with the *total* purchase cost typed into the *per-unit*
buying-price field (should divide total ÷ quantity first), which showed up
as a wildly negative "Profit today" on the dashboard. The profit
calculation itself (`app/Actions/Reports/ComputeDailySummary.php`,
`ComputeProfitForRange.php` — revenue minus each sale line's actual
per-batch cost via `SaleLine::costCents()`) is correct and doesn't need
touching; this was purely bad input data, since fixed by the wipe above.
Worth building at some point if not already done: an "enter total cost,
auto-divide by quantity" convenience option on the Receive Stock form, to
stop this class of mistake at the source.

## How this user likes to work

- **Milestone/screen-by-screen, with an explicit approval gate between
  each.** Don't bundle multiple screens' worth of changes into one
  delivery, and don't move to the next one without the user confirming the
  current one first — even if that means sitting idle after finishing a
  screen. This was stated explicitly for the original 6-milestone build and
  has held for the redesign too.
- **Verify bugs against the real running stack, not just the test
  harness**, when the user reports something broken — a prior "can't log
  in" report turned out to be a genuine SQLite case-sensitivity bug (see
  below), found by reproducing the exact request, not by assuming user
  error.
- **Destructive/scope-expanding actions**: check current data state and
  confirm scope before wiping or altering real records, especially once the
  blast radius turns out bigger than the literal request (happened twice
  here — a "just clear products" request turned out to also require
  clearing stock takes and customer credit ledgers due to FK constraints).
- When a request implies a real design/architecture decision (e.g. "let the
  owner grant permissions" could mean a fixed role tweak or a whole
  configurable-permissions system), use plan mode / ask clarifying
  questions rather than guessing about which one is wanted — the
  owner-configurable-permissions feature above is a good example of the
  depth expected: it went through an explicit plan (researched the full
  existing gate/permission architecture first, asked 2 clarifying
  questions, then wrote out data model, gate changes, UI, and test plan
  before touching code) rather than being guessed at from the first
  message.

## Technical gotchas specific to this codebase

- **SQLite text comparisons are case-sensitive (and don't trim
  whitespace); MySQL's default collation usually isn't.** Local dev is
  SQLite, production is MySQL. Any `WHERE column = $userInput` against
  free-text input (email, product name/barcode search, customer phone/name
  search) needs normalizing at write-time (see the `email` mutator on
  `App\Models\User`) or explicit `LIKE`/`LOWER()` matching — and needs
  testing against SQLite specifically, since MySQL will silently paper over
  a bug that SQLite would catch.
- **`Livewire::test(Component::class)->call('gatedMethod')` does not let a
  `Gate::authorize()` failure bubble up to PHPUnit's `expectException`** —
  Livewire's test `RequestBroker` deliberately excludes
  `AuthorizationException` from raw propagation and routes it through
  Laravel's normal exception handler (a 403) instead. Assert on the
  observable outcome instead (e.g. `assertDatabaseMissing(...)`). Full HTTP
  route tests (`$this->actingAs($user)->get(route(...))->assertForbidden()`)
  work as expected and should be used for gates checked in a component's
  `mount()`.
- **`php artisan db:seed` (and `migrate:fresh --seed`) wraps the run in
  `Model::unguarded()`**, so seeders can mass-assign fields outside
  `$fillable` (e.g. backdating `created_at`) that identical `::create([...])`
  calls in tests or tinker cannot. If a test needs to set a
  normally-guarded field, assign the attribute directly and `->save()`
  rather than passing it through `create()`/`fill()`/`update()`.

## Environment notes

- No persistent Vite dev server/HMR — assets are a production-style build
  (`npm run build`) served as static files from `public/build`. **Any
  change to a Blade view that introduces a Tailwind utility class not
  already used elsewhere in the codebase requires an `npm run build`**
  before it'll actually render — the CSS is not regenerated on the fly.
- For any browser-driven verification (screenshots, interaction testing),
  there's no persistent browser-automation tool installed — Playwright was
  installed ad hoc via `npm install --no-save playwright` (never added to
  `package.json`) and used directly, one script per verification pass.
- Repo is pushed to `git@github.com:machariaalex/pos.git`, `main` branch,
  clean single initial commit as of the redesign+features work above. SSH
  push access already confirmed working. `.gitignore` already excludes
  `.env`, the SQLite DB, `vendor/`, `node_modules/`, `public/build`, and
  `storage/app/backups` (added specifically since that directory can
  contain real DB backup dumps via `php artisan backup:database`).
