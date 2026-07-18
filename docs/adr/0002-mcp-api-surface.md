# ADR-0002: MCP API Surface

**Status:** Accepted
**Date:** 2024
**Deciders:** Project maintainer

## Context

The application needed an API surface specifically for AI clients (LLMs like Claude, ChatGPT, etc.) to interact with user data. Traditional REST or GraphQL APIs were considered alongside the emerging Model Context Protocol (MCP).

Options considered:

1. **REST API** — standard, well-understood, but requires AI clients to discover endpoints and understand URL conventions
2. **GraphQL** — typed, self-documenting, but adds complexity and doesn't natively handle tool semantics
3. **MCP (Model Context Protocol)** — emerging standard for AI tool use, typed tools with descriptions, designed for LLM consumption
4. **No API** — keep all data behind the Livewire UI only

The key insight was that AI clients interact with data differently than traditional API consumers — they need:
- **Described tools** with parameter types and semantics, not just endpoints
- **Self-documenting schemas** embedded in the protocol
- **Human-readable instructions** for how to use each tool effectively
- **Authentication** that works in headless OAuth flows

## Decision

**Use laravel/mcp to expose an MCP API surface with Laravel Passport OAuth 2.1 authentication.**

Three MCP servers expose the core domains:

| Server | Endpoint | Purpose |
|--------|----------|---------|
| Receipt Server | `POST /mcp/receipts` | Receipt metadata, line items, images |
| Recipe Server | `POST /mcp/recipes` | Recipe CRUD, search, categories, tags |
| Shopping List Server | `POST /mcp/shopping-list` | Real-time collaborative list management |

Authentication: Laravel Passport OAuth 2.1 tokens with the `mcp:use` scope.

## Consequences

### Positive

- **AI-native interface:** Tools have names, descriptions, and typed parameters that LLMs understand natively
- **Self-documenting:** Tool schemas and `#[Instructions]` are embedded in the protocol — no separate API docs needed
- **Standard protocol:** JSON-RPC over HTTP POST — any MCP-compatible client can connect
- **Fine-grained auth:** OAuth scopes control exactly what AI clients can access
- **Domain separation:** Each server is independent — can be developed, tested, and versioned separately
- **Reusable:** The same MCP tools work with Claude, ChatGPT, or any MCP-compatible desktop/web client
- **Real-time feedback:** Shopping list changes via MCP broadcast back to the Web UI instantly

### Negative

- **Additional auth complexity:** Maintaining both Sanctum (web sessions) and Passport (OAuth tokens) adds setup overhead
- **New protocol risk:** MCP is an emerging standard — breaking changes or deprecation are possible
- **Learning curve:** Developers must understand MCP conventions plus Laravel's MCP package
- **Not a REST API replacement:** MCP is designed for AI tool use, not general-purpose API consumption

### Mitigations

- `McpServerRegistry` centralizes server metadata — adding a server is declarative
- Shared middleware pattern: `['auth:api', CheckToken::using('mcp:use')]` on all MCP routes
- Tests validate both the tool behavior and the OAuth flow
- The REST `routes/api.php` remains available for traditional API endpoints if needed

## Related

- [ADR-0001: Livewire-First Architecture](0001-livewire-first.md) — MCP complements the Livewire UI
- [MCP Technical Documentation](../technical/mcp.md) — detailed implementation guide
