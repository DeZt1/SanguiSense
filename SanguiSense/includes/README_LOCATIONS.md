# Locations include and API

This file explains the new centralized locations approach that provides a unified municipalities and facilities list for all portals.

Files added
- `includes/locations.php` — canonical arrays and helpers:
  - `$MUNICIPALITIES` — array of municipality / city names used across portals
  - `$FACILITIES` — array of facility objects: ['name','type','city']
  - `get_municipalities()` and `get_facilities($filters = [])` helper functions
  - `locations_to_json()` convenience helper

- `api/get_locations.php` — simple REST-like endpoint returning JSON. Examples:
  - `/api/get_locations.php?type=municipalities`
  - `/api/get_locations.php?type=facilities&city=Cabanatuan&facility_type=hospital`

How to adopt in pages
- PHP server-rendered forms: include the file and use `get_municipalities()` or `get_facilities()` to populate `<select>` options.
  Example:

```php
include_once __DIR__ . '/../includes/locations.php';
foreach (get_municipalities() as $m) {
    echo '<option value="' . htmlspecialchars($m) . '">' . htmlspecialchars($m) . '</option>';
}
```

- JavaScript / dynamic pages: fetch `/api/get_locations.php` and render options on the client.

About ESB (Enterprise Service Bus)
- A full ESB is a platform-level integration (message buses, transforms, queues). For this codebase a lightweight, practical approach is recommended:
  - Centralize authoritative lists in `includes/locations.php` (server-side) and expose REST endpoints (`api/get_locations.php`)
  - Keep the arrays in one place and have all portals include the same file or call the API — this avoids duplication and keeps dropdowns consistent
  - If you later need cross-system, multi-server integration, migrate `api/get_locations.php` behind an internal service and add authentication and messaging (queue) as needed.

Next steps to finish rollout
- Update remaining `register.php` pages under `hospital/`, `bloodbank/`, and `sanguisense/donor/` to use the include or API.
- If you want, I can patch those files in the same style (low-risk change) — tell me which ones to update (I can update all found `register.php` files).

Contact
- If you want different canonical names or additional facilities, edit `includes/locations.php` directly. The API will reflect changes immediately.
