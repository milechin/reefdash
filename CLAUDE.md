I have a reef tank monitoring dashboard project. Here's the full context:

## Project Overview
A single-page reef tank water quality dashboard served from a Synology NAS via Apache/Web Station with PHP enabled. The dashboard tracks three tanks: Display Tank, QRT, and Lauren's.

## Files
- `index.php` — main dashboard (PHP file so the <head> can inject a cache-busted `<script src="tank_data.js?v={filemtime}>` tag)
- `tank_data.js` — all tank data as `const RAW = {...};`
- `save.php` — PHP endpoint that receives POSTed JSON, validates, and writes new tank_data.js to disk
- `save_targets.php` — PHP endpoint that writes targets.json to disk
- `save_equipment.php` — PHP endpoint that writes equipment.json to disk
- `tanks.json` — defines each tank: `[{key, label, emoji}]`
- `targets.json` — persisted min/max target values per parameter
- `equipment.json` — per-tank equipment list: `[{tank, item, purchased, expires, comment}]`
- `Fish Tank Log - Display.csv` — exported CSV used for bulk data imports
- `Dockerfile` — builds a `php:8.2-apache` container for local development

## Data Architecture
RAW is a single JS object containing:
- `display`, `qrt`, `laurens` — tank objects, each with:
  - `latest` — {temp, ph, salinity, nitrate, ammonia, alk, calcium, phosphate, lastDate}
  - `temp`, `ph`, `salinity`, `nitrate`, `alk`, `calcium`, `phosphate`, `ammonia` — arrays of {date, Value} objects
  - `waterChanges` — array of date strings ["YYYY-MM-DD", ...]
  - `dose` — array of {date, dose} objects for AFR Dose (ml/day)
  - `blog` — array of {date, text} objects for daily log entries
- `equipment` — legacy array (no longer used; equipment now lives in equipment.json)
- `log` — legacy array (no longer used; replaced by per-tank blog)

## Dashboard Layout (per tank panel)
1. **Maintenance section** — 2-column row: Last Water Change card (with `+ LOG` button) and Last Water Test card
2. **Current Parameters section** — section label with `+ Log Test` button inline, then 8 parameter KPI cards
3. **Controls bar (dateBar)** — date pickers, presets (90d default, 6mo, 1yr, All), Water Changes toggle, All For Reef Dose toggle, ⚙ Targets
4. **Parameter Trends section** — 7 Chart.js charts sharing a master x-axis
5. **Daily Log section** — free-text blog entries with `+ Add Entry` button
6. **Equipment section** — per-tank equipment table with `+ Add` and `✎ Edit` buttons

## Tab Navigation
Tabs are driven by `tanks.json` (PHP-generated). Fixed tabs: **Help**. No Equipment or Log tabs — both are now per-tank sections.

## CHART_DEFS
Drives all chart rendering — adding a chart only requires one entry here:
```js
const CHART_DEFS = [
  {key:'Temp',      label:'TEMPERATURE °F',  color:'#00d4ff', tMin:76,    tMax:80,    showDose:false},
  {key:'pH',        label:'pH',              color:'#a78bfa', tMin:8.1,   tMax:8.5,   showDose:true},
  {key:'Salinity',  label:'SALINITY',        color:'#2ecc71', tMin:1.025, tMax:1.027, showDose:false},
  {key:'ALK',       label:'ALKALINITY dKH',  color:'#ffd166', tMin:11,    tMax:12,    showDose:true},
  {key:'Calcium',   label:'CALCIUM ppm',     color:'#f39c12', tMin:435,   tMax:465,   showDose:true},
  {key:'Phosphate', label:'PHOSPHATE ppm',   color:'#ec4899', tMin:0,     tMax:0.03,  showDose:false},
  {key:'Nitrate',   label:'NITRATE ppm',     color:'#06b6d4', tMin:0,     tMax:5,     showDose:false},
];
```

## LOG_TEST_FIELDS / DATA_ARRAY_KEY / DATA_VAL_KEY
These three objects must stay in sync when adding a new parameter:
- `LOG_TEST_FIELDS` — defines the form inputs (key, label, unit, step, color)
- `DATA_ARRAY_KEY` — maps form key → RAW array name (e.g. `dose:'dose'`)
- `DATA_VAL_KEY`   — maps form key → value property name in each array entry (e.g. `dose:'dose'`)
- Includes `dose` (AFR Dose ml/day) as a loggable field alongside the 8 water parameters

## Log Water Test Modal
- Flatpickr calendar with cyan dot indicators on days that have any existing test entry
- Selecting a date loads existing values into all fields for editing
- Date field + 9 inputs: 8 parameters + AFR Dose (blank = skip)
- On save: inserts into RAW arrays sorted by date, updates latest if newest, recalculates date window, rebuilds panel
- POSTs updated `const RAW = ...` to save.php; falls back to download if no server

## Log Water Change Modal
- Flatpickr calendar with blue dot indicators on days that have existing water changes
- Selecting an existing date shows a `🗑 Delete Entry` button
- Same save/fallback pattern as Log Water Test

## Daily Log (Blog)
- Per-tank free-text entries stored in `RAW.[tank].blog` as `[{date, text}]`
- Add/edit/delete via modal with Flatpickr calendar (purple dots on existing dates)
- Blog entries in the current date window appear as clickable purple ▼ triangles at the top of all charts
- Clicking a triangle opens the blog modal for that date
- Triangle hover: grows and brightens; handled via chart `onClick`/`onHover` with pixel distance check (not annotation hitRadius)
- Blog dates are included in `getDateRange()` so entries beyond the last measurement date are not clipped
- Blog dates are included in `buildMasterLabels()` so they appear on the x-axis

## Equipment
- Stored in `equipment.json` as `[{tank, item, purchased, expires, comment}]`
- Tank key must match the tank key in `tanks.json` (e.g. `display`, `qrt`, `laurens`)
- `days` is computed dynamically at render time — do NOT store it in equipment.json
- Add/edit/delete via modal; saved to `save_equipment.php`
- Shown per-tank in each panel's Equipment section; no standalone Equipment tab

## Targets
- Stored in `targets.json` as `{key: {tMin, tMax}}` where key matches CHART_DEFS key
- Loaded via PHP on page load, applied to CHART_DEFS and KPI_DEFS before first render
- `applyTargets()` saves to `save_targets.php` after updating
- `resetTargets()` resets in memory only (does not save to file)

## Key Technical Decisions
- `dateBar` is a single DOM element physically moved into each panel's `controls-anchor-{panelId}` div after KPI render. It MUST be rescued to `#dateBarStash` before any `panel.innerHTML = ...` wipe, then re-appended. This is the most fragile part of the codebase — always rescue dateBar first inside buildTankPanel before setting innerHTML.
- `buildTankPanel` is the only place that calls `panel.innerHTML`. External callers (submitLogTest, submitLogWC, applyTargets) must NOT set innerHTML themselves — just reset initialized[tankKey] = false and call buildTankPanel.
- Chart.js instances are destroyed with try/catch + Chart.getChart(ctx) before recreating, since innerHTML replacement creates new canvas elements.
- `WATER_CHANGES` is a reference object pointing to `RAW.display.waterChanges` etc., so mutations to either are reflected in both.
- `DOSE_DATA` is a reference object pointing to `RAW.display.dose` etc. — stored in RAW so dose entries persist via save.php.
- `SAVE_URL = 'save.php'` — relative path works because the dashboard is served from the same directory.
- After logging, `getDateRange()` is called to recompute dateTo/dateFrom so new entries aren't filtered out by the existing date window.
- All date references are dynamic (`new Date()`) — no hardcoded dates anywhere in the codebase.
- `TANK_CONFIGS` (from tanks.json) drives tabs, panels, `initialized` map, and `TANK_NAMES` lookup — no hardcoded tank keys in JS.

## Saving Pattern
save.php receives POST with {content: "const RAW = ..."}, validates it starts with "const RAW", writes new tank_data.js. Returns {ok: true} or {ok: false, error: "..."}.

The dashboard's saveData() helper:
- result.ok → onSuccess() (closes modal)
- result.ok false → onServerError(msg) (shows red error in modal)
- fetch TypeError → onNoServer(jsContent) (shows amber download fallback)

save_targets.php and save_equipment.php accept a raw JSON array/object POST body directly.

## Nitrate Data Notes
- Display tank nitrate imported from `Fish Tank Log - Display.csv` (308 entries, 2021-01-11 to 2026-03-13)
- CSV has three nitrate columns at **0-indexed positions 13, 16, 19** (NOT 12, 15, 18 — off-by-one is a known past mistake):
  - [13] `Nitrate (ppm)` — API test kit
  - [16] `Nitrate (ppm) ` — Red Sea/Hanna colorimeter (preferred)
  - [19] `Nitrate (ppm) NYOS`
- Priority when deduplicating by date: col16 > col19 > col13
- Value key in RAW arrays is `Nitrate` (capital N), not `Value`

## Deployment
- All files in the same web directory
- No Node.js, no npm, no build step
- Local dev: `docker build -t reefdash . && docker run -p 8080:80 reefdash`

## Known Fragile Areas
1. dateBar rescue — must happen inside buildTankPanel before innerHTML, nowhere else
2. Date window filtering — after logging, always recompute the date range or new entries won't appear in charts
3. Chart.js canvas replacement — always destroy old instance with try/catch before creating new one on a replaced canvas
4. tank_data.js cache — the .php extension and PHP filemtime() cache-busting in the <head> is what ensures the browser loads fresh data after a save
5. Nitrate CSV column indices — always use 0-indexed 13/16/19, not 12/15/18
6. Blog date range — blog entry dates must be included in getDateRange() and buildMasterLabels() or entries beyond the last measurement date won't appear
