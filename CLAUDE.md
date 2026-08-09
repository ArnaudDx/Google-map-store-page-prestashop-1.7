# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Status: end of life — do not plan new work

**This module is unmaintained as of August 2026.** Last release is 2.1.1, supporting **PrestaShop 1.7 to 8.x only**. It is not PS9 compatible and will not be made so — `ps_versions_compliancy` caps at `8.99.99` (`storeggmap.php:52`), which makes PS9 refuse the install.

Do not propose or start PS9 compatibility work, dependency upgrades, or migrations off the deprecated Google Maps symbols (`google.maps.Marker`, `google.maps.places.Autocomplete`). Only certain, user-facing bugs get fixed, and only on request. The EOL notice lives in `README.md`; if that changes, update this file too.

## What this is

`storeggmap` is a PrestaShop module that displays store lists on a Google Map, both on the front office store-locator page (via a Smarty widget) and on the back office `AdminModules` config page / `AdminOrders` order detail page (PS 8+ only). There is no build step, package manager, or test suite — it's plain PHP (PrestaShop module API) + vanilla ES6 JS + Smarty templates, edited directly on disk.

This directory is bind-mounted into the sibling **PrestaShop 9** dev environment (`../../9/docker-compose.yml`, see `../../CLAUDE.md` for the orchestration) — but since the module now refuses to install on PS9, that container is only useful for reading files, not for testing. **To test a change, copy it into the running PS8 container**, which holds its own unmounted copy:

```bash
docker cp storeggmap.php ps8:/var/www/html/modules/storeggmap/storeggmap.php
docker exec ps8 chown -R www-data:www-data /var/www/html/modules/storeggmap
docker exec ps8 rm -rf /var/www/html/var/cache/*
```

Those copies are lost on `make reset-8`.

## No build/lint/test commands

There is nothing to run locally: no composer.json, no npm, no PHPUnit. Verify changes by installing/reconfiguring the module in a running PrestaShop back office (Modules > storeggmap) and checking browser console / PHP error logs. Use `make logs-8` and `make shell-8` from the parent repo to tail logs or get a shell in the container that mounts this module.

## Architecture

**Entry point**: `storeggmap.php` defines `Storeggmap extends Module implements WidgetInterface`. It registers three hooks:
- `actionAdminControllerSetMedia` — loads back.css/back.js (or adminOrders.js on `AdminOrders`) + the Google Maps JS API + `StoreGgMap.js` class, only on the module's own config page or `AdminOrders`.
- `actionFrontControllerSetMedia` — loads front.css/front.js + the Maps API on whichever front controllers are allowlisted in `STORE_GGMAP_PAGE` config (or all pages via `"*"`).
- `displayAdminOrderSide` — (PS 8+) renders a small map in the order detail sidebar if `STORE_GGMAP_ADMIN_ORDER` is enabled. It only flattens the order's invoice/delivery address to one line (`getAddressLiteral`) and passes it as `data-address`; the **geocoding happens in the browser** (`adminOrders.js`, `google.maps.Geocoder`). Don't move it back to PHP: a Maps key restricted by HTTP referrer — the normal setup, since the same key is exposed in the front office HTML — is rejected by the server-side Geocoding web service with `REQUEST_DENIED`. This `fetch()` is deliberately uncached, because its output varies per order.

The front-office widget itself is rendered via `renderWidget()`/`getWidgetVariables()` (the `WidgetInterface` contract), fetching `views/templates/hook/storeggmap.tpl`, and is invoked from a shop template with `{widget name="storeggmap"}`.

**Config storage**: all settings live in `Configuration` table keys prefixed `STORE_GGMAP_*` (APIKEY, ICON, LAT, LONG, PAGE, ZOOM, SEARCH, CUSTOM, ADMIN_ORDER, ADMIN_ORDER_ADDRESS_CHOICE) — set/read in `install()`/`uninstall()`/`getContent()`/`getFormValues()`. `getContent()` is the single admin-form handler, dispatching on submitted button name (`delete_icon` / `save_storemap`) via a `switch(true)` with validation helpers (`isValidPageSelection`, `isValidCustomization`, `isUploadedIcon` — the latter does real MIME-sniffing + `imagecreatefrom*` verification before accepting an uploaded marker icon).

**AJAX backend**: `controllers/front/StoreInformation.php` (`StoreggmapStoreInformationModuleFrontController`) is the sole front-office AJAX endpoint, gated by a per-day CSRF-ish token (`$module->getToken()`, sha256 of cookie key + version + date, embedded via `Media::addJsDef` as `storeGgMmapSettings.token`). It dispatches `action` (`getStores`, `getStoreDetail`, `searchStoreByRadius`) — all POST-only, checked against an `allowedActions` allowlist. `searchStoreByRadius` runs a raw haversine-formula SQL query (`Db::getInstance()->executeS`) filtering PrestaShop's native `store` table by radius.

**Front-end JS** (`views/js/`): `classes/StoreGgMap.js` is the single class driving the Google Map (`google.maps.Map`), used two ways:
- `initBo()` — back office: single draggable/click-to-set marker, used both on the module config page (`back.js`) and read-only on `AdminOrders` (`adminOrders.js`, centred on the coordinates it geocodes itself from the `data-address` attribute; `initBo` skips its dblclick handler there, since the `ggmap_lat`/`ggmap_long` form fields only exist on the config page).
- `initFo()` — front office (`front.js`): fetches all stores via AJAX, drops markers, wires click-to-show-detail (`InfoWindow` populated from `storeggmap_detail.tpl` fetched server-side), and optionally a Places Autocomplete search box (`initSearch`) that re-filters visible `.store-item` DOM elements by radius via `searchStoreByRadius`.

Because `hookActionAdminControllerSetMedia` fires before Smarty `postProcess`, `back.js`/`adminOrders.js` re-read live form field values (`ggmap_lat`, `ggmap_long`, etc.) from the DOM on `window load` rather than trusting the settings baked in at hook time — don't remove that pattern when touching those files.

**Versioning/upgrades**: `upgrade/upgrade-<version>.php` files (auto-run by PrestaShop on module update, function name `upgrade_module_<version_with_underscores>`) hold one-off `Configuration` migrations — check these before adding a new `Configuration` key so old installs get it via an upgrade script rather than only in `install()`.

**Translations**: `translations/fr.php` — a flat array of `AdminTranslations`-style keys mapped to French strings, keyed by an md5 of context+source text (PrestaShop's legacy translation format). New user-facing strings should go through `$this->l('...')` in PHP / equivalent in templates so they're extractable.
