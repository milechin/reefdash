# ReefDash

A single-page reef tank water quality dashboard. Tracks parameters (temperature, pH, salinity, alkalinity, calcium, phosphate, nitrate, ammonia) across multiple tanks with Chart.js trend charts, equipment tracking, and a daily log.

Designed to run on a Synology NAS via Apache/Web Station with PHP, or locally via Docker.

## Prerequisites

- Docker (local development)
- A web server with PHP 8.x (production) — tested on Synology NAS with Web Station

## Setup

### 1. Copy and configure the template files

```bash
cp tanks.template.json tanks.json
cp targets.template.json targets.json
cp equipment.template.json equipment.json
cp tank_data.template.js tank_data.js
```

**`tanks.json`** — define your tanks. Each entry needs a unique `key` (used internally), a display `label`, and an `emoji`:
```json
[
  { "key": "display", "label": "Display Tank", "emoji": "🐠" }
]
```

The `key` must match across `tanks.json`, `equipment.json`, and `tank_data.js`.

**`targets.json`** — set your min/max target bands for each parameter. These appear as shaded regions on the charts.

**`equipment.json`** — add your equipment entries. `purchased` and `expires` are `"YYYY-MM-DD"` or `null`.

**`tank_data.js`** — the live database. Initialize it from the template with empty arrays for each tank key defined in `tanks.json`. The dashboard writes back to this file via `save.php` whenever you log a test or water change.

### 2. Run locally

```bash
docker compose up --build
```

Open [http://localhost:8080](http://localhost:8080). File changes on the host are reflected immediately — no rebuild needed.

### 3. Deploy to production

Copy all files to your web server directory. Ensure the web server process has write permission on `tank_data.js` (required for `save.php` to persist logged data).

## Data files

These files are gitignored — they contain your personal data and configuration:

| File | Description |
|------|-------------|
| `tank_data.js` | All tank measurement history (the database) |
| `tanks.json` | Your tank definitions |
| `targets.json` | Your parameter target ranges |
| `equipment.json` | Your per-tank equipment list |

The corresponding `*.template.*` files in the repo document the schema and serve as a starting point.

## Adding a new parameter

1. Add an entry to `CHART_DEFS` in `index.php`
2. Add matching entries to `LOG_TEST_FIELDS`, `DATA_ARRAY_KEY`, and `DATA_VAL_KEY` in `index.php`
3. Add the new array to each tank in `tank_data.js` and update `tank_data.template.js`
4. Bump `_schemaVersion` in both files

## Adding a new tank

1. Add an entry to `tanks.json` with a unique `key`, `label`, and `emoji`
2. Add a matching tank object to `tank_data.js` (copy the structure from `tank_data.template.js`)
