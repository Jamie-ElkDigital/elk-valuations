<?php
session_start();
require_once 'db.php';

// Authentication Guard
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

$firm_id = $_SESSION['firm_id'];
$firm_name = $_SESSION['firm_name'];

try {
    $pdo = DB::getInstance();
    
    // Fetch Firm Branding
    $stmt = $pdo->prepare("SELECT * FROM firms WHERE id = ?");
    $stmt->execute([$firm_id]);
    $firm = $stmt->fetch();

    if (!$firm) {
        die("Firm configuration missing.");
    }

    // Fetch Valuations for this firm ONLY
    $stmt = $pdo->prepare("SELECT * FROM valuations WHERE firm_id = ? ORDER BY created_at DESC");
    $stmt->execute([$firm_id]);
    $valuations = $stmt->fetchAll();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | <?php echo htmlspecialchars($firm_name); ?></title>
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
  
  /* Derived approximations */
  --brand-accent-light: <?php echo $primary_color; ?>; 
  --brand-accent-dim: <?php echo $primary_color; ?>26;
  --brand-accent-border: <?php echo $primary_color; ?>4d;
  --brand-accent-glow: <?php echo $primary_color; ?>33;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    margin-top: 32px;
}

.valuation-card {
    background: var(--brand-surface-mid);
    border: 1px solid var(--border-subtle);
    padding: 24px;
    border-radius: var(--radius-lg);
    transition: transform 0.2s, border-color 0.2s;
    cursor: pointer;
    text-decoration: none;
    display: block;
    position: relative;
    overflow: hidden;
}

.valuation-card:hover {
    transform: translateY(-4px);
    border-color: var(--brand-accent-border);
    background: rgba(255,255,255,0.02);
}

.card-title {
    font-family: 'Barlow', sans-serif;
    font-size: 18px;
    font-weight: 600;
    color: var(--text-main);
    margin-bottom: 8px;
}

.card-meta {
    font-size: 12px;
    color: var(--text-faint);
    margin-bottom: 20px;
    line-height: 1.5;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}

.card-value-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-faint);
    margin-bottom: 4px;
}

.card-value {
    font-size: 22px;
    font-weight: 700;
    color: var(--brand-accent-light);
    font-family: 'DM Mono', monospace;
}

.btn-new {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--brand-accent);
    color: var(--brand-surface);
    padding: 10px 20px;
    border-radius: var(--radius);
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    transition: background 0.2s;
}
.btn-new:hover { background: var(--brand-accent-hover); }
</style>
</head>
<body>

<header>
  <div class="header-left">
    <span class="header-label"><?php echo htmlspecialchars($firm_name); ?> Intelligence Portal</span>
  </div>
  <div class="header-right" style="display: flex; align-items: center; gap: 20px;">
    <span style="font-size: 11px; color: var(--text-faint);"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
    <a href="logout.php" style="font-size: 10px; color: var(--brand-accent); text-decoration: none; text-transform: uppercase; letter-spacing: 0.1em; border: 1px solid var(--brand-accent-border); padding: 4px 8px; border-radius: 2px;">Logout</a>
  </div>
</header>

<div class="app-wrapper">
  <nav class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-section-label">Main</div>
      <a href="dashboard.php" class="nav-item active">
        <span style="width:20px; text-align:center;">📊</span>
        Dashboard
      </a>
      <a href="index.php" class="nav-item">
        <span style="width:20px; text-align:center;">➕</span>
        New Valuation
      </a>
    </div>
    
    <div class="sidebar-section">
      <div class="sidebar-section-label">Admin</div>
      <a href="settings.php" class="nav-item">
        <span style="width:20px; text-align:center;">⚙️</span>
        Firm Settings
      </a>
    </div>

    <div class="sidebar-logo-container">
      <?php if ($logo_url): ?>
        <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="<?php echo htmlspecialchars($firm_name); ?>" class="sidebar-logo">
      <?php else: ?>
        <img src="elk-design-logo.png" alt="ELK Digital" class="sidebar-logo">
      <?php endif; ?>
    </div>
  </nav>

  <main class="main">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px;">
      <div>
        <h1 class="page-title">Valuation Pipeline</h1>
        <p class="page-desc">Centralised intelligence for <?php echo htmlspecialchars($firm_name); ?> client portfolios.</p>
      </div>
      <a href="index.php" class="btn-new">Create New Valuation</a>
    </div>

    <div class="dashboard-grid">
      <?php foreach ($valuations as $v): ?>
        <a href="view-valuation.php?id=<?php echo $v['id']; ?>" class="valuation-card">
          <div class="card-title"><?php echo htmlspecialchars($v['client_name']); ?></div>
          <div class="card-meta">
            Sector: <?php echo htmlspecialchars($v['sector'] ?: 'General'); ?><br>
            Year End: <?php echo htmlspecialchars($v['year_end']); ?><br>
            Analyst: <?php echo htmlspecialchars($_SESSION['user_name']); ?>
          </div>
          <div class="card-footer">
            <div>
                <div class="card-value-label">Mid-Point Valuation</div>
                <div class="card-value">£<?php 
                    $val = (float)$v['valuation_mid'];
                    if ($val >= 1000000) echo number_format($val / 1000000, 2) . 'm';
                    else echo number_format($val / 1000, 0) . 'k';
                ?></div>
            </div>
            <div style="font-size: 11px; color: var(--text-faint);"><?php echo date('j M Y', strtotime($v['created_at'])); ?></div>
          </div>
        </a>
      <?php endforeach; ?>
      
      <?php if (empty($valuations)): ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 80px 40px; background: rgba(255,255,255,0.01); border: 1px dashed var(--border-subtle); border-radius: 4px;">
          <div style="font-size: 32px; margin-bottom: 16px;">📂</div>
          <h3 style="color: var(--text-main); margin-bottom: 8px;">No valuations yet</h3>
          <p style="color: var(--text-faint); font-size: 13px; margin-bottom: 24px;">Start by uploading statutory accounts to generate your first professional report.</p>
          <a href="index.php" class="btn btn-outline">Start First Valuation</a>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
