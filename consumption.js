// consumption.js — pure, DOM-free engine for the consumption-rate feature.
// Works as a browser global (window.Consumption) and as a node module (require) for tests.
// Implements the Consumption Rate spec (§5 formulas, §8 quality flags, §9 edges) plus bottle helpers.
(function (root) {
  'use strict';

  const GAL_TO_L = 3.78541;

  // ── date helpers (YYYY-MM-DD) ────────────────────────────────────────────
  function daysBetween(fromISO, toISO) {
    const a = new Date(fromISO + 'T00:00:00');
    const b = new Date(toISO + 'T00:00:00');
    return Math.round((b - a) / 86400000);
  }
  function addDays(iso, n) {
    const d = new Date(iso + 'T00:00:00');
    d.setDate(d.getDate() + n);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }
  const maxISO = (a, b) => (a > b ? a : b);
  const minISO = (a, b) => (a < b ? a : b);

  // ── tank-derived values ──────────────────────────────────────────────────
  function tankVolumeL(consumption, tank) {
    const v = consumption && consumption.tanks && consumption.tanks[tank] && consumption.tanks[tank].volumeGal;
    return v ? v * GAL_TO_L : null;
  }
  function wcFraction(consumption, tank) {
    const t = (consumption && consumption.tanks && consumption.tanks[tank]) || {};
    return (t.volumeGal && t.wcVolumeGal != null) ? t.wcVolumeGal / t.volumeGal : null;
  }

  // ── §5.2/5.3 dosing ──────────────────────────────────────────────────────
  // mL of `agent` dosed into `tank` during (t1, t2], summed over matching dose windows.
  // A window [from, to||t2] contributes mlPerDay for each day d with t1 < d <= t2 and from <= d <= to.
  function doseMlInInterval(doses, tank, agent, t1, t2) {
    let ml = 0;
    for (const d of (doses || [])) {
      if (d.tank !== tank || d.agent !== agent) continue;
      const from = d.from || t1;
      const to   = d.to   || t2;
      const lower = maxISO(addDays(t1, 1), from);
      const upper = minISO(t2, to);
      const n = daysBetween(lower, upper) + 1;
      if (n > 0) ml += n * d.mlPerDay;
    }
    return ml;
  }

  // Per-agent contribution to `param` (alk/ca/mg) over the interval, keyed by agent.
  function agentContributions(dosing, consumption, tank, param, t1, t2) {
    const volL = tankVolumeL(consumption, tank);
    const out = {};
    if (!volL || !dosing) return out;
    for (const [key, agent] of Object.entries(dosing.agents || {})) {
      const perMl = agent && agent.perMl && agent.perMl[param];
      if (perMl == null) continue;
      const ml = doseMlInInterval(dosing.doses || [], tank, key, t1, t2);
      if (ml > 0) out[key] = ml * perMl / volL;
    }
    return out;
  }

  // ── §5.4 water-change contribution ───────────────────────────────────────
  function wcContribution(consumption, waterChanges, tank, param, P1, P2, t1, t2) {
    const tcfg = (consumption.tanks && consumption.tanks[tank]) || {};
    const stdFrac = wcFraction(consumption, tank);
    const salt = consumption.saltMix && consumption.saltMix[param];
    if (salt == null) return { total: 0, count: 0 };
    const span = daysBetween(t1, t2);
    let total = 0, count = 0;
    for (const w of (waterChanges || [])) {
      if (w.date <= t1 || w.date > t2) continue;           // (t1, t2]
      const f = span ? daysBetween(t1, w.date) / span : 0;
      const pAt = P1 + (P2 - P1) * f;                       // linear interpolation
      let frac = (w.volumeGal != null && tcfg.volumeGal) ? w.volumeGal / tcfg.volumeGal : stdFrac;
      if (frac == null) continue;
      total += (salt - pAt) * frac;
      count++;
    }
    return { total, count };
  }

  // ── §5.5–5.6 intervals ───────────────────────────────────────────────────
  // opts: { readings:[{date,value}], dosing, consumption, waterChanges, tank, param }
  function computeIntervals(opts) {
    const { dosing, consumption, waterChanges, tank, param } = opts;
    const r = (opts.readings || []).slice().sort((a, b) => a.date.localeCompare(b.date));
    const maxDays = (consumption.calc && consumption.calc.maxDays) || 30;
    const out = [];
    for (let i = 1; i < r.length; i++) {
      const t1 = r[i - 1].date, t2 = r[i].date, P1 = r[i - 1].value, P2 = r[i].value;
      const days = daysBetween(t1, t2);
      if (days <= 0) continue;
      const observed = P2 - P1;
      const agents = agentContributions(dosing, consumption, tank, param, t1, t2);
      const agentTotal = Object.values(agents).reduce((a, b) => a + b, 0);
      const wc = wcContribution(consumption, waterChanges, tank, param, P1, P2, t1, t2);
      const trueConsumption = observed - agentTotal - wc.total;
      let flag;
      if (days < 3 || days > maxDays || wc.count > 2) flag = 'noisy';
      else if (wc.count === 0) flag = 'clean';
      else flag = 'corrected';
      out.push({ t1, t2, days, observed, agents, agentTotal, wc: wc.total, wcCount: wc.count, trueConsumption, rate: trueConsumption / days, flag });
    }
    return out;
  }

  // ── §5.7 rolling average ─────────────────────────────────────────────────
  // Only consuming (true<=0) intervals within [minDays,maxDays] and the recent window;
  // drop |rate| > outlierFactor × mean as outliers.
  function rollingRate(intervals, calc, today) {
    const minDays = (calc && calc.minDays) || 7;
    const maxDays = (calc && calc.maxDays) || 30;
    const windowDays = (calc && calc.windowDays) || 90;
    const outlierFactor = (calc && calc.outlierFactor) || 3;
    const cutoff = addDays(today, -windowDays);
    let pool = (intervals || []).filter(iv =>
      iv.days >= minDays && iv.days <= maxDays && iv.t2 >= cutoff && iv.trueConsumption <= 0);
    if (!pool.length) return { avgRate: null, n: 0, latest: null };
    const mean = pool.reduce((a, iv) => a + iv.rate, 0) / pool.length;
    if (mean !== 0) pool = pool.filter(iv => Math.abs(iv.rate) <= outlierFactor * Math.abs(mean));
    if (!pool.length) return { avgRate: null, n: 0, latest: null };
    const avg = pool.reduce((a, iv) => a + iv.rate, 0) / pool.length;
    return { avgRate: avg, n: pool.length, latest: pool[pool.length - 1] };
  }

  // ── bottles ──────────────────────────────────────────────────────────────
  function activeDose(doses, tank, agent, todayISO) {
    const active = (doses || []).filter(d =>
      d.tank === tank && d.agent === agent &&
      (!d.from || d.from <= todayISO) &&
      (!d.to   || d.to   >= todayISO));
    return active.length ? active[active.length - 1].mlPerDay : null;
  }
  function bottleRemainingMl(bottle, mlPerDay, todayISO) {
    if (!bottle || bottle.fillMl == null) return null;
    if (!mlPerDay || !bottle.filledOn) return bottle.fillMl;
    const used = mlPerDay * Math.max(0, daysBetween(bottle.filledOn, todayISO));
    return Math.max(0, bottle.fillMl - used);
  }
  function bottleDaysLeft(remainingMl, mlPerDay) {
    if (remainingMl == null || !mlPerDay) return null;
    return remainingMl / mlPerDay;
  }

  const api = {
    GAL_TO_L, daysBetween, addDays,
    tankVolumeL, wcFraction,
    doseMlInInterval, agentContributions, wcContribution,
    computeIntervals, rollingRate,
    activeDose, bottleRemainingMl, bottleDaysLeft,
  };

  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  else root.Consumption = api;
})(typeof window !== 'undefined' ? window : globalThis);
