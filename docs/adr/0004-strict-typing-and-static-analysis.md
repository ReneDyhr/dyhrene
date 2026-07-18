# ADR-0004: Strict Typing and PHPStan Level 9

**Status:** Accepted
**Date:** 2024
**Deciders:** Project maintainer

## Context

PHP is a dynamically typed language, but modern PHP (8.0+) supports robust type declarations. Static analysis tools like PHPStan can catch type errors, logic bugs, and architectural violations before they reach production.

The question was: **how strict should static analysis be?** Options:

1. **No static analysis** — rely on tests and runtime errors only
2. **PHPStan level 6 (Laravel default)** — catches common issues but permits many dynamic patterns
3. **PHPStan level 9 with strict rules** — maximum strictness, catches everything PHP can express
4. **Level 9 with custom rules** — add class suffix enforcement, ban dangerous casts, require Eloquent generics

Key considerations:
- The project is maintained by a **single developer** — automated guardrails prevent solo mistakes
- **Multiple domains** with complex data models — type safety prevents wiring errors
- **MCP tools** accept arbitrary JSON input from AI clients — type checking at the boundary is critical
- **Long-lived application** — technical debt accumulates over years; automated enforcement prevents drift

## Decision

**Enforce PHPStan level 9 with five rule packs, strict types on every file, and required class suffix naming.**

### Five Rule Packs

| Rule Pack | Purpose |
|-----------|---------|
| `larastan/larastan` | Laravel-aware rules (Eloquent types, facades, collections, container) |
| `phpstan/phpstan-strict-rules` | Boolean conditions, useless casts, parent constructor calls, strict comparisons |
| `shipmonk/phpstan-rules` | Class suffix naming, no null in binary ops, no mixed method calls, no variable overwriting, bans dangerous casts: `(array)`, `(object)`, `(unset)` (`ForbidCastRule`) |
| `symplify/phpstan-rules` | Explicit class prefixes/suffixes, no return setters, attribute validation, unique enums |
| `ekino/phpstan-banned-code` | Bans dangerous functions |

### Key Enforcements

1. **`declare(strict_types=1);`** on every PHP file (enforced by Pint)
2. **All parameters and returns typed** with native types; PHPDoc only for generics/array shapes
3. **Eloquent generics** on all relations and scopes: `@return HasMany<RecipeIngredient, $this>`
4. **Class suffix naming:** Exception → `*Exception`, Controller → `*Controller`, etc.
5. **No mixed in method calls or property access** — narrow first
6. **No null in binary operations** except `??` and `??=`
7. **Boolean conditions must be bool** — no truthy/falsy checks

### Class Suffix Naming Requirements

| Parent | Required Suffix |
|--------|-----------------|
| `\Exception` | `*Exception` |
| `\Illuminate\Routing\Controller` | `*Controller` |
| `\Illuminate\Support\ServiceProvider` | `*ServiceProvider` |
| `\Illuminate\Database\Eloquent\Factories\Factory` | `*Factory` |
| `\Illuminate\Mail\Mailable` | `*Mailable` |
| `\Illuminate\Notifications\Notification` | `*Notification` |
| `\Illuminate\Http\Resources\Json\JsonResource` | `*Resource` |
| `\Illuminate\Contracts\Validation\ValidationRule` | `*Rule` |
| `\Illuminate\Database\Eloquent\Scope` | `*Scope` |
| `\Illuminate\Database\Eloquent\Builder` | `*Builder` |

## Consequences

### Positive

- **Runtime safety:** Fewer type errors, null pointer errors, and casting bugs in production
- **Self-documenting code:** Types tell you what a function accepts and returns without reading the implementation
- **AI-assisted development:** AI coding agents benefit from strict types — they make fewer mistakes
- **Refactoring confidence:** Change a type signature and PHPStan tells you everywhere that's affected
- **Class naming consistency:** Suffix rules ensure you always know what kind of class any file contains

### Negative

- **Higher barrier to entry:** New developers unfamiliar with strict typing will be frustrated
- **More verbose code:** PHPDoc generics add lines to every model
- **PHPStan suppression:** Occasionally legitimate patterns need `@phpstan-ignore-next-line` with justification
- **Slower initial development:** Typing everything upfront takes more time than dynamic PHP

### Mitigations

- `pint.json` auto-fixes most style violations — developers don't think about formatting
- `composer larastan` runs quickly locally — feedback loop is under 10 seconds
- Template snippets and existing code provide copy-paste patterns for new classes
- PHPDoc generics follow a consistent pattern for all models

## Related

- [quality.md](../technical/quality.md) — full quality assurance documentation
- [ADR-0003: Pure Domain Services](0003-domain-services-pattern.md) — framework-agnostic domain services are enforced by convention and code review
