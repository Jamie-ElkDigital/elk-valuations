<?php
session_start();
require_once 'db.php';

// Authentication Guard
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

$firm_id = $_SESSION['firm_id'];
$user_name = $_SESSION['user_name'];
$firm_name = $_SESSION['firm_name'];

try {
    $pdo = DB::getInstance();
    $stmt = $pdo->prepare("SELECT * FROM firms WHERE id = ?");
    $stmt->execute([$firm_id]);
    $firm = $stmt->fetch();

    if (!$firm) {
        die("Firm configuration missing.");
    }
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// Map database colors to CSS variables
$primary_color = $firm['primary_color'] ?? '#c5a059';
$secondary_color = $firm['secondary_color'] ?? '#050505';
$logo_url = $firm['logo_url'] ?? '';

// Simple PHP function to approximate a lighter/darker color for the surface variants
// In Phase 4, we could use a proper color library.
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ELK Valuations | <?php echo htmlspecialchars($firm['name']); ?></title>
<link rel="icon" type="image/webp" href="favicon.webp">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=3.2.1">
<style>
:root {
  /* Dynamic overrides from database */
  --brand-accent: <?php echo $primary_color; ?>;
  --brand-surface: <?php echo $secondary_color; ?>;
  --brand-surface-mid: <?php echo $surface_mid; ?>;
  --brand-surface-light: <?php echo $surface_light; ?>;
  
  /* Compatibility Layer for Legacy Step Classes */
  --gold: var(--brand-accent);
  --gold-light: var(--brand-accent-light);
  --navy: var(--brand-surface);
  --navy-mid: var(--brand-surface-mid);
  --navy-light: var(--brand-surface-light);
  --border: var(--border-subtle);
  --cream: var(--text-main);
  
  /* Derived approximations */
  --brand-accent-light: <?php echo $primary_color; ?>; 
  --brand-accent-dim: <?php echo $primary_color; ?>26;
  --brand-accent-border: <?php echo $primary_color; ?>4d;
  --brand-accent-glow: <?php echo $primary_color; ?>33;
}
</style>
</head>
<body>

<header>
  <div class="header-left">
    <span class="header-label" id="clientNameDisplay">New Valuation</span>
    <span style="margin: 0 12px; color: var(--border-subtle);">|</span>
    <span style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($firm_name); ?></span>
  </div>
  <div class="header-right" style="display: flex; align-items: center; gap: 20px;">
    <span style="font-size: 11px; color: var(--text-faint);"><?php echo htmlspecialchars($user_name); ?></span>
    <a href="logout.php" style="font-size: 10px; color: var(--brand-accent); text-decoration: none; text-transform: uppercase; letter-spacing: 0.1em; border: 1px solid var(--brand-accent-border); padding: 4px 8px; border-radius: 2px;">Logout</a>
  </div>
</header>

<div id="debugOverlay" onclick="closeDebug()"></div>
<div id="debugModal">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
    <h3 style="color:var(--gold-light); margin:0;">AI Extraction Debug</h3>
    <button class="btn btn-outline" style="padding:4px 10px;" onclick="closeDebug()">Close</button>
  </div>
  <pre id="debugContent">Waiting for extraction...</pre>
</div>

<div class="app-wrapper">
  <nav class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-section-label">Main</div>
      <a href="dashboard.php" class="nav-item">
        <span style="width:20px; text-align:center;">📊</span>
        Dashboard
      </a>
      <a href="index.php" class="nav-item active">
        <span style="width:20px; text-align:center;">➕</span>
        New Valuation
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-label">Steps</div>
      <div class="nav-item" onclick="goTo(0)" id="nav0">
        <div class="nav-step">1</div>
        Business Details
      </div>
      <div class="nav-item" onclick="goTo(1)" id="nav1">
        <div class="nav-step">2</div>
        Financial Data
      </div>
      <div class="nav-item" onclick="goTo(2)" id="nav2">
        <div class="nav-step">3</div>
        Adjustments
      </div>
      <div class="nav-item" onclick="goTo(3)" id="nav3">
        <div class="nav-step">4</div>
        Shareholders
      </div>
      <div class="nav-item" onclick="goTo(4)" id="nav4">
        <div class="nav-step">5</div>
        Methodology
      </div>
      <div class="nav-item" onclick="goTo(5)" id="nav5">
        <div class="nav-step">6</div>
        Results &amp; Report
      </div>
    </div>
    <div style="padding: 16px 24px;">
      <div style="font-size:11px; color: var(--text-faint); line-height:1.6;">
        Developed & Supported by<br>
        <span style="color: var(--text-muted);">ELK Digital</span><br>
        Jamie Elkins
      </div>
    </div>

    <div style="margin: 24px 20px; padding: 16px; background: var(--brand-surface-mid); border: 1px solid var(--border-subtle); border-radius: 6px;">
      <div class="sidebar-section-label" style="margin-bottom:8px; color:var(--brand-accent-light);">Project Roadmap</div>
      <ul style="list-style:none; font-size:11px; color:var(--text-muted); display:flex; flex-direction:column; gap:6px;">
        <li><span style="color:var(--success);">✓</span> Smart PDF Upload</li>
        <li><span style="color:var(--success);">✓</span> AI Data Extraction</li>
        <li><span style="color:var(--success);">✓</span> Net Debt Calculations</li>
        <li><span style="color:var(--success);">✓</span> Save to Database</li>
        <li><span style="color:var(--brand-accent-light);">○</span> Client Dashboard</li>
        <li><span style="color:var(--brand-accent-light);">○</span> High-Fidelity PDF Export</li>
        <li><span style="color:var(--brand-accent-light);">○</span> Multi-User Auth</li>
      </ul>
    </div>

    <div class="sidebar-logo-container" style="padding: 24px 20px;">
      <?php if ($logo_url): ?>
        <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="<?php echo htmlspecialchars($firm['name']); ?>" style="max-height: 50px; width: auto; display: block; filter: drop-shadow(0 0 10px var(--brand-accent-glow));">
      <?php else: ?>
        <img src="elk-design-logo.png" alt="ELK Digital" style="max-height: 50px; width: auto; display: block; filter: drop-shadow(0 0 10px var(--brand-accent-glow));">
      <?php endif; ?>
    </div>
  </nav>

  <main class="main">

    <!-- PAGE 1: Business Details -->
    <div class="page active" id="page0">
      <div class="page-header">
        <div class="page-eyebrow">Step 1 of 6</div>
        <h1 class="page-title">Business Details</h1>
        <p class="page-desc">Upload statutory accounts to auto-fill the valuation setup, or enter the client company information manually.</p>
      </div>

      <div class="info-box" id="uploadBox" style="background: var(--navy-mid); border: 1px dashed var(--gold); padding: 32px; flex-direction: column; align-items: center; text-align: center; gap: 16px;">
        <div style="font-size: 24px;">📄</div>
        <div>
          <strong style="color: var(--cream); display: block; margin-bottom: 4px;">Smart PDF Upload</strong>
          <span class="text">Upload up to 3 years of Final Accounts. Gemini Pro will extract business details, financial figures, and share structure automatically.</span>
        </div>
        <input type="file" id="pdfUpload" multiple accept=".pdf" style="display: none;" onchange="handleFileUpload(event)">
        <button class="btn btn-outline" onclick="document.getElementById('pdfUpload').click()" id="uploadBtn">
          <span>Choose PDF Files</span>
        </button>
        <div id="uploadStatus" style="font-size: 11px; color: var(--gold); margin-top: 8px; display: none;">
          <span class="spinner"></span> Processing accounts please wait...
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group full">
          <label>Company Name</label>
          <input type="text" id="companyName" placeholder="e.g. Acme Trading Limited" oninput="updateHeader()">
        </div>
        <div class="form-group">
          <label>Company Number</label>
          <input type="text" id="companyNumber" placeholder="e.g. 12345678">
        </div>
        <div class="form-group">
          <label>Sector / Industry</label>
          <select id="sector">
            <option value="">Select sector…</option>
            <option>Professional Services</option>
            <option>HR &amp; Recruitment</option>
            <option>IT &amp; Technology</option>
            <option>Construction &amp; Trades</option>
            <option>Retail</option>
            <option>Hospitality &amp; Leisure</option>
            <option>Manufacturing</option>
            <option>Healthcare</option>
            <option>Financial Services</option>
            <option>Property</option>
            <option>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Year End</label>
          <select id="yearEnd">
            <option>30 April</option>
            <option>31 March</option>
            <option>31 December</option>
            <option>30 June</option>
            <option>31 January</option>
            <option>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Years Trading</label>
          <input type="number" id="yearsTrading" placeholder="10" min="1">
        </div>
        <div class="form-group">
          <label>Number of Employees</label>
          <input type="number" id="employees" placeholder="8" min="1">
        </div>
        <div class="form-group">
          <label>Purpose of Valuation</label>
          <select id="purpose">
            <option>Share sale / exit</option>
            <option>Shareholder buyout</option>
            <option>Succession planning</option>
            <option>Keyman insurance</option>
            <option>Shareholder protection</option>
            <option>HMRC / tax planning</option>
            <option>Management buyout (MBO)</option>
            <option>Investment / funding</option>
            <option>Divorce / legal dispute</option>
            <option>General advisory</option>
          </select>
        </div>
        <div class="form-group">
          <label>Report Date</label>
          <input type="text" id="reportDate" placeholder="e.g. March 2025">
        </div>
        <div class="form-group full">
          <label>Business Description (optional)</label>
          <textarea id="businessDesc" placeholder="Brief description of the business — what it does, its market position, key clients or contracts…"></textarea>
        </div>
      </div>

      <div class="btn-row">
        <div class="spacer"></div>
        <button class="btn btn-primary" onclick="goTo(1)">Continue to Financial Data →</button>
      </div>
    </div>

    <!-- PAGE 2: Financial Data -->
    <div class="page" id="page1">
      <div class="page-header">
        <div class="page-eyebrow">Step 2 of 6</div>
        <h1 class="page-title">Financial Data</h1>
        <p class="page-desc">Enter three years of P&amp;L figures as reported in the statutory accounts. These may have been auto-filled by your Step 1 upload.</p>
      </div>

      <div class="section-title">Profit &amp; Loss <span class="pill">3 Years</span></div>

      <table class="fin-table">
        <thead>
          <tr>
            <th style="width:40%">Line Item</th>
            <th>Year 1 (oldest) £</th>
            <th>Year 2 £</th>
            <th>Year 3 (most recent) £</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>Turnover</td>
            <td><input type="number" id="f_turn1" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_turn2" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_turn3" placeholder="0" oninput="calcFinancials()"></td>
          </tr>
          <tr><td>Cost of Sales</td>
            <td><input type="number" id="f_cos1" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_cos2" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_cos3" placeholder="0" oninput="calcFinancials()"></td>
          </tr>
          <tr class="subtotal">
            <td>Gross Profit</td>
            <td id="f_gp1">—</td><td id="f_gp2">—</td><td id="f_gp3">—</td>
          </tr>
          <tr><td>Administrative Expenses</td>
            <td><input type="number" id="f_admin1" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_admin2" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_admin3" placeholder="0" oninput="calcFinancials()"></td>
          </tr>
          <tr><td>Other Operating Income</td>
            <td><input type="number" id="f_other1" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_other2" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_other3" placeholder="0" oninput="calcFinancials()"></td>
          </tr>
          <tr class="total">
            <td>Operating Profit (EBIT)</td>
            <td id="f_op1">—</td><td id="f_op2">—</td><td id="f_op3">—</td>
          </tr>
          <tr><td>Depreciation &amp; Amortisation</td>
            <td><input type="number" id="f_dep1" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_dep2" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_dep3" placeholder="0" oninput="calcFinancials()"></td>
          </tr>
          <tr class="total">
            <td>EBITDA (pre-adjustment)</td>
            <td id="f_ebitda1">—</td><td id="f_ebitda2">—</td><td id="f_ebitda3">—</td>
          </tr>
        </tbody>
      </table>

      <div class="section-title">Balance Sheet <span class="pill">Most Recent Year</span></div>
      <div class="form-grid">
        <div class="form-group">
          <label>Net Assets (£)</label>
          <div class="input-prefix"><input type="number" id="b_netassets" placeholder="0"></div>
        </div>
        <div class="form-group">
          <label>Cash at Bank (£)</label>
          <div class="input-prefix"><input type="number" id="b_cash" placeholder="0"></div>
        </div>
        <div class="form-group">
          <label>Total Debtors (£)</label>
          <div class="input-prefix"><input type="number" id="b_debtors" placeholder="0"></div>
        </div>
        <div class="form-group">
          <label>Bank Loans Outstanding (£)</label>
          <div class="input-prefix"><input type="number" id="b_loans" placeholder="0"></div>
        </div>
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" onclick="goTo(0)">← Back</button>
        <div class="spacer"></div>
        <button class="btn btn-primary" onclick="goTo(2)">Continue to Adjustments →</button>
      </div>
    </div>

    <!-- PAGE 3: Adjustments -->
    <div class="page" id="page2">
      <div class="page-header">
        <div class="page-eyebrow">Step 3 of 6</div>
        <h1 class="page-title">Adjustments &amp; Addbacks</h1>
        <p class="page-desc">Normalise the accounts for EBITDA valuation purposes. Enter adjustments per year — positive values add back, negative values deduct.</p>
      </div>

      <div class="info-box">
        <span class="icon">ℹ</span>
        <span class="text">Common adjustments: director salary excess above market rate, one-off costs, personal expenses through the business, non-recurring income. Depreciation is already added back in step 2.</span>
      </div>

      <div class="adj-row header">
        <div>Adjustment Item</div>
        <div style="text-align:right">Year 1 £</div>
        <div style="text-align:right">Year 2 £</div>
        <div style="text-align:right">Year 3 £</div>
        <div style="text-align:right">Notes</div>
      </div>

      <div id="adjRows"></div>

      <button class="add-btn" onclick="addAdjRow()">+ Add adjustment</button>

      <div class="adj-row total-row" style="margin-top:16px">
        <div class="adj-label category">Net Adjustment</div>
        <div class="adj-value" id="adj_tot1">£0</div>
        <div class="adj-value" id="adj_tot2">£0</div>
        <div class="adj-value" id="adj_tot3">£0</div>
        <div></div>
      </div>

      <div class="adj-row total-row" style="background: rgba(76,175,130,0.1); border-color: rgba(76,175,130,0.3);">
        <div class="adj-label category" style="color: var(--success)">Adjusted EBITDA</div>
        <div class="adj-value positive" id="adj_ebitda1">£0</div>
        <div class="adj-value positive" id="adj_ebitda2">£0</div>
        <div class="adj-value positive" id="adj_ebitda3">£0</div>
        <div></div>
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" onclick="goTo(1)">← Back</button>
        <div class="spacer"></div>
        <button class="btn btn-primary" onclick="goTo(3)">Continue to Shareholders →</button>
      </div>
    </div>

    <!-- PAGE 4: Shareholders -->
    <div class="page" id="page3">
      <div class="page-header">
        <div class="page-eyebrow">Step 4 of 6</div>
        <h1 class="page-title">Share Structure</h1>
        <p class="page-desc">Enter each shareholder, their share class, and the number of shares held.</p>
      </div>

      <div class="shareholder-row header">
        <div>Shareholder Name</div>
        <div>Shares Held</div>
        <div>Share Class</div>
        <div></div>
      </div>

      <div id="shareholderRows"></div>

      <button class="add-btn" onclick="addShareholderRow()">+ Add shareholder</button>

      <div style="margin-top: 16px; padding: 12px 16px; background: var(--cream-dim); border-radius: var(--radius); display:flex; justify-content:space-between; align-items:center;">
        <span style="font-size:13px; color: var(--text-muted);">Total shares issued</span>
        <span style="font-family: 'DM Mono', monospace; font-size:15px; color: var(--cream);" id="totalSharesDisplay">0</span>
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" onclick="goTo(2)">← Back</button>
        <div class="spacer"></div>
        <button class="btn btn-primary" onclick="goTo(4)">Continue to Methodology →</button>
      </div>
    </div>

    <!-- PAGE 5: Methodology -->
    <div class="page" id="page4">
      <div class="page-header">
        <div class="page-eyebrow">Step 5 of 6</div>
        <h1 class="page-title">Methodology &amp; Multiples</h1>
        <p class="page-desc">Set the EBITDA weighting and multiple range for this valuation.</p>
      </div>

      <div class="section-title">Year Weighting</div>
      <p style="font-size:13px; color: var(--text-muted); margin-bottom:16px;">Most recent year carries more weight. Standard is 1×, 2×, 3× (oldest to most recent).</p>

      <div class="multiple-grid">
        <div class="multiple-card" onclick="setWeighting('1-2-3')" id="w123">
          <div class="year">Standard</div>
          <div class="weight">1:2:3</div>
          <div class="label">Oldest → Newest</div>
        </div>
        <div class="multiple-card" onclick="setWeighting('1-1-3')" id="w113">
          <div class="year">Recent-heavy</div>
          <div class="weight">1:1:3</div>
          <div class="label">Emphasise latest</div>
        </div>
        <div class="multiple-card" onclick="setWeighting('1-1-1')" id="w111">
          <div class="year">Equal</div>
          <div class="weight">1:1:1</div>
          <div class="label">Simple average</div>
        </div>
      </div>

      <div class="section-title">EBITDA Multiple Range</div>
      <p style="font-size:13px; color: var(--text-muted); margin-bottom:16px;">Set the low, mid, and high multiples for this sector. Professional services typically 3–6×.</p>

      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label>Low Multiple (×)</label>
          <input type="number" id="multLow" value="2.5" step="0.25" oninput="calcResults()">
        </div>
        <div class="form-group">
          <label>Mid Multiple (×)</label>
          <input type="number" id="multMid" value="3.5" step="0.25" oninput="calcResults()">
        </div>
        <div class="form-group">
          <label>High Multiple (×)</label>
          <input type="number" id="multHigh" value="5" step="0.25" oninput="calcResults()">
        </div>
      </div>

      <div class="section-title">Value Reductions</div>
      <p style="font-size:13px; color: var(--text-muted); margin-bottom:16px;">Apply deductions to the headline valuation — e.g. earnout risk, pending liabilities, key-person dependency.</p>

      <div class="form-grid">
        <div class="form-group">
          <label>Deduction Amount (£)</label>
          <div class="input-prefix"><input type="number" id="deduction" placeholder="0" oninput="calcResults()"></div>
        </div>
        <div class="form-group">
          <label>Deduction Description</label>
          <input type="text" id="deductionDesc" placeholder="e.g. Pending shareholder loan repayment">
        </div>
      </div>

      <div class="section-title">
        Accountant's Commentary
        <button id="aiMethodBtn" class="btn btn-outline" style="margin-left:auto; font-size:11px; padding:4px 10px; color:var(--gold-light);" onclick="generateNarrative('accountantNotes')">✦ Generate with AI</button>
      </div>
      <div class="form-group">
        <label>Detailed commentary for report</label>
        <textarea class="narrative-box" id="accountantNotes" placeholder="Describe the valuation rationale and financial performance here…"></textarea>
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" onclick="goTo(3)">← Back</button>
        <div class="spacer"></div>
        <button class="btn btn-primary btn-lg" onclick="goTo(5); calcResults()">Calculate Valuation →</button>
      </div>
    </div>

    <!-- PAGE 6: Results -->
    <div class="page" id="page5">
      <div class="page-header">
        <div class="page-eyebrow">Step 6 of 6</div>
        <h1 class="page-title">Valuation Results</h1>
        <p class="page-desc">Review the valuation before generating the client report.</p>
      </div>

      <div class="results-hero">
        <div class="page-eyebrow" id="r_company">—</div>
        <div style="font-family:'Playfair Display',serif; font-size:14px; color:var(--text-muted); margin-top:4px;" id="r_purpose">—</div>
        <div class="valuation-range">
          <div class="val-point">
            <div class="label">Conservative</div>
            <div class="amount" id="r_low">—</div>
            <div class="sublabel" id="r_low_mult">—</div>
          </div>
          <div class="val-point mid">
            <div class="label">Mid-point</div>
            <div class="amount" id="r_mid">—</div>
            <div class="sublabel" id="r_mid_mult">—</div>
          </div>
          <div class="val-point">
            <div class="label">Optimistic</div>
            <div class="amount" id="r_high">—</div>
            <div class="sublabel" id="r_high_mult">—</div>
          </div>
        </div>
      </div>

      <div class="results-grid">
        <div class="result-card">
          <div class="card-label">Weighted EBITDA</div>
          <div class="card-value" id="r_ebitda">—</div>
          <div class="card-sub" id="r_weighting">—</div>
        </div>
        <div class="result-card">
          <div class="card-label">Most Recent Turnover</div>
          <div class="card-value" id="r_turnover">—</div>
          <div class="card-sub" id="r_margin">—</div>
        </div>
        <div class="result-card">
          <div class="card-label">Net Assets</div>
          <div class="card-value" id="r_netassets">—</div>
          <div class="card-sub">Balance sheet basis</div>
        </div>
        <div class="result-card">
          <div class="card-label">Cash at Bank</div>
          <div class="card-value" id="r_cash">—</div>
          <div class="card-sub">Most recent year end</div>
        </div>
      </div>

      <div class="section-title">EBITDA Breakdown by Year</div>
      <div class="ebitda-breakdown">
        <div class="breakdown-row">
          <span class="year">Year 1 (oldest)</span>
          <span class="ebitda-val" id="r_ebitda_y1">—</span>
          <span class="weight" id="r_w1">×1</span>
          <span class="weighted" id="r_wv1">—</span>
        </div>
        <div class="breakdown-row">
          <span class="year">Year 2</span>
          <span class="ebitda-val" id="r_ebitda_y2">—</span>
          <span class="weight" id="r_w2">×2</span>
          <span class="weighted" id="r_wv2">—</span>
        </div>
        <div class="breakdown-row">
          <span class="year">Year 3 (most recent)</span>
          <span class="ebitda-val" id="r_ebitda_y3">—</span>
          <span class="weight" id="r_w3">×3</span>
          <span class="weighted" id="r_wv3">—</span>
        </div>
        <div class="breakdown-row" style="padding-top:12px; border-top:1px solid var(--border);">
          <span style="font-weight:600; color:var(--cream)">Weighted Average EBITDA</span>
          <span></span>
          <span></span>
          <span class="weighted" style="font-size:15px; color:#f08080;" id="r_wAvg">—</span>
        </div>
      </div>

      <div class="section-title">Value per Share</div>
      <table class="share-table" id="r_shareTable">
        <thead>
          <tr>
            <th>Shareholder</th>
            <th>Class</th>
            <th>Shares</th>
            <th>Value (mid)</th>
          </tr>
        </thead>
        <tbody id="r_shareBody"></tbody>
      </table>

      <div class="section-title" style="margin-top:28px">
        Accountant's Commentary
        <button id="aiNarrativeBtn" class="btn btn-primary" style="margin-left:auto; font-size:12px; padding:7px 14px;" onclick="generateNarrative()">✦ Generate AI Narrative</button>
      </div>
      <textarea class="narrative-box" id="r_narrative" placeholder="Click 'Generate AI Narrative' for Gemini Pro commentary, or type your own…" style="min-height:140px;"></textarea>

      <div class="report-disclaimer">
        <strong>Important:</strong> This valuation report has been prepared for the purpose stated above and should not be relied upon for any other purpose. The valuation is based on information provided by the directors and has not been independently verified. This report constitutes an opinion, not a guarantee of the price achievable on any open market transaction. GTA Accounting accepts no liability to any third party in connection with this report.
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" onclick="goTo(4)">← Back</button>
        <div class="spacer"></div>
        <div id="saveInfo" style="font-size:11px; color:var(--text-faint); margin-right:12px; display:none;">Last saved: <span id="saveTime"></span></div>
        <button id="saveBtn" class="btn btn-outline" style="color:var(--success); border-color:var(--success);" onclick="saveValuation()">💾 Save Valuation</button>
        <button class="btn btn-outline" onclick="window.print()">🖨 Print Report</button>
        <button class="btn btn-primary btn-lg" onclick="showStatus('Report generation coming soon — PDF export will be available in the full version.')">Generate PDF Report</button>
      </div>
    </div>

  </main>
</div>

<div class="status-bar" id="statusBar">
  <span id="statusMsg"></span>
</div>

<script>
// ── STATE ──
let adjRows = [];
let shareholderRows = [];
let weighting = [1, 2, 3];
let adjRowCount = 0;
let shareRowCount = 0;

// ── PDF UPLOAD & EXTRACTION ──
async function handleFileUpload(event) {
  const files = event.target.files;
  if (!files.length) return;

  const status = document.getElementById('uploadStatus');
  const btn = document.getElementById('uploadBtn');
  const debugModal = document.getElementById('debugModal');
  const debugContent = document.getElementById('debugContent');
  const debugOverlay = document.getElementById('debugOverlay');

  status.style.display = 'block';
  btn.disabled = true;
  debugContent.textContent = 'Uploading and processing ' + files.length + ' files...';

  const fileData = [];
  for (const file of files) {
    const base64 = await toBase64(file);
    fileData.push({
      mimeType: file.type,
      data: base64.split(',')[1]
    });
  }

  try {
    const response = await fetch('vertex-proxy.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        action: 'extract',
        files: fileData 
      })
    });

    const result = await response.json();
    console.log('Extraction Result:', result);
    
    // Show raw result in debug modal
    debugContent.textContent = JSON.stringify(result, null, 2);
    debugModal.style.display = 'flex';
    debugOverlay.style.display = 'block';

    if (result.error) throw new Error(result.error);
    
    if (!result.data || (typeof result.data === 'object' && Object.keys(result.data).length === 0)) {
      console.warn('No data found in result.data');
      showStatus('Extraction completed but no data was found.');
      return;
    }

    populateExtractedData(result.data);
    showStatus('Financial data extracted successfully ✓');
  } catch (err) {
    console.error(err);
    debugContent.textContent = 'ERROR: ' + err.message + '\n\n' + debugContent.textContent;
    showStatus('Extraction failed: ' + err.message);
  } finally {
    status.style.display = 'none';
    btn.disabled = false;
  }
}

function closeDebug() {
  document.getElementById('debugModal').style.display = 'none';
  document.getElementById('debugOverlay').style.display = 'none';
}

function toBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = error => reject(error);
  });
}

function populateExtractedData(data) {
  if (!data) {
    showStatus('Gemini returned an empty result. Please try again.');
    return;
  }
  // data is expected to be { year1: {...}, year2: {...}, year3: {...} }
  // Sorted oldest to newest
  const years = ['year1', 'year2', 'year3'];
  
  // 1. Populate Business Details (Step 1) from the most recent year
  const latest = data.year3 || data.year2 || data.year1;
  if (latest) {
    if (latest.companyName) {
      document.getElementById('companyName').value = latest.companyName;
      updateHeader();
    }
    if (latest.companyNumber) document.getElementById('companyNumber').value = latest.companyNumber;
    if (latest.yearEnd) document.getElementById('yearEnd').value = latest.yearEnd;
    if (latest.employees) document.getElementById('employees').value = latest.employees;
    if (latest.yearsTrading) document.getElementById('yearsTrading').value = latest.yearsTrading;
    
    if (latest.sector) {
      const sectorSelect = document.getElementById('sector');
      // Try to find a matching option
      Array.from(sectorSelect.options).forEach(opt => {
        if (opt.text.toLowerCase() === latest.sector.toLowerCase() || 
            latest.sector.toLowerCase().includes(opt.text.toLowerCase())) {
          sectorSelect.value = opt.text;
        }
      });
      // If no match found but a sector was provided, could default to "Other"
      if (!sectorSelect.value && latest.sector) sectorSelect.value = 'Other';
    }
    
    if (latest.description) document.getElementById('businessDesc').value = latest.description;
    if (latest.performanceCommentary) document.getElementById('accountantNotes').value = latest.performanceCommentary;
  }

  // 2. Populate Financial Data (Step 2)
  years.forEach((yKey, idx) => {
    const y = idx + 1;
    const d = data[yKey];
    if (!d) return;

    if (d.turnover) document.getElementById(`f_turn${y}`).value = d.turnover;
    if (d.cos) document.getElementById(`f_cos${y}`).value = d.cos;
    if (d.admin) document.getElementById(`f_admin${y}`).value = d.admin;
    if (d.other) document.getElementById(`f_other${y}`).value = d.other;
    if (d.depreciation) document.getElementById(`f_dep${y}`).value = d.depreciation;

    // Balance sheet (only for most recent year)
    if (idx === 2) {
      if (d.netAssets) document.getElementById('b_netassets').value = d.netAssets;
      if (d.cash) document.getElementById('b_cash').value = d.cash;
      if (d.debtors) document.getElementById('b_debtors').value = d.debtors;
      if (d.loans) document.getElementById('b_loans').value = d.loans;
    }
  });

  // 3. Handle adjustments (Step 3) - e.g. Director salaries
  const adjContainer = document.getElementById('adjRows');
  adjContainer.innerHTML = '';
  adjRowCount = 0;

  if ((data.year1 && data.year1.directorsSalaries) || 
      (data.year2 && data.year2.directorsSalaries) || 
      (data.year3 && data.year3.directorsSalaries)) {
    addAdjRow('Director salary adjustment', 
      (data.year1 && data.year1.directorsSalaries) ? data.year1.directorsSalaries : 0, 
      (data.year2 && data.year2.directorsSalaries) ? data.year2.directorsSalaries : 0, 
      (data.year3 && data.year3.directorsSalaries) ? data.year3.directorsSalaries : 0,
      'Extracted from accounts'
    );
  } else {
    addAdjRow('Director salary adjustment', '', '', '');
  }
  
  addAdjRow('Director pension addback', '', '', '');
  addAdjRow('Non-recurring / one-off costs', '', '', '');

  // 4. Handle Shareholders (Step 4)
  if (latest && latest.directors && Array.isArray(latest.directors) && latest.directors.length > 0) {
    const shContainer = document.getElementById('shareholderRows');
    shContainer.innerHTML = ''; // Clear existing
    shareRowCount = 0;
    
    let totalShares = 100; // Default
    if (latest.shareCapital && !isNaN(parseInt(latest.shareCapital))) {
       totalShares = parseInt(latest.shareCapital);
    }
    
    const numDirectors = latest.directors.length;
    const sharesPerDirector = Math.floor(totalShares / numDirectors);
    const remainder = totalShares % numDirectors;

    latest.directors.forEach((directorName, idx) => {
       // Give any remainder shares to the first director to ensure total matches
       const shares = sharesPerDirector + (idx === 0 ? remainder : 0);
       addShareholderRow(directorName, shares, 'Ordinary');
    });
  }

  calcFinancials();
}

// ── NAV ──
function goTo(idx) {
  // Toggle Page Visibility
  document.querySelectorAll('.page').forEach((p, i) => p.classList.toggle('active', i === idx));
  
  // Toggle Sidebar Step Highlighting (only for the Steps section)
  const steps = document.querySelectorAll('.sidebar-section:nth-of-type(2) .nav-item');
  steps.forEach((n, i) => {
    n.classList.toggle('active', i === idx);
  });
  
  if (idx === 5) calcResults();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateHeader() {
  const name = document.getElementById('companyName').value || 'New Valuation';
  document.getElementById('clientNameDisplay').textContent = name;
}

// ── FINANCIALS ──
function getNum(id) { return parseFloat(document.getElementById(id)?.value) || 0; }

function calcFinancials() {
  for (let y = 1; y <= 3; y++) {
    const turn = getNum(`f_turn${y}`);
    const cos = getNum(`f_cos${y}`);
    const admin = getNum(`f_admin${y}`);
    const other = getNum(`f_other${y}`);
    const dep = getNum(`f_dep${y}`);
    const gp = turn - cos;
    const op = gp - admin + other;
    const ebitda = op + dep;
    document.getElementById(`f_gp${y}`).textContent = fmt(gp);
    document.getElementById(`f_op${y}`).textContent = fmt(op);
    document.getElementById(`f_ebitda${y}`).textContent = fmt(ebitda);
  }
  calcAdjustments();
}

function getPreAdjEbitda(y) {
  const turn = getNum(`f_turn${y}`);
  const cos = getNum(`f_cos${y}`);
  const admin = getNum(`f_admin${y}`);
  const other = getNum(`f_other${y}`);
  const dep = getNum(`f_dep${y}`);
  const gp = turn - cos;
  const op = gp - admin + other;
  return op + dep;
}

// ── ADJUSTMENTS ──
function addAdjRow(label = '', v1 = '', v2 = '', v3 = '', notes = '') {
  const id = adjRowCount++;
  const row = document.createElement('div');
  row.className = 'adj-row';
  row.id = `adjRow${id}`;
  row.innerHTML = `
    <input type="text" class="adj-input" style="text-align:left; border-bottom: 1px solid var(--input-border); font-family:'DM Sans',sans-serif;" placeholder="e.g. Director salary addback" value="${label}" oninput="calcAdjustments()">
    <input type="number" class="adj-input" placeholder="0" value="${v1}" oninput="calcAdjustments()">
    <input type="number" class="adj-input" placeholder="0" value="${v2}" oninput="calcAdjustments()">
    <input type="number" class="adj-input" placeholder="0" value="${v3}" oninput="calcAdjustments()">
    <input type="text" class="adj-input" style="text-align:left; font-family:'DM Sans',sans-serif; font-size:11px;" placeholder="Note…" value="${notes}">
  `;
  document.getElementById('adjRows').appendChild(row);
  calcAdjustments();
}

function calcAdjustments() {
  const container = document.getElementById('adjRows');
  const rows = container.querySelectorAll('.adj-row');
  const totals = [0, 0, 0];
  rows.forEach(row => {
    const inputs = row.querySelectorAll('input[type="number"]');
    inputs.forEach((inp, i) => {
      totals[i] += parseFloat(inp.value) || 0;
    });
  });
  for (let i = 0; i < 3; i++) {
    const el = document.getElementById(`adj_tot${i+1}`);
    el.textContent = fmt(totals[i]);
    el.className = 'adj-value ' + (totals[i] >= 0 ? 'positive' : 'negative');
    const adjEbitda = getPreAdjEbitda(i + 1) + totals[i];
    document.getElementById(`adj_ebitda${i+1}`).textContent = fmt(adjEbitda);
  }
}

function getAdjEbitda(y) {
  const container = document.getElementById('adjRows');
  const rows = container.querySelectorAll('.adj-row');
  let total = 0;
  rows.forEach(row => {
    const inputs = row.querySelectorAll('input[type="number"]');
    if (inputs[y - 1]) total += parseFloat(inputs[y - 1].value) || 0;
  });
  return getPreAdjEbitda(y) + total;
}

// ── SHAREHOLDERS ──
function addShareholderRow(name = '', shares = '', cls = 'Ordinary') {
  const id = shareRowCount++;
  const row = document.createElement('div');
  row.className = 'shareholder-row';
  row.id = `shRow${id}`;
  row.innerHTML = `
    <input type="text" style="background:var(--input-bg); border:1px solid var(--input-border); border-radius:var(--radius); padding:8px 10px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:13px; width:100%; outline:none;" placeholder="Shareholder name" value="${name}" oninput="updateShareTotal()">
    <input type="number" style="background:var(--input-bg); border:1px solid var(--input-border); border-radius:var(--radius); padding:8px 10px; color:var(--text); font-family:'DM Mono',monospace; font-size:13px; width:100%; outline:none;" placeholder="100" value="${shares}" min="1" oninput="updateShareTotal()">
    <select style="background:var(--navy-mid); border:1px solid var(--input-border); border-radius:var(--radius); padding:8px 10px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:13px; width:100%; outline:none;">
      <option ${cls==='Ordinary'?'selected':''}>Ordinary</option>
      <option ${cls==='Ordinary A'?'selected':''}>Ordinary A</option>
      <option ${cls==='Ordinary B'?'selected':''}>Ordinary B</option>
      <option ${cls==='Ordinary C'?'selected':''}>Ordinary C</option>
      <option ${cls==='Preference'?'selected':''}>Preference</option>
    </select>
    <button class="remove-btn" onclick="this.closest('.shareholder-row').remove(); updateShareTotal()">×</button>
  `;
  document.getElementById('shareholderRows').appendChild(row);
  updateShareTotal();
}

function updateShareTotal() {
  let total = 0;
  document.querySelectorAll('#shareholderRows .shareholder-row').forEach(row => {
    const inp = row.querySelectorAll('input')[1];
    total += parseInt(inp?.value) || 0;
  });
  document.getElementById('totalSharesDisplay').textContent = total.toLocaleString();
}

// ── WEIGHTING ──
function setWeighting(key) {
  document.querySelectorAll('.multiple-card').forEach(c => c.classList.remove('selected'));
  if (key === '1-2-3') { weighting = [1,2,3]; document.getElementById('w123').classList.add('selected'); }
  else if (key === '1-1-3') { weighting = [1,1,3]; document.getElementById('w113').classList.add('selected'); }
  else { weighting = [1,1,1]; document.getElementById('w111').classList.add('selected'); }
  calcResults();
}

// ── RESULTS ──
function calcResults() {
  const e1 = getAdjEbitda(1);
  const e2 = getAdjEbitda(2);
  const e3 = getAdjEbitda(3);
  const [w1, w2, w3] = weighting;
  const totalWeight = w1 + w2 + w3;
  const wAvg = (e1 * w1 + e2 * w2 + e3 * w3) / totalWeight;

  const deduction = getNum('deduction');
  const multLow = getNum('multLow') || 2.5;
  const multMid = getNum('multMid') || 3.5;
  const multHigh = getNum('multHigh') || 5;

  // Calculate Net Debt (Loans - Cash)
  const cash = getNum('b_cash');
  const loans = getNum('b_loans');
  const netDebt = loans - cash; // Negative net debt means net cash

  const valLow = (wAvg * multLow) - netDebt - deduction;
  const valMid = (wAvg * multMid) - netDebt - deduction;
  const valHigh = (wAvg * multHigh) - netDebt - deduction;

  // hero
  document.getElementById('r_company').textContent = document.getElementById('companyName')?.value || '—';
  document.getElementById('r_purpose').textContent = document.getElementById('purpose')?.value || '—';

  document.getElementById('r_low').textContent = fmtShort(valLow);
  document.getElementById('r_mid').textContent = fmtShort(valMid);
  document.getElementById('r_high').textContent = fmtShort(valHigh);
  document.getElementById('r_low_mult').textContent = `${multLow}× EBITDA (Adj for Net Debt)`;
  document.getElementById('r_mid_mult').textContent = `${multMid}× EBITDA (Adj for Net Debt)`;
  document.getElementById('r_high_mult').textContent = `${multHigh}× EBITDA (Adj for Net Debt)`;

  // cards
  document.getElementById('r_ebitda').textContent = fmt(wAvg);
  document.getElementById('r_weighting').textContent = `Weighting: ${w1}:${w2}:${w3}`;
  const turn3 = getNum('f_turn3');
  document.getElementById('r_turnover').textContent = fmt(turn3);
  if (turn3) {
    const margin = ((getPreAdjEbitda(3) / turn3) * 100).toFixed(1);
    document.getElementById('r_margin').textContent = `EBITDA margin ${margin}%`;
  }
  document.getElementById('r_netassets').textContent = fmt(getNum('b_netassets'));
  document.getElementById('r_cash').textContent = fmt(getNum('b_cash'));

  // breakdown
  document.getElementById('r_ebitda_y1').textContent = fmt(e1);
  document.getElementById('r_ebitda_y2').textContent = fmt(e2);
  document.getElementById('r_ebitda_y3').textContent = fmt(e3);
  document.getElementById('r_w1').textContent = `×${w1}`;
  document.getElementById('r_w2').textContent = `×${w2}`;
  document.getElementById('r_w3').textContent = `×${w3}`;
  document.getElementById('r_wv1').textContent = fmt(e1 * w1);
  document.getElementById('r_wv2').textContent = fmt(e2 * w2);
  document.getElementById('r_wv3').textContent = fmt(e3 * w3);
  document.getElementById('r_wAvg').textContent = fmt(wAvg);

  // shares
  const totalShares = Array.from(document.querySelectorAll('#shareholderRows .shareholder-row'))
    .reduce((t, row) => t + (parseInt(row.querySelectorAll('input')[1]?.value) || 0), 0);
  const pricePerShare = totalShares > 0 ? valMid / totalShares : 0;

  const tbody = document.getElementById('r_shareBody');
  tbody.innerHTML = '';
  document.querySelectorAll('#shareholderRows .shareholder-row').forEach(row => {
    const inputs = row.querySelectorAll('input');
    const name = inputs[0]?.value || '—';
    const shares = parseInt(inputs[1]?.value) || 0;
    const cls = row.querySelector('select')?.value || '—';
    const val = pricePerShare * shares;
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${name}</td><td>${cls}</td><td>${shares.toLocaleString()}</td><td>${fmt(val)}</td>`;
    tbody.appendChild(tr);
  });

  // Automatic generation removed - manual button only
}

// ── SAVE TO DB ──
async function saveValuation() {
  const btn = document.getElementById('saveBtn');
  
  // Gather all data
  const data = {
    companyName: document.getElementById('companyName')?.value,
    companyNumber: document.getElementById('companyNumber')?.value,
    sector: document.getElementById('sector')?.value,
    yearEnd: document.getElementById('yearEnd')?.value,
    yearsTrading: document.getElementById('yearsTrading')?.value,
    employees: document.getElementById('employees')?.value,
    purpose: document.getElementById('purpose')?.value,
    reportDate: document.getElementById('reportDate')?.value,
    businessDesc: document.getElementById('businessDesc')?.value,
    
    // Financials
    financials: {
      turnover: [getNum('f_turn1'), getNum('f_turn2'), getNum('f_turn3')],
      cos: [getNum('f_cos1'), getNum('f_cos2'), getNum('f_cos3')],
      admin: [getNum('f_admin1'), getNum('f_admin2'), getNum('f_admin3')],
      other: [getNum('f_other1'), getNum('f_other2'), getNum('f_other3')],
      depreciation: [getNum('f_dep1'), getNum('f_dep2'), getNum('f_dep3')],
      balanceSheet: {
        netAssets: getNum('b_netassets'),
        cash: getNum('b_cash'),
        debtors: getNum('b_debtors'),
        loans: getNum('b_loans')
      }
    },
    
    // Adjustments
    adjustments: Array.from(document.querySelectorAll('#adjRows .adj-row')).map(row => {
      const inputs = row.querySelectorAll('input');
      return {
        label: inputs[0].value,
        v1: parseFloat(inputs[1].value) || 0,
        v2: parseFloat(inputs[2].value) || 0,
        v3: parseFloat(inputs[3].value) || 0,
        note: inputs[4].value
      };
    }),
    
    // Shareholders
    shareholders: Array.from(document.querySelectorAll('#shareholderRows .shareholder-row')).map(row => {
      const inputs = row.querySelectorAll('input');
      return {
        name: inputs[0].value,
        shares: parseInt(inputs[1].value) || 0,
        class: row.querySelector('select').value
      };
    }),
    
    // Methodology
    weighting: weighting,
    multiples: {
      low: getNum('multLow'),
      mid: getNum('multMid'),
      high: getNum('multHigh')
    },
    deduction: getNum('deduction'),
    deductionDesc: document.getElementById('deductionDesc').value,
    
    // Narratives
    accountantNotes: document.getElementById('accountantNotes').value,
    aiNarrative: document.getElementById('r_narrative').value,
    
    // Result
    valuationMid: parseFloat(document.getElementById('r_mid').textContent.replace(/[£,mk]/g, '')) * (document.getElementById('r_mid').textContent.includes('m') ? 1000000 : (document.getElementById('r_mid').textContent.includes('k') ? 1000 : 1))
  };

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Saving…';

  try {
    const response = await fetch('save-valuation.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    const result = await response.json();
    if (result.error) throw new Error(result.error);
    
    showStatus('Valuation saved to ELK Database ✓');
    btn.innerHTML = '💾 Saved';
    document.getElementById('saveInfo').style.display = 'block';
    document.getElementById('saveTime').textContent = new Date().toLocaleTimeString();
    setTimeout(() => {
      btn.innerHTML = '💾 Save Valuation';
      btn.disabled = false;
    }, 3000);
  } catch (err) {
    showStatus('Save failed: ' + err.message);
    btn.disabled = false;
    btn.innerHTML = '💾 Save Valuation';
  }
}

// ── FORMAT ──
function fmt(n) {
  if (isNaN(n) || n === null) return '—';
  const abs = Math.abs(n);
  const str = abs.toLocaleString('en-GB', { maximumFractionDigits: 0 });
  return (n < 0 ? '-£' : '£') + str;
}

function fmtShort(n) {
  if (isNaN(n) || !n) return '—';
  if (Math.abs(n) >= 1000000) return '£' + (n / 1000000).toFixed(2) + 'm';
  if (Math.abs(n) >= 1000) return '£' + (n / 1000).toFixed(0) + 'k';
  return fmt(n);
}

// ── STATUS ──
let statusTimer;
function showStatus(msg) {
  const bar = document.getElementById('statusBar');
  document.getElementById('statusMsg').textContent = msg;
  bar.classList.add('show');
  clearTimeout(statusTimer);
  statusTimer = setTimeout(() => bar.classList.remove('show'), 4000);
}

// ── AI NARRATIVE ──
async function generateNarrative(targetId = 'r_narrative') {
  const btnId = targetId === 'accountantNotes' ? 'aiMethodBtn' : 'aiNarrativeBtn';
  const btn = document.getElementById(btnId);
  const textarea = document.getElementById(targetId);
  
  // Build prompt from all current data
  const company   = document.getElementById('companyName')?.value || 'the company';
  const sector    = document.getElementById('sector')?.value || 'unspecified sector';
  const purpose   = document.getElementById('purpose')?.value || 'general advisory';
  const employees = document.getElementById('employees')?.value || 'unknown';
  const years     = document.getElementById('yearsTrading')?.value || 'unknown';
  const desc      = document.getElementById('businessDesc')?.value || '';
  const notes     = document.getElementById('accountantNotes')?.value || '';

  const e1 = getAdjEbitda(1), e2 = getAdjEbitda(2), e3 = getAdjEbitda(3);
  const [w1,w2,w3] = weighting;
  const wAvg = (e1*w1 + e2*w2 + e3*w3) / (w1+w2+w3);
  const turn3    = getNum('f_turn3');
  const multLow  = getNum('multLow') || 2.5;
  const multMid  = getNum('multMid') || 3.5;
  const multHigh = getNum('multHigh') || 5;
  
  const cash = getNum('b_cash');
  const loans = getNum('b_loans');
  const netDebt = loans - cash;
  const deduction = getNum('deduction');
  const deductDesc = document.getElementById('deductionDesc')?.value || '';
  
  const valLow  = (wAvg * multLow) - netDebt - deduction;
  const valMid  = (wAvg * multMid) - netDebt - deduction;
  const valHigh = (wAvg * multHigh) - netDebt - deduction;
  
  const margin = turn3 ? ((getPreAdjEbitda(3) / turn3) * 100).toFixed(1) : 'unknown';

  const prompt = `Write a comprehensive professional business valuation commentary. Use the following data:

Company: ${company}
Sector: ${sector}
Purpose: ${purpose}
Years trading: ${years}
Employees: ${employees}
Description: ${desc}

Financials (3 yrs):
- EBITDA: ${fmt(e1)} (Y1), ${fmt(e2)} (Y2), ${fmt(e3)} (Y3)
- Weighted Avg: ${fmt(wAvg)}
- Margin: ${margin}%
- Net Debt: ${fmt(netDebt)} (Loans: ${fmt(loans)}, Cash: ${fmt(cash)})

Valuation:
- Multiples: ${multLow}x - ${multHigh}x (Mid: ${multMid}x)
- Equity Value Range: ${fmtShort(valLow)} to ${fmtShort(valHigh)}
- Other Deductions: ${fmt(deduction)} (${deductDesc})

Write 4-5 detailed paragraphs. Analyze the growth trends, discuss the balance sheet strength (especially the net cash/debt position), justify the EBITDA multiple for this sector, and provide a professional conclusion. No bullet points or headers.`;

  // Show loading state
  btn.disabled = true;
  const originalHtml = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Generating…';
  textarea.value = 'Generating professional AI commentary...';

  try {
    const response = await fetch('vertex-proxy.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ prompt })
    });

    const data = await response.json();
    if (data.error) throw new Error(data.error);
    
    textarea.value = data.narrative;
    textarea.dataset.aiGenerated = '1';
    showStatus('AI commentary generated ✓');
  } catch (err) {
    textarea.value = 'Error: ' + err.message;
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
}

// ── INIT ──
function init() {
  // Default adjustment rows based on View HR pattern
  addAdjRow('Director salary adjustment', '', '', '');
  addAdjRow('Director pension addback', '', '', '');
  addAdjRow('Non-recurring / one-off costs', '', '', '');

  // Default shareholders
  addShareholderRow('', '', 'Ordinary');

  // Default weighting
  document.getElementById('w123').classList.add('selected');

  // Set default report date
  const now = new Date();
  document.getElementById('reportDate').value = now.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
}

init();
</script>

  <footer style="
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
      v3.2.1 (Built: 15 Mar 2026 14:30)
    </span></span>

  </footer>
</body>
</html>
