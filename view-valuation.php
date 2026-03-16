<?php
session_start();
require_once 'db.php';
require_once 'theme-engine.php';

// Authentication Guard
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

$uuid = $_GET['uuid'] ?? null;
if (!$uuid) {
    header('Location: dashboard.php');
    exit;
}

$firm_id = $_SESSION['firm_id'];

try {
    $pdo = DB::getInstance();
    
    // Fetch Valuation - Security: Must match firm_id and use uuid
    $stmt = $pdo->prepare("SELECT * FROM valuations WHERE uuid = ? AND firm_id = ?");
    $stmt->execute([$uuid, $firm_id]);
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
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=3.2.1">
<?php injectTheme($primary_color, $secondary_color); ?>
<style>
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
    <a href="export-pdf.php?uuid=<?php echo $v['uuid']; ?>" target="_blank" class="btn btn-outline" style="font-size: 11px; padding: 6px 12px;">🖨 Download PDF</a>
    <a href="index.php?edit=<?php echo $v['uuid']; ?>" class="btn btn-primary" style="font-size: 11px; padding: 6px 12px;">✏️ Edit Data</a>
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

<?php include 'footer.php'; ?>
</body>
</html>
