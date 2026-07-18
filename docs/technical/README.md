# Technical Documentation

Technical deep-dives for the Dyhrene application — local setup, architecture, quality assurance, and MCP integration.

## Contents

| Document | Description |
|----------|-------------|
| [architecture.md](architecture.md) | Layered architecture, domain-driven design, MCP internals, auth layers, event system, service patterns, route surface design, and full directory structure |
| [quality.md](quality.md) | Code style (Pint), static analysis (PHPStan level 9), class suffix rules, Pest testing conventions, CI pipeline, and code review guidelines |
| [mcp.md](mcp.md) | MCP integration deep-dive: server registration, tool structure, auth flow, route wiring, available servers and tools, adding new servers |

## Local Development Setup

### Prerequisites

- **Docker & Docker Compose** — for Laravel Sail
- **PHP 8.5** — if running locally without Docker
- **Node.js 20+** — for Vite asset compilation
- **Composer 2** — dependency management

### First-Time Setup

```bash
# 1. Clone the repository
git clone <repo-url> dyhrene
cd dyhrene

# 2. Install PHP dependencies
composer install

# 3. docker-compose.yml is committed (services: laravel.test, mariadb, phpmyadmin) — no sail:install needed

# 4. Copy environment file and configure
cp .env.example .env
# Edit .env with your database credentials, storage keys, etc.

# 5. Generate application key
php artisan key:generate

# 6. Start the Docker environment
./vendor/bin/sail up -d

# 7. Run database migrations
./vendor/bin/sail artisan migrate

# 8. Install and compile frontend assets
npm install
npm run dev
```

### Daily Development

```bash
# Start Sail (if not already running)
./vendor/bin/sail up -d

# Watch frontend for changes
npm run dev

# Access the app at http://localhost
```

## Docker / Sail Configuration

The application uses Laravel Sail with the following services defined in `docker-compose.yml`:

| Service | Purpose | Port |
|---------|---------|------|
| laravel.test | PHP application server | `${APP_PORT:-80}` |
| mariadb | MariaDB 10 (primary database) | 3306 |
| phpmyadmin | Database admin UI | — |

Additional Dockerfiles for production deployment:

- `Dockerfile.base.production` — base image
- `Dockerfile.production` — production image

### Sail Custom Commands

```bash
# Run Artisan commands
./vendor/bin/sail artisan <command>

# Run Composer
./vendor/bin/sail composer <command>

# Run NPM
./vendor/bin/sail npm <command>

# Run Pest tests
./vendor/bin/sail test
```

### Xdebug

Xdebug is configured in `docker-compose.yml`. Attach your IDE debugger to port **9003** on localhost for step debugging. Ensure your IDE is listening for Xdebug connections.

## Environment Variables

### Required

| Variable | Purpose |
|----------|---------|
| `APP_KEY` | Application encryption key (generated via `key:generate`) |
| `DB_CONNECTION` | Database driver (mysql, sqlite) |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database user |
| `DB_PASSWORD` | Database password |

### Storage (Wasabi / S3)

| Variable | Purpose |
|----------|---------|
| `WAS_ACCESS_KEY_ID` | S3 access key |
| `WAS_SECRET_ACCESS_KEY` | S3 secret key |
| `WAS_DEFAULT_REGION` | S3 region (e.g., eu-central-1) |
| `WAS_BUCKET_FINAL` | S3 bucket name |
| `WAS_URL` | Wasabi endpoint URL |

### eBird Integration

| Variable | Purpose |
|----------|---------|
| `EBIRD_USERNAME` | eBird login username (for scheduled import) |
| `EBIRD_PASSWORD` | eBird login password (for scheduled import) |

These are loaded in `config/services.php` under the `ebird` key.

### Reverb (WebSockets)

| Variable | Purpose |
|----------|---------|
| `REVERB_APP_ID` | Reverb application ID |
| `REVERB_APP_KEY` | Reverb app key |
| `REVERB_APP_SECRET` | Reverb app secret |
| `REVERB_HOST` | Reverb server host |
| `REVERB_PORT` | Reverb server port |

### Sentry

| Variable | Purpose |
|----------|---------|
| `SENTRY_LARAVEL_DSN` | Sentry DSN for error tracking |
| `SENTRY_TRACES_SAMPLE_RATE` | Traces sampling rate (0.0 – 1.0) |

### Fastmail (Mail Integration)

Loaded from `config/fastmail.php` (not `config/services.php`):

| Variable | Purpose |
|----------|---------|
| `FASTMAIL_API_TOKEN` | Fastmail API authentication token |
| `FASTMAIL_EMAIL` | Address to read mail for (To/Cc) |
| `FASTMAIL_FILTER_TO_RECIPIENT` | When true, only list messages To/Cc `FASTMAIL_EMAIL` (default: true) |
| `FASTMAIL_DEFAULT_MAILBOX_ROLE` | JMAP mailbox role opened by default in the Mail UI (default: archive) |
| `FASTMAIL_SESSION_URL` | JMAP session endpoint (default: https://api.fastmail.com/jmap/session) |
| `FASTMAIL_SESSION_CACHE_TTL` | Session cache TTL in seconds (default: 3600) |

### N8n (Webhooks)

| Variable | Purpose |
|----------|---------|
| `N8N_WEBHOOK_URL` | N8n webhook for receipt OCR extraction |
| `N8N_CLASSIFY_WEBHOOK_URL` | N8n webhook for classifying mail attachments as receipt / payslip / unknown |

### OpenAI

| Variable | Purpose |
|----------|---------|
| `OPENAI_API_KEY` | OpenAI API key |

## Build Pipeline

### Frontend Assets (Vite)

Entry points configured in `vite.config.js`:
- `resources/scss/app.scss` → compiled SCSS
- `resources/js/app.js` → bundled JavaScript (Echo + Pusher client)

```bash
# Development (hot module replacement)
npm run dev

# Production build
npm run build
```

Output goes to `public/build/` and is served by the `@vite()` Blade directive.

### Asset Structure

```
resources/
├── scss/           SCSS partials and variables
├── css/            Plain CSS files
├── js/             JavaScript (Echo, Pusher client)
└── views/          Blade templates
    ├── livewire/   Livewire component templates
    ├── components/ Reusable Blade components
    ├── mail/       Mail-related views
    ├── mcp/        MCP-related views
    ├── receipts/   Receipt-specific views
    └── vendor/     Published vendor views
```

## Deployment Notes

### Production Build

```bash
# Build frontend for production
npm run build

# Optimize Laravel
php artisan optimize

# Ensure storage is linked
php artisan storage:link
```

### Scheduler

The application scheduler is defined in `routes/console.php`. Run it every minute via cron:

```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks:
- **ebird:import** — runs daily at 06:00

### Queue Worker

For background jobs (PDF rendering, image processing, OpenAI calls):

```bash
php artisan queue:work
```

In production, use a process monitor like Supervisor.

## Debugging

### Sentry

Exceptions and performance traces are captured by Sentry automatically. Configuration is in `config/sentry.php`. Check the Sentry dashboard for error reports.

### Logs

Application logs are written to `storage/logs/laravel.log`:

```bash
# Tail logs in real time
tail -f storage/logs/laravel.log

# View recent entries
tail -n 100 storage/logs/laravel.log
```

### Laravel Tinker

Interactive REPL for database queries and code exploration:

```bash
php artisan tinker

# Example: find a user
>>> User::find(1)

# Example: check model state
>>> Recipe::count()
```

### Xdebug

1. Ensure Docker is running with Xdebug enabled
2. Configure your IDE to listen on port 9003
3. Set breakpoints and trigger requests

### Common Commands

```bash
# List all routes
php artisan route:list

# Clear caches
php artisan optimize:clear

# Check application environment
php artisan env

# View config values
php artisan config:show app

# Run a specific test with verbose output
./vendor/bin/pest tests/Feature/Mcp/ReceiptMcpServerTest.php -v
```

## Further Reading

- [Architecture](architecture.md) — codebase structure and patterns
- [Quality Gates](quality.md) — coding standards and static analysis
- [MCP Integration](mcp.md) — AI client access to app data
- [Models & ERD](../models.md) — database schema documentation
- [Developer Workflows](../workflows.md) — common task recipes
