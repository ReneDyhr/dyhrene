# BirdNET Station Configuration

## Current Issue

The BirdNET acoustic monitoring station at **Jels Skovvej 17, 6630 Rødding** (~55.38°N, 9.15°E) currently sends bogus
coordinates in its detection metadata. The latitude/longitude fields contain a Copenhagen placeholder
(`55.6761, 12.5683`) — approximately 200 km away from the actual site location.

## Required Fix (External Ops)

The BirdNET station's configuration file (external to this application) must be updated to send the correct
coordinates in detection metadata:

- **Latitude:** `55.38`
- **Longitude:** `9.15`

The station wrapper/script that POSTs to `/api/species/upload` should populate the `latitude` and `longitude`
fields in the metadata JSON with these values.

## How The Application Handles This

The application does **not trust per-detection coordinates** for any solar or geographic calculations.
All location-dependent calculations use the authoritative `sites` table record:

```
sites
  name      = "Jels Skovvej 17"
  latitude  = 55.38
  longitude = 9.15
  timezone  = Europe/Copenhagen
```

Per-detection latitude/longitude values from `birdnet_detections` are kept for audit purposes only and are
never read by:
- `SolarCalculator` (uses `sites.latitude/longitude`)
- `ObservationLocalizer` (uses `sites.timezone`)
- Any chart or aggregate

The application is resilient to incorrect per-detection coordinates regardless of whether the station config
is fixed. However, fixing the station config will make the raw data accurate for future auditing.

## Related Code

- `app/Http/Controllers/BirdnetDetectionController.php` — resolves site, stores per-detection coords in `birdnet_detections` only
- `app/Domain/Nature/SolarCalculator.php` — uses site coordinates exclusively
- `app/Domain/Nature/ObservationLocalizer.php` — uses site timezone exclusively
- `database/seeders/SiteSeeder.php` — seeds the authoritative site record
