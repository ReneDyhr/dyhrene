# Glossary

Definitions for terms, acronyms, and concepts used in the Dyhrene project.

---

## Frameworks & Tools

| Term | Definition |
|------|------------|
| **Laravel** | PHP web framework providing routing, ORM (Eloquent), queues, events, and more. Dyhrene runs Laravel 13. |
| **Livewire** | Full-stack framework for Laravel that enables dynamic, reactive UIs without writing JavaScript. Dyhrene uses Livewire 4. |
| **Blade** | Laravel's templating engine. Templates live in `resources/views/` and use the `.blade.php` extension. |
| **Sail** | Laravel's Docker-based local development environment. Run via `./vendor/bin/sail up`. |
| **Sanctum** | Lightweight authentication system for SPAs and simple API tokens. Used alongside Passport. |
| **Passport** | Full OAuth 2.1 server implementation for Laravel. Used for MCP API authentication in this project with the `mcp:use` scope. |
| **Reverb** | Laravel's first-party WebSocket server. Provides real-time broadcasting for shopping list collaboration. |
| **Echo** | Laravel's JavaScript library for listening to WebSocket events on the client side. |
| **Pint** | Laravel's opinionated PHP code style fixer. Configured in `pint.json` with the `per` preset. |
| **Pest** | Testing framework for PHP with a more expressive syntax than PHPUnit. Dyhrene uses Pest 4. |
| **PHPStan** | PHP static analysis tool. Run at level 9 with multiple rule packs. |
| **Vite** | Next-generation frontend build tool. Compiles SCSS and JS assets. |
| **Sentry** | Error monitoring and observability platform. Automatically captures exceptions. |
| **Tinker** | Laravel's interactive REPL (Read-Eval-Print Loop). Access via `php artisan tinker`. |
| **CI** | Continuous Integration. The project's CI pipeline is defined in `.github/workflows/laravel.yml`. |

## MCP & AI

| Term | Definition |
|------|------------|
| **MCP** | Model Context Protocol — an open standard for AI clients (LLMs) to interact with external tools and data sources. Dyhrene exposes its data through MCP servers. |
| **MCP Server** | A class extending `Laravel\Mcp\Server` that registers a set of tools. Dyhrene has ReceiptServer, RecipeServer, and ShoppingListServer. |
| **MCP Tool** | An individual action exposed by an MCP server. Each tool has typed parameters and returns structured data. |
| **mcp:use** | The Passport OAuth scope required for MCP access tokens. Clients must request this scope when obtaining tokens. |
| **McpServerRegistry** | Class at `app/Mcp/McpServerRegistry.php` that declaratively lists all MCP servers and their connection URLs. |
| **JSON-RPC** | The protocol used by MCP for client-server communication over HTTP POST. |
| **OpenAI** | AI provider accessible via `openai-php/laravel`. Used for LLM-powered features. |

## Authentication

| Term | Definition |
|------|------------|
| **OAuth 2.1** | The authorization framework used by Passport for MCP access. |
| **Sanctum** | Session-based auth for the web UI. |
| **Passport** | Token-based (OAuth) auth for API/MCP access. |
| **Scope** | A permission string attached to OAuth tokens. The `mcp:use` scope gates MCP server access. |
| **FormRequest** | Laravel class used for HTTP request validation and authorization. Named `Create*Request`, `Update*Request`, etc. |
| **Policy** | Laravel authorization class for resource-level permission checks. |

## Architecture & Patterns

| Term | Definition |
|------|------------|
| **Action** | A single-purpose class in `app/Actions/` with a `handle()` method. Encapsulates one business operation (e.g., `CreateReceiptAction`). |
| **Domain Service** | A pure, framework-agnostic class in `app/Domain/` that contains business logic with no Eloquent, HTTP, or facade dependencies. |
| **Livewire Component** | A PHP class in `app/Livewire/` paired with a Blade template that provides reactive UI without JavaScript. |
| **Thin Controller** | A controller with minimal logic — validates input, calls an Action, returns a response. Rarely used in Dyhrene's Livewire-first architecture. |
| **Eloquent** | Laravel's ActiveRecord-style ORM. Models map to database tables. Always use `Model::query()` never `DB::`. |
| **Factory** | A class that generates fake model instances for testing. Every model needs a Factory. |
| **Migration** | A PHP file (in `database/migrations/`) that describes database schema changes over time. |
| **Seeder** | A class that populates the database with initial or test data. Located in `database/seeders/`. |

## Code Quality

| Term | Definition |
|------|------------|
| **Pint** | Laravel's PHP code style fixer configured with the `per` preset. Run via `composer lint`. |
| **PHPStan Level 9** | The strictest static analysis level. Dyhrene enforces this for all code in `app/`, `bootstrap/`, `database/`, and `routes/`. |
| **Eloquent Generics** | PHPDoc annotations like `@return HasMany<ReceiptItem, $this>` that tell PHPStan about the types involved in Eloquent relations and scopes. |
| **Class Suffix Naming** | Rule enforced by shipmonk/phpstan-rules: certain parent classes require a specific suffix (e.g., `*Exception`, `*Controller`, `*Factory`). |
| **strict_types** | `declare(strict_types=1);` — required on every PHP file. Enables strict type checking. |
| **Leading Backslash** | Convention to prefix native PHP functions with `\` (e.g., `\trim()`, `\auth()`). Enforced by Pint. |

## Storage & Files

| Term | Definition |
|------|------------|
| **Wasabi** | S3-compatible cloud storage provider used for storing receipt images and PDFs. |
| **Flysystem** | Laravel's filesystem abstraction layer. `league/flysystem-aws-s3-v3` provides the S3 adapter. |
| **S3** | Amazon Simple Storage Service API. Wasabi is compatible with it. |
| **PDF-to-Image** | Conversion of PDF receipts to images using `spatie/pdf-to-image`. |

## Business Domains

### Recipes

| Term | Definition |
|------|------------|
| **Recipe** | A cooking recipe with name, description, note, ingredients, tags, and category assignments. |
| **RecipeIngredient** | A single ingredient line within a recipe. Lines prefixed with `#` are section headers. |
| **RecipeTag** | A free-text label attached to a recipe for classification/filtering. |
| **Category** | A named group for organizing recipes, with an optional icon. |
| **CategoryRecipe** | Pivot linking categories to recipes (along with user ownership). |
| **Favourite** | A boolean flag on recipes for quick access to marked recipes. |

### Receipts

| Term | Definition |
|------|------------|
| **Receipt** | A purchase receipt with vendor, date, currency, total, and line items. Has an attached documentation image/PDF. |
| **ReceiptItem** | A single line on a receipt with name, quantity, and amount. Total is computed as `quantity × amount`. |
| **ReceiptCategory** | A user-defined category for receipt line items with a color. Enables spending categorization. |
| **OCR** | Optical Character Recognition — extracting text from receipt images. |
| **N8n** | Workflow automation platform used as part of the receipt extraction pipeline (`N8nReceiptExtractor`). |

### Shopping Lists

| Term | Definition |
|------|------------|
| **Shopping List** | A collaborative, real-time list with items that can be added, checked, unchecked, removed, and reordered. |
| **Section Header** | A shopping list item prefixed with `#` — rendered as a bold heading and cannot be checked. |
| **Realtime / WebSocket** | Reverb and Echo enable instant updates across clients when the shopping list changes. |

### 3D Printing

| Term | Definition |
|------|------------|
| **PrintCustomer** | A client who orders 3D printed items. |
| **PrintMaterial** | A specific material variant (e.g., "PLA Silk Gold") with price per kg and waste factor. |
| **PrintMaterialType** | A category of material (e.g., "PLA") with an average kWh consumption rate. |
| **PrintJob** | A print order with plates, pieces, material, labor, and pricing calculations. |
| **PrintSetting** | Global pricing settings: electricity rate, wage rate, default markup, first-time fee. |
| **PrintOrderSequence** | Auto-incrementing order number per year (YYYY-NNN format). |
| **PrintActivityLog** | Audit log of actions performed on print jobs. |
| **Avance** | Danish term for profit markup percentage applied to calculated cost. |
| **calc_snapshot** | JSON snapshot of all costs, pricing, and profit frozen when a print job is locked. |

### Mail

| Term | Definition |
|------|------------|
| **JMAP** | JSON Meta Application Protocol — Fastmail's API protocol. Used via `FastmailJmapClient`. |
| **Fastmail** | Email service provider with JMAP API support. |
| **MailClassification** | The process of determining whether an email is a receipt, a payslip, or unknown. |
| **MailDocumentTypeEnum** | Enum with values: `receipt`, `payslip`, `unknown`. |
| **MailClassificationSourceEnum** | Enum tracking which classifier made a determination: `metadata`, `mobilepay`, `attachment_text`, `n8n`, `manual`. |
| **Classifier Pipeline** | Multi-stage classification: metadata → MobilePay → attachment text → N8n, each stage only invoked if previous is inconclusive. |

### Bird Species

| Term | Definition |
|------|------------|
| **Species** | A bird species record with common name (Danish convention), scientific name, eBird code, and taxonomic order. |
| **Observation** | A sighting record linked to a species with date, location, count, and checklist metadata. |
| **eBird** | Cornell Lab of Ornithology's citizen science platform. Data is imported via the `ebird:import` command. |
| **Merlin** | Bird identification app by Cornell. Checklist data is fetched for observation enrichment. |
| **Common Name** | In Dyhrene, species common names follow the **Danish** convention (e.g., "Gråand" not "Mallard"). |

### Storage

| Term | Definition |
|------|------------|
| **Storage** | A named storage location (e.g., "Garage", "Attic") containing items. |
| **StorageItem** | An item stored at a location with name, quantity, and sort order. |

### Settings

| Term | Definition |
|------|------------|
| **Category Settings** | UI for managing recipe categories. |
| **MCP Settings** | UI showing MCP connection URLs, OAuth endpoints, and server registration details. |
| **Icon** | A named icon (CSS class) assignable to categories. Stored in the `icons` table. |

## Conventions

| Term | Definition |
|------|------------|
| **Soft Deletes** | Models use Laravel's `SoftDeletes` trait — records are marked as deleted instead of being removed from the database. |
| **Timestamps** | Standard `created_at` / `updated_at` columns on most tables. |
| **Polymorphic Relations** | Currently **not used** in Dyhrene. All relationships are explicit foreign keys. |
| **Fillable** | The `$fillable` array on models that lists mass-assignable attributes. |
| **Casts** | The `$casts` array on models defining attribute type transformations (e.g., `'public' => 'boolean'`). |
| **Scopes** | Query scopes on models (e.g., `scopeForAuthUser()`, `scopeFavourites()`) for reusable query constraints. |
| **Accessor** | A computed attribute on a model (e.g., `getTotalAttribute()` on Receipt that sums line items). |
