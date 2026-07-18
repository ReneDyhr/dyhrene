# Feature Catalog

A comprehensive listing of every feature in Dyhrene, organized by domain.

---

## Recipes

The recipes domain is the most mature feature area, providing full CRUD with categorization, tagging, and search.

### Features

| Feature | Description | Livewire Component |
|---------|-------------|-------------------|
| **Recipe Listing** | Paginated list of the user's recipes with favorite toggle, category filtering, and search | `Recipes` |
| **View Recipe** | Full recipe display with ingredients, categories, tags, description, and notes | `SingleRecipe` |
| **Create Recipe** | Form to add a recipe with name, description, note, dynamic ingredient list, tags, and category selection | `AddRecipe` |
| **Edit Recipe** | Modify existing recipe including replacing ingredients, tags, and categories | `EditRecipe` |
| **Search Recipes** | Weighted multi-field search across name, description, note, ingredients, tags, and categories | `SearchRecipe` |
| **Category Filtering** | Browse recipes by category slug | `Categories` |
| **Tag Filtering** | Browse recipes by tag | `Tags` |
| **Favorites** | Toggle a recipe as favorite for quick access; filter by favorites | (scope on Recipe model) |
| **Public/Private** | Mark recipes as public or private | (field on Recipe model) |

### Data Model

- **Recipe** — name, description, note, public flag, favourite flag, soft deletes
- **RecipeIngredient** — lines of ingredients (lines prefixed `#` are section headers)
- **RecipeTag** — free-text tags
- **Category** — named groups with icon, slug, user ownership
- **CategoryRecipe** — pivot linking categories to recipes

### MCP Tools

Available via `mcp/recipes`: list, create, get, update, delete, search, list categories, list tags

---

## Receipts

The receipts domain handles purchase tracking with OCR extraction from uploaded documentation images and PDFs.

### Features

| Feature | Description | Livewire Component |
|---------|-------------|-------------------|
| **Receipt Listing** | List all receipts with date, vendor, total, and line item count | `Receipts\Index` |
| **Upload Receipt** | Create a receipt with header fields, image/PDF upload (max 15 MiB), and OCR extraction | `Receipts\Create` |
| **View Receipt** | Display receipt metadata, line items with category colors, computed totals, and the original scan | `Receipts\Show` |
| **Edit Receipt** | Modify receipt metadata (name, vendor, date, currency) | `Receipts\Edit` |
| **Mass Edit Items** | Batch edit line items across multiple receipts | `Receipts\MassEditItems` |
| **Line Item Categories** | Assign colored categories to receipt line items for spending analysis | (ReceiptCategory model) |
| **OCR Extraction** | Automatic extraction of vendor, date, currency, and line items from uploaded images via N8n pipeline | `N8nReceiptExtractor` |
| **PDF Support** | PDF receipts are converted to images for OCR using `spatie/pdf-to-image` | `ReceiptExtractionFilePreparer` |
| **Duplicate Detection** | `ReceiptDuplicateGuard` prevents importing the same receipt twice | (Support class) |
| **Receipt Image Serving** | Serve stored receipt scans directly from Wasabi with auth check | (route closure) |

### Data Model

- **Receipt** — name, vendor, description, currency, date, file_path, soft deletes
- **ReceiptItem** — name, quantity, amount, category reference (total = quantity × amount)
- **ReceiptCategory** — name, color, user ownership

### MCP Tools

Available via `mcp/receipts`: list, list categories, get items, get items batch, create (with base64 image), update, update items, get image

### Storage

Receipt documentation images and PDFs are stored on **Wasabi** (S3-compatible). Files are served through an authenticated route that verifies user ownership.

---

## Shopping Lists

A real-time collaborative shopping list with WebSocket-powered live updates.

### Features

| Feature | Description | Livewire Component |
|---------|-------------|-------------------|
| **Shopping List View** | Real-time list with active (unchecked) and checked items | `Shopping\ShoppingList` |
| **Add Item** | Add an item with a name (≥ 3 characters). Prefix with `#` for section headers. | (MCP tool / Livewire) |
| **Check / Uncheck** | Toggle item completion status. Section headers cannot be checked. | (MCP tool / Livewire) |
| **Remove Item** | Delete any item from the list (checked, active, or section header) | (MCP tool / Livewire) |
| **Reorder** | Drag or programmatically reorder active items | `ReorderShoppingListItemsTool` |
| **Real-time Sync** | Changes made via MCP tools are broadcast via Reverb to update the Livewire UI instantly | `ShoppingListMcpNotifier` |

### Data Model

- **ShoppingList** — name, order, status (active/checked), user ownership, soft deletes

### MCP Tools

Available via `mcp/shopping-list`: list, add item, remove item, check item, uncheck item, reorder items

### Real-time Architecture

```
MCP Tool makes change → ShoppingListMcpNotifier broadcasts event
→ Reverb WebSocket push → user.{id} channel → Livewire component re-renders
```

---

## 3D Printing

A complete print shop management workflow: customers, materials, jobs with pricing calculations, and activity logging.

### Features

| Feature | Description | Livewire Component |
|---------|-------------|-------------------|
| **Dashboard** | Overview of the print shop with key metrics | `Printing\Index` |
| **Customer Management** | CRUD for customers (name, email, phone, notes) with soft deletes | `PrintCustomers\{Index,Create,Edit}` |
| **Material Types** | Categories of materials (e.g., "PLA", "PETG") with average kWh consumption | `PrintMaterialTypes\{Index,Create,Edit}` |
| **Materials** | Specific materials (e.g., "PLA Silk Gold") with price per kg and waste factor percentage | `PrintMaterials\{Index,Create,Edit}` |
| **Print Jobs** | CRUD for jobs with pieces/plate, plates, grams/plate, hours/plate, labor hours, first-time order flag, markup override | `PrintJobs\{Index,Create,Show,Edit}` |
| **Pricing Calculator** | Pure domain service that calculates: totals (pieces, grams, hours, kWh), costs (material with waste, power, labor, first-time fee), pricing (markup, sales price, price per piece), and profit | `PrintJobCalculator` |
| **Job Locking** | Lock a job to freeze its pricing snapshot (status: draft → locked), storing `calc_snapshot` JSON with all cost/pricing/profit details | (PrintJob model method) |
| **Order Sequences** | Auto-incrementing order numbers per year (YYYY-NNN) | `PrintOrderSequence` |
| **Global Settings** | Electricity rate, wage rate, default markup percentage, first-time order fee — cached (1h TTL) | `PrintSettings\Edit` |
| **Activity Logging** | Audit trail of actions performed on print jobs (who, what, when, metadata) | `PrintActivityLog` |

### Data Model

- **PrintCustomer** — name, email, phone, notes, soft deletes
- **PrintMaterialType** — name, avg_kwh_per_hour
- **PrintMaterial** — material_type_id, name, price_per_kg_dkk, waste_factor_pct, notes, soft deletes
- **PrintJob** — order_no, date, description, internal_notes, customer/material references, pieces_per_plate, plates, grams_per_plate, hours_per_plate, labor_hours, is_first_time_order, avance_pct_override, status (draft/locked), locked_at, calc_snapshot (JSON), soft deletes
- **PrintSetting** — electricity_rate_dkk_per_kwh, wage_rate_dkk_per_hour, default_avance_pct, first_time_fee_dkk (singleton, id=1, cached)
- **PrintOrderSequence** — year, last_number (auto-incrementing)
- **PrintActivityLog** — print_job_id, action, user_id, metadata (JSON)

### Pricing Calculation Flow

```
PrintJob::buildSnapshot()
  → loads PrintMaterial + PrintMaterialType relations
  → loads PrintSetting::current() (cached)
  → assembles input array with all job inputs + settings rates
  → calls PrintJobCalculator::calculate($input)
  → returns comprehensive snapshot with totals, costs, pricing, profit
```

---

## Mail Integration

Fastmail email integration with automatic classification of receipts and payslips.

### Features

| Feature | Description | Livewire Component |
|---------|-------------|-------------------|
| **Inbox View** | Browse and search Fastmail emails | `Mail\Inbox` |
| **Email Retrieval** | Full JMAP client for Fastmail email, mailbox, identity, and session management | `FastmailJmapClient` + services |
| **Classification Pipeline** | Multi-stage automatic classification: metadata → MobilePay → attachment text → N8n | `MailDocumentClassificationService` |
| **Receipt Import** | Automatically import classified receipts into the receipts domain | `MailReceiptImportService` |
| **Classification History** | Track which classifier determined each email's type and confidence | `MailMessageClassification` |

### Classification Pipeline

```
Email arrives
  ↓
1. MetadataMailDocumentClassifier   — checks sender, subject, headers
  ↓ (if inconclusive)
2. MobilePayMailDocumentClassifier   — detects MobilePay payment notifications
  ↓ (if inconclusive)
3. AttachmentTextMailDocumentClassifier — extracts and analyzes attachment text
  ↓ (if inconclusive)
4. N8nMailDocumentClassifier          — calls N8n workflow for AI-based classification
  ↓
Result: { type: receipt|payslip|unknown, confidence: float, source: enum }
```

Classification sources (`MailClassificationSourceEnum`): `metadata`, `mobilepay`, `attachment_text`, `n8n`, `manual` — the `manual` source is recorded when a classification is set by hand rather than by the pipeline.

### Data Model

- **MailMessageClassification** — fastmail_email_id, document_type (enum: receipt/payslip/unknown), confidence (float), source (enum: metadata/mobilepay/attachment_text/n8n/manual), classified_at, receipt_id, processed_at

### CLI Commands

- `mail:classify` — run the classification pipeline
- `mail:import-receipts` — import classified receipts into the receipts domain

### Fastmail JMAP Client

A complete JMAP implementation with:
- Session management (`FastmailSession`)
- Email queries and retrieval (`FastmailEmailService`, `EmailQuery`)
- Mailbox operations (`FastmailMailboxService`)
- Identity management (`FastmailIdentityService`)
- Typed DTOs for all JMAP responses
- Custom exceptions for API and configuration errors

---

## Bird Species & Observations

Bird watching log with Danish species names and automated eBird import.

### Features

| Feature | Description | Livewire Component |
|---------|-------------|-------------------|
| **Species Index** | Browse all bird species with observation counts | `Species\SpeciesIndex` |
| **Species Detail** | View a species with all observations, sorted by date descending | `Species\SpeciesShow` |
| **Add Observation** | Log a new bird sighting: date, location, count, province | `Species\AddObservation` |
| **eBird Import** | Automated daily import from eBird: login, scrape species codes, download CSVs, create species/observations | `ebird:import` command |
| **Merlin Enrichment** | After import, fetch checklist metadata (time, duration, distance, party size, observation type) from Merlin | `EbirdImportService::enrichObservations()` |

### Data Model

- **Species** — common_name (Danish convention), scientific_name, ebird_code, taxonomic_order, user_id
- **Observation** — species_id, user_id, observed_at, observed_time, count, location, state_province, ebird_submission_id, observation_type, duration_min, distance_km, area_ha, observer_count, complete_checklist, source

### Danish Name Convention

Species common names follow the **Danish** convention. For example:
- "Gråand" not "Mallard"
- "Solsort" not "Common Blackbird"

This is because the primary user is Danish and the eBird import sources Danish common names from the localized eBird interface.

### Automation

The `ebird:import` command is scheduled daily at 06:00 via `routes/console.php`:

```php
Schedule::command('ebird:import')->dailyAt('06:00');
```

It uses credentials from `EBIRD_USERNAME` and `EBIRD_PASSWORD` in `.env` (or `config/services.php`).

See [ebird-integration.md](ebird-integration.md) for full details.

---

## Storage

A simple inventory system for physical storage locations.

### Features

| Feature | Description | Livewire Component |
|---------|-------------|-------------------|
| **Storage Locations** | View all storage locations and their contained items | `Storage` |
| **Storage Items** | Items with name, quantity, and sort order within each location | (Storage model relation) |

### Data Model

- **Storage** — name
- **StorageItem** — storage_id, name, quantity, sort_order

---

## Settings

Application configuration UI.

### Features

| Feature | Description | Livewire Component |
|---------|-------------|-------------------|
| **Category Management** | Create, edit, and delete recipe categories with icons | `Settings\Categories` |
| **MCP Connection Info** | Display MCP server endpoints, OAuth metadata, and connection instructions for AI clients | `Settings\McpConnection` |

### MCP Settings Detail

The MCP settings UI shows:
- Each registered MCP server (from `McpServerRegistry`)
- Server endpoint URLs (POST JSON-RPC paths)
- Shared OAuth configuration: authorization server metadata URL, client registration URL, token endpoint
- Per-server resource metadata and scope requirements
