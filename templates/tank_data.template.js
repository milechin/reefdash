// Schema template for tank_data.js — tracks structure, not data.
// Production tank_data.js is gitignored (it's the live database).
//
// SCHEMA VERSION: increment SCHEMA_VERSION and RAW._schemaVersion together
// whenever the RAW structure changes, then migrate the production file.
const SCHEMA_VERSION = 4;
//
// VALUE KEY CASING — must match exactly when adding entries:
//   temp      → Temp
//   ph        → pH
//   salinity  → Salinity
//   nitrate   → Nitrate      (NOT "Value" — known past mistake)
//   alk       → ALK
//   calcium   → Calcium
//   phosphate → Phosphate
//   ammonia   → Ammonia
//   magnesium → Magnesium
//
// MIGRATION: when this schema changes, manually update production tank_data.js
// on the NAS to match (add missing keys, rename fields, etc.) then hard-refresh
// the browser. No migration framework — changes are documented in git commit diffs.

const RAW = {
  "_schemaVersion": 4,
  "display": {
    "latest": {
      "temp": 0,
      "ph": 0,
      "salinity": 0,
      "nitrate": 0,
      "ammonia": 0,
      "alk": 0,
      "calcium": 0,
      "phosphate": 0,
      "magnesium": null,
      "lastDate": "YYYY-MM-DD"
    },
    "temp":       [{"date": "YYYY-MM-DD", "Temp": 0}],
    "ph":         [{"date": "YYYY-MM-DD", "pH": 0}],
    "salinity":   [{"date": "YYYY-MM-DD", "Salinity": 0}],
    "nitrate":    [{"date": "YYYY-MM-DD", "Nitrate": 0}],
    "alk":        [{"date": "YYYY-MM-DD", "ALK": 0}],
    "calcium":    [{"date": "YYYY-MM-DD", "Calcium": 0}],
    "phosphate":  [{"date": "YYYY-MM-DD", "Phosphate": 0}],
    "ammonia":    [{"date": "YYYY-MM-DD", "Ammonia": 0}],
    "magnesium":  [{"date": "YYYY-MM-DD", "Magnesium": 0}],
    "waterChanges": [{"date": "YYYY-MM-DD", "volumeGal": null}],
    "blog":       [{"date": "YYYY-MM-DD", "text": ""}]
  },
  "qrt": {
    "latest": {
      "temp": 0,
      "ph": 0,
      "salinity": 0,
      "nitrate": 0,
      "ammonia": 0,
      "alk": 0,
      "calcium": 0,
      "phosphate": 0,
      "magnesium": null,
      "lastDate": "YYYY-MM-DD"
    },
    "temp":       [{"date": "YYYY-MM-DD", "Temp": 0}],
    "ph":         [{"date": "YYYY-MM-DD", "pH": 0}],
    "salinity":   [{"date": "YYYY-MM-DD", "Salinity": 0}],
    "nitrate":    [{"date": "YYYY-MM-DD", "Nitrate": 0}],
    "alk":        [{"date": "YYYY-MM-DD", "ALK": 0}],
    "calcium":    [{"date": "YYYY-MM-DD", "Calcium": 0}],
    "phosphate":  [{"date": "YYYY-MM-DD", "Phosphate": 0}],
    "ammonia":    [{"date": "YYYY-MM-DD", "Ammonia": 0}],
    "magnesium":  [{"date": "YYYY-MM-DD", "Magnesium": 0}],
    "waterChanges": [{"date": "YYYY-MM-DD", "volumeGal": null}],
    "blog":       [{"date": "YYYY-MM-DD", "text": ""}]
  },
  "laurens": {
    "latest": {
      "temp": 0,
      "ph": 0,
      "salinity": 0,
      "nitrate": 0,
      "ammonia": 0,
      "alk": 0,
      "calcium": 0,
      "phosphate": 0,
      "magnesium": null,
      "lastDate": "YYYY-MM-DD"
    },
    "temp":       [{"date": "YYYY-MM-DD", "Temp": 0}],
    "ph":         [{"date": "YYYY-MM-DD", "pH": 0}],
    "salinity":   [{"date": "YYYY-MM-DD", "Salinity": 0}],
    "nitrate":    [{"date": "YYYY-MM-DD", "Nitrate": 0}],
    "alk":        [{"date": "YYYY-MM-DD", "ALK": 0}],
    "calcium":    [{"date": "YYYY-MM-DD", "Calcium": 0}],
    "phosphate":  [{"date": "YYYY-MM-DD", "Phosphate": 0}],
    "ammonia":    [{"date": "YYYY-MM-DD", "Ammonia": 0}],
    "magnesium":  [{"date": "YYYY-MM-DD", "Magnesium": 0}],
    "waterChanges": [{"date": "YYYY-MM-DD", "volumeGal": null}],
    "blog":       [{"date": "YYYY-MM-DD", "text": ""}]
  }
};
