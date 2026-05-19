# AI-Assisted Development Log

## B2B Catalog Resolution Service

**Date:** Tuesday, 19 May 2026  
**Candidate tool:** Cursor AI (Claude Sonnet 4.6)  
**Project:** `c:\dev\catalog`

---

## 12:13 PM — User

> @Candidate_Brief.md The use of AI is encouraged so I'm gonna rely on you for the planning. let's do it in logical phases. let's use laravel and vue3.

---

## 12:13 PM — AI

I read the brief carefully. Before we touch any code, let me switch to Plan mode so we can lock the approach down — these rules interact in subtle ways and I want to make sure we agree on phasing before I start writing tests/code.

**What I take the brief to be saying (sanity checks I worked through)**

I walked all 3 acceptance tests against the rules before planning so the design is anchored to behavior, not vibes:

- **Test 1** (`SILVERMED`, `search=saline`): visible = `SAL-100`, `SAL-250`, `SAL-050`; hidden = `SAL-IRR` (silver in `restrictedForTiers`). Sort: "Saline…" *starts-with* group before "Sterile Saline…" *contains* group, then price asc → `SAL-250 (2.50)`, `SAL-050 (5.40)`, `SAL-100 (4.10)`. Matches.
- **Test 2** (`BRONZECO`, `category=Devices`): `SCL-10` is contract-granted (override wins over its `restrictedForTiers` of silver/bronze), `CAN-18` and `SUT-30` fail the `silver` minimumTier for a bronze customer → total 1, hidden 2. Matches.
- **Test 3** (`GOLDHOSP`): all 16 visible (gold clears every floor and isn't on any denylist). `GLV-NIT` contract price 10.00 beats basePrice 12.50. `SCL-10` (0.95) is the cheapest in-stock → page-1 first row. 10 + 6 split across 2 pages. Matches.

So the rule precedences I'll codify:

- **Visibility**: `contractGrants` short-circuits TRUE; otherwise `tier ∉ restrictedForTiers` AND `tier ≥ minimumTier` (null floor = pass).
- **Price**: `contractPrices[sku]` > `tierPrices[tier]` > `basePrice`.
- **Hidden count**: among products that passed search+category but failed visibility (NOT the whole catalog).
- **Sort**: stable multi-key — `inStock desc`, `nameStartsWithSearch desc` (only if search present), `resolvedPrice asc`, `sku asc`.
- **Paginate last**, page size 10.

**Proposed architecture (Laravel 11 + Vue 3 via Vite)**

Single Laravel app, Vue 3 SPA-ish page served from a Blade entry, Vite + `@vitejs/plugin-vue`. No DB — data lives in `database/seed-data/{products,customers}.json` loaded by a repository.

```text
app/
  Domain/Catalog/
    Product.php, Customer.php, Tier.php
    VisibilityResolver.php, PriceResolver.php
    CatalogQuery.php, CatalogService.php, CatalogPage.php
  Repositories/JsonCatalogRepository.php
  Http/Controllers/CatalogController.php
  Http/Requests/CatalogRequest.php
resources/js/
  app.ts, App.vue, api.ts
resources/views/app.blade.php
routes/web.php, api.php
tests/Unit/Catalog/*, tests/Feature/CatalogEndpointTest.php
database/seed-data/products.json, customers.json
```

**Phased plan (TDD throughout — red, green, refactor each phase)**

- **Phase 0** — Scaffolding: Laravel 11, Vite + Vue plugin, seed JSON, `JsonCatalogRepository` smoke test
- **Phase 1** — `Tier` enum with `rank()` and `atLeast(?Tier)`
- **Phase 2** — `VisibilityResolver` (Rule A), 8 branch tests
- **Phase 3** — `PriceResolver` (Rule B), 5 precedence tests
- **Phase 4** — `CatalogService` filter + counts (Rules C, E)
- **Phase 5** — `CatalogService` sort (Rule D)
- **Phase 6** — `CatalogService` paginate + response shape (Rule F)
- **Phase 7** — `CatalogController` + `CatalogRequest`; the 3 acceptance tests as feature tests are the gate
- **Phase 8** — Vue 3 `App.vue` UI

---

## 12:19 PM — User

> let's pause. instead of laravel vue, what do you recommend we use? I'm still leaning towards laravel since that's where my expertise is.

---

## 12:19 PM — AI

Good instinct to pause. Quick honest read:

**My recommendation, given Laravel is your home turf: Laravel 11 + Blade + Alpine.js.** Reasons:

- Endpoint is still a clean `GET /catalog` returning JSON — exactly what the brief asks for.
- The "minimal web page" becomes one Blade file with ~40–60 lines of Alpine consuming that endpoint via `fetch`. No Node build pipeline, no Vite-for-JS, no SPA glue.
- All your domain code (the interesting part — visibility, pricing, sort, counts) lives in plain PHP classes with PHPUnit. **That's the part the interviewer will read.** Stack ceremony doesn't earn points; clean domain code does.
- Saves you ~10 min vs. the Vue setup. That 10 min goes into tests.

**Alternatives, ranked:**

1. **Laravel + Inertia + Vue 3** — middle ground; less fetch glue, but slightly muddies the "endpoint + page on top of it" framing.
2. **Laravel + Vue 3 via Vite** (original plan) — totally fine, just costs the scaffolding minutes.
3. **Laravel + Livewire** — fastest to write, but no first-class JSON endpoint. I'd avoid this.

**What I'd push back on:** picking Vue because "it looks more impressive." For a 45-min test, the interviewer is reading your domain layer and your tests.

**Suggested call:** Laravel + Blade + Alpine. Want me to lock that in, or do you want to stay with Vue 3?

---

## 12:20 PM — User

> you know what? let's go fully laravel and vue 3. Since those are what I know best. let's continue with the planning.

---

## 12:20 PM — AI

Good — locking in **Laravel 11 + Vue 3 via Vite**.

*(AI asked three clarifying questions via structured prompts — scope, SKU search case-sensitivity, and frontend integration style. Candidate selected: Core only / Phases 0–8, case-insensitive SKU search, plain SPA with fetch.)*

**Locked decisions:**


| Decision              | Choice                                                             |
| --------------------- | ------------------------------------------------------------------ |
| Scope                 | Phases 0–8 only. No stretch goals. No Vitest.                      |
| Search — SKU matching | Case-insensitive (matches the spirit of the rule)                  |
| Frontend pattern      | Plain SPA: Blade mount point, Vue calls `/api/catalog` via `fetch` |


Ready to start Phase 0. Project will live at `c:\dev\catalog` (subfolder, keeps `Candidate_Brief.md` separate).

---

## 12:26 PM — User

> go ahead and plan the implementation deeply.

---

## 12:26 PM — AI

*(AI produced a full deep-plan document saved to `.cursor/plans/b2b_catalog_resolution_service_293650b4.plan.md`, covering: stack & scope, behavioral rules table, Mermaid request-flow diagram, complete file layout, locked domain signatures in PHP, JSON response shape, 9 phases with specific test cases per phase, validation strategy, risks and non-goals.)*

Key domain signatures locked:

```php
enum Tier: string { Bronze, Silver, Gold }
    public function rank(): int;
    public function atLeast(?self $floor): bool;

final class VisibilityResolver {
    public function isVisible(Product $p, Customer $c): bool;
}
final class PriceResolver {
    public function resolve(Product $p, Customer $c): float;
}
final class CatalogService {
    public function search(CatalogQuery $q): CatalogPage;
}
```

---

## 12:29 PM — User

> B2B Catalog Resolution Service — Implementation Plan
>
> Implement the plan as specified, it is attached for your reference. Do NOT edit the plan file itself.
>
> To-do's from the plan have already been created. Do not create them again. Mark them as in_progress as you work, starting with the first one. Don't stop until you have completed all the to-dos.

---

## 12:29 PM — AI (Implementation begins)

**Phase 0 — Scaffolding**

Ran `composer create-project laravel/laravel catalog "^11.0"` and `npm install vue @vitejs/plugin-vue`. Composer took ~11 minutes (slow on Windows); a SQLite migration error appeared at the end but was irrelevant since no DB is used.

Updated `vite.config.js` to add `@vitejs/plugin-vue` and changed the entry point to `resources/js/app.ts`.

Created `database/seed-data/products.json` (16 products) and `customers.json` (3 customers) verbatim from the brief.

Created directory structure: `app/Domain/Catalog/`, `app/Repositories/`, `tests/Unit/Catalog/`.

**Domain layer written (Phases 1–6):**

- `Tier.php` — backed enum `Bronze/Silver/Gold` with `rank(): int` and `atLeast(?Tier): bool`
- `Product.php` / `Customer.php` — `final readonly` DTOs
- `VisibilityResolver.php` — three-branch logic: contractGrants short-circuit → restrictedForTiers check → minimumTier check
- `PriceResolver.php` — contract price → tier price → base price
- `CatalogService.php` — full pipeline: filter, split visible/hidden, price, sort (multi-key comparator), paginate
- `JsonCatalogRepository.php` — loads JSON files, deserializes into DTOs, cached per-request via `?array` properties

Unit tests also written for all phases (`TierTest`, `VisibilityResolverTest`, `PriceResolverTest`, `CatalogServiceFilterTest`, `CatalogServiceSortTest`, `CatalogServicePaginationTest`, `JsonCatalogRepositoryTest`).

---

## 12:45 PM — User

> skip writing the tests. just go straight to Implementation. simplify implementation for faster writing.

---

## 12:45 PM — AI

Understood — all domain classes are already written. Marking phases 1–6 done and pushing straight to controller, routes, and Vue UI.

**Phase 7 — HTTP layer**

- Discovered Laravel 11 no longer auto-loads `api.php` — manually wired it in `bootstrap/app.php` via `->withRouting(api: __DIR__.'/../routes/api.php')`
- Created `routes/api.php`: `Route::get('/catalog', [CatalogController::class, 'index'])`
- Updated `routes/web.php`: root serves `view('app')`
- `CatalogRequest.php`: validates `customer` (required), `search`, `category`, `page`
- `CatalogController.php`: builds `CatalogQuery`, calls `CatalogService::search()`, maps `CatalogPage` to JSON response
- `AppServiceProvider.php`: binds `JsonCatalogRepository` and `CatalogService` as singletons with explicit seed-file paths

**Phase 8 — Vue 3 UI**

- `resources/views/app.blade.php`: minimal shell with `<div id="app">` and `@vite(['resources/js/app.ts'])`
- `resources/js/app.ts`: mounts `App.vue`
- `resources/js/api.ts`: typed `fetchCatalog(params): Promise<CatalogResponse>` wrapper
- `resources/js/App.vue`: single-file Composition API component with:
  - Customer `<select>` (3 hardcoded options)
  - Search `<input>` debounced 250ms via `setTimeout`
  - Category `<select>` (hardcoded: Fluids, PPE, Devices, Wound, Equip)
  - Results `<table>` with SKU, Name, Category, Price (`$X.XX`), In Stock
  - Prev/Next pager disabled at edges; resets to page 1 on filter change
  - Status line: `Showing X–Y of {total} · {hidden} hidden for this account`

`npm run build` completed clean: 9 modules, 68.91 kB.

**Issue hit:** First API call returned 500.

**Diagnosis:** Laravel log showed `could not find driver (Connection: sqlite)` originating from the Cache service provider. The default `.env` had `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` — all pointing at SQLite, which has no PDO driver installed on this machine.

**Fix:** Updated `.env`:

```
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
APP_MAINTENANCE_STORE=file
```

Ran `php artisan config:clear`. Endpoint responded correctly on next call.

---

## 12:57 PM — AI (Acceptance tests verified live)

**Test 1** — `GET /api/catalog?customer=SILVERMED&search=saline`

```json
{
  "results": [
    { "sku": "SAL-250", "price": 2.5,  "inStock": true },
    { "sku": "SAL-050", "price": 5.4,  "inStock": true },
    { "sku": "SAL-100", "price": 4.1,  "inStock": true }
  ],
  "total": 3, "hidden": 1, "page": 1, "totalPages": 1
}
```

✓ Correct order (starts-with before contains), correct prices, correct counts.

**Test 2** — `GET /api/catalog?customer=BRONZECO&category=Devices`

```json
{
  "results": [
    { "sku": "SCL-10", "price": 0.95, "inStock": true }
  ],
  "total": 1, "hidden": 2, "page": 1, "totalPages": 1
}
```

✓ Contract grant overrides denylist for SCL-10; CAN-18 and SUT-30 fail silver minimum tier → counted as hidden.

**Test 3** — `GET /api/catalog?customer=GOLDHOSP` (page 1 + page 2)

```json
// Page 1
{ "total": 16, "hidden": 0, "page": 1, "totalPages": 2,
  "results[0]": { "sku": "SCL-10", "price": 0.95 } }

// Page 2 (6 items)
{ "results": [..., { "sku": "GLV-NIT", "price": 10 }, ...] }
```

✓ All 16 visible to gold, GLV-NIT contract price 10.00 wins over base 12.50, correct 10+6 pagination split.

**All 3 acceptance tests pass. Implementation complete.**

Server running at `http://localhost:8000`.

---

