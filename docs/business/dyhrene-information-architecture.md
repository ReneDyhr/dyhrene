# Dyhrene Information Architecture

> Status: **confirmed contract** for the whole-system design migration.
> Supersedes the legacy flat navigation. All route→area decisions below are the
> single source of truth enforced by `App\Support\Navigation\AppNavigation`.

## Goal

Replace Dyhrene's flat navigation and inconsistent page chrome with a dark
"family hub" shell that groups capabilities into six areas, while preserving
every existing route, CRUD workflow, authentication boundary, and MCP/OAuth
behaviour. The mockup is treated as an information architecture + design system,
not as a static page to copy.

## Areas

| Area | Danish label | Entry route | Capabilities |
|---|---|---|---|
| **Oversigt** | Oversigt | `/` (`index`) | Cross-domain summary: recipe count/latest, active shopping items, current-month receipts, inventory count, observation count, wild-edible count |
| **Køkken** | Køkken | `/kitchen` (`kitchen.index`) | Recipes, recipe search, categories/tags, shopping list, Lager (existing `Storage`) |
| **Natur** | Natur | `/nature` (`nature.dashboard`) | Nature dashboard, species, observations, sites/station, Wild Edibles (permanent) |
| **Værksted** | Værksted | `/workshop` (`workshop.index`) | Print jobs, materials, material types, customers, settings (existing costing system) |
| **Husholdning** | Husholdning | `/household` (`household.index`) | Receipts, receipt categories, inventory |
| **Familien** | Familien | `/family` (`family.index`) | Mail, MCP connection (navigation/settings grouping; no role model) |

## Route → area mapping

`AppNavigation::areaForRoute()` maps a route name to an area. The contract is:

- **Køkken** — exact `add`, `category`, `edit`, `search`, `settings.categories`,
  `single`, `storage`, `tag`; namespaces `shopping.`, `kitchen.`, `recipes.`
- **Natur** — namespaces `nature.`, `observations.`, `species.`, `wild-edibles.`
- **Værksted** — namespaces `print-customers.`, `print-jobs.`,
  `print-material-types.`, `print-materials.`, `print-settings.`, `printing.`,
  `workshop.`
- **Husholdning** — namespaces `inventory.`, `receipts.`, `household.`
- **Familien** — exact `settings.mcp`; namespaces `mail.`, `family.`
- **Oversigt** — everything else (the `default`), including the root `index`.

Entry routes are declared once in `AppNavigation::entryRouteFor()`; no Blade
view duplicates the mapping. The round-trip invariant
`areaForRoute(entryRouteFor(area)) === area` is pinned by tests.

## Confirmed product decisions

1. `/` is **Oversigt**. The recipe index moved to `/kitchen/recipes`
   (`recipes.index`); the `index` route name is retained so login/home redirects
   land on the overview.
2. **Lager** is the existing `Storage` module; no food-stock model.
3. **Værksted** is the existing print-costing system; no printer telemetry,
   print-run tracking, or filament quantities.
4. **Familien** is a navigation/settings grouping; no household membership,
   roles, or per-area permissions.
5. **Natur** includes Wild Edibles permanently.
6. **Search is contextual**: the shell search control posts to recipe search
   (`search`). The default/general search is recipes only; no cross-domain
   global search.
7. **Fonts** — Fraunces and IBM Plex Sans from Google Fonts, with CSS fallbacks
   and `preconnect` hints.

## Deferred capability (explicitly out of scope)

- True global/cross-domain search
- Printer telemetry, print-run tracking, filament quantities
- Household warranty/expiry tracking
- Family roles / per-area permissions
- Profile administration

None of these may be presented as live data until a source of truth, lifecycle,
and tests exist.

## Read models

Overview and domain landing pages consume immutable snapshots produced by
read-model readers (one per area), never Eloquent models or query builders in
the view:

- `OverviewSnapshot` ← `OverviewSnapshotReaderInterface`
- `KitchenSnapshot` ← `KitchenSnapshotReaderInterface`
- `HouseholdSnapshot` ← `HouseholdSnapshotReaderInterface`
- `WorkshopSnapshot` ← `WorkshopSnapshotReaderInterface`
- `NatureSnapshot` ← `NatureSnapshotReaderInterface`

Each reader is ownership-scoped to the authenticated user (except Workshop,
whose `PrintJob` model is not user-scoped).
