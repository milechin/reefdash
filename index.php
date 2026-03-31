<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐠 Reef Monitor</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-annotation/3.0.1/chartjs-plugin-annotation.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<?php
$v = file_exists('tank_data.js') ? filemtime('tank_data.js') : time();
$targetsFile = __DIR__ . '/targets.json';
$savedTargets = file_exists($targetsFile) ? (json_decode(file_get_contents($targetsFile), true) ?: []) : [];
$tanksFile = __DIR__ . '/tanks.json';
$tanks = file_exists($tanksFile) ? (json_decode(file_get_contents($tanksFile), true) ?: []) : [];
$equipFile = __DIR__ . '/equipment.json';
$equipment = file_exists($equipFile) ? (json_decode(file_get_contents($equipFile), true) ?: []) : [];
?>
<script src="tank_data.js?v=<?php echo $v; ?>"></script>
<script>
const SAVED_TARGETS = <?php echo json_encode($savedTargets); ?>;
const TANK_CONFIGS  = <?php echo json_encode($tanks); ?>;
const EQUIPMENT_RAW = <?php echo json_encode($equipment); ?>;
</script>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
:root {
  --deep: #060f1e;
  --mid: #0a1a30;
  --surface: #0e2440;
  --glass: rgba(255,255,255,0.04);
  --border: rgba(0,200,255,0.12);
  --text: #d8f0ff;
  --dim: #5a8aaa;
  --biolume: #00d4ff;
  --good: #2ecc71;
  --warn: #f39c12;
  --bad: #e74c3c;
  --anemone: #a78bfa;
  --coral: #ff6b6b;
  --sand: #ffd166;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { background:var(--deep); color:var(--text); font-family:'DM Sans',sans-serif; min-height:100vh; overflow-x:hidden; }

/* animated gradient bg */
body::before {
  content:''; position:fixed; inset:0; pointer-events:none; z-index:0;
  background: radial-gradient(ellipse 70% 50% at 15% 85%, rgba(0,80,160,0.18) 0%, transparent 55%),
              radial-gradient(ellipse 50% 40% at 85% 15%, rgba(0,212,255,0.07) 0%, transparent 50%);
}

/* bubbles */
.bubbles { position:fixed; inset:0; pointer-events:none; z-index:0; overflow:hidden; }
.bubble { position:absolute; bottom:-30px; border-radius:50%; border:1px solid rgba(0,212,255,0.18); background:rgba(0,212,255,0.05); animation:rise linear infinite; }
@keyframes rise { to { transform:translateY(-110vh) scale(1.1); opacity:0; } }

.wrap { position:relative; z-index:1; max-width:1320px; margin:0 auto; padding:20px 24px 40px; }

/* HEADER */
header { display:flex; align-items:center; justify-content:space-between; padding-bottom:18px; border-bottom:1px solid var(--border); margin-bottom:24px; }
.brand { display:flex; align-items:center; gap:14px; }
.logo { width:44px; height:44px; background:linear-gradient(135deg,#004d99,#00d4ff); border-radius:11px; display:flex;align-items:center;justify-content:center;font-size:22px; box-shadow:0 0 18px rgba(0,212,255,0.25); }
.brand h1 { font-family:'Space Mono',monospace; font-size:20px; font-weight:700; letter-spacing:-0.5px; }
.brand h1 span { color:var(--biolume); }
.brand p { font-size:11px; color:var(--dim); font-family:'Space Mono',monospace; margin-top:2px; }

/* TANK TABS */
.tank-tabs { display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
.tab-btn {
  font-family:'Space Mono',monospace; font-size:11px; letter-spacing:1px; text-transform:uppercase;
  padding:9px 20px; border-radius:8px; border:1px solid var(--border);
  background:var(--glass); color:var(--dim); cursor:pointer; transition:all 0.18s;
  display:flex; align-items:center; gap:7px;
}
.tab-btn:hover { border-color:var(--biolume); color:var(--biolume); }
.tab-btn.active { background:rgba(0,212,255,0.12); border-color:var(--biolume); color:var(--biolume); }
.tab-btn .dot { width:7px; height:7px; border-radius:50%; background:var(--dim); transition:background 0.18s; }
.tab-btn.active .dot { background:var(--biolume); box-shadow:0 0 6px var(--biolume); }

/* PANEL */
.panel { display:none; }
.panel.active { display:block; }

/* section labels */
.slabel { font-family:'Space Mono',monospace; font-size:10px; letter-spacing:2px; color:var(--dim); text-transform:uppercase; margin-bottom:12px; display:flex; align-items:center; gap:10px; }
.slabel::after { content:''; flex:1; height:1px; background:var(--border); }

/* KPI GRID */
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(148px,1fr)); gap:10px; margin-bottom:28px; }
.kpi { background:var(--glass); border:1px solid var(--border); border-radius:13px; padding:16px 14px; position:relative; overflow:hidden; transition:transform 0.2s,box-shadow 0.2s; }
.kpi:hover { transform:translateY(-2px); box-shadow:0 6px 24px rgba(0,212,255,0.09); }
.kpi::before { content:''; position:absolute; top:0;left:0;right:0;height:2px; background:var(--kc,var(--biolume)); border-radius:13px 13px 0 0; }
.kpi-lbl { font-size:10px; color:var(--dim); font-family:'Space Mono',monospace; letter-spacing:0.4px; margin-bottom:7px; }
.kpi-val { font-size:26px; font-weight:600; line-height:1; color:var(--kc,var(--biolume)); margin-bottom:3px; }
.kpi-unit { font-size:12px; color:var(--dim); }
.badge { display:inline-flex; align-items:center; gap:3px; font-size:10px; font-family:'Space Mono',monospace; padding:3px 8px; border-radius:20px; margin-top:7px; }
.badge.good { background:rgba(46,204,113,0.12); color:var(--good); }
.badge.warn { background:rgba(243,156,18,0.12); color:var(--warn); }
.badge.bad  { background:rgba(231,76,60,0.12);  color:var(--bad);  }
.badge .bd  { width:5px;height:5px;border-radius:50%;background:currentColor; }

/* CHART GRID */
.chart-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:28px; }
.ccard { background:var(--glass); border:1px solid var(--border); border-radius:13px; padding:18px 16px; }
.ccard-title { font-family:'Space Mono',monospace; font-size:10px; color:var(--dim); letter-spacing:1px; margin-bottom:4px; display:flex; justify-content:space-between; align-items:center; }
.ccard-target { font-size:9px; color:var(--biolume); opacity:0.8; }
.target-legend {
  display:inline-flex; align-items:center; gap:5px;
  font-size:9px; font-family:'Space Mono',monospace;
  color:rgba(46,204,113,0.9); letter-spacing:0.3px;
}
.target-swatch {
  display:inline-block; width:18px; height:8px;
  background:rgba(46,204,113,0.18);
  border:1.5px dashed rgba(46,204,113,0.6);
  border-radius:2px; flex-shrink:0;
}

/* TARGET EDITOR MODAL */
.modal-overlay {
  display:none; position:fixed; inset:0; z-index:100;
  background:rgba(6,15,30,0.82); backdrop-filter:blur(4px);
  align-items:center; justify-content:center;
}
.modal-overlay.open { display:flex; }
.modal {
  background:#0a1a30; border:1px solid var(--border);
  border-radius:16px; padding:28px 28px 20px; width:420px; max-width:95vw;
  box-shadow:0 20px 60px rgba(0,0,0,0.6);
}
.modal-header {
  display:flex; justify-content:space-between; align-items:center;
  margin-bottom:20px;
}
.modal-header h2 {
  font-family:'Space Mono',monospace; font-size:13px; font-weight:700;
  color:var(--text); letter-spacing:0.5px;
}
.modal-close {
  background:none; border:none; color:var(--dim); cursor:pointer;
  font-size:18px; line-height:1; padding:2px 6px; border-radius:4px;
  transition:color 0.15s;
}
.modal-close:hover { color:var(--text); }
.target-row-edit {
  display:grid; grid-template-columns:1fr 90px 90px; gap:8px;
  align-items:center; padding:9px 0;
  border-bottom:1px solid rgba(255,255,255,0.04);
}
.target-row-edit:last-child { border:none; }
.target-row-edit label {
  font-size:12px; color:var(--dim); display:flex; align-items:center; gap:7px;
}
.target-row-edit label span {
  width:8px; height:8px; border-radius:50%; display:inline-block; flex-shrink:0;
}
.target-num-input {
  font-family:'Space Mono',monospace; font-size:11px; width:100%;
  background:#060f1e; border:1px solid var(--border); color:var(--text);
  padding:5px 8px; border-radius:6px; outline:none; text-align:center;
  transition:border-color 0.15s;
}
.target-num-input:focus { border-color:var(--biolume); }
.modal-footer {
  display:flex; justify-content:flex-end; gap:8px; margin-top:18px;
}
.btn-reset {
  font-family:'Space Mono',monospace; font-size:10px; padding:7px 14px;
  border-radius:6px; border:1px solid var(--border); background:transparent;
  color:var(--dim); cursor:pointer; transition:all 0.15s;
}
.btn-reset:hover { border-color:var(--biolume); color:var(--biolume); }
.btn-apply {
  font-family:'Space Mono',monospace; font-size:10px; padding:7px 16px;
  border-radius:6px; border:none; background:var(--biolume); color:#060f1e;
  cursor:pointer; font-weight:700; transition:opacity 0.15s;
}
.btn-apply:hover { opacity:0.85; }
.gear-btn {
  font-family:'Space Mono',monospace; font-size:11px; padding:5px 11px;
  border-radius:6px; border:1px solid var(--border); background:transparent;
  color:var(--dim); cursor:pointer; transition:all 0.15s; white-space:nowrap;
}
.gear-btn:hover { border-color:var(--biolume); color:var(--biolume); }
canvas { max-height:160px; }

/* BOTTOM GRID */
.bottom-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

/* TABLE CARD */
.tcard { background:var(--glass); border:1px solid var(--border); border-radius:13px; padding:18px; }
.tcard h3 { font-family:'Space Mono',monospace; font-size:10px; color:var(--dim); letter-spacing:1.5px; text-transform:uppercase; margin-bottom:14px; }
table { width:100%; border-collapse:collapse; font-size:12px; }
th { font-family:'Space Mono',monospace; font-size:9px; color:var(--dim); text-align:left; padding:0 8px 9px; letter-spacing:0.5px; border-bottom:1px solid var(--border); }
td { padding:8px 8px; border-bottom:1px solid rgba(255,255,255,0.03); vertical-align:middle; }
tr:last-child td { border:none; }
.tbadge { font-family:'Space Mono',monospace; font-size:9px; padding:2px 7px; border-radius:4px; background:rgba(0,212,255,0.1); color:var(--biolume); }
.chip { font-family:'Space Mono',monospace; font-size:10px; padding:2px 8px; border-radius:20px; white-space:nowrap; }
.chip.good { background:rgba(46,204,113,0.12); color:var(--good); }
.chip.warn { background:rgba(243,156,18,0.12); color:var(--warn); }
.chip.bad  { background:rgba(231,76,60,0.12);  color:var(--bad);  }
.chip.gray { background:rgba(255,255,255,0.05); color:var(--dim); }

/* LOG */
.log-entry { display:flex; gap:12px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.04); }
.log-entry:last-child { border:none; }
.log-date { font-family:'Space Mono',monospace; font-size:10px; color:var(--biolume); min-width:78px; padding-top:2px; }
.log-text { font-size:12px; color:var(--dim); line-height:1.5; }
.log-init { font-family:'Space Mono',monospace; font-size:9px; padding:2px 7px; border-radius:4px; background:rgba(167,139,250,0.1); color:var(--anemone); height:fit-content; white-space:nowrap; }

/* BLOG */
.blog-entry { display:flex; gap:12px; padding:12px 0; border-bottom:1px solid rgba(255,255,255,0.04); align-items:flex-start; }
.blog-entry:last-child { border:none; }
.blog-date { font-family:'Space Mono',monospace; font-size:10px; color:#a78bfa; min-width:88px; padding-top:2px; }
.blog-text { font-size:13px; color:var(--text); line-height:1.6; flex:1; white-space:pre-wrap; }
.blog-edit-btn { font-family:'Space Mono',monospace; font-size:9px; padding:2px 8px; border-radius:4px; background:rgba(167,139,250,0.1); border:1px solid rgba(167,139,250,0.3); color:#a78bfa; cursor:pointer; white-space:nowrap; flex-shrink:0; }
.blog-edit-btn:hover { background:rgba(167,139,250,0.2); }
.blog-empty { font-family:'Space Mono',monospace; font-size:11px; color:var(--dim); padding:16px 0; }
.blog-textarea { width:100%; min-height:120px; background:var(--glass); border:1px solid rgba(167,139,250,0.3); border-radius:8px; color:var(--text); font-size:13px; font-family:'DM Sans',sans-serif; padding:10px 12px; resize:vertical; box-sizing:border-box; line-height:1.6; }
.blog-textarea:focus { outline:none; border-color:#a78bfa; }

/* TARGETS */
.targets-row { display:flex; justify-content:space-between; align-items:center; padding:7px 0; border-bottom:1px solid rgba(255,255,255,0.03); font-size:12px; }
.targets-row:last-child { border:none; }
.targets-row span:first-child { color:var(--dim); }
.targets-row span:last-child  { font-family:'Space Mono',monospace; font-size:11px; color:var(--sand); }

/* LAST READING badge */
.last-badge { font-family:'Space Mono',monospace; font-size:11px; background:var(--glass); border:1px solid var(--border); padding:7px 14px; border-radius:8px; color:var(--dim); }
.last-badge strong { color:var(--biolume); }

/* DATE RANGE BAR */
.date-bar {
  display:flex; align-items:center; gap:10px; flex-wrap:wrap;
  background:var(--glass); border:1px solid var(--border);
  border-radius:10px; padding:10px 16px; margin-bottom:20px;
}
.date-bar label { font-family:'Space Mono',monospace; font-size:10px; color:var(--dim); letter-spacing:0.5px; white-space:nowrap; }
.date-input {
  font-family:'Space Mono',monospace; font-size:11px;
  background:#0a1a30; border:1px solid var(--border); color:var(--text);
  padding:6px 10px; border-radius:6px; outline:none; cursor:pointer;
  transition:border-color 0.15s;
}
.date-input:focus { border-color:var(--biolume); }
.date-input::-webkit-calendar-picker-indicator { filter:invert(0.5) sepia(1) hue-rotate(180deg); cursor:pointer; }
.preset-btns { display:flex; gap:5px; margin-left:4px; }
.preset-btn {
  font-family:'Space Mono',monospace; font-size:10px; padding:5px 11px;
  border-radius:6px; border:1px solid var(--border); background:transparent;
  color:var(--dim); cursor:pointer; transition:all 0.15s; white-space:nowrap;
}
.preset-btn:hover { border-color:var(--biolume); color:var(--biolume); }
.preset-btn.active { background:rgba(0,212,255,0.12); border-color:var(--biolume); color:var(--biolume); }
.date-sep { color:var(--dim); font-size:12px; }

/* WATER CHANGE TOGGLE */
.wc-toggle {
  font-family:'Space Mono',monospace; font-size:10px; padding:5px 12px;
  border-radius:6px; border:1px solid rgba(59,130,246,0.35);
  background:transparent; color:#60a5fa; cursor:pointer;
  transition:all 0.15s; white-space:nowrap;
  display:flex; align-items:center; gap:6px;
}
.wc-toggle:hover { background:rgba(59,130,246,0.1); border-color:#60a5fa; }
.wc-toggle.active { background:rgba(59,130,246,0.18); border-color:#60a5fa; color:#93c5fd; }
#doseToggle.active { background:rgba(251,113,133,0.15); border-color:#fb7185; color:#fda4af; }
.wc-toggle.hidden-mode { opacity:0.4; pointer-events:none; }
.wc-dot { width:7px; height:7px; border-radius:50%; background:#60a5fa; box-shadow:0 0 5px #60a5fa; }

@media(max-width:1000px) { .chart-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:720px)  { .chart-grid{grid-template-columns:1fr;} .bottom-grid{grid-template-columns:1fr;} .kpi-grid{grid-template-columns:repeat(2,1fr);} }

/* HELP PAGE */
.help-hero {
  text-align:center; padding:32px 20px 28px; margin-bottom:28px;
  background:var(--glass); border:1px solid var(--border); border-radius:16px;
}
.help-logo { font-size:48px; margin-bottom:12px; }
.help-hero h2 {
  font-family:'Space Mono',monospace; font-size:18px; font-weight:700;
  color:var(--biolume); margin-bottom:10px; letter-spacing:-0.3px;
}
.help-hero p { font-size:14px; color:var(--dim); max-width:520px; margin:0 auto; line-height:1.6; }

.help-grid {
  display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
  gap:14px; margin-bottom:24px;
}
.help-card {
  background:var(--glass); border:1px solid var(--border); border-radius:13px;
  padding:20px 18px; transition:border-color 0.2s;
}
.help-card:hover { border-color:rgba(0,212,255,0.25); }
.help-card-icon { font-size:24px; margin-bottom:10px; }
.help-card h3 {
  font-family:'Space Mono',monospace; font-size:12px; font-weight:700;
  color:var(--biolume); margin-bottom:8px; letter-spacing:0.3px;
}
.help-card p { font-size:12.5px; color:var(--dim); line-height:1.65; }
.help-card p strong { color:var(--text); font-weight:600; }

.help-footer {
  text-align:center; padding:16px;
  border-top:1px solid var(--border);
}
.help-footer p { font-size:11px; color:var(--dim); font-family:'Space Mono',monospace; }
.help-footer strong { color:var(--text); }
/* ── Flatpickr theme overrides */
.flatpickr-calendar {
  background: var(--surface) !important;
  border: 1px solid var(--border) !important;
  box-shadow: 0 8px 32px rgba(0,0,0,0.5) !important;
  border-radius: 10px !important;
  font-family: 'DM Sans', sans-serif !important;
}
.flatpickr-months .flatpickr-month,
.flatpickr-current-month,
.flatpickr-current-month .cur-month,
.flatpickr-current-month input.cur-year {
  background: var(--surface) !important;
  color: var(--text) !important;
  fill: var(--text) !important;
}
.flatpickr-weekdays, .flatpickr-weekday {
  background: var(--surface) !important;
  color: var(--dim) !important;
}
.flatpickr-day {
  color: var(--text) !important;
  border-radius: 6px !important;
}
.flatpickr-day:hover { background: rgba(0,212,255,0.15) !important; border-color: transparent !important; }
.flatpickr-day.selected, .flatpickr-day.selected:hover {
  background: var(--biolume) !important;
  border-color: var(--biolume) !important;
  color: var(--deep) !important;
  font-weight: 700 !important;
}
.flatpickr-day.today { border-color: rgba(0,212,255,0.4) !important; }
.flatpickr-day.flatpickr-disabled, .flatpickr-day.prevMonthDay, .flatpickr-day.nextMonthDay {
  color: var(--dim) !important; opacity: 0.4 !important;
}
.flatpickr-prev-month, .flatpickr-next-month { fill: var(--biolume) !important; }
.flatpickr-prev-month:hover svg, .flatpickr-next-month:hover svg { fill: var(--text) !important; }
/* Dot indicator for days with existing test entries */
.flatpickr-day.has-entry { position: relative; }
.flatpickr-day.has-entry::after {
  content: '';
  position: absolute;
  bottom: 3px;
  left: 50%;
  transform: translateX(-50%);
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--biolume);
}
.flatpickr-day.selected.has-entry::after { background: var(--deep); }
.flatpickr-day.has-wc::after {
  content: '';
  position: absolute;
  bottom: 3px;
  left: 50%;
  transform: translateX(-50%);
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #60a5fa;
}
.flatpickr-day.selected.has-wc::after { background: var(--deep); }
.flatpickr-day.has-dose::after {
  content: '';
  position: absolute;
  bottom: 3px;
  left: 50%;
  transform: translateX(-50%);
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #fb7185;
  box-shadow: 0 0 4px #fb7185;
}
.flatpickr-day.selected.has-dose::after { background: var(--deep); }
</style>
</head>
<body>

<div class="bubbles" id="bubbles"></div>

<div class="wrap">
  <header>
    <div class="brand">
      <div class="logo">🐠</div>
      <div>
        <h1>Reef <span>Monitor</span></h1>
        <p>// Fish Tank Log Dashboard</p>
      </div>
    </div>
    <div class="last-badge" id="lastBadge">Last: <strong>—</strong></div>
  </header>

  <!-- TABS -->
  <div class="tank-tabs">
    <?php foreach ($tanks as $i => $tank): ?>
    <button class="tab-btn<?php echo $i === 0 ? ' active' : ''; ?>" onclick="switchTab('<?php echo htmlspecialchars($tank['key']); ?>',this)">
      <span class="dot"></span><?php echo htmlspecialchars($tank['label']); ?>
    </button>
    <?php endforeach; ?>
    <button class="tab-btn" onclick="switchTab('help',this)" style="margin-left:auto;">
      <span class="dot"></span>Help
    </button>
  </div>

  <!-- dateBar stashed here, JS moves it into each panel after KPIs -->
  <div id="dateBarStash" style="display:none">
    <div class="date-bar" id="dateBar">
      <label>FROM</label>
      <input type="date" class="date-input" id="dateFrom" onchange="onDateChange()">
      <span class="date-sep">→</span>
      <label>TO</label>
      <input type="date" class="date-input" id="dateTo" onchange="onDateChange()">
      <div class="preset-btns">
        <button class="preset-btn active" onclick="setPreset(90,this)">90d</button>
        <button class="preset-btn" onclick="setPreset(180,this)">6mo</button>
        <button class="preset-btn" onclick="setPreset(365,this)">1yr</button>
        <button class="preset-btn" onclick="setPreset(0,this)">All</button>
      </div>
      <div style="width:1px;background:var(--border);height:22px;margin:0 6px"></div>
      <button class="wc-toggle" id="wcToggle" onclick="toggleWaterChanges()">
        <span class="wc-dot"></span>Water Changes
      </button>
      <button class="wc-toggle" id="doseToggle" onclick="toggleDose()" style="border-color:rgba(251,113,133,0.35);color:#fb7185;">
        <span class="wc-dot" style="background:#fb7185;box-shadow:0 0 5px #fb7185"></span>All For Reef Dose
      </button>
      <button onclick="openLogDose()" style="font-family:'Space Mono',monospace;font-size:11px;padding:4px 10px;background:rgba(251,113,133,0.1);border:1px solid rgba(251,113,133,0.35);color:#fb7185;border-radius:4px;cursor:pointer;letter-spacing:0.5px;">+ Log Dose</button>
      <div style="width:1px;background:var(--border);height:22px;margin:0 2px"></div>
      <button class="gear-btn" onclick="openTargetEditor()" title="Edit target ranges">⚙ Targets</button>
    </div>
  </div>

  <!-- TARGET EDITOR MODAL -->
  <div class="modal-overlay" id="targetModal" onclick="if(event.target===this)closeTargetEditor()">
    <div class="modal">
      <div class="modal-header">
        <h2>⚙ Edit Target Ranges</h2>
        <button class="modal-close" onclick="closeTargetEditor()">✕</button>
      </div>
      <div id="targetEditorRows"></div>
      <div class="modal-footer">
        <button class="btn-reset" onclick="resetTargets()">Reset Defaults</button>
        <button class="btn-apply" onclick="applyTargets()">Apply</button>
      </div>
    </div>
  </div>

  <!-- LOG TEST MODAL -->
  <div class="modal-overlay" id="logTestModal" onclick="if(event.target===this)closeLogTest()">
    <div class="modal" style="max-width:460px">
      <div class="modal-header">
        <h2>🔬 Log Water Test</h2>
        <button class="modal-close" onclick="closeLogTest()">✕</button>
      </div>
      <div style="padding:4px 0 12px; font-family:'Space Mono',monospace; font-size:11px; color:var(--dim);">
        Tank: <span id="logTestTankLabel" style="color:var(--biolume)"></span>
      </div>
      <div class="target-row-edit" style="margin-bottom:16px;">
        <label style="color:var(--text);font-weight:600;">📅 Date</label>
        <input type="text" class="target-num-input" id="logTestDate" placeholder="Select a date" readonly style="flex:1;max-width:none;border-color:rgba(0,212,255,0.4);cursor:pointer;">
      </div>
      <div id="logTestFields"></div>
      <div id="logTestMsg" style="font-family:'Space Mono',monospace;font-size:11px;min-height:18px;margin-top:8px;"></div>
      <div class="modal-footer">
        <button class="btn-reset" onclick="closeLogTest()">Cancel</button>
        <button class="btn-apply" onclick="submitLogTest()">Save Entry</button>
      </div>
    </div>
  </div>

  <!-- LOG WATER CHANGE MODAL -->
  <div class="modal-overlay" id="logWCModal" onclick="if(event.target===this)closeLogWC()">
    <div class="modal" style="max-width:400px">
      <div class="modal-header">
        <h2>💧 Log Water Change</h2>
        <button class="modal-close" onclick="closeLogWC()">✕</button>
      </div>
      <div style="padding:4px 0 12px; font-family:'Space Mono',monospace; font-size:11px; color:var(--dim);">
        Tank: <span id="logWCTankLabel" style="color:#60a5fa"></span>
      </div>
      <div class="target-row-edit" style="margin-bottom:8px;">
        <label style="color:var(--text);font-weight:600;">📅 Date</label>
        <input type="text" class="target-num-input" id="logWCDate" placeholder="Select a date" readonly style="flex:1;max-width:none;border-color:rgba(59,130,246,0.4);cursor:pointer;">
      </div>
      <div id="logWCMsg" style="font-family:'Space Mono',monospace;font-size:11px;min-height:18px;margin-top:8px;"></div>
      <div class="modal-footer">
        <button class="btn-reset" onclick="closeLogWC()">Cancel</button>
        <button id="logWCDeleteBtn" class="btn-reset" style="display:none;border-color:rgba(231,76,60,0.4);color:#e74c3c;" onclick="deleteLogWC()">🗑 Delete Entry</button>
        <button class="btn-apply" style="background:rgba(59,130,246,0.15);border-color:#60a5fa;color:#93c5fd;" onclick="submitLogWC()">Save Water Change</button>
      </div>
    </div>
  </div>

  <!-- LOG AFR DOSE MODAL -->
  <div class="modal-overlay" id="logDoseModal" onclick="if(event.target===this)closeLogDose()">
    <div class="modal" style="max-width:400px">
      <div class="modal-header">
        <h2>💉 Log AFR Dose</h2>
        <button class="modal-close" onclick="closeLogDose()">✕</button>
      </div>
      <div style="padding:4px 0 12px; font-family:'Space Mono',monospace; font-size:11px; color:var(--dim);">
        Tank: <span id="logDoseTankLabel" style="color:#fb7185"></span>
      </div>
      <div class="target-row-edit" style="margin-bottom:16px;">
        <label style="color:var(--text);font-weight:600;">📅 Date</label>
        <input type="text" class="target-num-input" id="logDoseDate" placeholder="Select a date" readonly style="flex:1;max-width:none;border-color:rgba(251,113,133,0.4);cursor:pointer;">
      </div>
      <div class="target-row-edit">
        <label><span style="background:#fb7185;display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px;"></span>AFR Dose (ml/day)</label>
        <input class="target-num-input" id="logDoseValue" type="number" step="0.1"
          placeholder="leave blank to skip" style="flex:1;max-width:none;border-color:#fb718544">
      </div>
      <div id="logDoseMsg" style="font-family:'Space Mono',monospace;font-size:11px;min-height:18px;margin-top:8px;"></div>
      <div class="modal-footer">
        <button class="btn-reset" onclick="closeLogDose()">Cancel</button>
        <button id="logDoseDeleteBtn" class="btn-reset" style="display:none;border-color:rgba(231,76,60,0.4);color:#e74c3c;" onclick="deleteLogDose()">🗑 Delete Entry</button>
        <button class="btn-apply" style="background:rgba(251,113,133,0.15);border-color:#fb7185;color:#fda4af;" onclick="submitLogDose()">Save Entry</button>
      </div>
    </div>
  </div>

  <!-- BLOG MODAL -->
  <div class="modal-overlay" id="blogModal" onclick="if(event.target===this)closeBlog()">
    <div class="modal" style="max-width:500px">
      <div class="modal-header">
        <h2>📓 <span id="blogModalTitle">Blog Entry</span></h2>
        <button class="modal-close" onclick="closeBlog()">✕</button>
      </div>
      <div style="padding:4px 0 12px; font-family:'Space Mono',monospace; font-size:11px; color:var(--dim);">
        Tank: <span id="blogTankLabel" style="color:#a78bfa"></span>
      </div>
      <div class="target-row-edit" style="margin-bottom:14px;">
        <label style="color:var(--text);font-weight:600;">📅 Date</label>
        <input type="text" class="target-num-input" id="blogDate" placeholder="Select a date" readonly style="flex:1;max-width:none;border-color:rgba(167,139,250,0.4);cursor:pointer;">
      </div>
      <textarea class="blog-textarea" id="blogText" placeholder="Write your notes for the day…"></textarea>
      <div id="blogMsg" style="font-family:'Space Mono',monospace;font-size:11px;min-height:18px;margin-top:8px;"></div>
      <div class="modal-footer">
        <button class="btn-reset" onclick="closeBlog()">Cancel</button>
        <button id="blogDeleteBtn" class="btn-reset" style="display:none;border-color:rgba(231,76,60,0.4);color:#e74c3c;" onclick="deleteBlogEntry()">🗑 Delete</button>
        <button class="btn-apply" style="background:rgba(167,139,250,0.15);border-color:#a78bfa;color:#c4b5fd;" onclick="submitBlog()">Save Entry</button>
      </div>
    </div>
  </div>

  <!-- EQUIPMENT MODAL -->
  <div class="modal-overlay" id="equipModal" onclick="if(event.target===this)closeEquip()">
    <div class="modal" style="max-width:460px">
      <div class="modal-header">
        <h2>🔧 <span id="equipModalTitle">Equipment</span></h2>
        <button class="modal-close" onclick="closeEquip()">✕</button>
      </div>
      <div style="padding:4px 0 12px; font-family:'Space Mono',monospace; font-size:11px; color:var(--dim);">
        Tank: <span id="equipTankLabel" style="color:var(--biolume)"></span>
      </div>
      <div class="target-row-edit" style="margin-bottom:10px;">
        <label style="color:var(--text);font-weight:600;">Item</label>
        <input type="text" class="target-num-input" id="equipItem" placeholder="e.g. Cobalt Heater" style="flex:1;max-width:none;">
      </div>
      <div class="target-row-edit" style="margin-bottom:10px;">
        <label style="color:var(--text);font-weight:600;">Purchased</label>
        <input type="date" class="target-num-input" id="equipPurchased" style="flex:1;max-width:none;">
      </div>
      <div class="target-row-edit" style="margin-bottom:10px;">
        <label style="color:var(--text);font-weight:600;">Expires</label>
        <input type="date" class="target-num-input" id="equipExpires" style="flex:1;max-width:none;">
      </div>
      <div class="target-row-edit" style="margin-bottom:10px;">
        <label style="color:var(--text);font-weight:600;">Comment</label>
        <input type="text" class="target-num-input" id="equipComment" placeholder="e.g. Replaced" style="flex:1;max-width:none;">
      </div>
      <div id="equipMsg" style="font-family:'Space Mono',monospace;font-size:11px;min-height:18px;margin-top:8px;"></div>
      <div class="modal-footer">
        <button class="btn-reset" onclick="closeEquip()">Cancel</button>
        <button id="equipDeleteBtn" class="btn-reset" style="display:none;border-color:rgba(231,76,60,0.4);color:#e74c3c;" onclick="deleteEquip()">🗑 Delete</button>
        <button class="btn-apply" onclick="submitEquip()">Save</button>
      </div>
    </div>
  </div>

  <?php foreach ($tanks as $i => $tank): ?>
  <div class="panel<?php echo $i === 0 ? ' active' : ''; ?>" id="panel-<?php echo htmlspecialchars($tank['key']); ?>"></div>
  <?php endforeach; ?>



  <!-- HELP PANEL -->
  <div class="panel" id="panel-help">
    <div class="help-hero">
      <div class="help-logo">🐠</div>
      <h2>Reef Monitor — Dashboard Guide</h2>
      <p>A live view of your fish tank water quality data, parsed directly from your Fish Tank Log spreadsheet.</p>
    </div>

    <div class="help-grid">

      <div class="help-card">
        <div class="help-card-icon">🗂️</div>
        <h3>Tank Tabs</h3>
        <p>Switch between tank tabs to view each tank's water chemistry independently. Switching tabs resets the date range to the last 90 days for that tank.</p>
      </div>

      <div class="help-card">
        <div class="help-card-icon">🔢</div>
        <h3>Status Cards</h3>
        <p>Each tank opens with a <strong>Maintenance</strong> row — <strong>💧 Last Water Change</strong> and <strong>🔬 Last Water Test</strong> — showing days elapsed and a status badge: <span style="color:#2ecc71">● Recent</span> (≤7 days), <span style="color:#f39c12">▲ Due Soon</span> (8–14 days), or <span style="color:#e74c3c">▲ Overdue</span> (&gt;14 days). The <strong>Current Parameters</strong> section shows the most recent reading for each parameter colour-coded against the target range.</p>
      </div>

      <div class="help-card">
        <div class="help-card-icon">📅</div>
        <h3>Date Range</h3>
        <p>The control bar between the status cards and charts filters the date window shown across all plots simultaneously. Use the <strong>From / To</strong> date pickers for a custom range, or the quick presets — <strong>90d</strong> (default), <strong>6mo</strong>, <strong>1yr</strong>, and <strong>All</strong>. All charts always share the same x-axis so readings line up across parameters.</p>
      </div>

      <div class="help-card">
        <div class="help-card-icon">📊</div>
        <h3>Parameter Charts</h3>
        <p>Seven charts are shown per tank: <strong>Temperature</strong>, <strong>pH</strong>, <strong>Salinity</strong>, <strong>Alkalinity</strong>, <strong>Calcium</strong>, <strong>Phosphate</strong>, and <strong>Nitrate</strong>. The <span style="color:rgba(46,204,113,0.9)">green shaded band</span> marks the target range. All charts share the same x-axis. When blog entries exist in the current window, a <strong style="color:rgba(167,139,250,0.9)">▼ Blog Entry</strong> legend appears — purple triangles at the top of each chart are clickable to open that entry.</p>
      </div>

      <div class="help-card">
        <div class="help-card-icon">🟦</div>
        <h3>Water Changes</h3>
        <p>Toggle <strong>Water Changes</strong> in the control bar to overlay dashed blue vertical lines on every chart, marking each recorded water change event. Use the <strong>+ LOG</strong> button on the Last Water Change card to add or delete entries. Selecting an existing date shows a <strong>🗑 Delete Entry</strong> button.</p>
      </div>

      <div class="help-card">
        <div class="help-card-icon">🩷</div>
        <h3>All For Reef Dose</h3>
        <p>Toggle <strong>All For Reef Dose</strong> to add a coral-coloured stepped line to the <strong>pH</strong>, <strong>Alkalinity</strong>, and <strong>Calcium</strong> charts with its own right-hand axis in ml/day. The line holds flat at the last recorded dose until a new value is logged. Dose is logged via <strong>+ Log Test</strong> in the Current Parameters bar.</p>
      </div>

      <div class="help-card">
        <div class="help-card-icon">🔬</div>
        <h3>Logging a Water Test</h3>
        <p>Click <strong>+ Log Test</strong> in the Current Parameters bar. Select a date — <strong>cyan dots</strong> on the calendar indicate days with existing entries, which are loaded automatically for editing. Leave any field blank to skip it. Includes fields for all 8 parameters plus <strong>AFR Dose</strong>. On save the charts refresh immediately.</p>
      </div>

      <div class="help-card">
        <div class="help-card-icon">💧</div>
        <h3>Logging a Water Change</h3>
        <p>Click <strong>+ LOG</strong> on the Last Water Change card. Select a date — <strong>blue dots</strong> indicate existing water change dates. Selecting an existing date reveals a <strong>🗑 Delete Entry</strong> button. Saved changes update the chart annotations and the Last Water Change KPI card immediately.</p>
      </div>

      <div class="help-card">
        <div class="help-card-icon">📓</div>
        <h3>Daily Log</h3>
        <p>Each tank has a <strong>Daily Log</strong> section at the bottom of its panel for free-text notes. Click <strong>+ Add Entry</strong> to write a note, or <strong>✎ Edit</strong> on an existing entry to update or delete it. Entries in the current date window appear as <strong style="color:rgba(167,139,250,0.9)">purple ▼ triangles</strong> at the top of every chart — click a triangle to view or edit that entry directly.</p>
      </div>

      <div class="help-card">
        <div class="help-card-icon">⚙️</div>
        <h3>Editing Targets</h3>
        <p>Click <strong>⚙ Targets</strong> to open the target range editor. Each parameter has editable <strong>Min</strong> and <strong>Max</strong> fields. Clicking <strong>Apply</strong> saves the values to <strong>targets.json</strong> so they persist across page loads and immediately updates all chart bands and KPI badges. <strong>Reset Defaults</strong> restores the original salt mix targets.</p>
      </div>

      <div class="help-card">
        <div class="help-card-icon">🔧</div>
        <h3>Equipment</h3>
        <p>Each tank panel has an <strong>Equipment</strong> section showing items with their expiry dates and warranty status. Items expiring within <strong>180 days</strong> are flagged in amber, <strong>expired</strong> items appear in red, and active items are shown in green. Use <strong>+ Add</strong> to add a new item or <strong>✎ Edit</strong> to update or delete an existing one. Changes are saved to <strong>equipment.json</strong> immediately.</p>
      </div>

    </div>

    <div class="help-footer">
      <p>Data sourced from <strong>Fish_Tank_Log.xlsx</strong> · Dashboard built with Chart.js · Last data: <strong id="helpLastDate">—</strong></p>
    </div>
  </div>
</div>

<script>

// ── BUBBLES
const bubblesEl = document.getElementById('bubbles');
for (let i=0;i<16;i++){const b=document.createElement('div');b.className='bubble';const s=Math.random()*18+5;b.style.cssText=`width:${s}px;height:${s}px;left:${Math.random()*100}%;animation-duration:${Math.random()*18+14}s;animation-delay:${Math.random()*10}s;`;bubblesEl.appendChild(b);}

// ── WATER CHANGE DATA
const WATER_CHANGES = {
  display: RAW.display.waterChanges,
  qrt:     RAW.qrt.waterChanges,
  laurens: RAW.laurens.waterChanges,
};

let showWaterChanges = false;

function toggleWaterChanges() {
  showWaterChanges = !showWaterChanges;
  const btn = document.getElementById('wcToggle');
  btn.classList.toggle('active', showWaterChanges);
  refreshAllCharts(currentTankKey, currentTankKey);
}

function updateWcToggleVisibility(tankKey) {
  const btn = document.getElementById('wcToggle');
  const hasData = (WATER_CHANGES[tankKey] || []).length > 0;
  btn.classList.toggle('hidden-mode', !hasData);
}

// ── CHART DEFAULTS
Chart.defaults.color='#5a8aaa';
Chart.defaults.borderColor='rgba(0,212,255,0.07)';

// ── TANK NAME LOOKUP (from tanks.json via TANK_CONFIGS)
const TANK_NAMES = Object.fromEntries(TANK_CONFIGS.map(t => [t.key, t.label]));

// ── DATE RANGE STATE
let currentTankKey = TANK_CONFIGS[0].key;
let dateFrom = null; // null = no filter
let dateTo   = null;

function getDateRange() {
  // Compute global min/max across ALL data for current tank, including blog entries
  const td = RAW[currentTankKey];
  let allDates = [];
  ['temp','ph','salinity','alk','calcium','phosphate','nitrate','ammonia'].forEach(k=>{
    (td[k]||[]).forEach(d=>allDates.push(d.date));
  });
  (td.blog || []).forEach(e => allDates.push(e.date));
  (td.dose || []).forEach(r => allDates.push(r.date));
  allDates.sort();
  return { min: allDates[0], max: allDates[allDates.length-1] };
}

function filterByDate(data) {
  return data.filter(d => {
    if (dateFrom && d.date < dateFrom) return false;
    if (dateTo   && d.date > dateTo)   return false;
    return true;
  });
}

const charts = {};
function makeChart(id, data, valueKey, color, tMin, tMax, canShowDose, masterLabels) {
  if (charts[id]) { try { charts[id].destroy(); } catch(e) {} delete charts[id]; }
  const ctx = document.getElementById(id);
  if (!ctx) return;
  // If Chart.js has a stale instance registered on this canvas (e.g. after innerHTML reset), clear it
  const existing = Chart.getChart(ctx);
  if (existing) { try { existing.destroy(); } catch(e) {} }

  // Apply date filter to actual data
  const filtered = filterByDate(data);

  // Dose data (only for eligible charts when toggle is on)
  const doseActive = canShowDose && showDose;
  const doseFiltered = doseActive ? filterByDate(DOSE_DATA[currentTankKey] || []) : [];

  // Use the shared master label array (same x-axis for every chart on this tab)
  // Fall back to building locally if not provided (e.g. initial buildTankPanel call)
  const allDates = masterLabels || (() => {
    const wcDates = showWaterChanges ? filterByDate(
      (WATER_CHANGES[currentTankKey] || []).map(d => ({date: d}))
    ).map(d => d.date) : [];
    return [...new Set([
      ...filtered.map(d => d.date),
      ...wcDates,
      ...doseFiltered.map(d => d.date)
    ])].sort();
  })();

  // Build value arrays aligned to allDates
  const dataMap = {};
  filtered.forEach(d => { dataMap[d.date] = d[valueKey]; });
  const values = allDates.map(d => dataMap[d] !== undefined ? dataMap[d] : null);

  // Show a point only on dates where this chart has an actual reading.
  // Use masterLabels.length to decide dot size consistently across all charts —
  // if the shared window is dense (many dates) keep dots small; if sparse, slightly larger.
  const dotSize = allDates.length < 60 ? 2.5 : 2;
  const pointRadii     = allDates.map(d => dataMap[d] !== undefined ? dotSize : 0);
  const pointHoverRadii = allDates.map(d => dataMap[d] !== undefined ? 5 : 0);

  const doseMap = {};
  doseFiltered.forEach(d => { doseMap[d.date] = d.dose; });
  // Forward-fill dose values; track which dates are actual entries for point display
  let lastDose = null;
  const doseValues = allDates.map(d => {
    if (doseMap[d] !== undefined) lastDose = doseMap[d];
    return lastDose;
  });
  // Point radius array: 3 on entry dates, 0 on forward-filled dates

  // Datasets
  const datasets = [{
    label: valueKey,
    data: values,
    borderColor: color,
    backgroundColor: 'transparent',
    fill: false,
    borderWidth: 1.8,
    pointRadius: pointRadii,
    pointHoverRadius: pointHoverRadii,
    tension: 0.4,
    spanGaps: true,
    yAxisID: 'y',
  }];

  if (doseActive) {
    datasets.push({
      label: 'Dose (ml/day)',
      data: doseValues,
      borderColor: 'rgba(251,113,133,0.9)',
      backgroundColor: 'transparent',
      fill: false,
      borderWidth: 1.5,
      pointRadius: 0,
      pointHoverRadius: 0,
      pointBackgroundColor: 'rgba(251,113,133,1)',
      stepped: 'before',
      tension: 0,
      yAxisID: 'yDose',
    });
  }

  // Build annotations
  const annotations = {};
  if (tMin !== undefined) {
    annotations.band = {
      type:'box', yMin:tMin, yMax:tMax,
      backgroundColor:'rgba(46,204,113,0.12)',
      borderColor:'rgba(46,204,113,0.5)', borderWidth:1.5,
      borderDash: [4, 3],
    };
  }
  if (showWaterChanges) {
    const wcSet = new Set(WATER_CHANGES[currentTankKey] || []);
    allDates.forEach((d, i) => {
      if (wcSet.has(d)) {
        annotations[`wc_${i}`] = {
          type: 'line', scaleID: 'x', value: d,
          borderColor: 'rgba(59,130,246,0.55)',
          borderWidth: 1, borderDash: [3, 3],
        };
      }
    });
  }

  // Blog annotations — purple dots at bottom of chart, clickable
  const blogSet = new Set((RAW[currentTankKey].blog || []).map(e => e.date));
  allDates.forEach((d, i) => {
    if (blogSet.has(d)) {
      annotations[`blog_${i}`] = {
        type: 'point',
        xScaleID: 'x', yScaleID: 'yBlog',
        xValue: d, yValue: 9,
        radius: 5, hitRadius: 0,
        pointStyle: 'triangle',
        rotation: 180,
        z: 10,
        backgroundColor: 'rgba(167,139,250,0.75)',
        borderColor: 'rgba(167,139,250,0.9)',
        borderWidth: 1,
      };
    }
  });

  // Scales
  const scales = {
    x: {
      grid: {color:'rgba(255,255,255,0.03)'},
      ticks: {maxTicksLimit:7, font:{family:'Space Mono',size:8}, maxRotation:45, minRotation:45}
    },
    y: {
      grid: {color:'rgba(255,255,255,0.03)'},
      ticks: {font:{family:'Space Mono',size:8}},
      position: 'left',
      grace: '25%',
    },
  };
  if (doseActive) {
    scales.yDose = {
      position: 'right',
      grid: { drawOnChartArea: false },
      ticks: {
        font: {family:'Space Mono', size:8},
        color: 'rgba(251,191,36,0.75)',
        callback: v => v + ' ml'
      },
      title: {
        display: false,
      }
    };
  }
  // Hidden scale for blog dot y-position (fixed at bottom)
  scales.yBlog = { display: false, min: 0, max: 10, offset: false };

  charts[id] = new Chart(ctx, {
    type:'line',
    data:{ labels: allDates, datasets },
    options:{
      responsive:true, maintainAspectRatio:true,
      clip: false,
      interaction:{mode:'index',intersect:false},
      onClick(event, elements, chart) {
        const anns = chart.options.plugins.annotation.annotations;
        const xScale = chart.scales.x, yScale = chart.scales.yBlog;
        if (!yScale) return;
        for (const [key, ann] of Object.entries(anns)) {
          if (!key.startsWith('blog_')) continue;
          const px = xScale.getPixelForValue(ann.xValue);
          const py = yScale.getPixelForValue(ann.yValue);
          const dist = Math.sqrt((event.x - px) ** 2 + (event.y - py) ** 2);
          if (dist <= 8) { openBlog(ann.xValue); return; }
        }
      },
      onHover(event, elements, chart) {
        const anns = chart.options.plugins.annotation.annotations;
        const xScale = chart.scales.x, yScale = chart.scales.yBlog;
        if (!yScale) return;
        let nearDot = false;
        for (const [key, ann] of Object.entries(anns)) {
          if (!key.startsWith('blog_')) continue;
          const px = xScale.getPixelForValue(ann.xValue);
          const py = yScale.getPixelForValue(ann.yValue);
          const dist = Math.sqrt((event.x - px) ** 2 + (event.y - py) ** 2);
          const isNear = dist <= 8;
          const wasLarge = ann.radius === 7;
          if (isNear && !wasLarge) {
            ann.radius = 7; ann.backgroundColor = 'rgba(167,139,250,1)';
            chart.update('none');
          } else if (!isNear && wasLarge) {
            ann.radius = 5; ann.backgroundColor = 'rgba(167,139,250,0.75)';
            chart.update('none');
          }
          if (isNear) nearDot = true;
        }
        chart.canvas.style.cursor = nearDot ? 'pointer' : '';
      },
      plugins:{
        legend:{display: doseActive, labels:{
          color:'#5a8aaa', font:{family:'Space Mono',size:8},
          boxWidth:10, padding:8,
          filter: item => item.datasetIndex === 1, // only show dose label
        }},
        tooltip:{
          backgroundColor:'#0a1a30',borderColor:'rgba(0,212,255,0.2)',borderWidth:1,
          titleFont:{family:'Space Mono',size:10},bodyFont:{family:'DM Sans'},
          filter: item => item.raw !== null,
        },
        annotation:{ annotations }
      },
      scales,
    }
  });
}

function buildMasterLabels(tankKey) {
  const td = RAW[tankKey];
  const metrics = ['temp','ph','salinity','alk','calcium','phosphate','nitrate','ammonia'];

  // Collect all measurement dates across every metric for this tank
  let dates = [];
  metrics.forEach(k => {
    (td[k] || []).forEach(d => { if (d.date) dates.push(d.date); });
  });

  // Add water change dates if toggle is on
  if (showWaterChanges) {
    (WATER_CHANGES[tankKey] || []).forEach(d => dates.push(d));
  }

  // Add dose dates if toggle is on
  if (showDose) {
    (DOSE_DATA[tankKey] || []).forEach(d => dates.push(d.date));
  }

  // Always include blog entry dates
  (RAW[tankKey].blog || []).forEach(e => dates.push(e.date));

  // Filter to current date window and deduplicate
  return [...new Set(
    dates.filter(d => {
      if (dateFrom && d < dateFrom) return false;
      if (dateTo   && d > dateTo)   return false;
      return true;
    })
  )].sort();
}

function refreshAllCharts(panelId, tankKey) {
  const td = RAW[tankKey];
  const masterLabels = buildMasterLabels(tankKey);
  CHART_DEFS.forEach(cd=>{
    const dataKey = DATA_KEY_MAP[cd.key];
    const data = (td[dataKey] || []).filter(d=>d[cd.key]!==null && d[cd.key]!==undefined);
    makeChart(`chart-${panelId}-${dataKey}`, data, cd.key, cd.color, cd.tMin, cd.tMax, cd.showDose, masterLabels);
  });
}

// Date range controls
function setPreset(days, btn) {
  document.querySelectorAll('.preset-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  if (days === 0) {
    dateFrom = null;
    dateTo   = null;
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value   = '';
  } else {
    const range = getDateRange();
    const end   = range.max;
    const start = subtractDays(end, days);
    dateFrom = start;
    dateTo   = end;
    document.getElementById('dateFrom').value = start;
    document.getElementById('dateTo').value   = end;
  }
  refreshAllCharts(currentTankKey, currentTankKey);
}

function onDateChange() {
  document.querySelectorAll('.preset-btn').forEach(b=>b.classList.remove('active'));
  dateFrom = document.getElementById('dateFrom').value || null;
  dateTo   = document.getElementById('dateTo').value   || null;
  refreshAllCharts(currentTankKey, currentTankKey);
}

function subtractDays(dateStr, days) {
  // Parse as local date to avoid UTC midnight timezone shifts
  const [y, m, d] = dateStr.split('-').map(Number);
  const date = new Date(y, m-1, d - days);
  return date.toISOString().slice(0,10);
}

function initDateInputs(tankKey) {
  const range = getDateRange();
  // Don't reset the inputs on every tab switch — only if switching tanks
  const fromEl = document.getElementById('dateFrom');
  const toEl   = document.getElementById('dateTo');
  if (!fromEl.value && !toEl.value) {
    // Leave blank = "All"
  }
}

// ── ALL FOR REEF DOSAGE DATA (live references into RAW so save.php persists entries)
const DOSE_DATA = {
  display: RAW.display.dose,
  qrt:     RAW.qrt.dose,
  laurens: RAW.laurens.dose
};

let showDose = false;

function toggleDose() {
  showDose = !showDose;
  const btn = document.getElementById('doseToggle');
  btn.classList.toggle('active', showDose);
  refreshAllCharts(currentTankKey, currentTankKey);
}

function updateDoseToggleVisibility(tankKey) {
  const btn = document.getElementById('doseToggle');
  const hasData = (DOSE_DATA[tankKey] || []).length > 0;
  btn.classList.toggle('hidden-mode', !hasData);
}

// ── KPI DEFINITIONS
const KPI_DEFS = [
  {key:'temp',    label:'Temperature', unit:'°F',  dec:1, min:76,    max:80,    color:'#00d4ff', icon:'🌡️'},
  {key:'ph',      label:'pH',          unit:'',    dec:2, min:8.1,   max:8.5,   color:'#a78bfa', icon:'⚗️'},
  {key:'salinity',label:'Salinity',    unit:'SG',  dec:3, min:1.025, max:1.027, color:'#2ecc71', icon:'🧂'},
  {key:'alk',     label:'Alkalinity',  unit:'dKH', dec:1, min:11,    max:12,    color:'#ffd166', icon:'💧'},
  {key:'calcium', label:'Calcium',     unit:'ppm', dec:0, min:435,   max:465,   color:'#f39c12', icon:'🦴'},
  {key:'phosphate',label:'Phosphate',  unit:'ppm', dec:3, min:0,     max:0.03,  color:'#ec4899', icon:'🔬'},
  {key:'nitrate', label:'Nitrate',     unit:'ppm', dec:2, min:0,     max:0.3,   color:'#06b6d4', icon:'🧪'},
  {key:'ammonia', label:'Ammonia',     unit:'ppm', dec:3, min:0,     max:0.05,  color:'#84cc16', icon:'☢️'},
];

const CHART_DEFS = [
  {key:'Temp',      label:'TEMPERATURE °F',  color:'#00d4ff', tMin:76,   tMax:80,    showDose:false},
  {key:'pH',        label:'pH',              color:'#a78bfa', tMin:8.1,  tMax:8.5,   showDose:true},
  {key:'Salinity',  label:'SALINITY',        color:'#2ecc71', tMin:1.025,tMax:1.027, showDose:false},
  {key:'ALK',       label:'ALKALINITY dKH',  color:'#ffd166', tMin:11,   tMax:12,    showDose:true},
  {key:'Calcium',   label:'CALCIUM ppm',     color:'#f39c12', tMin:435,  tMax:465,   showDose:true},
  {key:'Phosphate', label:'PHOSPHATE ppm',   color:'#ec4899', tMin:0,    tMax:0.03,  showDose:false},
  {key:'Nitrate',   label:'NITRATE ppm',    color:'#06b6d4', tMin:0,    tMax:5,     showDose:false},
];

const DATA_KEY_MAP = {Temp:'temp',pH:'ph',Salinity:'salinity',ALK:'alk',Calcium:'calcium',Phosphate:'phosphate',Nitrate:'nitrate',Ammonia:'ammonia'};

// Store defaults for reset (before applying saved targets)
const CHART_DEFS_DEFAULTS = CHART_DEFS.map(cd => ({...cd}));

// Apply persisted targets from targets.json
Object.entries(SAVED_TARGETS).forEach(([key, t]) => {
  const cd = CHART_DEFS.find(c => c.key === key);
  if (!cd) return;
  cd.tMin = t.tMin; cd.tMax = t.tMax;
  const kpi = KPI_DEFS.find(k => DATA_KEY_MAP[cd.key] === k.key);
  if (kpi) { kpi.min = t.tMin; kpi.max = t.tMax; }
});

function openTargetEditor() {
  const container = document.getElementById('targetEditorRows');
  container.innerHTML = '';
  CHART_DEFS.forEach((cd, i) => {
    if (cd.tMin === undefined) return;
    container.innerHTML += `
      <div class="target-row-edit">
        <label>
          <span style="background:${cd.color}"></span>
          ${cd.label}
        </label>
        <input class="target-num-input" id="tmin_${i}" type="number" step="any"
          value="${cd.tMin}" placeholder="Min"
          style="border-color:${cd.color}44">
        <input class="target-num-input" id="tmax_${i}" type="number" step="any"
          value="${cd.tMax}" placeholder="Max"
          style="border-color:${cd.color}44">
      </div>`;
  });
  document.getElementById('targetModal').classList.add('open');
}

function closeTargetEditor() {
  document.getElementById('targetModal').classList.remove('open');
}

function applyTargets() {
  CHART_DEFS.forEach((cd, i) => {
    if (cd.tMin === undefined) return;
    const minEl = document.getElementById(`tmin_${i}`);
    const maxEl = document.getElementById(`tmax_${i}`);
    if (!minEl || !maxEl) return;
    const newMin = parseFloat(minEl.value);
    const newMax = parseFloat(maxEl.value);
    if (!isNaN(newMin)) cd.tMin = newMin;
    if (!isNaN(newMax)) cd.tMax = newMax;
    // Also sync KPI_DEFS so status badges update
    const kpi = KPI_DEFS.find(k => k.label.toUpperCase() === cd.label || DATA_KEY_MAP[cd.key] === k.key);
    if (kpi) { kpi.min = cd.tMin; kpi.max = cd.tMax; }
  });
  closeTargetEditor();
  // Rebuild panel to refresh KPI badges + chart legends
  initialized[currentTankKey] = false;
  buildTankPanel(currentTankKey, currentTankKey);
  initialized[currentTankKey] = true;
  saveTargets();
}

function saveTargets() {
  const payload = {};
  CHART_DEFS.forEach(cd => {
    if (cd.tMin !== undefined) payload[cd.key] = {tMin: cd.tMin, tMax: cd.tMax};
  });
  fetch('save_targets.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(payload)
  }).catch(() => {}); // silent fail — targets are already applied in memory
}

function resetTargets() {
  CHART_DEFS_DEFAULTS.forEach((def, i) => {
    CHART_DEFS[i].tMin = def.tMin;
    CHART_DEFS[i].tMax = def.tMax;
  });
  // Re-open to show reset values
  openTargetEditor();
}

// ── LOG TEST MODAL
const LOG_TEST_FIELDS = [
  {key:'temp',     label:'Temperature', unit:'°F',  step:'0.1', color:'#00d4ff'},
  {key:'ph',       label:'pH',          unit:'',    step:'0.01',color:'#a78bfa'},
  {key:'salinity', label:'Salinity',    unit:'SG',  step:'0.001',color:'#2ecc71'},
  {key:'alk',      label:'Alkalinity',  unit:'dKH', step:'0.1', color:'#ffd166'},
  {key:'calcium',  label:'Calcium',     unit:'ppm', step:'1',   color:'#f39c12'},
  {key:'phosphate',label:'Phosphate',   unit:'ppm', step:'0.001',color:'#ec4899'},
  {key:'nitrate',  label:'Nitrate',     unit:'ppm',    step:'0.1',  color:'#06b6d4'},
  {key:'ammonia',  label:'Ammonia',     unit:'ppm',    step:'0.01', color:'#84cc16'},
];

const DATA_ARRAY_KEY = {temp:'temp',ph:'ph',salinity:'salinity',alk:'alk',calcium:'calcium',phosphate:'phosphate',nitrate:'nitrate',ammonia:'ammonia'};
const DATA_VAL_KEY   = {temp:'Temp',ph:'pH',salinity:'Salinity',alk:'ALK',calcium:'Calcium',phosphate:'Phosphate',nitrate:'Nitrate',ammonia:'Ammonia'};

function loadLogTestValues(date) {
  if (!date) return;
  const td = RAW[currentTankKey];
  LOG_TEST_FIELDS.forEach(f => {
    const arrKey = DATA_ARRAY_KEY[f.key];
    const valKey = DATA_VAL_KEY[f.key];
    const entry = td[arrKey].find(r => r.date === date);
    const input = document.getElementById('lt_' + f.key);
    if (input) input.value = entry != null ? entry[valKey] : '';
  });
  const msg = document.getElementById('logTestMsg');
  const hasData = LOG_TEST_FIELDS.some(f => {
    const entry = td[DATA_ARRAY_KEY[f.key]].find(r => r.date === date);
    return entry != null;
  });
  msg.style.color = 'var(--biolume)';
  msg.textContent = hasData ? '✎ Existing entry loaded — edit and save to update.' : '';
}

let _logTestFlatpickr = null;

function getTestDatesForTank(tankKey) {
  // Collect all dates that have at least one parameter entry
  const td = RAW[tankKey];
  const dateSet = new Set();
  LOG_TEST_FIELDS.forEach(f => {
    td[DATA_ARRAY_KEY[f.key]].forEach(r => dateSet.add(r.date));
  });
  return Array.from(dateSet);
}

function openLogTest() {
  document.getElementById('logTestTankLabel').textContent = TANK_NAMES[currentTankKey] || currentTankKey;

  // Build input fields
  const container = document.getElementById('logTestFields');
  container.innerHTML = LOG_TEST_FIELDS.map(f => `
    <div class="target-row-edit">
      <label><span style="background:${f.color}"></span>${f.label}${f.unit ? ' ('+f.unit+')' : ''}</label>
      <input class="target-num-input" id="lt_${f.key}" type="number" step="${f.step}"
        placeholder="leave blank to skip" style="flex:1;max-width:none;border-color:${f.color}44">
    </div>`).join('');

  document.getElementById('logTestMsg').textContent = '';
  document.getElementById('logTestModal').classList.add('open');

  // Destroy previous flatpickr instance if any
  if (_logTestFlatpickr) { _logTestFlatpickr.destroy(); _logTestFlatpickr = null; }

  const existingDates = getTestDatesForTank(currentTankKey);
  const today = new Date().toISOString().split('T')[0];

  _logTestFlatpickr = flatpickr('#logTestDate', {
    defaultDate: today,
    maxDate: today,
    dateFormat: 'Y-m-d',
    onDayCreate(dObj, dStr, fp, dayElem) {
      const d = dayElem.dateObj;
      const iso = d.getFullYear() + '-'
        + String(d.getMonth()+1).padStart(2,'0') + '-'
        + String(d.getDate()).padStart(2,'0');
      if (existingDates.includes(iso)) dayElem.classList.add('has-entry');
    },
    onChange(selectedDates, dateStr) {
      loadLogTestValues(dateStr);
    }
  });

  // Pre-load values for today if an entry exists
  loadLogTestValues(today);
}

function closeLogTest() {
  document.getElementById('logTestModal').classList.remove('open');
}

function submitLogTest() {
  const date = document.getElementById('logTestDate').value;
  if (!date) {
    document.getElementById('logTestMsg').style.color = '#e74c3c';
    document.getElementById('logTestMsg').textContent = 'Please select a date.';
    return;
  }

  const td = RAW[currentTankKey];
  let saved = 0;

  LOG_TEST_FIELDS.forEach(f => {
    const input = document.getElementById('lt_' + f.key);
    const raw = input.value.trim();
    if (raw === '') return; // skip blanks
    const val = parseFloat(raw);
    if (isNaN(val)) return;

    const arrKey = DATA_ARRAY_KEY[f.key];
    const valKey = DATA_VAL_KEY[f.key];
    const arr = td[arrKey];

    // Remove any existing entry for this date, then insert sorted
    const idx = arr.findIndex(r => r.date === date);
    if (idx >= 0) arr.splice(idx, 1);
    const entry = {date};
    entry[valKey] = val;
    const insertAt = arr.findIndex(r => r.date > date);
    if (insertAt === -1) arr.push(entry);
    else arr.splice(insertAt, 0, entry);

    // Update latest if this date is the newest
    if (!td.latest.lastDate || date >= td.latest.lastDate) {
      td.latest[f.key] = val;
      td.latest.lastDate = date;
    }
    saved++;
  });

  if (saved === 0) {
    document.getElementById('logTestMsg').style.color = '#f39c12';
    document.getElementById('logTestMsg').textContent = 'No values entered — nothing saved.';
    return;
  }

  // Recompute date window so new entry is included, preserving the active preset
  const activePreset = document.querySelector('.preset-btn.active');
  const presetDays = activePreset ? parseInt(activePreset.textContent) || 0 : 0;
  const range = getDateRange();
  if (presetDays > 0) {
    dateTo   = range.max;
    dateFrom = subtractDays(range.max, presetDays);
    document.getElementById('dateFrom').value = dateFrom;
    document.getElementById('dateTo').value   = dateTo;
  } else {
    // "All" preset — clear filters
    dateFrom = null; dateTo = null;
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value   = '';
  }

  // Rebuild the current panel to reflect new data
  initialized[currentTankKey] = false;
  buildTankPanel(currentTankKey, currentTankKey);
  initialized[currentTankKey] = true;

  // Generate updated tank_data.js content
  const jsContent = 'const RAW = ' + JSON.stringify(RAW, null, 2) + ';\n';

  // Try to POST to local server endpoint first (works when running via server.js)
  saveData(jsContent,
    () => closeLogTest(),
    (errMsg) => {
      document.getElementById('logTestMsg').style.color = '#e74c3c';
      document.getElementById('logTestMsg').textContent = '✗ Server error: ' + errMsg;
    },
    (jsContent) => {
      document.getElementById('logTestMsg').style.color = '#f39c12';
      document.getElementById('logTestMsg').textContent =
        `✓ Saved ${saved} value${saved>1?'s':''} for ${date}. No server detected — download to persist:`;
      const a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob([jsContent], {type:'application/javascript'}));
      a.download = 'tank_data.js';
      a.style.cssText = 'display:inline-block;margin-top:10px;font-family:Space Mono,monospace;font-size:10px;padding:7px 14px;background:rgba(0,212,255,0.12);border:1px solid var(--biolume);color:var(--biolume);border-radius:6px;text-decoration:none;cursor:pointer;';
      a.textContent = '⬇ Download updated tank_data.js';
      const msg = document.getElementById('logTestMsg');
      msg.appendChild(document.createElement('br'));
      msg.appendChild(a);
    }
  );
}

// ── LOG WATER CHANGE
let _logWCFlatpickr = null;

function openLogWC() {
  document.getElementById('logWCTankLabel').textContent = TANK_NAMES[currentTankKey] || currentTankKey;
  document.getElementById('logWCMsg').textContent = '';
  document.getElementById('logWCModal').classList.add('open');

  if (_logWCFlatpickr) { _logWCFlatpickr.destroy(); _logWCFlatpickr = null; }

  const wcDates = WATER_CHANGES[currentTankKey] || [];
  const today = new Date().toISOString().split('T')[0];

  const deleteBtn = document.getElementById('logWCDeleteBtn');
  deleteBtn.style.display = 'none';

  function updateDeleteBtn(date) {
    deleteBtn.style.display = date && wcDates.includes(date) ? '' : 'none';
  }

  _logWCFlatpickr = flatpickr('#logWCDate', {
    defaultDate: today,
    maxDate: today,
    dateFormat: 'Y-m-d',
    onDayCreate(dObj, dStr, fp, dayElem) {
      const d = dayElem.dateObj;
      const iso = d.getFullYear() + '-'
        + String(d.getMonth()+1).padStart(2,'0') + '-'
        + String(d.getDate()).padStart(2,'0');
      if (wcDates.includes(iso)) dayElem.classList.add('has-wc');
    },
    onChange(selectedDates, dateStr) {
      document.getElementById('logWCMsg').textContent = '';
      updateDeleteBtn(dateStr);
    }
  });

  updateDeleteBtn(today);
}

function closeLogWC() {
  document.getElementById('logWCModal').classList.remove('open');
}

function submitLogWC() {
  const date = document.getElementById('logWCDate').value;
  if (!date) {
    document.getElementById('logWCMsg').style.color = '#e74c3c';
    document.getElementById('logWCMsg').textContent = 'Please select a date.';
    return;
  }

  const wc = WATER_CHANGES[currentTankKey];
  if (!wc.includes(date)) {
    const idx = wc.findIndex(d => d > date);
    if (idx === -1) wc.push(date);
    else wc.splice(idx, 0, date);
  }

  // Rebuild panel once to refresh KPI card and charts
  initialized[currentTankKey] = false;
  buildTankPanel(currentTankKey, currentTankKey);
  initialized[currentTankKey] = true;

  const jsContent = 'const RAW = ' + JSON.stringify(RAW, null, 2) + ';\n';

  saveData(jsContent,
    () => closeLogWC(),
    (errMsg) => {
      document.getElementById('logWCMsg').style.color = '#e74c3c';
      document.getElementById('logWCMsg').textContent = '✗ Server error: ' + errMsg;
    },
    (jsContent) => {
      document.getElementById('logWCMsg').style.color = '#f39c12';
      document.getElementById('logWCMsg').textContent = `✓ Water change on ${date} logged. No server detected — download to persist:`;
      const a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob([jsContent], {type:'application/javascript'}));
      a.download = 'tank_data.js';
      a.style.cssText = 'display:inline-block;margin-top:10px;font-family:Space Mono,monospace;font-size:10px;padding:7px 14px;background:rgba(59,130,246,0.12);border:1px solid #60a5fa;color:#60a5fa;border-radius:6px;text-decoration:none;cursor:pointer;';
      a.textContent = '⬇ Download updated tank_data.js';
      const msg = document.getElementById('logWCMsg');
      msg.appendChild(document.createElement('br'));
      msg.appendChild(a);
    }
  );
}

function deleteLogWC() {
  const date = document.getElementById('logWCDate').value;
  if (!date) return;

  const wc = WATER_CHANGES[currentTankKey];
  const idx = wc.indexOf(date);
  if (idx === -1) return;
  wc.splice(idx, 1);

  initialized[currentTankKey] = false;
  buildTankPanel(currentTankKey, currentTankKey);
  initialized[currentTankKey] = true;

  const jsContent = 'const RAW = ' + JSON.stringify(RAW, null, 2) + ';\n';

  saveData(jsContent,
    () => closeLogWC(),
    (errMsg) => {
      document.getElementById('logWCMsg').style.color = '#e74c3c';
      document.getElementById('logWCMsg').textContent = '✗ Server error: ' + errMsg;
    },
    (jsContent) => {
      document.getElementById('logWCMsg').style.color = '#f39c12';
      document.getElementById('logWCMsg').textContent = `✓ Water change on ${date} deleted. No server detected — download to persist:`;
      const a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob([jsContent], {type:'application/javascript'}));
      a.download = 'tank_data.js';
      a.style.cssText = 'display:inline-block;margin-top:10px;font-family:Space Mono,monospace;font-size:10px;padding:7px 14px;background:rgba(231,76,60,0.12);border:1px solid #e74c3c;color:#e74c3c;border-radius:6px;text-decoration:none;cursor:pointer;';
      a.textContent = '⬇ Download updated tank_data.js';
      const msg = document.getElementById('logWCMsg');
      msg.appendChild(document.createElement('br'));
      msg.appendChild(a);
    }
  );
}

// ── LOG AFR DOSE
let _logDoseFlatpickr = null;

function openLogDose() {
  document.getElementById('logDoseTankLabel').textContent = TANK_NAMES[currentTankKey] || currentTankKey;
  document.getElementById('logDoseMsg').textContent = '';
  document.getElementById('logDoseValue').value = '';
  document.getElementById('logDoseModal').classList.add('open');

  if (_logDoseFlatpickr) { _logDoseFlatpickr.destroy(); _logDoseFlatpickr = null; }

  const doseDateSet = new Set((RAW[currentTankKey].dose || []).map(r => r.date));
  const today = new Date().toISOString().split('T')[0];

  const deleteBtn = document.getElementById('logDoseDeleteBtn');
  deleteBtn.style.display = 'none';

  function loadDoseValue(date) {
    if (!date) return;
    const entry = (RAW[currentTankKey].dose || []).find(r => r.date === date);
    document.getElementById('logDoseValue').value = entry != null ? entry.dose : '';
    deleteBtn.style.display = entry != null ? '' : 'none';
    const msg = document.getElementById('logDoseMsg');
    msg.style.color = 'var(--biolume)';
    msg.textContent = entry != null ? '✎ Existing entry loaded — edit and save to update.' : '';
  }

  _logDoseFlatpickr = flatpickr('#logDoseDate', {
    defaultDate: today,
    maxDate: today,
    dateFormat: 'Y-m-d',
    onDayCreate(dObj, dStr, fp, dayElem) {
      const d = dayElem.dateObj;
      const iso = d.getFullYear() + '-'
        + String(d.getMonth()+1).padStart(2,'0') + '-'
        + String(d.getDate()).padStart(2,'0');
      if (doseDateSet.has(iso)) dayElem.classList.add('has-dose');
    },
    onChange(selectedDates, dateStr) {
      document.getElementById('logDoseMsg').textContent = '';
      loadDoseValue(dateStr);
    }
  });

  loadDoseValue(today);
}

function closeLogDose() {
  document.getElementById('logDoseModal').classList.remove('open');
}

function submitLogDose() {
  const date = document.getElementById('logDoseDate').value;
  if (!date) {
    document.getElementById('logDoseMsg').style.color = '#e74c3c';
    document.getElementById('logDoseMsg').textContent = 'Please select a date.';
    return;
  }
  const raw = document.getElementById('logDoseValue').value.trim();
  if (raw === '') {
    document.getElementById('logDoseMsg').style.color = '#f39c12';
    document.getElementById('logDoseMsg').textContent = 'No value entered — nothing saved.';
    return;
  }
  const val = parseFloat(raw);
  if (isNaN(val)) {
    document.getElementById('logDoseMsg').style.color = '#e74c3c';
    document.getElementById('logDoseMsg').textContent = 'Invalid value.';
    return;
  }

  const td = RAW[currentTankKey];
  if (!td.dose) td.dose = [];
  const arr = td.dose;

  const idx = arr.findIndex(r => r.date === date);
  if (idx >= 0) arr.splice(idx, 1);
  const entry = {date, dose: val};
  const insertAt = arr.findIndex(r => r.date > date);
  if (insertAt === -1) arr.push(entry);
  else arr.splice(insertAt, 0, entry);

  // Recompute date window so new entry is included
  const activePreset = document.querySelector('.preset-btn.active');
  const presetMatch = activePreset && activePreset.getAttribute('onclick')?.match(/setPreset\((\d+)/);
  const presetDays = presetMatch ? parseInt(presetMatch[1]) : 0;
  const range = getDateRange();
  if (presetDays > 0) {
    dateTo   = range.max;
    dateFrom = subtractDays(range.max, presetDays);
    document.getElementById('dateFrom').value = dateFrom;
    document.getElementById('dateTo').value   = dateTo;
  } else {
    dateFrom = null; dateTo = null;
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value   = '';
  }

  initialized[currentTankKey] = false;
  buildTankPanel(currentTankKey, currentTankKey);
  initialized[currentTankKey] = true;

  const jsContent = 'const RAW = ' + JSON.stringify(RAW, null, 2) + ';\n';
  saveData(jsContent,
    () => closeLogDose(),
    (errMsg) => {
      document.getElementById('logDoseMsg').style.color = '#e74c3c';
      document.getElementById('logDoseMsg').textContent = '✗ Server error: ' + errMsg;
    },
    (jsContent) => {
      document.getElementById('logDoseMsg').style.color = '#f39c12';
      document.getElementById('logDoseMsg').textContent = `✓ Saved dose for ${date}. No server detected — download to persist:`;
      const a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob([jsContent], {type:'application/javascript'}));
      a.download = 'tank_data.js';
      a.style.cssText = 'display:inline-block;margin-top:10px;font-family:Space Mono,monospace;font-size:10px;padding:7px 14px;background:rgba(251,113,133,0.12);border:1px solid #fb7185;color:#fda4af;border-radius:6px;text-decoration:none;cursor:pointer;';
      a.textContent = '⬇ Download updated tank_data.js';
      const msg = document.getElementById('logDoseMsg');
      msg.appendChild(document.createElement('br'));
      msg.appendChild(a);
    }
  );
}

function deleteLogDose() {
  const date = document.getElementById('logDoseDate').value;
  if (!date) return;
  const td = RAW[currentTankKey];
  const arr = td.dose || [];
  const idx = arr.findIndex(r => r.date === date);
  if (idx < 0) return;
  arr.splice(idx, 1);

  initialized[currentTankKey] = false;
  buildTankPanel(currentTankKey, currentTankKey);
  initialized[currentTankKey] = true;

  const jsContent = 'const RAW = ' + JSON.stringify(RAW, null, 2) + ';\n';
  saveData(jsContent,
    () => closeLogDose(),
    (errMsg) => {
      document.getElementById('logDoseMsg').style.color = '#e74c3c';
      document.getElementById('logDoseMsg').textContent = '✗ Server error: ' + errMsg;
    },
    (jsContent) => {
      document.getElementById('logDoseMsg').style.color = '#f39c12';
      document.getElementById('logDoseMsg').textContent = `✓ Deleted dose for ${date}. No server detected — download to persist:`;
      const a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob([jsContent], {type:'application/javascript'}));
      a.download = 'tank_data.js';
      a.style.cssText = 'display:inline-block;margin-top:10px;font-family:Space Mono,monospace;font-size:10px;padding:7px 14px;background:rgba(251,113,133,0.12);border:1px solid #fb7185;color:#fda4af;border-radius:6px;text-decoration:none;cursor:pointer;';
      a.textContent = '⬇ Download updated tank_data.js';
      const msg = document.getElementById('logDoseMsg');
      msg.appendChild(document.createElement('br'));
      msg.appendChild(a);
    }
  );
}

// ── BLOG
let _blogFlatpickr = null;

function openBlog(editDate) {
  const td = RAW[currentTankKey];
  document.getElementById('blogTankLabel').textContent = TANK_NAMES[currentTankKey] || currentTankKey;
  document.getElementById('blogMsg').textContent = '';

  const existingDates = (td.blog || []).map(e => e.date);
  const today = new Date().toISOString().split('T')[0];
  const targetDate = editDate || today;

  document.getElementById('blogModalTitle').textContent = editDate ? 'Edit Entry' : 'New Entry';
  document.getElementById('blogModal').classList.add('open');

  if (_blogFlatpickr) { _blogFlatpickr.destroy(); _blogFlatpickr = null; }

  _blogFlatpickr = flatpickr('#blogDate', {
    defaultDate: targetDate,
    maxDate: today,
    dateFormat: 'Y-m-d',
    onDayCreate(dObj, dStr, fp, dayElem) {
      const d = dayElem.dateObj;
      const iso = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
      if (existingDates.includes(iso)) {
        dayElem.style.position = 'relative';
        const dot = document.createElement('span');
        dot.style.cssText = 'position:absolute;bottom:3px;left:50%;transform:translateX(-50%);width:4px;height:4px;border-radius:50%;background:#a78bfa;';
        dayElem.appendChild(dot);
      }
    },
    onChange(selectedDates, dateStr) {
      loadBlogEntry(dateStr);
    }
  });

  loadBlogEntry(targetDate);
}

function loadBlogEntry(date) {
  const td = RAW[currentTankKey];
  const entry = (td.blog || []).find(e => e.date === date);
  document.getElementById('blogText').value = entry ? entry.text : '';
  document.getElementById('blogDeleteBtn').style.display = entry ? '' : 'none';
  document.getElementById('blogMsg').textContent = entry ? '✎ Existing entry loaded — edit and save to update.' : '';
  document.getElementById('blogMsg').style.color = '#a78bfa';
}

function closeBlog() {
  document.getElementById('blogModal').classList.remove('open');
  if (_blogFlatpickr) { _blogFlatpickr.destroy(); _blogFlatpickr = null; }
}

function submitBlog() {
  const date = document.getElementById('blogDate').value;
  const text = document.getElementById('blogText').value.trim();
  if (!date) {
    document.getElementById('blogMsg').style.color = '#e74c3c';
    document.getElementById('blogMsg').textContent = 'Please select a date.';
    return;
  }
  if (!text) {
    document.getElementById('blogMsg').style.color = '#e74c3c';
    document.getElementById('blogMsg').textContent = 'Please enter some text.';
    return;
  }

  const td = RAW[currentTankKey];
  if (!td.blog) td.blog = [];
  const idx = td.blog.findIndex(e => e.date === date);
  if (idx !== -1) {
    td.blog[idx].text = text;
  } else {
    td.blog.push({date, text});
    td.blog.sort((a,b) => a.date.localeCompare(b.date));
  }

  initialized[currentTankKey] = false;
  buildTankPanel(currentTankKey, currentTankKey);
  initialized[currentTankKey] = true;

  const jsContent = 'const RAW = ' + JSON.stringify(RAW, null, 2) + ';\n';
  saveData(jsContent,
    () => closeBlog(),
    (errMsg) => {
      document.getElementById('blogMsg').style.color = '#e74c3c';
      document.getElementById('blogMsg').textContent = '✗ Server error: ' + errMsg;
    },
    (jsContent) => {
      document.getElementById('blogMsg').style.color = '#f39c12';
      document.getElementById('blogMsg').textContent = `✓ Entry saved. No server detected — download to persist:`;
      const a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob([jsContent], {type:'application/javascript'}));
      a.download = 'tank_data.js';
      a.style.cssText = 'display:inline-block;margin-top:10px;font-family:Space Mono,monospace;font-size:10px;padding:7px 14px;background:rgba(167,139,250,0.12);border:1px solid #a78bfa;color:#a78bfa;border-radius:6px;text-decoration:none;cursor:pointer;';
      a.textContent = '⬇ Download updated tank_data.js';
      const msg = document.getElementById('blogMsg');
      msg.appendChild(document.createElement('br'));
      msg.appendChild(a);
    }
  );
}

function deleteBlogEntry() {
  const date = document.getElementById('blogDate').value;
  if (!date) return;
  const td = RAW[currentTankKey];
  const idx = (td.blog || []).findIndex(e => e.date === date);
  if (idx === -1) return;
  td.blog.splice(idx, 1);

  initialized[currentTankKey] = false;
  buildTankPanel(currentTankKey, currentTankKey);
  initialized[currentTankKey] = true;

  const jsContent = 'const RAW = ' + JSON.stringify(RAW, null, 2) + ';\n';
  saveData(jsContent,
    () => closeBlog(),
    (errMsg) => {
      document.getElementById('blogMsg').style.color = '#e74c3c';
      document.getElementById('blogMsg').textContent = '✗ Server error: ' + errMsg;
    },
    (jsContent) => {
      document.getElementById('blogMsg').style.color = '#f39c12';
      document.getElementById('blogMsg').textContent = `✓ Entry deleted. No server detected — download to persist:`;
      const a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob([jsContent], {type:'application/javascript'}));
      a.download = 'tank_data.js';
      a.style.cssText = 'display:inline-block;margin-top:10px;font-family:Space Mono,monospace;font-size:10px;padding:7px 14px;background:rgba(231,76,60,0.12);border:1px solid #e74c3c;color:#e74c3c;border-radius:6px;text-decoration:none;cursor:pointer;';
      a.textContent = '⬇ Download updated tank_data.js';
      const msg = document.getElementById('blogMsg');
      msg.appendChild(document.createElement('br'));
      msg.appendChild(a);
    }
  );
}

// ── SHARED SAVE HELPER
// onSuccess: called when server confirms write
// onServerError: called when server responded but reported an error
// onNoServer: called when fetch failed entirely (no server running) — receives jsContent for download
const SAVE_URL = 'save.php';

function saveData(jsContent, onSuccess, onServerError, onNoServer) {
  fetch(SAVE_URL, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({content: jsContent})
  })
  .then(r => {
    // Try to parse JSON regardless of status code so we can show the error
    return r.json().then(result => ({ ok: r.ok, result }));
  })
  .then(({ ok, result }) => {
    if (result.ok) {
      onSuccess();
    } else {
      onServerError(result.error || `HTTP ${ok ? 200 : 'error'}: unknown`);
    }
  })
  .catch(err => {
    // Only fall through to download if it's a network error (fetch couldn't connect at all)
    if (err instanceof TypeError) {
      onNoServer(jsContent);
    } else {
      onServerError(err.message || 'Unknown error');
    }
  });
}

function statusOf(v,min,max) {
  if (v===null||v===undefined) return null;
  if (v>=min&&v<=max) return 'good';
  if (v<min) return 'warn';
  return 'bad';
}
const statusText = {good:'● In Range', warn:'▼ Low', bad:'▲ High'};

function buildTankPanel(panelId, tankKey) {
  const panel = document.getElementById('panel-'+panelId);
  if (!panel) return;
  const td = RAW[tankKey];

  // Days since last water change
  const _today = new Date().toISOString().split('T')[0];
  const wcDates = (WATER_CHANGES[tankKey] || []).filter(d => d <= _today).sort();
  const lastWc = wcDates[wcDates.length - 1] || null;
  let daysSinceWc = null;
  if (lastWc) {
    const [y,m,d] = lastWc.split('-').map(Number);
    const then = new Date(y, m-1, d);
    const now  = new Date();
    daysSinceWc = Math.floor((now - then) / 86400000);
  }
  const wcStatus = daysSinceWc === null ? '' :
    daysSinceWc <= 7  ? `<span class="badge good"><span class="bd"></span>● Recent</span>` :
    daysSinceWc <= 14 ? `<span class="badge warn"><span class="bd"></span>▲ Due Soon</span>` :
                        `<span class="badge bad"><span class="bd"></span>▲ Overdue</span>`;
  const wcCard = `<div class="kpi" style="--kc:#60a5fa">
    <div class="kpi-lbl" style="display:flex;justify-content:space-between;align-items:center">
      <span>💧 LAST WATER CHANGE</span>
      <button onclick="openLogWC()" style="font-family:'Space Mono',monospace;font-size:11px;padding:4px 10px;background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.4);color:#60a5fa;border-radius:4px;cursor:pointer;letter-spacing:0.5px;">+ LOG</button>
    </div>
    <div class="kpi-val" style="font-size:22px">${daysSinceWc !== null ? daysSinceWc : '—'}</div>
    <div class="kpi-unit">${lastWc ? 'days ago · ' + lastWc : 'no data'}</div>
    ${wcStatus}
  </div>`;

  // Days since last water test (most recent date any parameter was recorded)
  const allMetricDates = ['temp','ph','salinity','alk','calcium','phosphate','nitrate','ammonia']
    .flatMap(k => (td[k] || []).map(r => r.date))
    .filter(d => d <= _today)
    .sort();
  const lastTest = allMetricDates[allMetricDates.length - 1] || null;
  let daysSinceTest = null;
  if (lastTest) {
    const [y,m,d] = lastTest.split('-').map(Number);
    const then = new Date(y, m-1, d);
    const now  = new Date();
    daysSinceTest = Math.floor((now - then) / 86400000);
  }
  const testStatus = daysSinceTest === null ? '' :
    daysSinceTest <= 7  ? `<span class="badge good"><span class="bd"></span>● Recent</span>` :
    daysSinceTest <= 14 ? `<span class="badge warn"><span class="bd"></span>▲ Due Soon</span>` :
                          `<span class="badge bad"><span class="bd"></span>▲ Overdue</span>`;
  const testCard = `<div class="kpi" style="--kc:#a78bfa">
    <div class="kpi-lbl">🔬 LAST WATER TEST</div>
    <div class="kpi-val" style="font-size:22px">${daysSinceTest !== null ? daysSinceTest : '—'}</div>
    <div class="kpi-unit">${lastTest ? 'days ago · ' + lastTest : 'no data'}</div>
    ${testStatus}
  </div>`;

  // KPIs
  let kpiHtml = '<div class="slabel">Maintenance</div>'
    + '<div class="kpi-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:20px">' + wcCard + testCard + '</div>'
    + '<div class="slabel">Current Parameters <button onclick="openLogTest()" style="font-family:\'Space Mono\',monospace;font-size:11px;padding:4px 10px;background:rgba(0,212,255,0.1);border:1px solid rgba(0,212,255,0.35);color:var(--biolume);border-radius:4px;cursor:pointer;letter-spacing:0.5px;text-transform:none;">+ Log Test</button></div><div class="kpi-grid">';
  KPI_DEFS.forEach(k=>{
    const v = td.latest[k.key];
    const s = statusOf(v,k.min,k.max);
    const dv = v!==null&&v!==undefined ? v.toFixed(k.dec) : '—';
    kpiHtml += `<div class="kpi" style="--kc:${k.color}">
      <div class="kpi-lbl">${k.icon} ${k.label.toUpperCase()}</div>
      <div class="kpi-val">${dv}</div>
      <div class="kpi-unit">${k.unit}</div>
      ${s ? `<span class="badge ${s}"><span class="bd"></span>${statusText[s]}</span>` : ''}
    </div>`;
  });
  kpiHtml += '</div>';

  // Charts — anchor div receives the dateBar element via JS after render
  let chartHtml = '<div id="controls-anchor-'+panelId+'"></div>'
    + '<div class="slabel">Parameter Trends</div><div class="chart-grid">';
  const hasBlogInWindow = (td.blog || []).some(e =>
    (!dateFrom || e.date >= dateFrom) && (!dateTo || e.date <= dateTo)
  );
  const blogLegend = hasBlogInWindow
    ? `<span class="target-legend" style="color:rgba(167,139,250,0.9)">▼&nbsp;Blog&nbsp;Entry</span>`
    : '';
  CHART_DEFS.forEach(cd=>{
    const dataKey = DATA_KEY_MAP[cd.key];
    const targetLabel = cd.tMin !== undefined
      ? `<span class="target-legend"><span class="target-swatch"></span>Target&nbsp;${cd.tMin}–${cd.tMax}</span>`
      : '';
    chartHtml += `<div class="ccard">
      <div class="ccard-title">${cd.label}<span style="display:inline-flex;gap:8px;align-items:center">${targetLabel}${blogLegend}</span></div>
      <canvas id="chart-${panelId}-${dataKey}" height="160"></canvas>
    </div>`;
  });
  chartHtml += '</div>';

  // Blog section
  const blogEntries = (td.blog || []).slice().sort((a,b) => b.date.localeCompare(a.date));
  let blogHtml = `<div class="slabel">Daily Log <button onclick="openBlog()" style="font-family:'Space Mono',monospace;font-size:11px;padding:4px 10px;background:rgba(167,139,250,0.1);border:1px solid rgba(167,139,250,0.35);color:#a78bfa;border-radius:4px;cursor:pointer;letter-spacing:0.5px;text-transform:none;">+ Add Entry</button></div>`
    + '<div class="tcard">';
  if (blogEntries.length === 0) {
    blogHtml += '<div class="blog-empty">No entries yet.</div>';
  } else {
    blogEntries.forEach(e => {
      blogHtml += `<div class="blog-entry">
        <div class="blog-date">${e.date}</div>
        <div class="blog-text">${e.text.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
        <button class="blog-edit-btn" onclick="openBlog('${e.date}')">✎ Edit</button>
      </div>`;
    });
  }
  blogHtml += '</div>';

  // Equipment section
  const tankEquip = EQUIPMENT_RAW.filter(e => e.tank === tankKey);
  let equipHtml = `<div class="slabel">Equipment <button onclick="openEquip()" style="font-family:'Space Mono',monospace;font-size:11px;padding:4px 10px;background:rgba(0,212,255,0.1);border:1px solid rgba(0,212,255,0.35);color:var(--biolume);border-radius:4px;cursor:pointer;letter-spacing:0.5px;text-transform:none;">+ Add</button></div>`;
  if (tankEquip.length > 0) {
    equipHtml += '<div class="tcard"><table>'
      + '<thead><tr><th>ITEM</th><th>EXPIRES</th><th>STATUS</th><th></th></tr></thead><tbody>';
    tankEquip.forEach((e, i) => {
      const idx = EQUIPMENT_RAW.indexOf(e);
      equipHtml += equipRowHtml(e, false, idx);
    });
    equipHtml += '</tbody></table></div>';
  }

  // Rescue dateBar before innerHTML wipe (it may currently be a child of this panel)
  const dateBarEl = document.getElementById('dateBar');
  const dateBarStash = document.getElementById('dateBarStash');
  if (dateBarEl && dateBarStash) dateBarStash.appendChild(dateBarEl);

  // Clear and repopulate the panel
  panel.innerHTML = kpiHtml + chartHtml + blogHtml + equipHtml;

  // Move the dateBar element into the anchor slot between KPIs and charts
  const anchor = document.getElementById('controls-anchor-' + panelId);
  const dateBar = document.getElementById('dateBar');
  if (anchor && dateBar) {
    anchor.appendChild(dateBar);
    dateBar.style.display = 'flex';
    dateBar.style.marginBottom = '20px';
  }

  // Render charts
  const masterLabels = buildMasterLabels(tankKey);
  CHART_DEFS.forEach(cd=>{
    const dataKey = DATA_KEY_MAP[cd.key];
    const data = (td[dataKey] || []).filter(d=>d[cd.key]!==null && d[cd.key]!==undefined);
    makeChart(`chart-${panelId}-${dataKey}`, data, cd.key, cd.color, cd.tMin, cd.tMax, cd.showDose, masterLabels);
  });
}

// ── EQUIPMENT HELPERS
function equipDaysLeft(expires) {
  if (!expires) return null;
  const now = new Date(); now.setHours(0,0,0,0);
  const exp = new Date(expires);
  return Math.round((exp - now) / 86400000);
}

function equipRowHtml(e, showTank, idx) {
  const d = equipDaysLeft(e.expires);
  let cls='gray', txt='No expiry';
  if (e.comment==='Replaced')  { cls='gray'; txt='Replaced'; }
  else if (d===null)            { cls='gray'; txt='No expiry'; }
  else if (d<0)                 { cls='bad';  txt='Expired'; }
  else if (d<180)               { cls='warn'; txt=`${d}d left`; }
  else                          { cls='good'; txt=`${d}d left`; }
  const tankCell = showTank ? `<td><span class="tbadge">${TANK_NAMES[e.tank]||e.tank}</span></td>` : '';
  const editCell = idx !== undefined
    ? `<td><button class="blog-edit-btn" onclick="openEquip(${idx})">✎ Edit</button></td>`
    : '<td></td>';
  return `<tr>
    ${tankCell}
    <td style="font-size:11px">${e.item}</td>
    <td style="font-family:'Space Mono',monospace;font-size:10px;color:#5a8aaa">${e.expires||'—'}</td>
    <td><span class="chip ${cls}">${txt}</span></td>
    ${editCell}
  </tr>`;
}

// ── EQUIPMENT MODAL
let _equipEditIdx = null;

function openEquip(idx) {
  _equipEditIdx = idx !== undefined ? idx : null;
  const isEdit = _equipEditIdx !== null;
  document.getElementById('equipModalTitle').textContent = isEdit ? 'Edit Equipment' : 'Add Equipment';
  document.getElementById('equipTankLabel').textContent = TANK_NAMES[currentTankKey] || currentTankKey;
  document.getElementById('equipMsg').textContent = '';
  document.getElementById('equipDeleteBtn').style.display = isEdit ? '' : 'none';

  if (isEdit) {
    const e = EQUIPMENT_RAW[_equipEditIdx];
    document.getElementById('equipItem').value      = e.item      || '';
    document.getElementById('equipPurchased').value = e.purchased || '';
    document.getElementById('equipExpires').value   = e.expires   || '';
    document.getElementById('equipComment').value   = e.comment   || '';
  } else {
    document.getElementById('equipItem').value      = '';
    document.getElementById('equipPurchased').value = '';
    document.getElementById('equipExpires').value   = '';
    document.getElementById('equipComment').value   = '';
  }
  document.getElementById('equipModal').classList.add('open');
}

function closeEquip() {
  document.getElementById('equipModal').classList.remove('open');
}

function saveEquipAndRebuild(onSuccess) {
  fetch('save_equipment.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(EQUIPMENT_RAW)
  })
  .then(r => r.json())
  .then(res => {
    if (res.ok) {
      onSuccess();
    } else {
      document.getElementById('equipMsg').style.color = '#e74c3c';
      document.getElementById('equipMsg').textContent = '✗ Server error: ' + res.error;
    }
  })
  .catch(() => {
    document.getElementById('equipMsg').style.color = '#f39c12';
    document.getElementById('equipMsg').textContent = '✗ Could not reach server.';
  });
}

function rebuildEquipViews() {
  initialized[currentTankKey] = false;
  buildTankPanel(currentTankKey, currentTankKey);
  initialized[currentTankKey] = true;
}

function submitEquip() {
  const item = document.getElementById('equipItem').value.trim();
  if (!item) {
    document.getElementById('equipMsg').style.color = '#e74c3c';
    document.getElementById('equipMsg').textContent = 'Item name is required.';
    return;
  }
  const entry = {
    tank:      currentTankKey,
    item,
    purchased: document.getElementById('equipPurchased').value || null,
    expires:   document.getElementById('equipExpires').value   || null,
    comment:   document.getElementById('equipComment').value.trim(),
  };

  if (_equipEditIdx !== null) {
    EQUIPMENT_RAW[_equipEditIdx] = entry;
  } else {
    EQUIPMENT_RAW.push(entry);
  }

  saveEquipAndRebuild(() => { rebuildEquipViews(); closeEquip(); });
}

function deleteEquip() {
  if (_equipEditIdx === null) return;
  EQUIPMENT_RAW.splice(_equipEditIdx, 1);
  saveEquipAndRebuild(() => { rebuildEquipViews(); closeEquip(); });
}

// ── TAB SWITCHING
const initialized = Object.fromEntries(TANK_CONFIGS.map(t => [t.key, false]));
const TANK_TABS = new Set(TANK_CONFIGS.map(t => t.key));

function switchTab(key, btn) {
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
  document.getElementById('panel-'+key).classList.add('active');

  if (TANK_TABS.has(key)) {
    // Reset date range when switching between tanks — default to 90d
    if (key !== currentTankKey) {
      currentTankKey = key;
      const range = getDateRange();
      dateTo   = range.max;
      dateFrom = subtractDays(range.max, 90);
      document.getElementById('dateFrom').value = dateFrom;
      document.getElementById('dateTo').value   = dateTo;
      document.querySelectorAll('.preset-btn').forEach((b,i)=>b.classList.toggle('active', i===0));
    }

    updateWcToggleVisibility(key);
    updateDoseToggleVisibility(key);

    if (!initialized[key]) {
      buildTankPanel(key, key);
      initialized[key] = true;
    } else {
      // Re-render charts with current date range
      refreshAllCharts(key, key);
      // Move dateBar into this panel's anchor
      const anchor = document.getElementById('controls-anchor-' + key);
      const dateBar = document.getElementById('dateBar');
      if (anchor && dateBar) anchor.appendChild(dateBar);
    }

    const tankData = RAW[key];
    document.getElementById('lastBadge').innerHTML = `Last: <strong>${tankData.latest.lastDate || '—'}</strong>`;
  } else if (key === 'help') {
    document.getElementById('lastBadge').innerHTML = `<strong>Fish Tank Log</strong>`;
    // Populate the last date on the help page from the most recent reading across all tanks
    const dates = TANK_CONFIGS.map(t => RAW[t.key]?.latest?.lastDate).filter(Boolean).sort();
    const latestDate = dates[dates.length - 1] || '—';
    const el = document.getElementById('helpLastDate');
    if (el) el.textContent = latestDate;
  } else {
    document.getElementById('lastBadge').innerHTML = `<strong>Fish Tank Log</strong>`;
  }
}

// init first tank tab — default to 90 days
currentTankKey = TANK_CONFIGS[0].key;
const _initRange = getDateRange();
dateTo   = _initRange.max;
dateFrom = subtractDays(_initRange.max, 90);
document.getElementById('dateFrom').value = dateFrom;
document.getElementById('dateTo').value   = dateTo;
buildTankPanel(currentTankKey, currentTankKey);
initialized[currentTankKey] = true;
document.getElementById('lastBadge').innerHTML = `Last: <strong>${RAW[currentTankKey].latest.lastDate}</strong>`;
updateWcToggleVisibility(currentTankKey);
updateDoseToggleVisibility(currentTankKey);
</script>
</body>
</html>
