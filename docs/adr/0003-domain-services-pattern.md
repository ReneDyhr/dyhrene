# ADR-0003: Pure Domain Services Pattern

**Status:** Accepted
**Date:** 2024
**Deciders:** Project maintainer

## Context

The application contains business logic that spans multiple surfaces:
- **Livewire UI** — browser-based interaction
- **MCP tools** — AI client interaction
- **CLI commands** — automated imports and processing
- **Queue jobs** — background processing

Without a clear separation, business logic would be duplicated across these surfaces or tightly coupled to the framework. For example, the print job pricing calculation could be:
- Embedded in a Livewire component (only works from the UI)
- Duplicated in an MCP tool (maintenance nightmare)
- Extracted to a model method (couples business logic to Eloquent)

Options considered:

1. **Business logic in Models** — traditional Laravel approach, but couples logic to Eloquent and the framework
2. **Business logic in Service classes** — better, but Service classes in Laravel often mix framework concerns (HTTP, queue, cache) with pure logic
3. **Domain services in app/Domain/** — pure PHP classes with zero framework dependencies, called from anywhere
4. **DDD-lite with aggregates and repositories** — overkill for this application's scale

## Decision

**Place framework-agnostic business logic in `app/Domain/` as pure PHP services.**

These services:
- Accept only **primitives, arrays, or simple DTOs** as input
- Return only **primitives or arrays**
- Contain **zero** Eloquent, HTTP, facade, or any other framework dependencies
- Are **trivially unit-testable** — no mocking of framework internals needed
- Can be called from **any surface** (Livewire, MCP, CLI, queue jobs) identically

The canonical example is `PrintJobCalculator`:

```php
// app/Domain/Printing/PrintJobCalculator.php
final class PrintJobCalculator
{
    public function calculate(array $input): array
    {
        // Pure math — no Eloquent, no HTTP, no facades
        $totals = $this->calculateTotals($input);
        $costs = $this->calculateCosts($input, $totals);
        $pricing = $this->calculatePricing($input, $costs, $totals);
        $profit = $this->calculateProfit($costs, $pricing, $totals);

        return ['totals' => $totals, 'costs' => $costs,
                'pricing' => $pricing, 'profit' => $profit];
    }
}
```

Framework-aware orchestration (Eloquent queries, file I/O, HTTP calls) belongs in:
- `app/Actions/` — single-purpose orchestrators calling Eloquent + Domain services
- `app/Services/` — multi-step integration services (Fastmail, mail classification)

## Consequences

### Positive

- **Testability:** Domain services accept arrays/numbers and return arrays — tests are simple input/output assertions with no mocking
- **Reusability:** `PrintJobCalculator::calculate()` works from Livewire, MCP tools, and CLI commands identically
- **Code clarity:** Pure functions are easier to reason about — no side effects, no hidden state
- **Surface-agnostic:** New features or access methods can reuse existing domain logic without changes
- **Enforced separation of concerns:** Framework dependencies in a domain service stand out immediately and are rejected in code review

### Negative

- **Data transformation overhead:** Callers must assemble input arrays from Eloquent models and map output arrays back
- **More files:** Each domain concept may need a separate file, increasing the file count
- **Discipline required:** Developers must resist the temptation to throw framework dependencies into Domain services
- **Not always necessary:** Simple CRUD operations don't benefit from this pattern — Actions suffice

### Mitigations

- Models can provide helper methods for assembling domain service input (e.g., `PrintJob::buildSnapshot()`)
- The pattern is enforced by **convention and code review**, not by static analysis — PHPStan has no rule that blanket-bans `Illuminate\*` imports in `app/Domain/`, so reviewers must reject framework dependencies in domain services
- Start with Actions; promote to domain services only when logic is complex or multi-surface

## Related

- [ADR-0001: Livewire-First Architecture](0001-livewire-first.md) — domain services are called from Livewire components
- [ADR-0004: Strict Typing](0004-strict-typing-and-static-analysis.md) — strict typing keeps domain service inputs/outputs well-defined
