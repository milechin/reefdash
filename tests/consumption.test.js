// Tests for consumption.js — derived from the Consumption Rate spec (§5–§9).
// Zero-dependency; run with:  node tests/consumption.test.js
const C = require('../consumption.js');

let pass = 0, fail = 0;
const approx = (a, b, tol = 0.05) => Math.abs(a - b) <= tol;
function ok(cond, label) { if (cond) { pass++; } else { fail++; console.log('  ✗ ' + label); } }
function eq(actual, expected, label, tol) {
  ok(approx(actual, expected, tol), `${label}: got ${actual}, expected ≈ ${expected}`);
}

// ── Spec §4 fixtures (Display tank)
const consumption = {
  saltMix: { alk: 12.5, ca: 465, mg: 1390 },
  calc: { minDays: 7, maxDays: 30, windowDays: 90, outlierFactor: 3 },
  tanks: { display: { volumeGal: 20, wcVolumeGal: 4 } },
};
const dosing = {
  agents: {
    afr:      { label: 'All For Reef', perMl: { alk: 5.6, ca: 40.0, mg: 1.9 } },
    ballingB: { label: 'Balling B',    perMl: { alk: 6.664 } },
  },
  doses: [
    { tank: 'display', agent: 'afr',      mlPerDay: 2,  from: '2026-01-01', to: null },
    { tank: 'display', agent: 'ballingB', mlPerDay: 30, from: '2026-05-16', to: '2026-05-30' },
  ],
};
const waterChanges = [
  { date: '2026-05-20', volumeGal: null },
  { date: '2026-05-26', volumeGal: null },
];
const t1 = '2026-05-16', t2 = '2026-05-30';

// ── Derived tank values
eq(C.tankVolumeL(consumption, 'display'), 75.7, 'tankVolumeL 20gal', 0.1);
eq(C.wcFraction(consumption, 'display'), 0.20, 'wcFraction 4/20', 0.001);

// ── §5.2 dose mL in interval (28 mL AFR over 14 days; 420 mL Balling B)
eq(C.doseMlInInterval(dosing.doses, 'display', 'afr', t1, t2), 28, 'AFR mL in interval');
eq(C.doseMlInInterval(dosing.doses, 'display', 'ballingB', t1, t2), 420, 'Balling B mL in interval');
// single-day burst (from==to) → exactly one day's dose
eq(C.doseMlInInterval([{tank:'display',agent:'x',mlPerDay:10,from:'2026-05-20',to:'2026-05-20'}], 'display','x',t1,t2), 10, 'single-day burst = 1×mlPerDay');
// ongoing dose started before the interval → full interval
eq(C.doseMlInInterval([{tank:'display',agent:'y',mlPerDay:3,from:'2020-01-01',to:null}], 'display','y',t1,t2), 42, 'ongoing dose = days×mlPerDay');

// ── §5.2/5.3 agent contributions
const aAlk = C.agentContributions(dosing, consumption, 'display', 'alk', t1, t2);
eq(aAlk.afr, 2.07, 'AFR alk contribution');
eq(aAlk.ballingB, 36.96, 'Balling B alk contribution', 0.06);
const aCa = C.agentContributions(dosing, consumption, 'display', 'ca', t1, t2);
eq(aCa.afr, 14.79, 'AFR ca contribution');
ok(aCa.ballingB === undefined, 'Balling B has no ca contribution');

// ── §5.4 water-change contribution (Ca interpolation + dilution)
const wcCa = C.wcContribution(consumption, waterChanges, 'display', 'ca', 460, 380, t1, t2);
eq(wcCa.total, 18.0, 'WC ca contribution total', 0.1);
ok(wcCa.count === 2, 'WC count = 2');
const wcAlk = C.wcContribution(consumption, waterChanges, 'display', 'alk', 10.8, 10.8, t1, t2);
eq(wcAlk.total, 0.68, 'WC alk contribution total');

// ── §5.5/5.6 full interval (the worked example)
const ivAlk = C.computeIntervals({ readings: [{date:t1,value:10.8},{date:t2,value:10.8}], dosing, consumption, waterChanges, tank:'display', param:'alk' });
ok(ivAlk.length === 1, 'one alk interval');
eq(ivAlk[0].observed, 0, 'observed alk');
eq(ivAlk[0].trueConsumption, -39.71, 'true consumption alk', 0.1);
eq(ivAlk[0].rate, -2.84, 'alk rate dKH/day', 0.02);
ok(ivAlk[0].flag === 'corrected', 'alk interval flag = corrected (🟡)');

const ivCa = C.computeIntervals({ readings: [{date:t1,value:460},{date:t2,value:380}], dosing, consumption, waterChanges, tank:'display', param:'ca' });
eq(ivCa[0].trueConsumption, -112.79, 'true consumption ca', 0.2);
eq(ivCa[0].rate, -8.06, 'ca rate ppm/day', 0.02);

// ── §5.7 rolling average (single qualifying interval)
const rrCa = C.rollingRate(ivCa, consumption.calc, '2026-05-30');
eq(rrCa.avgRate, -8.06, 'rolling avg ca rate', 0.02);
ok(rrCa.n === 1, 'rolling avg n = 1');

// ── §8 quality flags
const flagOf = (wcDates, days) => {
  const a = '2026-05-01', b = C.addDays(a, days);
  const wc = wcDates.map(d => ({date: d, volumeGal: null}));
  return C.computeIntervals({ readings:[{date:a,value:8},{date:b,value:8}], dosing:{agents:{},doses:[]}, consumption, waterChanges: wc, tank:'display', param:'alk' })[0].flag;
};
ok(flagOf([], 14) === 'clean', 'flag clean (0 WC)');
ok(flagOf(['2026-05-05'], 14) === 'corrected', 'flag corrected (1 WC)');
ok(flagOf(['2026-05-03','2026-05-05','2026-05-07'], 14) === 'noisy', 'flag noisy (>2 WC)');
ok(flagOf([], 2) === 'noisy', 'flag noisy (days<3)');

// ── §9 edges
ok(C.computeIntervals({ readings:[{date:t1,value:8}], dosing, consumption, waterChanges:[], tank:'display', param:'alk' }).length === 0, 'first reading → no interval');
// days outside [minDays,maxDays] excluded from rolling avg
const shortIv = C.computeIntervals({ readings:[{date:'2026-05-01',value:8},{date:'2026-05-03',value:7}], dosing:{agents:{},doses:[]}, consumption, waterChanges:[], tank:'display', param:'alk' });
ok(C.rollingRate(shortIv, consumption.calc, '2026-05-03').n === 0, 'sub-minDays interval excluded from rolling avg');
// positive true_consumption dropped from rolling avg
const posIv = C.computeIntervals({ readings:[{date:'2026-05-01',value:8},{date:'2026-05-15',value:12}], dosing:{agents:{},doses:[]}, consumption, waterChanges:[], tank:'display', param:'alk' });
ok(posIv[0].trueConsumption > 0, 'accumulating interval has positive true consumption');
ok(C.rollingRate(posIv, consumption.calc, '2026-05-15').n === 0, 'positive interval excluded from rolling avg');

// ── bottle helpers
eq(C.bottleRemainingMl({fillMl:500, filledOn:'2026-06-01'}, 10, '2026-06-11'), 400, 'bottle remaining 500-10×10');
eq(C.bottleRemainingMl({fillMl:50, filledOn:'2026-06-01'}, 10, '2026-07-01'), 0, 'bottle remaining floored at 0');
ok(C.bottleRemainingMl({fillMl:500, filledOn:'2026-06-01'}, null, '2026-06-11') === 500, 'no active dose → no decay');
eq(C.bottleDaysLeft(400, 10), 40, 'bottle days left');
ok(C.bottleDaysLeft(400, null) === null, 'no dose → days left null');
eq(C.activeDose(dosing.doses, 'display', 'afr', '2026-05-20'), 2, 'active dose AFR');

console.log(`\n${pass} passed, ${fail} failed`);
process.exit(fail ? 1 : 0);
