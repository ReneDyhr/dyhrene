---
alwaysApply: true
---

# Project Coding Standards & Guidelines

You are an expert in PHP 8.5, Laravel 13, Livewire 4, Pest 4, Laravel MCP, and modern strictly-typed PHP development. This document is the single source of truth for how code is written, tested, and reviewed in this repository.

## 0. Quality Gates (run after EVERY code change)

Every time you change PHP code, configuration, migrations, or routes, run **all three** gates in this exact order before marking work complete. They mirror CI in [.github/workflows/laravel.yml](.github/workflows/laravel.yml):

1. `composer lint` — runs `pint --test` (read-only style check). Auto-fix locally with `./vendor/bin/pint`.
2. `composer larastan` — runs PHPStan analysis at level 9. Must finish with **zero** errors.
3. `composer test` — runs the Pest test suite. All tests must pass.

If any gate fails, fix it before continuing. Do not introduce `@phpstan-ignore`, `@noinspection`, or skipped tests to make a gate pass — fix the underlying issue, or stop and ask.

There is **no** `composer pint`, `composer full-test`, or `composer dev` script in this project. Use only the scripts defined in [composer.json](composer.json).

## 1. Stack & Tech Overview

The project is a Laravel application with a Livewire-rendered UI and an MCP API surface for AI clients.

- **Runtime**: PHP `^8.5`, Laravel `^13.0` (see [composer.json](composer.json)).
- **UI**: Livewire `^4.3` components rendering Blade in [resources/views/livewire/](resources/views/livewire/). Pure CSS / SCSS only — **no Tailwind, no utility frameworks**. Assets are built with Vite.
- **Auth**: Laravel Passport (OAuth 2.1) and Laravel Sanctum. MCP access tokens require the `mcp:use` scope.
- **Realtime**: Laravel Reverb with `laravel-echo` and `pusher-js` on the client.
- **AI / MCP**: `laravel/mcp` exposes HTTP MCP servers. `openai-php/laravel` for LLM calls.
- **Storage**: `league/flysystem-aws-s3-v3` (Wasabi/S3-compatible). PDF rendering via `spatie/pdf-to-image`.
- **Observability**: `sentry/sentry-laravel`.
- **Quality tooling**: Laravel Pint (style), Larastan + Shipmonk + Symplify + PHPStan strict + Ekino banned-code (static analysis level 9), Pest 4 (tests).

## 2. PHP & Code Style

Pint is the single source of truth for formatting; rules live in [pint.json](pint.json) using the `per` preset. Notable rules you must follow when writing new code:

- `declare(strict_types=1);` is **required** at the top of every PHP file.
- Use modern PHP: typed properties, readonly props, enums, `match`, named args, first-class callable syntax, intersection/union types.
- Always type parameters and return types. Prefer native types over PHPDoc; use PHPDoc only to add generics or array shapes that PHP cannot express.
- `native_function_invocation` is set to `@all` with `strict: true`. **Global PHP functions must be called with a leading backslash.** Examples seen in the codebase:

```php
\auth()->id();
\route('single', ['id' => $recipe->id]);
\view('livewire.recipes.add', ['title' => 'Add Recipe']);
\trim($tag);
\explode(',', $this->tags);
\array_values($this->ingredients);
\round($value, 2);
\is_numeric($value);
```

- `native_constant_invocation` is also `strict` — use `\PHP_INT_MAX`, etc., except for `null`, `true`, `false`.
- Strings: single quotes by default; use `explicit_string_variable` (e.g. `"{$name}"`).
- Arrays: short syntax `[]`, trailing comma in multiline arrays/arguments/parameters/match.
- Imports: alpha-ordered, no unused imports, no aliased imports unless required.
- Class members: ordered per `ordered_class_elements` (traits → cases → constants → properties → constructor → magic → public methods → protected → private).
- PHPDoc: no superfluous tags, vertically aligned, no empty docblocks. Don't restate types you've already typed natively.
- No `dd`, `dump`, `var_dump`, `print_r`, or commented-out code in committed files.
- One class/interface/enum/trait per file (`ForbiddenMultipleClassLikeInOneFileRule`).
- Forbidden casts: `(array)`, `(object)`, `(unset)`. Use proper conversion (`(string)`, `(int)`, etc.) or explicit constructors.

## 3. Static Analysis (PHPStan Level 9)

Configuration is in [phpstan.neon](phpstan.neon). Analyzed paths: `app/`, `bootstrap/`, `database/`, `routes/`. Excluded: `vendor/`, `config/`, `public/`, `resources/`, `storage/`, `tests/`.

Active rule packs:

- `larastan/larastan` — Laravel-aware rules.
- `phpstan/phpstan-strict-rules` — strict comparisons, boolean conditions, useless casts, parent constructor calls, etc.
- `shipmonk/phpstan-rules` — class suffix naming, native return typehints, no null in binary ops, no variable overwriting, forbid mixed method calls, etc.
- `symplify/phpstan-rules` — explicit class suffix/prefix, no return setters, require attribute names/namespaces, unique enum constants.
- `ekino/phpstan-banned-code` — bans dangerous functions/casts.

### 3.1 Required class suffixes

Per `classSuffixNaming` in [phpstan.neon](phpstan.neon):

| Parent / contract | Required suffix |
|---|---|
| `\Exception` | `*Exception` |
| `\PHPStan\Rules\Rule` | `*Rule` |
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

### 3.2 Eloquent generics

Models, scopes, and relations require PHPDoc generics. Example from [app/Models/Recipe.php](app/Models/Recipe.php):

```php
class Recipe extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * @param  Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeForAuthUser(Builder $query): Builder
    {
        return $query->where('user_id', \Illuminate\Support\Facades\Auth::id());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<RecipeIngredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
```

### 3.3 Other static-analysis rules to remember

- No comparison of `mixed`; cast/narrow first (`forbidMethodCallOnMixed`, `forbidFetchOnMixed`).
- No null in binary operations except `??` and `??=`.
- No null in interpolated strings.
- No variable overwriting in loops.
- No dynamic class/method/property names (`NoDynamicNameRule`).
- No useless casts, useless else, useless nullable returns, useless null property defaults.
- Required `previous` exception when re-throwing (`requirePreviousExceptionPass`).
- Boolean conditions must be `bool` — no truthy checks on `string|null`, etc.
- Use `@phpstan-ignore-next-line` only with a written justification on the same line.

## 4. Project Structure

Stick to the existing structure. **Do not create new top-level directories** under `app/` without approval. **Delete `.gitkeep`** when you add the first real file to a directory.

```
app/
  Actions/        Single-purpose business operations (handle())
  Console/        Artisan commands
  Domain/         Pure, framework-agnostic services per subdomain
  Enums/          Backed enums (+ Concerns/ for shared traits)
  Events/         Broadcast / domain events
  Exceptions/     Custom exceptions
  Http/
    Controllers/  Thin HTTP entrypoints (currently empty)
    Middleware/
    Requests/     FormRequest classes for validation/authorization
  Livewire/       UI components (recipes, receipts, shopping, printing, ...)
  Mcp/            Laravel MCP servers, tools, routes, registry
  Models/         Eloquent models (lean: relations, scopes, casts)
  Providers/      Service providers
  Support/        Generic helpers / value objects
```

### 4.1 `app/Http/Controllers`

Currently empty — the Livewire-first stack means most flows live in Livewire. If you add a controller:

- Do not extend an abstract base controller.
- Keep it thin — accept a `FormRequest`, dispatch an Action, return a response.
- Use constructor or method injection for Actions.

```php
public function store(CreateReceiptRequest $request, CreateReceiptAction $action): RedirectResponse
{
    $receipt = $action->handle($request->user(), $request->validated());

    return \redirect()->route('receipts.show', $receipt);
}
```

### 4.2 `app/Http/Requests`

- Always use `FormRequest` for HTTP validation.
- Naming: `Create{Resource}Request`, `Update{Resource}Request`, `Delete{Resource}Request`.
- Authorization belongs in `authorize()`, rules in `rules()`. See [app/Http/Requests/CreateReceiptRequest.php](app/Http/Requests/CreateReceiptRequest.php).

### 4.3 `app/Actions`

- One purpose per Action. Public entrypoint is `handle(...)`.
- Verb-first names: `CreateReceiptAction`, `UpdateReceiptAction`, `DeleteReceiptAction`.
- Document array shapes for input data so PHPStan can verify them.

```php
class CreateReceiptAction
{
    /**
     * @param ?array{name?: null|string, vendor?: null|string, currency?: null|string, date?: null|string} $data
     */
    public function handle(User $user, ?array $data): Receipt
    {
        $data['user_id'] = $user->id;

        return Receipt::query()->create($data);
    }
}
```

See [app/Actions/CreateReceiptAction.php](app/Actions/CreateReceiptAction.php).

### 4.4 `app/Livewire`

- Components are the primary UI/business glue. Keep them lean: validation + delegation.
- Validate with `$this->validate([...])` and pass the result on. Don't duplicate FormRequest rules in Livewire when the same flow exists for HTTP — share rules via a method.
- Delegate persistence to an Action when the flow is non-trivial.
- Public component properties must declare types and (when array) `@var` shape.
- `render()` returns `View` and uses `\view(...)`.

Pattern example based on [app/Livewire/EditRecipe.php](app/Livewire/EditRecipe.php):

```php
class EditRecipe extends Component
{
    public int $id;
    public string $name = '';

    /** @var list<int> */
    public array $selectedCategories = [];

    public function mount(int $id): void { /* load via scopeForAuthUser() */ }

    public function render(): View
    {
        return \view('livewire.recipes.edit', [/* ... */]);
    }

    public function save(): ?Redirector
    {
        $data = $this->validate([/* ... */]);
        // delegate to Action or Eloquent...
        return $this->redirect(\route('single', ['id' => $id]));
    }
}
```

### 4.5 `app/Domain/<Subdomain>`

Pure, framework-agnostic services. **No Eloquent, no facades, no HTTP, no DI of Laravel internals.** All inputs come in as primitives or DTOs/arrays; outputs are primitives or arrays. This makes them trivially testable. Example: [app/Domain/Printing/PrintJobCalculator.php](app/Domain/Printing/PrintJobCalculator.php).

### 4.6 `app/Models`

Lean models. Allowed responsibilities: `$fillable`, `$casts`, relations, query scopes, simple accessors/mutators, factory binding.

- Cast status fields to enums.
- Use `softDeletes()` and `timestamps()` where the schema has them.
- Annotate relations and scopes with PHPStan generics (see §3.2).
- Always use `Model::query()->...` — never reach for `DB::`.
- Always provide a Factory: `php artisan make:factory {Model}Factory`.

### 4.7 `app/Enums`

- Backed string enums for stable values (e.g. `LanguageEnum`).
- Reusable behavior in `app/Enums/Concerns/`.
- `RequireUniqueEnumConstantRule` is enabled; cases must have unique values.

### 4.8 `app/Mcp`

The MCP surface is built on `laravel/mcp`. When adding a new MCP capability:

1. Create the server class in `app/Mcp/Servers/{Domain}Server.php` extending `Laravel\Mcp\Server` and decorate with `#[Name]`, `#[Version]`, `#[Instructions]` attributes (see [app/Mcp/Servers/ReceiptServer.php](app/Mcp/Servers/ReceiptServer.php)).
2. Place tools under `app/Mcp/Tools/{Domain}/{Action}Tool.php`.
3. Define the route in `app/Mcp/{Domain}/{Domain}McpRoute.php` and wire it from [routes/ai.php](routes/ai.php).
4. Register it in [app/Mcp/McpServerRegistry.php](app/Mcp/McpServerRegistry.php) so the settings UI lists the connection URLs.
5. Require the `mcp:use` Passport scope.

### 4.9 Routes

Routes are split by surface area in [routes/](routes/):

- `web.php` — browser-facing routes and Livewire mounts.
- `api.php` — REST endpoints.
- `ai.php` — MCP servers (`Mcp::web(...)`).
- `channels.php` — Reverb broadcast auth.
- `console.php` — Artisan closures / schedule.

### 4.10 Views & assets

- Blade lives in [resources/views/](resources/views/): `livewire/`, `components/`, `mcp/`, `receipts/`.
- Stylesheets in `resources/scss/` and `resources/css/`. Pure CSS / SCSS only.
- JS in `resources/js/` (Echo + Pusher client setup). Built with Vite (`npm run dev` / `npm run build`).

## 5. Database & Eloquent

- All schema changes go through migrations. Never edit the database directly. Use descriptive names: `create_receipts_table`, `add_status_to_print_jobs_table`.
- Add foreign key constraints, indexes for hot columns, and `softDeletes()`/`timestamps()` where appropriate.
- Cast enum-like columns to PHP enums.
- **Never use the `DB::` facade.** Use `Model::query()` and Eloquent. Eager load with `with()` to avoid N+1. Use `chunk()` / `cursor()` for large result sets.

```php
// Good
Recipe::query()->with(['ingredients', 'tags'])->forAuthUser()->get();

// Bad
\DB::table('recipes')->where('user_id', $userId)->get();
```

## 6. Validation & Authorization

- HTTP boundary: `FormRequest` with `authorize()` + `rules()`.
- Livewire boundary: `$this->validate([...])`. Move shared rules into a static method or a dedicated Rule class if reused.
- Authorization for resources uses Policies; for MCP / API use Passport scopes (notably `mcp:use`).
- Never trust client input. Never echo secrets, tokens, or env values into views, logs, or responses.

## 7. Testing (Pest 4)

Configuration lives in [tests/Pest.php](tests/Pest.php) and [tests/TestCase.php](tests/TestCase.php). Feature tests automatically use `DatabaseTransactions`. The custom `RunsMigrations` trait in [tests/Concerns/RunsMigrations.php](tests/Concerns/RunsMigrations.php) runs migrations against the in-memory SQLite connection configured in [phpunit.xml](phpunit.xml).

### 7.1 Layout

- `tests/Feature/Http/{Component}Test.php` — controllers / HTTP endpoints.
- `tests/Feature/Console/{Command}Test.php` — Artisan commands.
- `tests/Feature/Mcp/{Server}Test.php` — MCP servers / tools.
- `tests/Feature/Policies/{Policy}Test.php` — policy authorization.
- `tests/Unit/Actions/{Action}Test.php`
- `tests/Unit/Models/{Model}Test.php`
- `tests/Unit/Jobs/{Job}Test.php`
- `tests/Unit/Domain/{Subdomain}/{Service}Test.php`
- `tests/Unit/Support/{Helper}Test.php`

### 7.2 Coverage metadata is required

`phpunit.xml` has `requireCoverageMetadata="true"`. Every test class/file must declare what it covers, e.g.:

```php
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreateReceiptAction::class)]
final class CreateReceiptActionTest extends TestCase {}
```

For Pest tests, use `covers(CreateReceiptAction::class)` or `coversNothing()` at the top of the test file.

### 7.3 Conventions

- One Factory per Model. Use `Model::factory()->create()` in tests.
- Describe behavior, not implementation: `it('creates a receipt for the authenticated user', ...)`.
- Test both happy and failure paths.
- Mock external services (OpenAI, S3, Reverb) — never hit the network.
- Don't delete tests without explicit approval.

## 8. Frontend

- Vite + `laravel-vite-plugin`. Entry points configured in [vite.config.js](vite.config.js).
- Pure CSS / SCSS. Reuse existing variables and partials in `resources/scss/`.
- Realtime via `laravel-echo` + `pusher-js` against Laravel Reverb.
- No new npm dependencies without approval (see §10).

## 9. Security & Performance

- Eager load relations to prevent N+1; add indexes for frequently queried columns.
- Use queue jobs for slow work (image processing, PDF rendering, OpenAI calls). Implement retries / failed-job handling.
- Validate file uploads strictly: MIME type, extension, size (e.g. MCP receipt scans cap at 15 MiB decoded). Store with generated names — never trust user-supplied filenames.
- Sentry captures exceptions; don't swallow exceptions silently.
- Soft deletes: ensure related stored files / external resources are cleaned up consistently.

## 10. Workflow Rules

- **No new top-level `app/` directories** without approval.
- **No dependency changes** to [composer.json](composer.json) or [package.json](package.json) without explicit approval.
- Delete `.gitkeep` when adding the first real file to a directory.
- Stick to existing Laravel conventions before reaching for custom patterns or new packages.
- Commits should be small and descriptive; PRs must pass [.github/workflows/laravel.yml](.github/workflows/laravel.yml) (Pint + Larastan) before merge.
- Local dev runs via Laravel Sail (`./vendor/bin/sail up`) or the devcontainer; Vite via `npm run dev`.

## 11. Task Completion Checklist

Before declaring any change complete, every item below must be true:

- [ ] `composer lint` passes (Pint clean).
- [ ] `composer larastan` passes (PHPStan level 9, zero errors).
- [ ] `composer test` passes (all Pest tests green).
- [ ] No new `@phpstan-ignore`, skipped tests, `dd`/`dump`, or commented-out code.
- [ ] `declare(strict_types=1);` on every new PHP file.
- [ ] New Eloquent models have a Factory and use `HasFactory<...>` generic.
- [ ] Schema changes ship with a migration.
- [ ] New Livewire / Action / Domain / MCP code has Unit or Feature tests with coverage metadata.
- [ ] Touched directories follow §4 (no rogue subfolders, no Filament/Services drift).
- [ ] Public API additions (controllers, routes, MCP tools) are authorized and validated.

---

**When in doubt**, prefer Laravel conventions, the patterns already used in [app/](app/), and the rules expressed by [pint.json](pint.json) and [phpstan.neon](phpstan.neon). Consistency with the rest of the codebase outweighs personal preference.
