# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Dyhrene** is a multi-feature Laravel 13 application with a Livewire 4 UI and an MCP (Model Context Protocol) API surface for AI clients. It includes:

- **Recipes**: A recipe management system with ingredients, categories, and tags
- **Receipts**: Receipt scanning and OCR with line-item tracking and MCP integration
- **Shopping Lists**: Collaborative shopping list management with real-time updates via WebSockets
- **3D Printing Management**: A complete print shop workflow (customers, materials, jobs, pricing)
- **Mail Integration**: Fastmail integration for receipt and invoice classification
- **Storage**: S3-compatible (Wasabi) file storage for receipt images and PDFs

The application uses **Laravel Passport** (OAuth 2.1) for authentication and MCP access control, **Laravel Reverb** for real-time features, and **Sentry** for observability.

## Technology Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 8.5, Laravel 13 |
| **Frontend** | Livewire 4, Blade templates, Pure CSS/SCSS, Vite |
| **Realtime** | Laravel Reverb, Laravel Echo, Pusher.js |
| **Auth** | Laravel Passport (OAuth 2.1), Laravel Sanctum |
| **AI/MCP** | laravel/mcp (MCP servers), openai-php/laravel |
| **Storage** | Wasabi (S3-compatible), Flysystem |
| **Testing** | Pest 4, PHPUnit |
| **Quality** | Pint (style), Larastan (static analysis level 9), PHPStan strict rules |

## Essential Commands

### Development

```bash
# Start the local dev environment (Laravel Sail)
./vendor/bin/sail up

# Run Vite dev server (in another terminal)
npm run dev

# Run the app in production mode
npm run build
```

### Quality Gates (run before committing)

All three gates must pass—they mirror CI in `.github/workflows/laravel.yml`:

```bash
# 1. Style check (auto-fix with ./vendor/bin/pint)
composer lint

# 2. Static analysis (PHPStan level 9)
composer larastan

# 3. Test suite (Pest)
composer test
```

### Common Tasks

```bash
# Run a single test file
./vendor/bin/pest tests/Feature/Mcp/ReceiptMcpServerTest.php

# Run tests matching a pattern
./vendor/bin/pest --filter="creates a receipt"

# Generate a new model with factory
php artisan make:model ModelName -mf

# Create a migration
php artisan make:migration create_table_name

# Run migrations
php artisan migrate

# Create a new Livewire component
php artisan livewire:make ComponentName

# Tinker (REPL)
php artisan tinker
```

## Code Architecture & Structure

### Strict Types & Generics

Every PHP file must start with:
```php
<?php

declare(strict_types=1);
```

All functions and methods must have parameter and return types. Use PHPDoc only for generics and array shapes that native PHP types cannot express:

```php
// Native types only—preferred
public function handle(User $user, string $name): Receipt { }

// PHPDoc for generics/shapes
/** @param array{name: string, vendor?: string} $data */
public function process(array $data): void { }

// Eloquent generics—required
/** @return Builder<$this> */
public function scopeActive(Builder $query): Builder { }

/** @return HasMany<User, $this> */
public function users(): HasMany { }
```

### Directory Structure & Responsibilities

```
app/
├── Actions/           Single-purpose business operations (verb-first: CreateReceiptAction)
├── Console/Commands/  Artisan commands
├── Domain/            Pure, framework-agnostic services (PrintJobCalculator)
├── Enums/             Backed string enums for stable values
├── Events/            Broadcast and domain events
├── Exceptions/        Custom exceptions (suffix: *Exception)
├── Http/
│   ├── Controllers/   Thin HTTP entry points (usually empty; use Livewire)
│   ├── Middleware/    Request/response middleware
│   └── Requests/      FormRequest classes for validation (Create*, Update*, Delete*)
├── Livewire/          UI components (primary UI/business glue)
├── Mcp/
│   ├── Servers/       MCP servers (ReceiptServer, RecipeServer, ShoppingListServer)
│   ├── Tools/         MCP tool implementations grouped by domain
│   └── {Domain}/      Domain-specific MCP route files
├── Models/            Lean Eloquent models (relations, scopes, casts, factories only)
├── Providers/         Service providers
├── Services/          Multi-step business logic (Fastmail integration, mail classification)
└── Support/           Generic helpers and value objects
```

### Key Patterns

#### 1. Models (Lean & Explicit)

Models are thin: only `$fillable`, `$casts`, relations, query scopes, and factory bindings.

```php
class Recipe extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'description'];
    protected $casts = ['public' => 'boolean'];

    /** @return Builder<$this> */
    public function scopeForAuthUser(Builder $query): Builder
    {
        return $query->where('user_id', \auth()->id());
    }

    /** @return HasMany<RecipeIngredient, $this> */
    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
```

Always use `Model::query()->...`, never `DB::table()`.

#### 2. Actions (Single Purpose)

Actions encapsulate business logic with a public `handle()` method. Use array shapes in PHPDoc to document input data:

```php
class CreateReceiptAction
{
    /**
     * @param ?array{name?: string, vendor?: string, currency?: string} $data
     */
    public function handle(User $user, ?array $data): Receipt
    {
        $data['user_id'] = $user->id;
        return Receipt::query()->create($data);
    }
}
```

#### 3. Livewire Components (Lean & Delegating)

Components are the primary UI glue—they validate input and delegate persistence to Actions.

```php
class EditRecipe extends Component
{
    public int $id;
    public string $name = '';
    /** @var list<int> */
    public array $selectedCategories = [];

    public function mount(int $id): void
    {
        $recipe = Recipe::with(['ingredients', 'tags'])->forAuthUser()->find($id);
        $this->name = $recipe->name;
    }

    public function render(): View
    {
        return \view('livewire.recipes.edit', ['categories' => Category::all()]);
    }

    public function save(): ?Redirector
    {
        $data = $this->validate(['name' => 'required|string']);
        // delegate to Action or Eloquent...
        return $this->redirect(\route('single', ['id' => $this->id]));
    }
}
```

Public properties must declare types and (for arrays) a `@var` shape. Use `\view(...)` with leading backslash.

#### 4. MCP Servers & Tools

MCP servers are HTTP endpoints that expose tools to AI clients. Authentication uses Laravel Passport with the `mcp:use` scope.

**Server structure:**
```php
#[Name(value: 'Receipts Server')]
#[Version(value: '0.1.0')]
#[Instructions(value: '...markdown...')]
class ReceiptServer extends Server
{
    protected array $tools = [
        ListReceiptsTool::class,
        CreateReceiptTool::class,
        // ...
    ];
}
```

**Tool structure:**
```php
class ListReceiptsTool extends Tool
{
    #[Description('List the user\'s receipts')]
    public function execute(
        #[Description('Filter: start date (YYYY-MM-DD)')] ?string $from = null,
        #[Description('Filter: end date (YYYY-MM-DD)')] ?string $to = null,
    ): ToolResult
    {
        $receipts = Receipt::query()->forAuthUser()->get();
        return $this->result(\json_encode($receipts));
    }
}
```

Routes are defined in `app/Mcp/{Domain}/{Domain}McpRoute.php` and wired from `routes/ai.php`. Register servers in `app/Mcp/McpServerRegistry.php` so they appear in the settings UI.

#### 5. Domain Services (Pure & Testable)

Domain services are framework-agnostic: no Eloquent, no facades, no HTTP. Inputs are primitives or DTOs; outputs are primitives or arrays.

```php
// app/Domain/Printing/PrintJobCalculator.php
final class PrintJobCalculator
{
    public function calculateCost(
        float $materialCostPerGram,
        float $weightGrams,
        float $machineHourlyRate,
        float $printHoursNeeded,
    ): float {
        return ($materialCostPerGram * $weightGrams) 
            + ($machineHourlyRate * $printHoursNeeded);
    }
}
```

These are trivial to unit-test and reusable across HTTP, Livewire, MCP, and CLI.

### Routes & Livewire-First Design

Routes are split by surface area:

| File | Purpose |
|------|---------|
| `routes/web.php` | Browser routes mounting Livewire components |
| `routes/api.php` | REST endpoints (rarely used; Livewire-first) |
| `routes/ai.php` | MCP servers via `Mcp::web(...)` |
| `routes/channels.php` | Reverb broadcast auth |
| `routes/console.php` | Artisan closures & schedule |

The application is **Livewire-first**: most logic lives in Livewire components, not controllers. Controllers are rare; use them only for thin HTTP entry points if needed.

### Database & Migrations

All schema changes go through migrations. Never edit the database directly.

- Use descriptive migration names: `create_receipts_table`, `add_status_to_print_jobs_table`
- Include foreign key constraints, indexes for hot columns, `softDeletes()`, and `timestamps()`
- Cast enum-like columns to PHP enums in models

```php
// Migration
Schema::create('print_jobs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->enum('status', ['pending', 'printing', 'completed'])->default('pending');
    $table->softDeletes();
    $table->timestamps();
});

// Model
protected $casts = [
    'status' => PrintJobStatus::class,
];
```

### Views & Assets

- **Blade** lives in `resources/views/`: `livewire/`, `components/`, `mcp/`, `receipts/`
- **Pure CSS/SCSS** in `resources/scss/` and `resources/css/` (no Tailwind, no utility frameworks)
- **JavaScript** in `resources/js/` (Echo + Pusher client setup, built with Vite)

Entry points: `resources/scss/app.scss`, `resources/js/app.js` → `vite.config.js` → bundled to `public/build/`

## Testing & Coverage

### Test Layout

```
tests/
├── Feature/
│   ├── Http/
│   ├── Console/
│   ├── Mcp/
│   ├── Policies/
│   └── [Component]Test.php
├── Unit/
│   ├── Actions/
│   ├── Models/
│   ├── Jobs/
│   ├── Domain/
│   └── Support/
├── Pest.php          (Test setup & helpers)
├── TestCase.php      (Base test class)
└── Concerns/
    └── RunsMigrations.php
```

### Conventions

- **Coverage metadata is required:** Every test class must declare what it covers.
  ```php
  use PHPUnit\Framework\Attributes\CoversClass;

  #[CoversClass(CreateReceiptAction::class)]
  final class CreateReceiptActionTest extends TestCase {}
  ```
  Or in Pest files: `covers(CreateReceiptAction::class)` or `coversNothing()`

- **Naming:** Describe behavior, not implementation.
  ```php
  it('creates a receipt for the authenticated user', function () { });
  ```

- **Factories:** Use `Model::factory()->create()` in tests. Every model needs a Factory.

- **Database:** Feature tests automatically use `DatabaseTransactions`; in-memory SQLite is configured in `phpunit.xml`.

- **Mocking:** Mock external services (OpenAI, S3, Reverb)—never hit the network in tests.

## Code Style & Static Analysis

### Pint (Style)

Style rules live in `pint.json` (preset: `per`). Key rules:

- Short array syntax: `[]`
- Trailing commas in multiline arrays/arguments/parameters
- Single quotes by default; use explicit string variables: `"{$name}"`
- **Native function invocation with leading backslash:** `\trim()`, `\json_encode()`, `\auth()->id()`
- **Native constant invocation:** `\PHP_INT_MAX` (except `null`, `true`, `false`)
- One class/interface/enum/trait per file
- Class members ordered: traits → cases → constants → properties → constructor → magic → public → protected → private

```php
// Good
\trim($tag);
\explode(',', $str);
\array_values($arr);
\response($data)->header('X-Custom', 'value');

// Bad
trim($tag);
Auth::id();
```

Auto-fix style violations:
```bash
./vendor/bin/pint
```

### PHPStan (Static Analysis, Level 9)

Configuration: `phpstan.neon`. Analyzed paths: `app/`, `bootstrap/`, `database/`, `routes/`. Excluded: `vendor/`, `config/`, `tests/`, `resources/`, `public/`, `storage/`.

#### Class Suffixes (Enforced)

| Parent | Required Suffix |
|--------|-----------------|
| `\Exception` | `*Exception` |
| `\PHPUnit\Framework\TestCase` | `*Test` |
| `\Illuminate\Routing\Controller` | `*Controller` |
| `\Illuminate\Support\ServiceProvider` | `*ServiceProvider` |
| `\Illuminate\Database\Eloquent\Factories\Factory` | `*Factory` |
| `\Illuminate\Mail\Mailable` | `*Mailable` |
| `\Illuminate\Notifications\Notification` | `*Notification` |
| `\Illuminate\Http\Resources\Json\JsonResource` | `*Resource` |
| `\Illuminate\Contracts\Validation\ValidationRule` | `*Rule` |
| `\Illuminate\Database\Eloquent\Scope` | `*Scope` |
| `\Illuminate\Database\Eloquent\Builder` | `*Builder` |

#### Key Rules

- **No comparison of `mixed`:** Cast or narrow first.
- **No null in binary operations** except `??` and `??=`.
- **No variable overwriting** in loops.
- **No dynamic class/method/property names.**
- **No useless casts, null defaults, or nullable returns.**
- **Required `previous` exception** when re-throwing: `throw new Exception(..., previous: $e);`
- **Boolean conditions must be `bool`:** No truthy checks on `string|null`, etc.

Use `@phpstan-ignore-next-line` **only** with a written justification:
```php
$data = (array) $obj; // @phpstan-ignore-next-line Conversion required for legacy API
```

**Never use** `@phpstan-ignore`, `@noinspection`, or skipped tests to bypass analysis—fix the underlying issue.

## Before Committing

1. **All quality gates pass:**
   ```bash
   composer lint && composer larastan && composer test
   ```

2. **No debug code:** No `dd`, `dump`, `var_dump`, `print_r`, or commented-out code.

3. **No suppressed errors:** No `@phpstan-ignore`, skipped tests, or `--no-verify` flags.

4. **Strict types & generics:**
   - `declare(strict_types=1);` on every new PHP file
   - All parameters and returns typed
   - Eloquent relations and scopes have PHPDoc generics

5. **New models have factories:** `#[CoversClass]` or `covers()` on test classes.

6. **No new top-level `app/` directories** without approval.

7. **No dependency changes** to `composer.json` or `package.json` without approval.

8. **Commits are small & descriptive.** PRs must pass CI before merge.

## Debugging & Observability

- **Sentry:** Exceptions are automatically captured. Check `config/sentry.php` for configuration.
- **Laravel Tinker:** Use `php artisan tinker` for interactive debugging.
- **Logs:** Check `storage/logs/laravel.log` (or tail it: `tail -f storage/logs/laravel.log`).
- **Xdebug:** Configured in `docker-compose.yml`; attach your IDE debugger to port 9003.

## Resources

- **CURSOR rules (most detailed):** `.cursor/rules/RULE.md`
- **PHPStan config:** `phpstan.neon`
- **Pint config:** `pint.json`
- **Tests setup:** `tests/Pest.php`, `tests/TestCase.php`
- **CI pipeline:** `.github/workflows/laravel.yml`
- **MCP registry:** `app/Mcp/McpServerRegistry.php`

