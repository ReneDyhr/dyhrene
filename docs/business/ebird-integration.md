# eBird Integration

How Dyhrene imports bird observation data from eBird and enriches it via Merlin.

---

## Overview

Dyhrene integrates with [eBird](https://ebird.org/) (Cornell Lab of Ornithology) to automatically import the user's bird observations. The integration:

1. Logs into eBird via Cornell's CAS authentication
2. Scrapes the world lifelist page to discover all observed species codes
3. Downloads per-species CSV data containing all observations
4. Creates/updates Species and Observation records
5. Enriches observations with checklist metadata from Merlin

## Configuration

### Environment Variables

In `.env`:

```env
EBIRD_USERNAME=your_ebird_username
EBIRD_PASSWORD=your_ebird_password
```

### Services Config

In `config/services.php`:

```php
'ebird' => [
    'username' => \env('EBIRD_USERNAME', ''),
    'password' => \env('EBIRD_PASSWORD', ''),
],
```

Credentials can also be passed as command-line options (overrides config):

```bash
php artisan ebird:import --username="user@example.com" --password="secret"
```

## The Import Command

### Signature

```
ebird:import {--user=1} {--username=} {--password=}
```

### Options

| Option | Default | Description |
|--------|---------|-------------|
| `--user` | `1` | The Laravel user ID to import for |
| `--username` | (from config) | eBird login username |
| `--password` | (from config) | eBird login password |

### Schedule

In `routes/console.php`:

```php
Schedule::command('ebird:import')->dailyAt('06:00');
```

Runs every morning at 6:00 AM, importing any new observations since the last run.

## Import Pipeline Details

The import is handled by `App\Services\Ebird\EbirdImportService`:

### Step 1: Login (Cornell CAS)

```
GET https://secure.birds.cornell.edu/cassso/login?service=...
  → Extract CSRF "execution" token from hidden input
  → POST login form with username, password, execution token
  → Session cookies stored in Guzzle CookieJar
```

If login fails, the entire import is aborted and the error is logged.

### Step 2: Fetch Species Codes

```
GET https://ebird.org/lifelist/world
  → Parse HTML for `spp=XXXXX` patterns in links
  → Deduplicate: array_unique(codes)
```

Returns a list of eBird species codes (e.g., `mallar3`, `comrav`). If no codes are found, the import stops.

### Step 3: Download Per-Species CSVs

For each species code, download the observation history:

```
GET https://ebird.org/lifelist?r=world&spp={code}&time=life&fmt=csv
  → Parse CSV: header row → column names
  → Extract: Common Name, Scientific Name, Taxon Order, SubID, Date, Count, Location, S/P
```

### Step 4: Create/Update Species Records

```php
Species::query()->firstOrCreate(
    ['common_name' => $commonName, 'user_id' => $user->id],
    ['scientific_name' => $scientificName, 'ebird_code' => $code,
     'taxonomic_order' => $taxonomicOrder]
);
```

If the species already exists but lacks an `ebird_code`, it's updated.

### Step 5: Create Observation Records

```php
Observation::query()->firstOrCreate(
    ['ebird_submission_id' => $submissionId],
    ['species_id' => $species->id, 'user_id' => $user->id,
     'observed_at' => $date, 'count' => $count,
     'location' => $location, 'state_province' => $stateProvince,
     'source' => 'ebird_import']
);
```

The `ebird_submission_id` field prevents duplicates.

### Step 6: Enrich from Merlin

After all observations are created, the importer fetches checklist metadata from Merlin for observations that lack enrichment data:

```
GET https://ebird.org/merlin/checklist/download?subID={subId}
  → Parse CSV: Start Time, Duration, Distance, Area, Party Size,
               Complete Checklist, Observation Type
  → Update all observations with that submission ID
```

Enriched fields:

| Field | Merlin Source |
|-------|--------------|
| `observed_time` | "Start Time" column |
| `observation_type` | "Observation Type" column |
| `duration_min` | "Duration" column (minutes) |
| `distance_km` | "Distance" column (kilometers) |
| `area_ha` | "Area" column (hectares) |
| `observer_count` | "Party Size" column |
| `complete_checklist` | "Complete Checklist" column (true/false) |

## Import Statistics

The command outputs a summary:

```
Import complete:
  Species created:    12
  Observations created: 287
  Observations enriched: 245
```

If errors occur, they are listed. The import returns `FAILURE` if any error was encountered.

## Data Model

### Species

| Column | Type | Source |
|--------|------|--------|
| `common_name` | string | CSV "Common Name" (Danish convention) |
| `scientific_name` | string | CSV "Scientific Name" |
| `ebird_code` | string | Scraped from lifelist page |
| `taxonomic_order` | int | CSV "Taxon Order" |
| `user_id` | foreign key | Command `--user` option |

### Observation

| Column | Type | Source |
|--------|------|--------|
| `species_id` | foreign key | Matched/previously created Species |
| `user_id` | foreign key | Command `--user` option |
| `ebird_submission_id` | string (unique) | CSV "SubID" |
| `observed_at` | date | CSV "Date" |
| `observed_time` | time | Merlin checklist "Start Time" |
| `count` | int | CSV "Count" |
| `location` | string | CSV "Location" |
| `state_province` | string | CSV "S/P" |
| `observation_type` | string | Merlin "Observation Type" |
| `duration_min` | int | Merlin "Duration" |
| `distance_km` | float | Merlin "Distance" |
| `area_ha` | float | Merlin "Area" |
| `observer_count` | int | Merlin "Party Size" |
| `complete_checklist` | boolean | Merlin "Complete Checklist" |
| `source` | string | Always `"ebird_import"` for automated imports |

## Danish Name Convention

Species common names follow **Danish naming**. This is because:

- The primary user is Danish
- eBird's localized interface returns Danish common names in the CSV export
- The `common_name` field stores the Danish names directly from the eBird CSV

Examples:

| Danish Name | English Name | Scientific Name |
|-------------|-------------|-----------------|
| Gråand | Mallard | *Anas platyrhynchos* |
| Solsort | Common Blackbird | *Turdus merula* |
| Musvit | Great Tit | *Parus major* |
| Gråspurv | House Sparrow | *Passer domesticus* |

Note: The `scientific_name` field stores the standardized Latin name regardless of language.

## Error Handling

The import service is resilient to partial failures:

- **Login failure:** Aborts entire import, logs error
- **Species code fetch failure:** Aborts entire import, logs error
- **Per-species CSV failure:** Skips that species, continues with others, logs warning
- **Merlin checklist failure:** Skips enrichment for that submission, logs warning, continues
- **CSV parsing failure:** Individual rows with mismatched column count are skipped

All errors are logged via Laravel's `Log` facade and returned in the `errors` array of the import stats.

## Testing

The eBird import service can be tested with mocked HTTP responses:

```php
Http::fake([
    'secure.birds.cornell.edu/*' => Http::response('<html>...execution token...</html>'),
    'ebird.org/lifelist/world' => Http::response('<html>...spp=mallar3...</html>'),
    'ebird.org/lifelist*spp=*' => Http::response("Common Name,Scientific Name,...\nGråand,Anas platyrhynchos,..."),
    'ebird.org/merlin/*' => Http::response("Start Time,Duration,...\n07:30,60,..."),
]);
```

In unit tests, mock the `EbirdImportService` to avoid network calls entirely.
