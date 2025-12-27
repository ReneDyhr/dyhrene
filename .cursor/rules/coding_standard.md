# Project Coding Standards & Guidelines

## Technology Stack

### Backend
- **PHP**: ^8.4 (use PHP 8.4 features)
- **Laravel**: ^12.00
- **Livewire**: ^3.5 (for reactive components)
- **Laravel Reverb**: ^1.0 (WebSocket server)
- **Laravel Sanctum**: ^4.0 (API authentication)

### Frontend
- **Vite**: ^6.0.6 (build tool)
- **Livewire**: ^3.5 (reactive UI components)
- **Sass**: ^1.83.0 (CSS preprocessing)

### Testing & Quality
- **Pest PHP**: Latest (testing framework)
- **PHPStan**: Level 9 (strictest static analysis)
- **Laravel Pint**: ^1.10 (code formatting with PER preset)
- **Larastan**: ^3.1 (Laravel-specific PHPStan rules)

### Additional Tools
- **PHPStan Strict Rules**: ^2.0
- **ShipMonk PHPStan Rules**: ^4.1
- **Symplify PHPStan Rules**: ^14.6
- **Ekino PHPStan Banned Code**: ^3.0

## 1. Coding Standards

### 1.1 PHP Standards
- Use PHP 8.4 features (typed properties, enums, match expressions, etc.)
- Always declare strict types: `declare(strict_types=1);`
- Use type hints for all parameters and return types
- Prefer readonly properties when appropriate
- Use array shapes and strict typing via PHPStan

### 1.2 Code Formatting
- Follow Laravel Pint configuration (pint.json)
- Uses PER (PSR-12 Extended) preset
- Enforces strict formatting rules (see pint.json for details)
- Run `composer lint` after making changes

### 1.3 Static Analysis
- PHPStan Level 9 (highest level) is enforced
- All code must pass PHPStan analysis
- Run `composer larastan` after making changes
- Strict rules enabled:
  - No loose comparisons
  - Enforce native return type hints
  - Forbid method calls on mixed types
  - Require explicit types everywhere

### 1.4 General Guidelines
- Use single quotes for strings (unless containing single quotes)
- Use short array syntax: `[]` instead of `array()`
- Always use trailing commas in multiline arrays/arguments
- Use strict comparison operators (`===`, `!==`)
- Prefer null coalescing operator (`??`) over ternary
- Use match expressions over switch when possible

## 2. Project Structure & Architecture

### 2.1 General Rules
- Delete `.gitkeep` files when adding actual files to directories
- Stick to existing structure—do not create new folders
- Avoid `DB::` facade; use `Model::query()` only
- No dependency changes without approval
- Follow PSR-4 autoloading standards

### 2.2 Directory Conventions

#### app/Http/Controllers
- No abstract or base controllers
- Keep controllers thin—delegate to Actions
- Use dependency injection for Actions

#### app/Http/Requests
- Always use FormRequest classes for validation
- Name requests with action prefixes: `Create`, `Update`, `Delete`
- Example: `CreateTodoRequest`, `UpdateTodoRequest`, `DeleteTodoRequest`

#### app/Actions
- Use Actions pattern for business logic
- Name Actions with verbs: `CreateTodoAction`, `UpdateTodoAction`
- Actions should be single-purpose and testable
- Example usage:
```php
public function store(CreateTodoRequest $request, CreateTodoAction $action)
{
    $user = $request->user();
    $action->handle($user, $request->validated());
}
```

#### app/Models
- Use Eloquent models
- Define relationships explicitly
- Use type hints for all properties
- Generate a Factory for each model

## 3. Testing

### 3.1 Testing Framework
- Use Pest PHP for all tests
- All code must be tested
- Do not remove tests without approval
- Run `composer test` before finalizing changes

### 3.2 Test Quality Checks
- Run `composer lint` after changes
- Run `composer larastan` after changes
- Run `composer test` before finalizing
- All tests must pass

### 3.3 Test Directory Structure
- **Console Commands**: `tests/Feature/Console`
- **Controllers**: `tests/Feature/Http`
- **Actions**: `tests/Unit/Actions`
- **Models**: `tests/Unit/Models`
- **Jobs**: `tests/Unit/Jobs`

### 3.4 Test Requirements
- Generate a `{Model}Factory` with each model
- Write tests for all new functionality
- Use factories for test data generation
- Test both happy paths and edge cases

## 4. Database & Eloquent

### 4.1 Query Building
- **Never use** `DB::` facade
- **Always use** `Model::query()` for query building
- Use Eloquent relationships instead of manual joins when possible
- Prefer eager loading to avoid N+1 queries

### 4.2 Migrations
- Use descriptive migration names
- Include both `up()` and `down()` methods
- Use foreign key constraints where appropriate

## 5. Code Quality Workflow

### 5.1 Before Committing
1. Run `composer lint` to check code formatting
2. Run `composer larastan` to check static analysis
3. Run `composer test` to ensure all tests pass
4. Fix any issues before finalizing

### 5.2 Code Review Checklist
- [ ] All PHPStan errors resolved (Level 9)
- [ ] Code formatted with Pint
- [ ] All tests passing
- [ ] No `DB::` facade usage
- [ ] Actions pattern used for business logic
- [ ] FormRequests used for validation
- [ ] Strict types declared
- [ ] Type hints on all methods

## 6. Task Completion Requirements

- Follow all rules before marking tasks complete
- Ensure all quality checks pass
- All code must be tested
- No PHPStan errors or warnings
- Code must be properly formatted 