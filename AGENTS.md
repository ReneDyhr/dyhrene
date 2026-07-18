# AGENTS.md

A guide for AI coding assistants working on the **Dyhrene** Laravel application. For Claude-specific tooling conventions, see [CLAUDE.md](CLAUDE.md). For the complete developer rulebook, see [.cursor/rules/RULE.md](.cursor/rules/RULE.md).

---

## Project Overview

**Dyhrene** is a multi-feature personal application built on Laravel 13 with a Livewire 4 UI and an MCP (Model Context Protocol) API surface for AI clients. It manages recipes, receipts (with OCR), collaborative shopping lists, 3D printing jobs, Fastmail-powered email classification, bird species observations, and S3-compatible file storage.

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.5, Laravel 13 |
| Frontend | Livewire 4, Blade, Pure CSS/SCSS, Bootstrap 3, Vite |
| Realtime | Laravel Reverb, Laravel Echo, Pusher.js |
| Auth | Laravel Passport (OAuth 2.1), Laravel Sanctum |
| AI / MCP | laravel/mcp, openai-php/laravel |
| Storage | Wasabi (S3-compatible), Flysystem |
| Testing | Pest 4, PHPUnit |
| Quality | Pint (per preset), PHPStan level 9, multiple rule packs |

## How to Run the Project

### Local Development (Sail / Docker)

```bash
# Start the Laravel Sail environment
./vendor/bin/sail up

# In another terminal — run Vite dev server
npm run dev

# Production build
npm run build
```

### Environment Setup

Copy `.env.example` to `.env` and configure:
- `DB_*` — database credentials
- `AWS_*` — S3-compatible storage (default disk)
- `WAS_ACCESS_KEY_ID`, `WAS_SECRET_ACCESS_KEY`, `WAS_DEFAULT_REGION`, `WAS_BUCKET_FINAL`, `WAS_URL` — Wasabi disk (receipt storage)
- `EBIRD_USERNAME`, `EBIRD_PASSWORD` — for eBird import command
- `REVERB_*` — WebSocket server config
- `SENTRY_*` — error monitoring

Note: some of these variables (e.g. `WAS_*`, `EBIRD_*`, `REVERB_*`, `SENTRY_*`) are not present in `.env.example` and must be added to `.env` manually.

## Quality Gates (Run Before Every Commit)

These three gates mirror CI and **must all pass**:

```bash
# 1. Style check (auto-fix: ./vendor/bin/pint)
composer lint

# 2. Static analysis (PHPStan level 9)
composer larastan

# 3. Test suite
composer test
```

## Key Architectural Patterns

### 1. Livewire-First UI

The application is **Livewire-first**. Almost all logic lives in Livewire components under `app/Livewire/`. Controllers are rare; use Livewire components as the primary UI/business glue. Views live in `resources/views/livewire/`.

### 2. Actions (Single-Purpose Operations)

Business logic in `app/Actions/` follows a verb-first, single-responsibility pattern (`CreateReceiptAction`, `UpdateReceiptAction`, `DeleteReceiptAction`). Each Action exposes a `handle()` method.

### 3. Domain Services (Pure, Framework-Agnostic)

Services in `app/Domain/` contain pure business logic. They accept primitives/DTOs, return primitives/arrays, and have **zero** framework dependencies (no Eloquent, no facades, no HTTP). Example: `PrintJobCalculator`.

### 4. MCP Servers & Tools

MCP servers expose tools to AI clients via `laravel/mcp`. Each server is a class extending `Laravel\Mcp\Server` with `#[Name]`, `#[Version]`, `#[Instructions]` attributes. Tools are individual classes in `app/Mcp/Tools/`. Auth uses Laravel Passport with `mcp:use` scope. See [docs/technical/mcp.md](docs/technical/mcp.md).

### 5. Models (Lean & Explicit)

Models are thin — only `$fillable`, `$casts`, relations, scopes, and factory bindings. Always use `Model::query()->...` never `DB::table()`. All relations and scopes must have Eloquent generics in PHPDoc.

### 6. Strict Typing

Every PHP file begins with `declare(strict_types=1);`. All parameters and returns are typed. Native PHP functions are called with a leading backslash: `\trim()`, `\json_encode()`, `\auth()`.

## Directory Structure & Responsibilities

```
app/
├── Actions/            Single-purpose business operations (handle())
├── Console/Commands/   Artisan commands (ebird:import, mail:classify)
├── Domain/             Pure, framework-agnostic services (Printing, ...)
├── Enums/              Backed string enums (LanguageEnum, MailDocumentTypeEnum)
├── Events/             Broadcast and domain events
├── Exceptions/         Custom exceptions (*Exception suffix required)
├── Http/
│   ├── Controllers/    Thin HTTP entry points (rarely used)
│   ├── Middleware/     Request/response middleware
│   └── Requests/       FormRequest classes (Create*, Update*, Delete*)
├── Livewire/           UI components — primary UI/business glue
├── Mcp/
│   ├── Servers/        MCP server classes (ReceiptServer, RecipeServer, ...)
│   ├── Tools/          MCP tool implementations by domain
│   ├── {Domain}/       Domain-specific MCP route files + support
│   └── McpServerRegistry.php
├── Models/             23 Eloquent models (lean: relations, scopes, casts)
├── Providers/          Service providers (AppServiceProvider, ...)
├── Services/           Framework-aware business logic (Fastmail, Mail, Ebird, Receipts)
└── Support/            Generic helpers (Format, ReceiptDuplicateGuard)

routes/
├── web.php             Browser routes → Livewire components
├── api.php             REST endpoints (rarely used)
├── ai.php              MCP servers via Mcp::web(...)
├── channels.php        Reverb broadcast auth
└── console.php         Artisan schedule definitions

tests/
├── Feature/            Feature tests (Http, Console, Mcp, Policies)
├── Unit/               Unit tests (Actions, Models, Jobs, Domain, Support)
├── Concerns/           Shared traits (RunsMigrations)
├── Pest.php            Test setup & helpers
└── TestCase.php        Base test class
```

## Existing Documentation

- **[CLAUDE.md](CLAUDE.md)** — Claude Code specific conventions and detailed patterns
- **[.cursor/rules/RULE.md](.cursor/rules/RULE.md)** — Complete development rulebook (most detailed)
- **[docs/technical/](docs/technical/)** — Technical architecture, quality gates, MCP deep-dive
- **[docs/adr/](docs/adr/)** — Architecture Decision Records
- **[docs/business/](docs/business/)** — Business domain documentation
- **[docs/glossary.md](docs/glossary.md)** — Glossary of terms
- **[docs/workflows.md](docs/workflows.md)** — Common developer workflows
- **[docs/models.md](docs/models.md)** — Entity-relationship documentation
- **[phpstan.neon](phpstan.neon)** — PHPStan level 9 config
- **[pint.json](pint.json)** — Code style configuration
- **[.github/workflows/laravel.yml](.github/workflows/laravel.yml)** — CI pipeline

## Key Conventions

- `declare(strict_types=1);` on every PHP file
- Leading backslash on native functions: `\trim()`, `\view()`, `\auth()`
- PHPStan level 9 with larastan, shipmonk, symplify, ekino rules
- Required class suffixes (Exception, Controller, ServiceProvider, Factory, etc.)
- Eloquent generics in PHPDoc for all relations/scopes
- No Tailwind — Bootstrap 3 + custom SCSS with `#53875F` green
- Feature branches off `main`, never commit directly to main
- Never use `DB::` facade — always `Model::query()`
