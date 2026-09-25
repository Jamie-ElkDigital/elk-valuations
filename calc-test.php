<?php
// php calc-test.php : self-check for calc.php against hand-worked View HR Limited (09523327) figures, 25 Sep 2026.
require __DIR__ . '/calc.php';
// check() throws regardless of zend.assertions, so a production php.ini cannot turn this into a no-op (Codex, 25 Sep 2026)
function check(bool $ok, string $what): void { if (!$ok) { fwrite(STDERR, "calc-test: FAIL $what
"); exit(1); } }
$fin = ['turnover' => [700000, 800000, 859547], 'cos' => [300000, 350000, 381717], 'admin' => [250000, 280000, 110000],
        'other' => [0, 0, 0], 'depreciation' => [10000, 10000, 10000],
        'balanceSheet' => ['netAssets' => 209306, 'cash' => 150000, 'debtors' => 90000, 'loans' => 20000]];
// pre-adjustment EBITDA: Y1 160000, Y2 180000, Y3 377830
$adj = [['label' => 'Leaver salary', 'v1' => 0, 'v2' => 0, 'v3' => 35000],
        ['label' => 'Leaver employer NI', 'v1' => 0, 'v2' => 0, 'v3' => 3770],
        ['label' => 'Leaver employer pension', 'v1' => 0, 'v2' => 0, 'v3' => 1050],
        ['label' => 'Replacement cost', 'v1' => 0, 'v2' => 0, 'v3' => -60000],
        ['label' => 'Future commitment', 'v1' => 0, 'v2' => 0, 'v3' => -40000]];
$meth = ['weighting' => [1, 2, 3], 'multiples' => ['low' => 2.5, 'mid' => 3.5, 'high' => 5], 'deduction' => 1000,
         'kpRevenue' => 100000, 'kpLeakage' => 25, 'useNetDebt' => true, 'method' => 'netassets'];
$r = elk_calc($fin, $adj, $meth);
$eq = fn($a, $b) => abs($a - $b) < 0.005;
check($eq($r['ebitda'][0], 160000), 'line 1'); check($eq($r['ebitda'][1], 180000), 'line 2');
check($eq($r['ebitda'][2], 377830 + 39820 - 100000), 'line 3');            // 317650
check($eq($r['margin'][2], 317650 / 859547), 'line 4');
check($eq($r['avgMargin'], (160000 + 180000 + 317650) / 3 / ((700000 + 800000 + 859547) / 3)), 'line 5');
$wAvg = (160000 * 1 + 180000 * 2 + 317650 * 3) / 6;               // 245491.666..
check($eq($r['wAvgRaw'], $wAvg), 'line 6');
$leak = 100000 * 0.25 * (317650 / 859547);
check($eq($r['leakage'], $leak), 'line 7');
$net = $wAvg - $leak; $debt = 20000 - 150000;                       // net cash -130000
check($eq($r['valLow'],  $net * 2.5 - $debt - 1000), 'line 8');
check($eq($r['valMid'],  $net * 3.5 - $debt - 1000), 'line 9');
check($eq($r['valHigh'], $net * 5   - $debt - 1000), 'line 10');
check($r['basisValue'] == 209306 && $r['method'] === 'netassets', 'line 11');
$r2 = elk_calc(['turnover' => [0, 0, 0]], [], []);                  // empty inputs: no NaN, no division by zero
check($r2['margin'][2] === null && $r2['avgMargin'] === null && $r2['valMid'] == 0 && $r2['basisValue'] == 0, 'line 12');
echo "calc-test: OK\n";
