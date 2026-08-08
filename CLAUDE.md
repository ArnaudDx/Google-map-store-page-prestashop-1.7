# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`storeggmap` is a PrestaShop module (PS 1.7 through 9) that displays store lists on a Google Map, both on the front office store-locator page (via a Smarty widget) and on the back office `AdminModules` config page / `AdminOrders` order detail page (PS 8+ only). There is no build step, package manager, or test suite — it's plain PHP (PrestaShop module API) + vanilla ES6 JS + Smarty templates, edited directly on disk.

This module is bind-mounted into the sibling PrestaShop-8 dev environment at `../../8/modules/storeggmap` (see `../../CLAUDE.md` for the Docker orchestration that runs it) — changes here are live in that container without a rebuild.

## No build/lint/test commands

There is nothing to run locally: no composer.json, no npm, no PHPUnit. Verify changes by installing/reconfiguring the module in a running PrestaShop back office (Modules > storeggmap) and checking browser console / PHP error logs. Use `make logs-8` and `make shell-8` from the parent repo to tail logs or get a shell in the container that mounts this module.

## Architecture

**Entry point**: `storeggmap.php` defines `Storeggmap extends Module implements WidgetInterface`. It registers three hooks:
- `actionAdminControllerSetMedia` — loads back.css/back.js (or adminOrders.js on `AdminOrders`) + the Google Maps JS API + `StoreGgMap.js` class, only on the module's own config page or `AdminOrders`.
- `actionFrontControllerSetMedia` — loads front.css/front.js + the Maps API on whichever front controllers are allowlisted in `STORE_GGMAP_PAGE` config (or all pages via `"*"`).
- `displayAdminOrderSide` — (PS 8+) renders a small map in the order detail sidebar, geocoding the order's invoice/delivery address via the Google Geocoding API (`getCoordinateByAddress`) if `STORE_GGMAP_ADMIN_ORDER` is enabled.

The front-office widget itself is rendered via `renderWidget()`/`getWidgetVariables()` (the `WidgetInterface` contract), fetching `views/templates/hook/storeggmap.tpl`, and is invoked from a shop template with `{widget name="storeggmap"}`.

**Config storage**: all settings live in `Configuration` table keys prefixed `STORE_GGMAP_*` (APIKEY, ICON, LAT, LONG, PAGE, ZOOM, SEARCH, CUSTOM, ADMIN_ORDER, ADMIN_ORDER_ADDRESS_CHOICE) — set/read in `install()`/`uninstall()`/`getContent()`/`getFormValues()`. `getContent()` is the single admin-form handler, dispatching on submitted button name (`delete_icon` / `save_storemap`) via a `switch(true)` with validation helpers (`isValidPageSelection`, `isValidCustomization`, `isUploadedIcon` — the latter does real MIME-sniffing + `imagecreatefrom*` verification before accepting an uploaded marker icon).

**AJAX backend**: `controllers/front/StoreInformation.php` (`StoreggmapStoreInformationModuleFrontController`) is the sole front-office AJAX endpoint, gated by a per-day CSRF-ish token (`$module->getToken()`, sha256 of cookie key + version + date, embedded via `Media::addJsDef` as `storeGgMmapSettings.token`). It dispatches `action` (`getStores`, `getStoreDetail`, `searchStoreByRadius`) — all POST-only, checked against an `allowedActions` allowlist. `searchStoreByRadius` runs a raw haversine-formula SQL query (`Db::getInstance()->executeS`) filtering PrestaShop's native `store` table by radius.

**Front-end JS** (`views/js/`): `classes/StoreGgMap.js` is the single class driving the Google Map (`google.maps.Map`), used two ways:
- `initBo()` — back office: single draggable/click-to-set marker, used both on the module config page (`back.js`) and read-only-ish on `AdminOrders` (`adminOrders.js`, seeded from the geocoded order address in `data-lat`/`data-lng` attributes).
- `initFo()` — front office (`front.js`): fetches all stores via AJAX, drops markers, wires click-to-show-detail (`InfoWindow` populated from `storeggmap_detail.tpl` fetched server-side), and optionally a Places Autocomplete search box (`initSearch`) that re-filters visible `.store-item` DOM elements by radius via `searchStoreByRadius`.

Because `hookActionAdminControllerSetMedia` fires before Smarty `postProcess`, `back.js`/`adminOrders.js` re-read live form field values (`ggmap_lat`, `ggmap_long`, etc.) from the DOM on `window load` rather than trusting the settings baked in at hook time — don't remove that pattern when touching those files.

**Versioning/upgrades**: `upgrade/upgrade-<version>.php` files (auto-run by PrestaShop on module update, function name `upgrade_module_<version_with_underscores>`) hold one-off `Configuration` migrations — check these before adding a new `Configuration` key so old installs get it via an upgrade script rather than only in `install()`.

**Translations**: `translations/fr.php` — a flat array of `AdminTranslations`-style keys mapped to French strings, keyed by an md5 of context+source text (PrestaShop's legacy translation format). New user-facing strings should go through `$this->l('...')` in PHP / equivalent in templates so they're extractable.
