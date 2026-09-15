# Dyhrene Design Acceptance Criteria

> Contract for visual/behavioural acceptance of the whole-system design
> migration. Applies to every screen rendered inside `<x-layouts.app-shell>`.

## Design tokens

Defined in `resources/scss/_settings.design-tokens.scss` (dark scheme):

| Token | Purpose |
|---|---|
| `--app-paper` / `--app-card` / `--app-ink` / `--app-ink-soft` / `--app-line` | Base surfaces and text |
| `--app-area-overview` … `--app-area-family` | One accent per area |
| `--app-focus` | Focus ring |
| `--app-font-display` (Fraunces) / `--app-font-body` (IBM Plex Sans) | Typography |

## Responsive breakpoints

Verified at **375 px, 768 px, 1024 px, 1440 px**:

- Desktop (≥768 px): left rail navigation, header with search + user context.
- Mobile (<768 px): rail hidden, fixed bottom navigation with six area items,
  full-width search in the header.
- No horizontal overflow at any breakpoint.

## Accessibility

- **Landmarks**: exactly one `<nav aria-label="Primær navigation">` (desktop) and
  one `<nav aria-label="Mobil navigation">` (mobile); one `<main>`.
- **Skip link**: `Spring til indhold` targets `#main-content` and is the first
  focusable element.
- **Focus**: visible `:focus-visible` ring on all links, buttons, and inputs.
- **Current area**: the active nav item carries `aria-current="page"` and
  `data-area="<area>"`; it stays correct across Livewire updates.
- **Contrast**: text and focus rings meet WCAG AA on the dark surfaces.
- **Reduced motion**: respect `prefers-reduced-motion` (no non-essential
  animation).
- **Search**: the control is a labelled form (`aria-label="Opskriftssøgning"`)
  with submit-on-Enter behaviour.

## Navigation & deep links

- Every area is a real URL (no JavaScript-only tab toggling): browser history,
  refresh, and direct links must work.
- CRUD detail pages (`/recipe/{id}`, `/receipts/{receipt}`,
  `/print-jobs/{printJob}`, etc.) remain canonical; their active area is derived
  by `AppNavigation`.
- The shell renders safely without an authenticated user (null-safe user chip).

## Content correctness

- Overview and landing cards render only values from the read-model snapshots;
  no Blade view issues `Model::query()` or computes totals.
- Empty datasets render an explicit empty state, never a fabricated value.

## Verification

```bash
composer lint      # Pint
composer larastan  # PHPStan level 9
composer test      # Pest
npm run build      # Vite/SCSS
```

Browser acceptance is performed per room at the four breakpoints before merging
a room's PR.
