<?php
/**
 * ELK Valuations - the one calculator (25 Sep 2026).
 * Port of index.php calcResults()/getAdjEbitda(): view-valuation.php and export-pdf.php take every figure
 * from here so the three surfaces cannot drift. Keep it in step with the JS; calc-test.php is the guard.
 */
function elk_calc(array $fin, array $adj, array $meth): array {
    $e = []; $turn = []; $margin = [];
    for ($i = 0; $i < 3; $i++) {
        $t = (float)($fin['turnover'][$i] ?? 0);
        $pre = $t - (float)($fin['cos'][$i] ?? 0) - (float)($fin['admin'][$i] ?? 0)
             + (float)($fin['other'][$i] ?? 0) + (float)($fin['depreciation'][$i] ?? 0);
        $sum = 0;
        foreach ($adj as $row) $sum += (float)($row['v' . ($i + 1)] ?? 0);
        $turn[$i] = $t;
        $e[$i] = $pre + $sum;
        $margin[$i] = $t > 0 ? $e[$i] / $t : null;
    }
    $w = array_values($meth['weighting'] ?? [1, 2, 3]);
    if (count($w) !== 3 || array_sum($w) == 0) $w = [1, 2, 3];
    $wAvg = ($e[0] * $w[0] + $e[1] * $w[1] + $e[2] * $w[2]) / array_sum($w);

    // Key person leakage: revenue at risk x leakage % x year-3 margin
    $kpRev  = (float)($meth['kpRevenue'] ?? 0);
    $kpLeak = (float)($meth['kpLeakage'] ?? 0) / 100;
    $leak   = $kpRev * $kpLeak * ($margin[2] ?? 0);
    $adjWAvg = $wAvg - $leak;

    $m = $meth['multiples'] ?? [];
    $mLow = (float)($m['low'] ?? 0) ?: 2.5; $mMid = (float)($m['mid'] ?? 0) ?: 3.5; $mHigh = (float)($m['high'] ?? 0) ?: 5;
    $bs = $fin['balanceSheet'] ?? [];
    $netDebt = !empty($meth['useNetDebt']) ? (float)($bs['loans'] ?? 0) - (float)($bs['cash'] ?? 0) : 0;
    $ded = (float)($meth['deduction'] ?? 0);

    $avgTurn = array_sum($turn) / 3;
    $netAssets = (float)($bs['netAssets'] ?? 0);
    $method = ($meth['method'] ?? 'ebitda') === 'netassets' ? 'netassets' : 'ebitda';
    $valMid = $adjWAvg * $mMid - $netDebt - $ded;
    return [
        'ebitda'    => $e,
        'turnover'  => $turn,
        'margin'    => $margin,                                        // fraction or null when turnover is 0
        'avgMargin' => $avgTurn > 0 ? (array_sum($e) / 3) / $avgTurn : null,
        'weighting' => $w,
        'wAvgRaw'   => $wAvg,
        'leakage'   => $leak,
        'wAvg'      => $adjWAvg,
        'netDebt'   => $netDebt,
        'deduction' => $ded,
        'multLow' => $mLow, 'multMid' => $mMid, 'multHigh' => $mHigh,
        'valLow'    => $adjWAvg * $mLow - $netDebt - $ded,
        'valMid'    => $valMid,
        'valHigh'   => $adjWAvg * $mHigh - $netDebt - $ded,
        'netAssets' => $netAssets,
        'method'    => $method,
        'basisValue' => $method === 'netassets' ? $netAssets : $valMid, // what the share table divides
    ];
}
