<?php
session_start();
require_once 'db.php';

// Authentication Guard
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

$firm_id = $_SESSION['firm_id'];

try {
    $pdo = DB::getInstance();
    
    // Fetch Valuation - Security: Must match firm_id
    $stmt = $pdo->prepare("SELECT * FROM valuations WHERE id = ? AND firm_id = ?");
    $stmt->execute([$id, $firm_id]);
    $v = $stmt->fetch();

    if (!$v) {
        die("Valuation not found or access denied.");
    }

    // Decode JSON data
    $financials = json_decode($v['financials_json'], true);
    $adjustments = json_decode($v['adjustments_json'], true);
    $shareholders = json_decode($v['shareholders_json'], true);
    $methodology = json_decode($v['methodology_json'], true);
    $multiples = $methodology['multiples'] ?? [];

    // Fetch Firm Branding
    $stmt = $pdo->prepare("SELECT * FROM firms WHERE id = ?");
    $stmt->execute([$firm_id]);
    $firm = $stmt->fetch();

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

$primary_color = $firm['primary_color'] ?? '#c5a059';
$secondary_color = $firm['secondary_color'] ?? '#050505';
$logo_url = $firm['logo_url'] ?? '';

// Helper for dynamic surface variations
function adjustBrightness($hex, $steps) {
    $steps = max(-255, min(255, $steps));
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

$surface_mid = adjustBrightness($secondary_color, 15);
$surface_light = adjustBrightness($secondary_color, 30);

function fmt($n) {
    if ($n === null || $n === '') return '—';
    return '£' . number_format((float)$n, 0);
}

function fmtShort($n) {
    if (!$n) return '—';
    if (abs($n) >= 1000000) return '£' . number_format($n / 1000000, 2) . 'm';
    if (abs($n) >= 1000) return '£' . number_format($n / 1000, 0) . 'k';
    return fmt($n);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report: <?php echo htmlspecialchars($v['client_name']); ?> | ELK Valuations</title>
<link rel="icon" type="image/webp" href="favicon.webp">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=3.2.1">
<style>
:root {
  --brand-accent: <?php echo $primary_color; ?>;
  --brand-surface: <?php echo $secondary_color; ?>;
  --brand-surface-mid: <?php echo $surface_mid; ?>;
  --brand-surface-light: <?php echo $surface_light; ?>;
  
  /* Compatibility Layer */
  --gold: var(--brand-accent);
  --gold-light: var(--brand-accent-light);
  --navy: var(--brand-surface);
  --navy-mid: var(--brand-surface-mid);
  --navy-light: var(--brand-surface-light);
  --border: var(--border-subtle);
  --cream: var(--text-main);

  --brand-accent-light: <?php echo $primary_color; ?>; 
  --brand-accent-dim: <?php echo $primary_color; ?>26;
  --brand-accent-border: <?php echo $primary_color; ?>4d;
  --brand-accent-glow: <?php echo $primary_color; ?>33;
}

.report-container {
    max-width: 1000px;
    margin: 0 auto;
    background: var(--brand-surface);
}

.valuation-range {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    margin-top: 32px;
    gap: 20px;
}

.val-point {
    text-align: center;
    flex: 1;
}

.val-point .label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--text-faint);
    margin-bottom: 8px;
}

.val-point .amount {
    font-family: 'DM Mono', monospace;
    font-size: 32px;
    font-weight: 700;
    color: var(--text-main);
}

.val-point.mid .amount {
    font-size: 48px;
    color: var(--brand-accent-light);
}

.val-point .sublabel {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 4px;
}

@media print {
    .sidebar, header, footer, .no-print { display: none !important; }
    .main { padding: 0 !important; max-width: 100% !important; }
    .app-wrapper { display: block !important; }
    body { background: #fff !important; color: #000 !important; }
    .results-hero { border: 2px solid #000 !important; background: #f9f9f9 !important; color: #000 !important; }
    .result-card, .ebitda-breakdown { border: 1px solid #ddd !important; background: #fff !important; color: #000 !important; }
    .card-value { color: #000 !important; }
}
</style>
</head>
<body>

<header class="no-print">
  <div class="header-left">
    <a href="dashboard.php" style="color: var(--text-faint); text-decoration: none; font-size: 12px;">← Dashboard</a>
    <span style="margin: 0 12px; color: var(--border-subtle);">|</span>
    <span class="header-label">Viewing Report: <?php echo htmlspecialchars($v['client_name']); ?></span>
  </div>
  <div class="header-right" style="display: flex; align-items: center; gap: 16px;">
    <button class="btn btn-outline" onclick="window.print()" style="font-size: 11px; padding: 6px 12px;">🖨 Print Report</button>
    <a href="index.php?edit=<?php echo $v['id']; ?>" class="btn btn-primary" style="font-size: 11px; padding: 6px 12px;">✏️ Edit Data</a>
  </div>
</header>

<div class="app-wrapper" style="grid-template-columns: 1fr;">
  <main class="main" style="max-width: 1000px; margin: 0 auto;">
    
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end;">
      <div>
        <div class="page-eyebrow">Business Intelligence Report</div>
        <h1 class="page-title"><?php echo htmlspecialchars($v['client_name']); ?></h1>
        <p class="page-desc">Valuation prepared on <?php echo date('j F Y', strtotime($v['created_at'])); ?> for the purpose of <?php echo htmlspecialchars($v['purpose']); ?>.</p>
      </div>
      <?php if ($logo_url): ?>
        <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Firm Logo" style="max-height: 60px;">
      <?php endif; ?>
    </div>

    <div class="results-hero">
        <div class="valuation-range">
          <div class="val-point">
            <div class="label">Conservative</div>
            <div class="amount"><?php 
                $wAvg = (float)$v['valuation_mid'] / (float)$multiples['mid']; // Rough calc for display
                echo fmtShort($wAvg * (float)$multiples['low']); 
            ?></div>
            <div class="sublabel"><?php echo $multiples['low']; ?>× EBITDA</div>
          </div>
          <div class="val-point mid">
            <div class="label">Mid-point Equity Value</div>
            <div class="amount"><?php echo fmtShort($v['valuation_mid']); ?></div>
            <div class="sublabel"><?php echo $multiples['mid']; ?>× EBITDA (Net Debt Adjusted)</div>
          </div>
          <div class="val-point">
            <div class="label">Optimistic</div>
            <div class="amount"><?php echo fmtShort($wAvg * (float)$multiples['high']); ?></div>
            <div class="sublabel"><?php echo $multiples['high']; ?>× EBITDA</div>
          </div>
        </div>
    </div>

    <div class="results-grid">
        <div class="result-card">
          <div class="card-label">Weighted Average EBITDA</div>
          <div class="card-value"><?php echo fmt($wAvg); ?></div>
          <div class="card-sub">Adjusted Basis</div>
        </div>
        <div class="result-card">
          <div class="card-label">Most Recent Turnover</div>
          <div class="card-value"><?php echo fmt($financials['turnover'][2] ?? 0); ?></div>
          <div class="card-sub">Year End <?php echo htmlspecialchars($v['year_end']); ?></div>
        </div>
        <div class="result-card">
          <div class="card-label">Net Assets</div>
          <div class="card-value"><?php echo fmt($financials['balanceSheet']['netAssets'] ?? 0); ?></div>
          <div class="card-sub">Balance Sheet Basis</div>
        </div>
        <div class="result-card">
          <div class="card-label">Cash at Bank</div>
          <div class="card-value"><?php echo fmt($financials['balanceSheet']['cash'] ?? 0); ?></div>
          <div class="card-sub">Liquidity Position</div>
        </div>
    </div>

    <div class="section-title">Professional Commentary</div>
    <div style="background: var(--brand-surface-mid); border: 1px solid var(--border-subtle); padding: 32px; border-radius: 4px; line-height: 1.8; color: var(--text-muted); font-size: 15px; white-space: pre-wrap;">
<?php echo htmlspecialchars($v['ai_narrative'] ?: $v['accountant_notes']); ?>
    </div>

    <div class="section-title">Shareholder Allocation</div>
    <table class="share-table">
        <thead>
          <tr>
            <th>Shareholder</th>
            <th>Class</th>
            <th>Shares</th>
            <th>Estimated Value (Mid)</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $totalShares = 0;
          foreach($shareholders as $sh) $totalShares += (int)$sh['shares'];
          foreach ($shareholders as $sh): 
            $shareVal = $totalShares > 0 ? ((int)$sh['shares'] / $totalShares) * (float)$v['valuation_mid'] : 0;
          ?>
            <tr>
              <td><?php echo htmlspecialchars($sh['name']); ?></td>
              <td><?php echo htmlspecialchars($sh['class']); ?></td>
              <td><?php echo number_format((int)$sh['shares']); ?></td>
              <td style="color: var(--brand-accent-light); font-weight: 600;"><?php echo fmt($shareVal); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
    </table>

    <div class="report-disclaimer">
        <strong>Report Integrity:</strong> This valuation has been generated via the ELK Valuations AI Extraction Engine for <?php echo htmlspecialchars($firm['name']); ?>. 
        The underlying data was sourced from statutory accounts for <?php echo htmlspecialchars($v['client_name']); ?> (Co. No. <?php echo htmlspecialchars($v['company_number']); ?>).
        This document is for advisory purposes only.
    </div>

  </main>
</div>

<footer class="no-print" style="
    background: #0d0d18;
    border-top: 1px solid rgba(197, 160, 89, 0.1);
    padding: 16px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: 'Barlow', sans-serif;
    font-size: 11px;
    color: rgba(237,237,240,0.3);
    letter-spacing: 0.04em;
  ">
    <span>&copy; 2026 ELK Valuations (ELK Digital). All rights reserved.</span>
    <span>Design &amp; Development by <a href="https://elkdesignservices.com" target="_blank" style="color:rgba(237,237,240,0.5); text-decoration:none; border-bottom:1px solid rgba(197, 160, 89, 0.2);">ELK Digital</a> &mdash; elkdesignservices.com <span style="margin-left:8px; color:#ffffff; opacity:1.0;">
      <?php 
        $version = getenv('APP_VERSION') ?: '3.2.1';
        $buildTime = getenv('BUILD_TIME') ?: 'Deployment Pending';
        echo "v{$version} (Built: {$buildTime})"; 
      ?>
    </span></span>
</footer>

</body>
</html>
