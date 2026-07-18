# Business Domain Documentation

Documentation for the business domains that Dyhrene manages — what each feature does, user roles, and integration points.

## Contents

| Document | Description |
|----------|-------------|
| [features.md](features.md) | Complete feature catalog across all 8 domains |
| [ebird-integration.md](ebird-integration.md) | eBird import pipeline, Danish species conventions, and scheduled automation |

## Domain Overview

Dyhrene spans eight distinct business domains, each with its own models, UI components, and (where applicable) MCP tools:

| Domain | Models | UI (Livewire) | MCP Server | Integration |
|--------|--------|---------------|------------|-------------|
| **Recipes** | 6 models | Recipes, AddRecipe, EditRecipe, SingleRecipe, SearchRecipe | Recipe Server | — |
| **Receipts** | 3 models | Index, Create, Edit, Show, MassEditItems | Receipt Server | Wasabi S3, N8n OCR |
| **Shopping Lists** | 1 model | ShoppingList | Shopping List Server | Reverb WebSockets |
| **3D Printing** | 7 models | Dashboard, Customers, Materials, Jobs, Settings | — | — |
| **Mail** | 1 model | Inbox | — | Fastmail JMAP, OpenAI |
| **Bird Species** | 2 models | SpeciesIndex, SpeciesShow, AddObservation | — | eBird, Merlin |
| **Storage** | 2 models | Storage | — | — |
| **Settings** | — | Categories, McpConnection | — | Passport OAuth |

## User Roles

Dyhrene is a **single-user application**. There is no role-based authorization model — the single authenticated user owns all data.

### Authorization Model

- **Web UI:** Session-based authentication (Sanctum) — all routes require `auth` middleware
- **MCP API:** Token-based authentication (Passport) — tokens require `mcp:use` scope
- **Data isolation:** All queries use `scopeForAuthUser()` which scopes to `Auth::id()`
- **Policies:** For resource-level authorization where needed (verify ownership)

## Integration Points

| Integration | Protocol | Purpose |
|-------------|----------|---------|
| **Fastmail** | JMAP (HTTP) | Email retrieval, mailbox management, identity lookup |
| **Wasabi** | S3 (HTTP) | Receipt image and PDF storage |
| **OpenAI** | REST (HTTP) | LLM-powered features |
| **eBird / Merlin** | HTTP scraping | Species list, observation import, checklist enrichment |
| **N8n** | REST (HTTP) | Receipt OCR extraction pipeline |
| **Reverb** | WebSocket | Real-time shopping list updates |
| **Sentry** | HTTP | Error monitoring and tracing |
