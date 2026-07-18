# ADR-0005: Pure CSS/SCSS, No Utility Frameworks

**Status:** Accepted
**Date:** 2024
**Deciders:** Project maintainer

## Context

The application needed a consistent visual design. Modern frontend tooling offers two main approaches:

1. **Utility-first CSS (Tailwind)** — compose designs from atomic utility classes in HTML
2. **Component/semantic CSS (Bootstrap, custom SCSS)** — named classes with meaning, extracted to stylesheets
3. **CSS-in-JS (styled-components, Emotion)** — JavaScript-driven styles (requires JS framework)
4. **Design system library (Material UI, Chakra)** — pre-built components (requires JS framework)

Key constraints and preferences:
- **Single developer** — not a designer, needs simplicity and consistency
- **Livewire stack** — Blade templates, no JavaScript framework
- **Custom visual identity** — `#53875F` green as primary color, not a generic theme
- **Long-term maintainability** — style decisions should age well, not require constant refactoring
- **Preference for explicit, named classes** — `recipe-card` is clearer than `bg-white rounded-lg shadow-md p-4`

## Decision

**Use Bootstrap 3 as a base CSS framework with custom SCSS. No utility-first frameworks (Tailwind). No CSS-in-JS.**

The stylesheet is compiled from `resources/scss/app.scss` via Vite. Custom variables and partials override Bootstrap defaults and add application-specific styling.

### Design Principles

1. **Semantic class names** — `.recipe-card`, `.receipt-item`, `.print-job-summary`
2. **Variables for consistency** — colors, spacing, typography defined once in SCSS variables
3. **Component-scoped styles** — each Livewire component or Blade partial has its own SCSS partial
4. **Bootstrap 3 as foundation** — grid system, forms, typography, utilities
5. **`#53875F` green** as the primary brand color throughout

## Consequences

### Positive

- **Readable HTML:** Class names describe what elements are, not how they're styled
- **Separation of concerns:** Styles live in `.scss` files, HTML in `.blade.php` files
- **Bootstrap ecosystem:** Well-documented grid, components, and helpers
- **No build-time CSS generation:** SCSS compiles once — no dynamic class scanning
- **Visual consistency:** Variables enforce consistent spacing, colors, and typography
- **CSS debugging:** Browser DevTools show named classes, not utility soup
- **Designer-friendly:** A CSS developer can work on `.scss` files without touching HTML

### Negative

- **Larger CSS files:** Bootstrap 3 is heavier than utility-class approaches
- **No design system guarantees:** Without utility constraints, it's possible to drift from the design system
- **Manual media queries:** Responsive design requires explicit breakpoints in SCSS
- **Naming fatigue:** Every element needs a meaningful class name
- **Less trendy:** Utility-first CSS (Tailwind) is the dominant approach in 2024-2026 Laravel ecosystem

### Mitigations

- SCSS variables define the design system — drift is caught in code review
- Bootstrap 3 grid handles most responsive needs without custom media queries
- Blade component partials keep HTML clean and reusable
- The application's visual language is intentionally simple and consistent

## Related

- [ADR-0001: Livewire-First Architecture](0001-livewire-first.md) — CSS approach is compatible with Blade/Livewire
- The `#53875F` primary color is used consistently across the application
