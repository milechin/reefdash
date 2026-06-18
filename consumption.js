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

  // Per-agent dose detail for `param` over the interval (for the verification breakdown).
  function agentDoses(dosing, consumption, tank, param, t1, t2) {
    const volL = tankVolumeL(consumption, tank);
    const out = [];
    if (!volL || !dosing) return out;
    for (const [key, agent] of Object.entries(dosing.agents || {})) {
      const perMl = agent && agent.perMl && agent.perMl[param];
      if (perMl == null) continue;
      const mlDosed = doseMlInInterval(dosing.doses || [], tank, key, t1, t2);
      if (mlDosed <= 0) continue;
      out.push({ agent: key, label: agent.label || key, mlDosed, perMl, perMlPerL: perMl / volL, contribution: mlDosed * perMl / volL });
    }
    return out;
  }

  // Convenience map { agent: contribution } over agentDoses.
  function agentContributions(dosing, consumption, tank, param, t1, t2) {
    const out = {};
    for (const d of agentDoses(dosing, consumption, tank, param, t1, t2)) out[d.agent] = d.contribution;
    return out;
  }

  // ── §5.4 water-change contribution ───────────────────────────────────────
  // A water change with no recorded volume is SKIPPED (not estimated) and reported in `skipped`,
  // so the interval can be flagged; its dilution step is simply not computed.
  function wcContribution(consumption, waterChanges, tank, param, P1, P2, t1, t2) {
    const tcfg = (consumption.tanks && consumption.tanks[tank]) || {};
    const salt = consumption.saltMix && consumption.saltMix[param];
    const span = daysBetween(t1, t2);
    let total = 0, count = 0, skipped = 0;
    const items = [];
    for (const w of (waterChanges || [])) {
      if (w.date <= t1 || w.date > t2) continue;           // (t1, t2]
      const f = span ? daysBetween(t1, w.date) / span : 0;
      const pAt = P1 + (P2 - P1) * f;                       // linear interpolation
      if (w.volumeGal == null || !tcfg.volumeGal || salt == null) {
        skipped++;
        items.push({ date: w.date, pAtWc: pAt, volumeGal: w.volumeGal, fraction: null, delta: 0, accounted: false });
        continue;
      }
      const fraction = w.volumeGal / tcfg.volumeGal;
      const delta = (salt - pAt) * fraction;
      total += delta; count++;
      items.push({ date: w.date, pAtWc: pAt, volumeGal: w.volumeGal, fraction, delta, accounted: true });
    }
    return { total, count, skipped, items };
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
      const doses = agentDoses(dosing, consumption, tank, param, t1, t2);
      const doseTotal = doses.reduce((a, d) => a + d.contribution, 0);
      const wc = wcContribution(consumption, waterChanges, tank, param, P1, P2, t1, t2);
      const trueConsumption = observed - doseTotal - wc.total;
      const events = wc.count + wc.skipped;
      let flag;
      if (days < 3 || days > maxDays || events > 2) flag = 'noisy';
      else if (events === 0) flag = 'clean';
      else flag = 'corrected';
      out.push({ t1, t2, days, P1, P2, observed, doses, doseTotal, wcItems: wc.items, wcTotal: wc.total, wcCount: wc.count, wcSkipped: wc.skipped, incomplete: wc.skipped > 0, trueConsumption, rate: trueConsumption / days, flag });
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
    tankVolumeL,
    doseMlInInterval, agentDoses, agentContributions, wcContribution,
    computeIntervals, rollingRate,
    activeDose, bottleRemainingMl, bottleDaysLeft,
  };

  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  else root.Consumption = api;
})(typeof window !== 'undefined' ? window : globalThis);
