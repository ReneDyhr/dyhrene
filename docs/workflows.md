# Developer Workflows

Common workflows for developing on the Dyhrene codebase. Step-by-step guides for the most frequent tasks.

---

## Adding a New Feature

### 1. Create a Feature Branch

```bash
git checkout main
git pull origin main
git checkout -b feat/descriptive-feature-name
```

### 2. Create Models & Migrations

```bash
# Generate the model with factory and migration
php artisan make:model NewModel -mf

# Or generate separately
php artisan make:model NewModel
php artisan make:factory NewModelFactory --model=NewModel
php artisan make:migration create_new_models_table
```

Write the migration:

```php
Schema::create('new_models', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('name');
    $table->text('description')->nullable();
    $table->softDeletes();
    $table->timestamps();
});
```

Run the migration:

```bash
php artisan migrate
```

### 3. Build the Model (Lean)

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewModel extends Model
{
    /** @use HasFactory<\Database\Factories\NewModelFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'description'];

    protected $casts = ['published' => 'boolean'];
}
```

### 4. Create Livewire Components

```bash
php artisan livewire:make NewModels/Index
php artisan livewire:make NewModels/Create
php artisan livewire:make NewModels/Edit
php artisan livewire:make NewModels/Show
```

Build each component with proper types:

```php
class Index extends Component
{
    public function render(): View
    {
        return \view('livewire.new-models.index', [
            'items' => NewModel::query()->forAuthUser()->paginate(20),
        ]);
    }
}
```

### 5. Add Routes

In `routes/web.php`:

```php
Route::get('/new-models', App\Livewire\NewModels\Index::class)
    ->middleware('auth')->name('new-models.index');
Route::get('/new-models/create', App\Livewire\NewModels\Create::class)
    ->middleware('auth')->name('new-models.create');
```

### 6. Add Navigation Link

In `resources/views/components/layouts/sidenav.blade.php`, add a link for the new feature.

### 7. Create Actions (if needed)

If business logic is non-trivial, create an Action:

```php
// app/Actions/CreateNewModelAction.php
class CreateNewModelAction
{
    public function handle(User $user, array $data): NewModel
    {
        $data['user_id'] = $user->id;
        return NewModel::query()->create($data);
    }
}
```

### 8. Write Tests

```bash
# Feature test
# tests/Feature/Http/NewModelTest.php

# Unit test for Action
# tests/Unit/Actions/CreateNewModelActionTest.php

# Unit test for Model
# tests/Unit/Models/NewModelTest.php
```

Every test must have `covers()` annotation:

```php
covers(CreateNewModelAction::class);

it('creates a new model for the user', function () {
    // ...
});
```

### 9. Run Quality Gates

```bash
composer lint && composer larastan && composer test
```

### 10. Commit and Push

```bash
git add .
git commit -m "feat: add new model CRUD"
git push origin feat/descriptive-feature-name
```

### 11. Open a PR

Create a pull request against `main`. CI must pass before merge.

---

## Adding a New MCP Server/Tool

See also: [docs/technical/mcp.md](technical/mcp.md) for the full guide.

### Quick Checklist

1. **Create route file** — `app/Mcp/{Domain}/{Domain}McpRoute.php` with `PATH` constant
2. **Create tool classes** — `app/Mcp/Tools/{Domain}/{Action}Tool.php`: extend `Laravel\Mcp\Server\Tool`, add `#[Name]`/`#[Description]` (and `#[IsReadOnly]`) class attributes, declare parameters in `schema(JsonSchema $schema): array`, and implement `handle(Request $request): Response` returning `Response::structured(...)`
3. **Create server class** — `app/Mcp/Servers/{Domain}Server.php` with `$tools`, `$resources`, and `$prompts` arrays
4. **Wire route** — `routes/ai.php`: `Mcp::web(Path::PATH, Server::class)->middleware(...)`
5. **Register** — `app/Mcp/McpServerRegistry.php`: add entry to `servers()` array
6. **Write tests** — `tests/Feature/Mcp/{Domain}McpServerTest.php`
7. **Run gates** — `composer lint && composer larastan && composer test`

---

## Running Quality Gates

### Before Every Commit

```bash
# All three gates in order
composer lint      # Pint style check (read-only)
composer larastan  # PHPStan level 9
composer test      # Pest test suite
```

### Auto-Fix Style Issues

```bash
./vendor/bin/pint
```

### Run a Single Test

```bash
./vendor/bin/pest tests/Feature/Mcp/ReceiptMcpServerTest.php
./vendor/bin/pest --filter="creates a receipt"
```

### Run with Verbose Output

```bash
./vendor/bin/pest -v
composer larastan -v
```

---

## Database Changes

### Creating a Migration

```bash
php artisan make:migration add_new_column_to_table_name
```

### Migration Best Practices

- Use descriptive names
- Add foreign key constraints
- Add indexes for frequently queried columns
- Include `softDeletes()` and `timestamps()` where appropriate

### Running Migrations

```bash
# Apply all pending migrations
php artisan migrate

# Rollback the last batch
php artisan migrate:rollback

# Refresh everything (drop all tables, re-run)
php artisan migrate:fresh

# Fresh + seed
php artisan migrate:fresh --seed
```

### After Schema Changes

- Update the model's `$fillable` and `$casts`
- Update the model's Factory (`database/factories/`)
- Update any affected tests
- Run `composer larastan` to catch missing generics or type issues

---

## Debugging

### Laravel Tinker (REPL)

```bash
php artisan tinker

>>> User::find(1)
>>> Recipe::with('ingredients')->first()
>>> Receipt::query()->forAuthUser()->count()
```

### Logs

```bash
# Tail recent logs
tail -f storage/logs/laravel.log

# View last 100 lines
tail -n 100 storage/logs/laravel.log

# Search logs
grep "error" storage/logs/laravel.log
```

### Xdebug

1. Ensure Docker is running with Xdebug enabled (configured in `docker-compose.yml`)
2. Set your IDE to listen on port **9003**
3. Set breakpoints in PHP files
4. Trigger requests in the browser

### Sentry

Check the Sentry dashboard for production exceptions. Configuration is in `config/sentry.php`.

### Common Debug Commands

```bash
# List all routes
php artisan route:list

# Show route details
php artisan route:list --path=receipts

# Check config
php artisan config:show database

# Clear all caches
php artisan optimize:clear

# Check queued jobs
php artisan queue:monitor
```

---

## Deployment

### Production Build

```bash
# 1. Build frontend assets
npm run build

# 2. Optimize Laravel
php artisan optimize

# 3. Link storage
php artisan storage:link

# 4. Run migrations
php artisan migrate --force
```

### After Deployment

```bash
# Restart queue workers
php artisan queue:restart

# Clear cache if needed
php artisan optimize:clear
php artisan optimize
```

### Production-Specific Commands

```bash
# Run scheduler manually (for testing)
php artisan schedule:run

# Run a specific scheduled command
php artisan ebird:import --user=1 --username=... --password=...

# Check application health
php artisan about
```

---

## PR Workflow

### Before Opening a PR

```bash
# 1. Update from main
git checkout main
git pull origin main
git checkout feat/my-feature
git rebase main

# 2. Run quality gates
composer lint && composer larastan && composer test

# 3. Review your own diff
git diff main

# 4. Push
git push origin feat/my-feature --force-with-lease
```

### PR Requirements

- [ ] CI passes (Pint + Larastan)
- [ ] All tests pass
- [ ] Coverage metadata on new tests
- [ ] No debug code (`dd`, `dump`, `var_dump`)
- [ ] No suppressed PHPStan errors without justification
- [ ] New models have factories
- [ ] Schema changes have migrations
- [ ] Commits are small and descriptive
- [ ] No dependency changes without approval

### After Merge

```bash
git checkout main
git pull origin main
git branch -d feat/my-feature
```

---

## Common Commands Reference

| Task | Command |
|------|---------|
| Start dev environment | `./vendor/bin/sail up` |
| Start Vite dev server | `npm run dev` |
| Run all tests | `composer test` |
| Run single test | `./vendor/bin/pest path/to/Test.php` |
| Auto-fix style | `./vendor/bin/pint` |
| Check style | `composer lint` |
| Static analysis | `composer larastan` |
| Run migrations | `php artisan migrate` |
| Fresh database | `php artisan migrate:fresh --seed` |
| Interactive REPL | `php artisan tinker` |
| List routes | `php artisan route:list` |
| Clear caches | `php artisan optimize:clear` |
| Tail logs | `tail -f storage/logs/laravel.log` |
| Make model | `php artisan make:model Name -mf` |
| Make migration | `php artisan make:migration description` |
| Make Livewire | `php artisan livewire:make ComponentName` |
| Make command | `php artisan make:command CommandName` |
| Run scheduler | `php artisan schedule:run` |
