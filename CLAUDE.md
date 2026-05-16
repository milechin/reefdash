# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview
A single-page reef tank water quality dashboard served from a Synology NAS via Apache/Web Station with PHP enabled. The dashboard tracks three tanks: Display Tank, QRT, and Lauren's.

## Development

**Local dev (Docker):**
```
docker build -t reefdash . && docker run -p 8080:80 -v $(pwd):/var/www/html reefdash
```
Open http://localhost:8080. No Node.js, no npm, no build step.

**VS Code Dev Container:** Open in container via `.devcontainer/devcontainer.json` — starts Apache on port 80 pointed at `/workspaces/reefdash`.

**Deployment:** Copy all files to the Synology NAS web directory. The `.php` extension on `index.php` is required for the cache-busting `filemtime()` injection in `<head>`.

## Files
- `index.php` — entire frontend (HTML + CSS + JS, ~85KB); PHP only used for cache-busting script tag and injecting `tanks.json`/`targets.json` at page load
- `tank_data.js` — all tank data as `const RAW = {...}`
- `save.php` — POST endpoint; validates content starts with `const RAW`, writes `tank_data.js`
- `save_targets.php` — POST endpoint; writes raw JSON body to `targets.json`
- `save_equipment.php` — POST endpoint; writes raw JSON body to `equipment.json`
- `tanks.json` — tank definitions: `[{key, label, emoji}]`; drives tabs and panels
- `targets.json` — persisted min/max target values per parameter
- `equipment.json` — per-tank equipment: `[{tank, item, purchased, expires, comment}]`
- `Fish Tank Log - Display.csv` — source for bulk nitrate import (gitignored)
- `templates/` — reference templates for all gitignored data files (`tank_data.template.js`, `tanks.template.json`, `targets.template.json`, `equipment.template.json`); not deployed

## Data Architecture
`RAW` is a single JS object (the entire `tank_data.js` file):
- `display`, `qrt`, `laurens` — tank objects, each with:
  - `latest` — `{temp, ph, salinity, nitrate, ammonia, alk, calcium, phosphate, lastDate}`
  - parameter arrays — `{date, Value}` objects (except `nitrate`: `{date, Nitrate}`)
  - `waterChanges` — `["YYYY-MM-DD", ...]`
  - `dose` — `[{date, dose}]` for AFR Dose ml/day
  - `blog` — `[{date, text}]` for daily log entries
- `equipment`, `log` — legacy top-level arrays; unused (equipment → equipment.json, log → per-tank blog)

`WATER_CHANGES` and `DOSE_DATA` are reference objects pointing into RAW tank arrays — mutations are reflected in both.

## Dashboard Layout (per tank panel)
1. Maintenance section — Last Water Change + Last Water Test cards
2. Current Parameters — 8 KPI cards with `+ Log Test` button
3. Controls bar (`dateBar`) — date pickers, presets (90d default), Water Changes toggle, Dose toggle, ⚙ Targets
4. Parameter Trends — 7 Chart.js charts on a shared x-axis
5. Daily Log — blog entries with `+ Add Entry`
6. Equipment — per-tank table with `+ Add` / `✎ Edit`

Tabs are driven by `tanks.json` (PHP-injected). Only fixed tab is **Help**.

## CHART_DEFS
The single source of truth for chart rendering. Adding a chart only requires one entry:
```js
const CHART_DEFS = [
  {key:'Temp',      label:'TEMPERATURE °F',  color:'#00d4ff', tMin:76,    tMax:80,    showDose:false},
  {key:'pH',        label:'pH',              color:'#a78bfa', tMin:8.1,   tMax:8.5,   showDose:true},
  {key:'Salinity',  label:'SALINITY',        color:'#2ecc71', tMin:1.025, tMax:1.027, showDose:false},
  {key:'ALK',       label:'ALKALINITY dKH',  color:'#ffd166', tMin:11,    tMax:12,    showDose:true},
  {key:'Calcium',   label:'CALCIUM ppm',     color:'#f39c12', tMin:435,   tMax:465,   showDose:true},
  {key:'Phosphate', label:'PHOSPHATE ppm',   color:'#ec4899', tMin:0,     tMax:0.03,  showDose:false},
  {key:'Nitrate',   label:'NITRATE ppm',     color:'#06b6d4', tMin:0,     tMax:5,     showDose:false},
  {key:'Magnesium', label:'MAGNESIUM ppm',   color:'#14b8a6', tMin:1250,  tMax:1350,  showDose:false},
];
```

## LOG_TEST_FIELDS / DATA_ARRAY_KEY / DATA_VAL_KEY
These three objects must stay in sync when adding a new loggable parameter:
- `LOG_TEST_FIELDS` — form inputs (key, label, unit, step, color)
- `DATA_ARRAY_KEY` — form key → RAW array name (e.g. `dose:'dose'`)
- `DATA_VAL_KEY` — form key → value property name per entry (e.g. `dose:'dose'`)

## Modals
**Log Water Test** — Flatpickr with cyan dots on existing test days; selecting a date loads existing values for editing; 8 parameters + AFR Dose (blank = skip); inserts sorted, updates `latest` if newest.

**Log Water Change** — Flatpickr with blue dots; selecting an existing date shows `🗑 Delete Entry`.

**Daily Log (Blog)** — Flatpickr with purple dots; entries appear as purple ▼ triangles at the top of all charts in the active date window; clicking a triangle opens the modal for that date. Triangle hit detection uses pixel distance check in `onClick`/`onHover` (not annotation `hitRadius`). Blog dates are included in `getDateRange()` and `buildMasterLabels()` so entries beyond the last measurement date are not clipped.

All modals use the same save/fallback pattern: `result.ok` → close modal; `result.ok false` → red error; fetch TypeError → amber download fallback.

## Equipment
- `days` is computed at render time — do NOT store in equipment.json
- `tank` key must match the key in tanks.json

## Targets
- Loaded via PHP at page load and applied to `CHART_DEFS` and `KPI_DEFS` before first render
- `applyTargets()` saves to `save_targets.php`; `resetTargets()` resets in memory only

## Key Technical Decisions
- **`dateBar` rescue** — `dateBar` is a single DOM element physically moved per panel. It MUST be rescued to `#dateBarStash` before any `panel.innerHTML = ...`, then re-appended. This is the most fragile part of the codebase — always rescue inside `buildTankPanel` before setting innerHTML.
- **`buildTankPanel` owns innerHTML** — it is the only function that sets `panel.innerHTML`. External callers (`submitLogTest`, `submitLogWC`, `applyTargets`) must NOT set innerHTML; they reset `initialized[tankKey] = false` and call `buildTankPanel`.
- **Chart.js canvas replacement** — always destroy the old instance via `Chart.getChart(ctx)` (with try/catch) before creating a new one; innerHTML replacement creates new canvas elements.
- **`TANK_CONFIGS`** (from tanks.json) drives tabs, panels, `initialized` map, and `TANK_NAMES` lookup — no hardcoded tank keys in JS.
- **Date window** — after any logging action, call `getDateRange()` to recompute dateTo/dateFrom so new entries aren't filtered out.
- All date references use `new Date()` — no hardcoded dates.

## Saving Pattern
`save.php` receives `POST {content: "const RAW = ..."}`, validates the prefix, writes `tank_data.js`.  
`save_targets.php` and `save_equipment.php` accept a raw JSON body directly.

`saveData()` helper in `index.php`:
- `result.ok` → `onSuccess()` (closes modal)
- `result.ok === false` → `onServerError(msg)` (red error in modal)
- fetch `TypeError` → `onNoServer(jsContent)` (amber download fallback)

## Nitrate CSV Notes
CSV columns are **0-indexed**; positions 13, 16, 19 (not 12, 15, 18 — a known past off-by-one):
- [13] `Nitrate (ppm)` — API test kit
- [16] `Nitrate (ppm) ` — Red Sea/Hanna colorimeter (preferred)
- [19] `Nitrate (ppm) NYOS`

Deduplication priority: col16 > col19 > col13. Value key in RAW is `Nitrate` (capital N), not `Value`.

## Known Fragile Areas
1. **dateBar rescue** — must happen inside `buildTankPanel` before innerHTML, nowhere else
2. **Date window** — after logging, always recompute via `getDateRange()` or new entries won't appear
3. **Chart.js canvas** — destroy old instance with try/catch before recreating on a replaced canvas
4. **tank_data.js cache** — the `.php` extension + `filemtime()` in `<head>` ensures fresh data after a save
5. **Nitrate CSV columns** — always use 0-indexed 13/16/19, not 12/15/18
6. **Blog date range** — blog dates must be in `getDateRange()` and `buildMasterLabels()` or out-of-range entries won't appear
