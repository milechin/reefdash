// consumption.js — pure, DOM-free helpers for the consumption-rate feature.
// Works as a browser global (window.Consumption) and as a node module (require) for tests.
// Phase 2 uses the bottle helpers; Phase 3 extends this file with the consumption math.
(function (root) {
  'use strict';

  // Whole-day difference between two YYYY-MM-DD dates (toISO - fromISO).
  function daysBetween(fromISO, toISO) {
    const a = new Date(fromISO + 'T00:00:00');
    const b = new Date(toISO + 'T00:00:00');
    return Math.round((b - a) / 86400000);
  }

  // The mL/day in effect for a tank+agent on a given date — the latest dose window
  // whose [from, to] range covers `todayISO` (to:null = ongoing). null if none active.
  function activeDose(doses, tank, agent, todayISO) {
    const active = (doses || []).filter(d =>
      d.tank === tank && d.agent === agent &&
      (!d.from || d.from <= todayISO) &&
      (!d.to   || d.to   >= todayISO));
    return active.length ? active[active.length - 1].mlPerDay : null;
  }

  // Volume of agent left in the bottle now: fill amount minus what the daily dose has
  // consumed since the fill date (floored at 0). If no active dose, no decay.
  function bottleRemainingMl(bottle, mlPerDay, todayISO) {
    if (!bottle || bottle.fillMl == null) return null;
    if (!mlPerDay || !bottle.filledOn) return bottle.fillMl;
    const used = mlPerDay * Math.max(0, daysBetween(bottle.filledOn, todayISO));
    return Math.max(0, bottle.fillMl - used);
  }

  // Estimated days until the bottle is empty at the current daily dose.
  function bottleDaysLeft(remainingMl, mlPerDay) {
    if (remainingMl == null || !mlPerDay) return null;
    return remainingMl / mlPerDay;
  }

  const api = { daysBetween, activeDose, bottleRemainingMl, bottleDaysLeft };

  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  else root.Consumption = api;
})(typeof window !== 'undefined' ? window : globalThis);
