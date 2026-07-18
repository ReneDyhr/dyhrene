# ADR-0001: Livewire-First Architecture

**Status:** Accepted
**Date:** 2024
**Deciders:** Project maintainer

## Context

When building Dyhrene, a decision was needed on the frontend architecture. The options considered were:

1. **Traditional Laravel controllers with Blade views** — full page reloads, simple but dated UX
2. **Inertia.js + Vue/React** — SPA-like experience but requires a JavaScript framework, separate API endpoints, and two codebases (PHP + JS)
3. **Livewire** — reactive components in PHP, no JavaScript framework needed, tight Laravel integration
4. **Pure API backend with separate SPA frontend** — maximum flexibility but doubles development effort and requires maintaining an API surface

The application needed to manage multiple complex domains (recipes, receipts, shopping lists, 3D printing, mail, species) with real-time features (shopping list collaboration via WebSockets).

Key constraints:
- **Single developer** maintaining the entire codebase
- **PHP-first** skillset with minimal JavaScript overhead
- **Real-time features** needed for shopping list collaboration
- **Complex forms** with dynamic fields (recipe ingredients, receipt line items, print job calculations)

## Decision

**Use Livewire 4 as the primary UI framework for all browser-facing features.**

Livewire components are the first-class UI pattern. Controllers are minimized — used only when Livewire doesn't fit (e.g., image serving, redirect closures).

All browser routes in `routes/web.php` mount Livewire components directly:

```php
Route::get('/', Recipes::class)->middleware('auth')->name('index');
Route::get('/recipe/{id}/edit', EditRecipe::class)->middleware('auth')->name('edit');
```

Livewire components live in `app/Livewire/` with their Blade templates in `resources/views/livewire/`.

## Consequences

### Positive

- **Single codebase language:** All UI logic is PHP — no context-switching between PHP and JavaScript
- **Tight Laravel integration:** Access to Eloquent, validation, auth, and events directly from components
- **Real-time support:** Livewire works naturally with Reverb/Echo for WebSocket updates
- **Complex forms simplified:** Dynamic ingredient lists, line items, and pricing calculators are easier to build
- **No API surface needed for UI:** Livewire handles state and AJAX without REST endpoints
- **Lower cognitive load:** One less framework to learn and maintain for the solo developer

### Negative

- **Server load:** Every interaction hits the server (mitigated by Livewire's debouncing and lazy loading)
- **Not a SPA:** Page navigation causes full reloads (acceptable for this application's UX)
- **Less ecosystem:** Fewer third-party components compared to Vue/React ecosystems
- **Harder to hire for:** Livewire is less common than Vue or React in the market
- **JavaScript libraries still needed:** Echo, Pusher, and other JS dependencies are still required for real-time features

### Mitigations

- Use Livewire's `wire:model.debounce` for text inputs to reduce server round-trips
- Use `wire:lazy` for below-the-fold components
- Keep Livewire components lean — delegate persistence to Actions
- Scrape JavaScript to an absolute minimum — only Echo/Pusher setup in `resources/js/app.js`

## Related

- [ADR-0002: MCP API Surface](0002-mcp-api-surface.md) — the companion decision for AI client access
- [ADR-0003: Pure Domain Services](0003-domain-services-pattern.md) — business logic lives outside Livewire
