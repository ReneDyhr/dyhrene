# Wild Edibles Gathering Map

**Status:** Product decisions confirmed; implementation-ready after the technical checklist below
**Domain:** Nature / foraging
**Audience:** Product and implementation
**Primary user:** Authenticated Dyhrene user

## Problem Statement

The user currently has no structured way to remember where wild edible foods can be gathered. Information about mushrooms, berries, fruit, and similar edibles is easy to lose, difficult to browse geographically, and not connected to seasonal availability or a history of actual pickings.

The user needs one private map-based place to:

- distinguish gathering spots by edible type;
- find all spots for a selected type, such as mushrooms;
- find spots that are ready to pick in one or more selected months, such as September;
- store practical information about each edible spot, including its name, description, location, and photos; and
- record individual picking events with a date, location, and comment.

## Solution

Add a private **Wild Edibles** domain with a map as its primary index. Each **Wild Edible** is a named edible gathering entry tied to one map coordinate. A map marker uses a stable icon and colour for the entry's **Edible Type**.

The first release supports four types:

- Mushroom
- Berry
- Fruit
- Other

Each Wild Edible may have an optional picking season represented by an inclusive start and end month. The map can filter entries by one or more Edible Types and by one or more calendar months. Selecting September, for example, shows every entry whose season includes September.

Each Wild Edible has a detail page with its metadata, map location, photos, and **Picking Logs**. A Picking Log records when the user picked from the entry, the registered picking coordinates, and an optional comment.

All data is scoped to the authenticated user and is private by default.

## User Stories

1. As an authenticated user, I want to open a Wild Edibles map, so that I can see my known gathering spots geographically.
2. As an authenticated user, I want every map marker to display the icon for its Edible Type, so that mushrooms, berries, fruit, and other edibles are easy to distinguish.
3. As an authenticated user, I want a visible map legend, so that I can understand the marker icons without opening every entry.
4. As an authenticated user, I want to filter the map by Mushroom, Berry, Fruit, or Other, so that I can focus on the edible type I am looking for.
5. As an authenticated user, I want to select multiple Edible Types, so that I can compare several kinds of edible at once.
6. As an authenticated user, I want to filter the map by one calendar month, so that I can see what is ready to pick during that month.
7. As an authenticated user, I want to select multiple calendar months, so that I can plan gathering across a period such as September through November.
8. As an authenticated user, I want type and month filters to work together, so that I can see only the relevant subset, such as mushrooms ready in September.
9. As an authenticated user, I want to clear filters, so that I can return to the complete map.
10. As an authenticated user, I want the map to show a useful empty state when no entry matches the filters, so that I know the filters were applied rather than the page failing.
11. As an authenticated user, I want to create a Wild Edible, so that I can add a new gathering spot to the map.
12. As an authenticated user, I want to choose an Edible Type when creating an entry, so that the map can assign the correct marker.
13. As an authenticated user, I want to enter a name such as a mushroom species or simply “Apples”, so that the entry reflects the level of identification I have available.
14. As an authenticated user, I want to enter a description, so that I can record useful notes about the spot, habitat, access, or quantity.
15. As an authenticated user, I want to select a location by placing a marker on a map, so that I can record the gathering spot accurately.
16. As an authenticated user, I want to adjust a selected location before saving, so that an accidental map click does not create an incorrect spot.
17. As an authenticated user, I want an optional human-readable location name, so that I can recognise the spot without relying only on coordinates.
18. As an authenticated user, I want to enter the months in which an edible is ready for picking, so that the map can support seasonal planning.
19. As an authenticated user, I want to record an availability period that crosses the end of the year, such as November through February, so that winter seasons are represented correctly.
20. As an authenticated user, I want to leave the season unspecified when I do not know it, so that I can still save the gathering spot without inventing seasonal data.
21. As an authenticated user, I want to mark an entry as available all year, so that it appears for every month filter without entering twelve separate values.
22. As an authenticated user, I want to add multiple photos to a Wild Edible, so that I can document the edible and recognise the spot later.
23. As an authenticated user, I want to view photos on the detail page, so that I can inspect the entry without downloading files manually.
24. As an authenticated user, I want to soft-delete an obsolete photo, so that the entry remains accurate without physically deleting the source image.
25. As an authenticated user, I want to edit a Wild Edible, so that I can correct its name, description, season, location, type, or photos.
26. As an authenticated user, I want to delete a Wild Edible, so that obsolete gathering spots no longer appear on my map.
27. As an authenticated user, I want to open a marker and see a concise summary, so that I can quickly identify the edible and open its details.
28. As an authenticated user, I want the marker popup to expose the entry's name and type, so that I can distinguish nearby entries.
29. As an authenticated user, I want to open the full entry from a marker popup, so that I can read its description and picking history.
30. As an authenticated user, I want to see the entry's season on its detail page, so that I can understand why it appears for a month filter.
31. As an authenticated user, I want to log a Picking, so that I can keep a history of when I gathered from a spot.
32. As an authenticated user, I want the picking date to default to today, so that logging a current picking is quick.
33. As an authenticated user, I want the picking location to default to the Wild Edible's registered coordinates, so that I do not have to select the same spot again.
34. As an authenticated user, I want to adjust the picking location when necessary, so that the log reflects where I actually picked if it differed from the saved spot.
35. As an authenticated user, I want to add an optional comment to a Picking, so that I can record observations such as quantity, quality, or access conditions.
36. As an authenticated user, I want to see all Pickings for an entry in reverse chronological order, so that the latest gathering information is easiest to find.
37. As an authenticated user, I want the picking location to remain meaningful even if I later edit the main gathering spot, so that historical logs are not silently changed.
38. As an authenticated user, I want all Wild Edibles, photos, and Pickings to be private to my account, so that sensitive gathering locations are not exposed.
39. As an authenticated user, I want unauthenticated visitors to be denied access to the feature, so that the map and exact coordinates are protected.
40. As an authenticated user, I want validation errors to identify missing or invalid fields, so that I can correct an entry without losing the rest of the form.

## Implementation Decisions

### Domain vocabulary

- **Wild Edible:** A named edible gathering entry at one geographic location. One edible may be recorded more than once when it exists at different locations; each record is its own map marker.
- **Gathering Spot:** The geographic location belonging to a Wild Edible. The MVP does not introduce a separate reusable spot entity.
- **Edible Type:** A controlled enum used for filtering, marker iconography, and display labels.
- **Picking:** One historical event where the user gathered the Wild Edible.
- **Picking Log:** The persisted record of a Picking.
- **Season:** An optional inclusive calendar-month interval describing when the Wild Edible is ready to pick.

### Data model

Introduce three explicit Eloquent models and tables. Do not use a polymorphic relation; Dyhrene currently uses explicit foreign keys.

#### Wild Edible

Required and optional data:

- `user_id` — owner;
- `type` — backed enum: `mushroom`, `berry`, `fruit`, or `other`;
- `name` — required, user-entered display name;
- `description` — nullable text;
- `location_name` — nullable human-readable place name;
- `latitude` and `longitude` — required geographic coordinates;
- `season_start_month` and `season_end_month` — nullable integers from 1 to 12;
- `season_all_year` — required boolean; `false` means either a complete partial season or an unspecified season, while `true` means available in every month;
- timestamps and soft-delete support.

Use a decimal coordinate representation with sufficient precision for map display. Coordinates must be validated within the legal latitude and longitude ranges.

A Wild Edible has many photos and many Pickings, and belongs to one User.

#### Wild Edible Photo

Persist one row per uploaded image with:

- `wild_edible_id`;
- private storage path;
- original file name;
- MIME type;
- file size;
- preserved source image metadata, including EXIF metadata;
- timestamps.

Use the existing S3-compatible Wasabi storage approach and an authenticated file-serving route. Photos are image-only uploads in the MVP. Each upload control accepts exactly one image; additional images are added through subsequent uploads. There is no batch or multi-file upload.

The upload contract is:

- accepted image formats: JPEG, PNG, GIF, and WebP;
- SVG is rejected even though it is technically an image MIME type, because it can contain active content;
- maximum size: 10 MiB per image, matching the existing inventory upload limit;
- the original uploaded bytes and embedded metadata are preserved; the application must not strip EXIF metadata or re-encode the image;
- generated private storage names are used; the user-provided filename is retained only as display/download metadata and is sanitized before use in a response header;
- a photo may be hidden through a soft-delete operation, but its object is not physically removed at that point.

Photos have soft-delete support. A soft-deleted Wild Edible hides its photos and Pickings from normal views but keeps them restorable. A permanent deletion of a Wild Edible permanently deletes its related photo records and removes the related Wasabi objects. No other operation may physically delete a photo object.

#### Picking

Persist:

- `wild_edible_id`;
- `user_id`;
- `picked_at` — required date;
- `latitude` and `longitude` — required coordinates copied from the Wild Edible by default;
- `comment` — nullable text;
- timestamps.

The Picking stores a coordinate snapshot rather than relying only on the current Wild Edible coordinates. This preserves the historical place if the main entry is moved later. A Picking is not itself shown as a permanent map marker in the MVP.

### Season and month-filter semantics

- A season is inclusive of both start and end months.
- If `season_start_month <= season_end_month`, the season is a normal interval, e.g. March–June.
- If `season_start_month > season_end_month`, the season wraps across the year, e.g. November–February includes November, December, January, and February.
- A user selecting multiple months gets entries ready in **at least one** selected month (OR semantics within the month filter).
- Type filters and month filters combine with AND semantics.
- Entries with no season are shown when no month filter is active, but are excluded when a month filter is active because readiness cannot be established.
- `season_all_year = true` represents availability in every month and is mutually exclusive with a partial season. An unspecified season is represented by `season_all_year = false` with both month endpoints null.
- A season with only one month is valid and represents that single month.
- The create/edit form must require both season endpoints when a partial season is used and must reject an end month without a start month, or vice versa.

Implement the month matching as a small pure domain seam so wrap-around behaviour can be tested independently of Eloquent and Livewire. Query filtering should remain explicit and reusable through a model scope or dedicated query object.

### Map and marker behaviour

- The map is the primary Wild Edibles index and loads only the authenticated user's non-deleted entries.
- Every Wild Edible produces one marker.
- Marker icon and colour are derived from the Edible Type, not user-editable per entry in the MVP.
- The map includes a legend with accessible text labels; icons must not be the only way to identify a type.
- Marker popups show at least the name, type, optional location name, season summary, and a link/action to view details.
- The map supports selecting a point for create/edit and moving the selected marker before saving.
- When the user has no saved Wild Edibles, the initial map centre is latitude `55.40628811114651`, longitude `9.186381580256743`. The implementation must use a documented, stable default zoom appropriate for the surrounding area.
- The map uses the Google Maps JavaScript API. Google Maps Advanced Markers are used for type-specific marker icons; the deprecated legacy `google.maps.Marker` API must not be used.
- The Google Maps browser key is supplied through application configuration, restricted by allowed HTTP referrers and restricted to the Maps JavaScript API in Google Cloud. It must not be committed to the repository or embedded as an unrestricted secret.
- The MVP does not require Places API or Geocoding API. Users select and adjust coordinates directly on the map; `location_name` is entered manually.
- Wild Edible records and exact marker coordinates are loaded only by the authenticated Dyhrene page. The application must not publish them through a public Google data feed or public endpoint. Google may still receive normal browser/map-request metadata such as the user's IP address and map viewport as a consequence of using Google Maps.
- The map implementation must follow Dyhrene's Livewire-first architecture and pure CSS/SCSS convention. Google Maps JavaScript integration is an approved exception to the no-framework UI convention and must be isolated to the map surface.
- The map must remain usable on narrow screens. Filters appear at the top of the map page and remain usable without requiring desktop-only controls.
- If marker volume makes the map unreadable, clustering may be added as an implementation detail without changing the domain contract.

### Screens and interactions

Provide the following authenticated Livewire-facing surfaces:

1. **Wild Edibles map/index**
   - map;
   - type filter;
   - month filter;
   - legend;
   - create-entry action;
   - empty state for no entries and no filter matches.
2. **Create Wild Edible**
   - type, name, description, optional location name;
   - map coordinate picker;
   - season controls, including unspecified and all-year states;
   - photo upload.
3. **Wild Edible detail**
   - metadata and season;
   - map location;
   - photo gallery;
   - Picking history;
   - action to add a Picking;
   - edit and delete actions.
4. **Edit Wild Edible**
   - same editable fields as create;
   - existing photo management;
   - coordinate adjustment.
5. **Add Picking**
   - date defaulting to today;
   - map showing the default saved location;
   - coordinate adjustment;
   - optional comment.

Follow existing Dyhrene patterns: authenticated routes, user ownership checks in every load/update/delete path, Actions for non-trivial persistence, lean models, typed enums, and validation in the Livewire flow or shared validation seam as appropriate.

### Authorization and privacy

- Every Wild Edible, Wild Edible Photo, and Picking must belong to the authenticated user directly or through its parent.
- A user must not be able to load, edit, delete, attach to, or retrieve files for another user's records by changing an ID in a request or Livewire action.
- Do not add public map sharing, public URLs, or unauthenticated image serving in this release.
- Exact coordinates are sensitive gathering information and must not be exposed through an unauthenticated endpoint.
- Private photos are served through an authenticated, ownership-checked route with inline image disposition, `X-Content-Type-Options: nosniff`, and no public Wasabi URL. A soft-deleted parent or photo is not served.
- Preserved EXIF metadata is intentional. The detail view must not expose a photo to another user, but it may display the original image metadata to the owning user when the browser exposes it.
- Livewire validation errors, logs, and error-monitoring payloads must not record exact latitude/longitude values or private image contents unnecessarily. Coordinate fields must be scrubbed from Sentry event context where applicable.

### Navigation and API surface

Add the feature to the existing authenticated application navigation using the product's existing naming and styling conventions.

The first release is browser-only. No MCP server or external REST API is required for this specification. If MCP exposure is later requested, it should be specified separately with explicit privacy and coordinate-sharing rules.

## Implementation checklist

The feature requires the following implementation work:

1. Add the `WildEdibleTypeEnum`, `WildEdible`, `WildEdiblePhoto`, and `Picking` domain model support, including migrations, factories, ownership scopes, relationships, casts, indexes, and soft-delete behaviour.
2. Add the pure season matcher and the reusable authenticated query path for type/month filtering, including year-wrapping seasons and the separate unspecified/all-year states.
3. Add authenticated Livewire map, create, detail, edit, and Picking flows. The map must use Google Maps JavaScript API Advanced Markers and support marker selection and coordinate picking.
4. Add Google Maps configuration and deployment documentation: browser key, HTTP referrer restrictions, API restriction, allowed application origins, and a safe local/test configuration.
5. Add sequential single-image upload, private Wasabi storage, preserved source metadata, authenticated inline serving, soft-delete handling, and permanent cleanup only during permanent Wild Edible deletion.
6. Add navigation and authenticated routes following existing Dyhrene naming conventions.
7. Add authorization and privacy tests for every read, write, delete, image-serving, Livewire-action, and cross-user path.
8. Add domain, query, Livewire/feature, and browser/manual map verification, then run the normal PHP and frontend quality gates.

## Testing Decisions

Tests should verify externally observable behaviour rather than Livewire implementation details or the exact map library API.

### Domain tests

Create focused tests for the pure season matcher:

- normal interval, including both endpoints;
- single-month interval;
- year-wrapping interval;
- all-year interval;
- no season;
- multiple selected months;
- invalid month values rejected before matching.

### Model/query tests

Verify that the Wild Edible query:

- returns only the authenticated user's entries;
- excludes soft-deleted entries;
- filters by one and multiple Edible Types;
- applies the month semantics correctly, including wrapped seasons;
- combines type and month filters with AND semantics;
- excludes unknown-season entries when a month filter is active.

### Feature and Livewire tests

Follow the existing Inventory and Species testing style for authenticated CRUD flows:

- unauthenticated access is denied;
- a user can create a valid Wild Edible;
- required fields and coordinate ranges are validated;
- partial seasons require both endpoints;
- all-year and unspecified season states persist distinctly;
- a user can edit and delete only their own entries;
- another user's entry cannot be viewed or mutated;
- a user can upload exactly one supported image per upload and see it on the detail surface;
- supported image metadata is preserved;
- unsupported files, SVG files, and oversized files are rejected;
- a user can soft-delete only their own photos;
- soft-deleting an edible does not physically delete its photos;
- permanently deleting an edible physically deletes all related photo objects;
- private images cannot be retrieved by another user, through a public URL, or after the parent/photo is soft-deleted;
- a user can create a Picking with the default location;
- a user can adjust the Picking location and save a comment;
- the Picking keeps its coordinate snapshot after the parent location changes;
- a user cannot create a Picking for another user's Wild Edible.

### Browser/map verification

Because map rendering depends on browser JavaScript and external tile assets, include a focused browser-level verification for:

- map loads with markers and the type legend;
- selecting a type updates visible results;
- selecting a month updates visible results;
- clearing filters restores the complete result set;
- clicking a marker opens the correct entry summary;
- clicking the map during create/edit records a coordinate;
- the map and filters remain usable at a narrow viewport.

Map rendering and interaction are verified manually as part of release verification. Keep the domain and Livewire behaviour fully covered by automated tests; do not weaken those tests because Google Maps rendering is external-browser behaviour.

### Quality gates

Implementation must pass Dyhrene's normal gates:

- `composer lint`;
- `composer larastan`;
- `composer test`;
- frontend production build when map assets or JavaScript dependencies change.

## Acceptance Criteria

The feature is ready when all of the following are true:

1. An authenticated user can create a Wild Edible with type, name, coordinates, optional description/location name, optional season, and zero or more photos.
2. The saved entry appears as a marker on the user's private Google Maps JavaScript API map.
3. Marker iconography and legend labels distinguish all supported types.
4. The map's type filter supports selecting one or multiple types.
5. The map's month filter supports selecting one or multiple months.
6. A September filter includes every entry whose inclusive season contains September, including wrapped seasons, and excludes entries with no known season.
7. Type and month filters combine correctly, and clearing them restores the unfiltered map.
8. A marker popup links to a detail view containing the entry's name, type, location, description, season, photos, and Picking history.
9. A user can soft-delete their own entry and cannot access another user's entry; soft deletion removes it from the map without physically deleting its photos.
10. A user can add a Picking with date, registered map location, and optional comment.
11. A Picking defaults to the parent entry's coordinates but can store adjusted coordinates.
12. Editing the parent entry's location does not rewrite existing Picking coordinates.
13. Uploaded photos are stored privately, one image per upload, with source metadata preserved, and are served only after ownership authorization.
14. All feature tests, static analysis, style checks, and relevant frontend build checks pass.

## Out of Scope

The following are explicitly not part of the first release:

- public sharing of gathering spots or public/private toggles;
- collaboration or multi-user editing;
- recommendations about whether an edible is safe to consume;
- automatic mushroom, berry, or plant identification from photos;
- toxicity warnings or species-identification guarantees;
- recording harvest quantity, weight, or yield analytics;
- inventory integration for gathered food;
- recipes or preservation workflows;
- navigation, route planning, distance calculations, or directions to a spot;
- recurring reminders or notifications when a season begins;
- external species databases or automatic seasonal data imports;
- a separate reusable location library shared by multiple entries;
- showing Picking logs as separate map markers;
- MCP or REST API exposure;
- multiple disjoint seasons for one Wild Edible.

## Further Notes

- The feature should use “Wild Edibles” as the product-facing collection name and “Wild Edible” as the singular record name. “Gathering Spot” describes the location aspect but is not a separate MVP entity.
- A record named “Apples” is valid; the system must not require a scientific species name.
- The app should present a clear disclaimer that the map is a personal record and that users are responsible for correct identification and safe/legal foraging. This is informational only and must not imply that the application validates edibility.
- Existing `Site` records are used by the bird-observation domain and should not be overloaded for Wild Edibles. A future consolidation of geographic concepts can be considered separately after real usage patterns are known.
- Existing Wasabi/S3 image storage and authenticated attachment-serving conventions should be reused rather than introducing a second storage mechanism. Unlike the existing generic attachment flow, this feature must serve images inline, preserve source metadata, and use soft-delete plus permanent cleanup tied to permanent Wild Edible deletion.
- The type enum should own stable machine values and display metadata such as label and marker icon, keeping marker rendering consistent across the map, legend, forms, and detail page.
- The Picking location decision is resolved: a Picking defaults to the parent Wild Edible's coordinates but may be moved before saving. Its saved coordinates are an immutable historical snapshot unless the Picking itself is explicitly edited in a later feature.

## Remaining technical decisions before implementation

The product behaviour is now decided. The remaining items are implementation/configuration choices rather than open product questions:

- approve the Google Maps JavaScript API dependency and configure a restricted browser key for each environment;
- decide whether permanent Wild Edible deletion is exposed in the UI in this release or implemented as a protected cleanup operation for a later release. Regardless of UI timing, no image object may be physically deleted by soft deletion or by ordinary photo removal.
