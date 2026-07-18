# Architecture Decision Records (ADRs)

This directory documents significant architectural decisions for the Dyhrene application.

## What is an ADR?

An Architecture Decision Record (ADR) captures a single architectural decision — the context, the options considered, the chosen approach, and the consequences. It serves as a historical reference for current and future developers.

## ADR Template

```markdown
# ADR-NNNN: Title

**Status:** Proposed | Accepted | Deprecated | Superseded by ADR-NNNN
**Date:** YYYY-MM-DD
**Deciders:** [names]

## Context

What is the problem we are solving? What forces are at play?

## Decision

What did we decide to do?

## Consequences

What are the positive outcomes (pros) and negative tradeoffs (cons)?
```

## Active ADRs

| ADR | Title | Status | Date |
|-----|-------|--------|------|
| [ADR-0001](0001-livewire-first.md) | Livewire-First Architecture | Accepted | 2024 |
| [ADR-0002](0002-mcp-api-surface.md) | MCP API Surface | Accepted | 2024 |
| [ADR-0003](0003-domain-services-pattern.md) | Pure Domain Services Pattern | Accepted | 2024 |
| [ADR-0004](0004-strict-typing-and-static-analysis.md) | Strict Typing and PHPStan Level 9 | Accepted | 2024 |
| [ADR-0005](0005-pure-css-no-tailwind.md) | Pure CSS/SCSS, No Utility Frameworks | Accepted | 2024 |

## How to Propose an ADR

1. Create a new file in this directory: `docs/adr/NNNN-descriptive-slug.md`
2. Use the next sequential number (check the list above)
3. Follow the template format
4. Set status to **Proposed**
5. Open a pull request for discussion
6. Once consensus is reached, change status to **Accepted** and merge

## How to Deprecate an ADR

1. Change the status to **Deprecated**
2. Add a note explaining why it was deprecated
3. If replaced by another ADR, add "Superseded by ADR-NNNN" to the status
4. Update the table above

## Status Meanings

| Status | Meaning |
|--------|---------|
| **Proposed** | Under discussion, not yet agreed upon |
| **Accepted** | Approved and in effect |
| **Deprecated** | No longer in effect (but preserved for historical record) |
| **Superseded** | Replaced by a newer ADR |
