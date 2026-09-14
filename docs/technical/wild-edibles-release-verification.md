# Wild Edibles manual release verification

This feature is intentionally verified manually because Google Maps rendering and Advanced Markers require a browser with a configured API key.

## Configuration

- Set `GOOGLE_MAPS_API_KEY` outside source control.
- Restrict the key to the deployed application origins and the Maps JavaScript API.
- Do not enable Places or Geocoding solely for this feature; coordinates are selected directly on the map.
- Confirm the existing private `wasabi` disk is configured before testing photo uploads.

## Release checks

1. Run `php artisan migrate` and confirm the three Wild Edibles migrations complete.
2. Sign in as user A and open `/wild-edibles`; confirm the map is centred at latitude `55.40628811114651`, longitude `9.186381580256743`, with the configured default zoom (`9`).
3. Create one edible of each type and verify each marker uses its type colour and the legend matches the marker types.
4. Select one or more type and month filters. Confirm the table and map both update, including wrapped seasons such as November–February; confirm unspecified seasons do not appear for a month filter.
5. Create an edible with no season, all-year season, and a partial season. Confirm each label is distinct.
6. Select a coordinate directly on the map, enter a manual location name, save, and confirm no Places/Geocoding lookup is required.
7. Upload exactly one JPEG, PNG, GIF, or WebP. Confirm the source object is private, the original bytes are unchanged, and EXIF/source metadata remains available. Confirm SVG, animated, unsupported, and oversized files are rejected.
8. Open the authenticated photo URL as the owner. Confirm a different authenticated user and a logged-out browser cannot read it.
9. Add a picking. Confirm its initial marker is at the edible location, move it by clicking and dragging, save, and verify the saved latitude/longitude are a snapshot independent of later edible edits.
10. Edit an edible, add another single photo, soft-delete the edible, and confirm it disappears from the map and normal queries while its source objects remain in private storage.
11. Confirm a protected permanent cleanup operation removes the associated source objects only when the parent is permanently deleted.
12. Sign in as user B and confirm user A’s edibles, coordinates, photos, and pickings are absent from all map, list, detail, and direct URL requests.

## Automated gates

```bash
composer lint
./vendor/bin/phpstan analyse --memory-limit=512M
composer test
npm run build
```
