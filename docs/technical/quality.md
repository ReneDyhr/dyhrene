# Quality Assurance

How code quality is enforced in the Dyhrene codebase — style, static analysis, testing, CI, and review.

---

## Quality Gate Pipeline

Every code change must pass three gates:

```bash
composer lint      # Pint code style (read-only check)
composer larastan  # PHPStan level 9 static analysis (zero errors)
composer test      # Pest test suite (all tests pass)
```

These gates mirror the CI pipeline defined in `.github/workflows/laravel.yml`.

---

## 1. Pint — Code Style

**Config file:** `pint.json`
**Preset:** `per` (PER Coding Style)

### Key Style Rules

| Rule | Configuration |
|------|--------------|
| **declare_strict_types** | `true` — `declare(strict_types=1);` on every file |
| **native_function_invocation** | `@all` with `strict: true` — all global functions prefixed `\` |
| **native_constant_invocation** | `strict: true` — `\PHP_INT_MAX`, etc. (except `null`, `true`, `false`) |
| **array_syntax** | Short: `[]` |
| **trailing_comma_in_multiline** | Trailing commas in arrays, arguments, parameters, match |
| **single_quote** | Single quotes by default |
| **explicit_string_variable** | `"{$name}"` syntax |
| **ordered_imports** | Alphabetically sorted |
| **ordered_class_elements** | traits → cases → constants → properties → constructor → magic → public → protected → private |
| **no_unused_imports** | `true` |
| **no_useless_else** | `true` |
| **no_superfluous_phpdoc_tags** | `true` — remove redundant type annotations |
| **phpdoc_align** | Vertically aligned for `@param`, `@return`, `@throws`, `@var` |
| **modernize_types_casting** | `true` — use modern casting style |
| **ternary_to_null_coalescing** | `true` |

### Running Pint

```bash
# Dry-run / check (CI mode): exit code 1 if any changes needed
composer lint

# Auto-fix all style violations
./vendor/bin/pint

# Fix a specific file
./vendor/bin/pint app/Models/Recipe.php

# Fix a directory
./vendor/bin/pint app/Livewire/
```

### Avoid

- No `dd`, `dump`, `var_dump`, `print_r`, or commented-out code in committed files
- No backward-incompatible casts: `(array)`, `(object)`, `(unset)` — banned by ShipMonk's `ForbidCastRule` (shipmonk/phpstan-rules)

---

## 2. PHPStan — Static Analysis Level 9

**Config file:** `phpstan.neon`
**Level:** 9 (maximum strictness)

### Analyzed Paths

- `app/`
- `bootstrap/`
- `database/`
- `routes/`

### Excluded Paths

- `vendor/` · `config/` · `public/` · `resources/` · `storage/` · `tests/`

### Rule Packs

| Pack | Source | Purpose |
|------|--------|---------|
| **larastan/larastan** | `vendor/larastan/larastan/extension.neon` | Laravel-aware rules (facades, Eloquent, collections) |
| **phpstan/phpstan-strict-rules** | `vendor/phpstan/phpstan-strict-rules/rules.neon` | Disallows loose comparisons, boolean conditions must be bool, useless casts, must call parent constructor |
| **shipmonk/phpstan-rules** | `vendor/shipmonk/phpstan-rules/rules.neon` | Class suffix naming, no null in binary ops, forbid mixed method calls, ban variable overwriting, enforce native return types, forbid `(array)`/`(object)`/`(unset)` casts (`ForbidCastRule`) |
| **symplify/phpstan-rules** | `vendor/symplify/phpstan-rules/config/services/services.neon` | Explicit class prefix/suffix, no return setters, require attribute names, unique enum constants, no dynamic names |
| **ekino/phpstan-banned-code** | `vendor/ekino/phpstan-banned-code/extension.neon` | Bans dangerous functions |

### Class Suffix Naming (Enforced)

Every class extending these parents **must** use the corresponding suffix:

| Parent / Contract | Required Suffix |
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

### Key Static Analysis Rules

| Rule | Description |
|------|-------------|
| `allowComparingOnlyComparableTypes` | No comparing unrelated types |
| `forbidMethodCallOnMixed` | Narrow `mixed` before calling methods |
| `forbidFetchOnMixed` | Narrow `mixed` before property access |
| `forbidNullInBinaryOperations` | No null in `+`, `-`, `*`, etc. (except `??` and `??=`) |
| `forbidNullInAssignOperations` | No null in `+=`, `-=`, etc. (except `??=`) |
| `forbidNullInInterpolatedString` | No `null` in `"{$var}"` |
| `forbidVariableTypeOverwriting` | No reassigning variables with different types |
| `requirePreviousExceptionPass` | Re-throwing requires `previous: $e` |
| `booleansInConditions` | `if`/`while` conditions must be `bool` |
| `uselessCast` | No casts that don't change the type |
| `overwriteVariablesWithLoop` | No `foreach ($items as $item)` if outer `$item` exists |
| `noVariableVariables` | No `$$name` dynamic variables |
| `NoDynamicNameRule` | No `$obj->$method()` dynamic calls |
| `RequireAttributeNameRule` | All PHP attributes must have a name |
| `RequireUniqueEnumConstantRule` | Enum cases must have unique values |
| `NoReturnSetterMethodRule` | Setter methods should use `void` not `return $this` |

### Eloquent Generics (Required)

All Eloquent relations and query scopes must have PHPDoc generics:

```php
/** @return HasMany<RecipeIngredient, $this> */
public function ingredients(): HasMany { }

/** @param Builder<$this> $query
 *  @return Builder<$this> */
public function scopeForAuthUser(Builder $query): Builder { }
```

### Suppressing Errors

Use `@phpstan-ignore-next-line` **only** with a written justification on the same line:

```php
$data = (array) $obj; // @phpstan-ignore-next-line Conversion required for legacy API
```

**Never** use `@phpstan-ignore`, `@noinspection`, or skipped tests to bypass analysis.

---

## 3. Testing — Pest 4

### Test Structure

```
tests/
├── Feature/            Feature tests (HTTP, console, MCP, policies)
│   ├── Http/
│   ├── Console/
│   ├── Mcp/
│   └── Policies/
├── Unit/               Unit tests (Actions, Models, Jobs, Domain, Support)
│   ├── Actions/
│   ├── Models/
│   ├── Jobs/
│   ├── Domain/
│   └── Support/
├── Concerns/
│   └── RunsMigrations.php
├── Pest.php            Test setup, helpers, global uses
└── TestCase.php        Base test class
```

### Testing Conventions

#### Coverage Metadata (Required)

`phpunit.xml` has `requireCoverageMetadata="true"`. Every test must declare what it covers:

```php
// PHPUnit style
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreateReceiptAction::class)]
final class CreateReceiptActionTest extends TestCase {}

// Pest style
covers(CreateReceiptAction::class);

it('creates a receipt', function () { ... });
```

For tests that don't map to a specific class, use `coversNothing()`.

#### Naming

Describe behavior, not implementation:

```php
// Good
it('creates a receipt for the authenticated user', function () { ... });
it('returns 403 when user does not own the receipt', function () { ... });

// Avoid
it('tests the create method', function () { ... });
```

#### Factories

Every model needs a Factory. Use `Model::factory()->create()` in tests:

```php
$user = User::factory()->create();
$recipe = Recipe::factory()->for($user)->create();
```

#### Database Transactions

Feature tests automatically use `DatabaseTransactions`. The in-memory SQLite database is configured in `phpunit.xml`.

#### Mocking

Mock external services (OpenAI, S3, Reverb, Fastmail) — never hit the network in tests:

```php
Http::fake(['https://api.openai.com/*' => Http::response(['choices' => [...]])]);
Storage::fake('wasabi');
```

#### Running Tests

```bash
# All tests
composer test

# Single test file
./vendor/bin/pest tests/Feature/Mcp/ReceiptMcpServerTest.php

# Filter by test name
./vendor/bin/pest --filter="creates a receipt"

# Parallel (opt-in — requires the --parallel flag)
./vendor/bin/pest --parallel

# With coverage
./vendor/bin/pest --coverage
```

---

## 4. CI Pipeline

**Workflow file:** `.github/workflows/laravel.yml`

### Trigger

- Push to `main`
- Pull request to `main`

### Job: laravel-tests

| Step | Description |
|------|-------------|
| **Setup PHP 8.5** | Required extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, fileinfo |
| **Install Composer deps** | `composer install -q --no-ansi --no-interaction --prefer-dist` |
| **Copy .env** | From `.env.example` |
| **Generate app key** | `php artisan key:generate` |
| **Permissions** | `chmod -R 777 storage bootstrap/cache` |
| **Pint** | `./vendor/bin/pint --test` (fails if any style issues) |
| **Larastan** | `composer larastan -v` (fails if any errors) |

The test execution step is currently commented out (uses MySQL service). Local testing uses SQLite in-memory.

---

## 5. Pre-Commit Checklist

Before committing, verify all of the following:

```markdown
- [ ] `composer lint` passes (Pint clean)
- [ ] `composer larastan` passes (PHPStan level 9, zero errors)
- [ ] `composer test` passes (all Pest tests green)
- [ ] No `dd`, `dump`, `var_dump`, `print_r`, or commented-out code
- [ ] No new `@phpstan-ignore`, `@noinspection`, or skipped tests
- [ ] `declare(strict_types=1);` on every new PHP file
- [ ] All parameters and returns are typed (native types preferred)
- [ ] New Eloquent models have a Factory and use `HasFactory<...>` generic
- [ ] New models/relations/scopes have Eloquent generics in PHPDoc
- [ ] Schema changes ship with a migration
- [ ] New code has tests with coverage metadata (`covers()` or `#[CoversClass]`)
- [ ] No new top-level `app/` directories
- [ ] No dependency changes to `composer.json` or `package.json` without approval
- [ ] No use of `DB::` facade — use `Model::query()` instead
- [ ] Eager loaded relationships where N+1 could occur
- [ ] File uploads have MIME type, extension, and size validation
```

---

## 6. Code Review Guidelines

When reviewing a pull request, verify:

### Style & Conventions
- [ ] Code follows Pint style (caller verified by CI)
- [ ] `declare(strict_types=1);` present on all new files
- [ ] Native functions called with leading backslash
- [ ] Class suffixes match requirements
- [ ] No dead code, commented-out code, or debug statements
- [ ] Clean commit history with descriptive messages

### Static Analysis
- [ ] PHPStan passes at level 9 with zero errors
- [ ] Eloquent generics present on all relations and scopes
- [ ] Array shapes documented for complex array parameters
- [ ] No suppressed errors without justification

### Testing
- [ ] All tests pass
- [ ] New features have both unit and feature tests
- [ ] Coverage metadata (`covers()` or `#[CoversClass]`) present
- [ ] Both happy and failure paths tested
- [ ] External services mocked (no network calls in tests)
- [ ] Edge cases considered (empty, null, boundary values)

### Architecture
- [ ] New classes live in appropriate directories
- [ ] No new top-level `app/` directories without justification
- [ ] Domain logic in `app/Domain/` is framework-agnostic
- [ ] Actions are single-purpose with `handle()` method
- [ ] Livewire components delegate persistence to Actions
- [ ] Models are lean (fillable, casts, relations, scopes only)

### Security
- [ ] Authorization checked (Policies for web, Passport scopes for API/MCP)
- [ ] User input validated (FormRequest or Livewire `$this->validate()`)
- [ ] No secrets, tokens, or env values exposed in views/logs/responses
- [ ] File uploads validated for MIME type, extension, and size
- [ ] SQL injection prevented (always use Eloquent, never raw queries)
- [ ] XSS prevented (Blade auto-escapes by default)

### Performance
- [ ] N+1 queries prevented (eager load with `with()`)
- [ ] Indexes added for frequently queried columns
- [ ] Large result sets use `chunk()` or `cursor()`
- [ ] Slow work queued (images, PDFs, OpenAI calls)
- [ ] Cache used where appropriate (PrintSettings uses cache)
