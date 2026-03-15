<?php
session_start();
require_once 'db.php';

// Authentication Guard
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

$firm_id = $_SESSION['firm_id'];
$pdo = DB::getInstance();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['firm_name'] ?? '';
    $primary_color = $_POST['primary_color'] ?? '#c5a059';

    try {
        $stmt = $pdo->prepare("UPDATE firms SET name = ?, primary_color = ? WHERE id = ?");
        $stmt->execute([$name, $primary_color, $firm_id]);
        $_SESSION['firm_name'] = $name;
        $message = 'Firm settings updated successfully.';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}

// Fetch current firm state
$stmt = $pdo->prepare("SELECT * FROM firms WHERE id = ?");
$stmt->execute([$firm_id]);
$firm = $stmt->fetch();

$primary_color = $firm['primary_color'] ?? '#c5a059';
$secondary_color = $firm['secondary_color'] ?? '#050505';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Firm Settings | <?php echo htmlspecialchars($firm['name']); ?></title>
<link rel="icon" type="image/webp" href="favicon.webp">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
<style>
:root {
  --brand-accent: <?php echo $primary_color; ?>;
  --brand-surface: <?php echo $secondary_color; ?>;
  --brand-accent-light: <?php echo $primary_color; ?>; 
  --brand-accent-dim: <?php echo $primary_color; ?>26;
  --brand-accent-border: <?php echo $primary_color; ?>4d;
  --brand-accent-glow: <?php echo $primary_color; ?>33;
}

.settings-container {
    max-width: 640px;
    background: var(--brand-surface-mid);
    border: 1px solid var(--border-subtle);
    padding: 40px;
    border-radius: var(--radius-lg);
}

.color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-top: 8px;
}

input[type="color"] {
    -webkit-appearance: none;
    border: none;
    width: 48px;
    height: 48px;
    cursor: pointer;
    background: none;
}
input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
input[type="color"]::-webkit-color-swatch {
    border: 1px solid var(--border-subtle);
    border-radius: 4px;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
    color: var(--success);
    padding: 16px;
    border-radius: 4px;
    margin-bottom: 32px;
    font-size: 14px;
}
</style>
</head>
<body>

<header>
  <div class="header-left">
    <span class="header-label"><?php echo htmlspecialchars($firm['name']); ?> Settings</span>
  </div>
  <div class="header-right" style="display: flex; align-items: center; gap: 20px;">
    <a href="logout.php" style="font-size: 10px; color: var(--brand-accent); text-decoration: none; text-transform: uppercase; letter-spacing: 0.1em; border: 1px solid var(--brand-accent-border); padding: 4px 8px; border-radius: 2px;">Logout</a>
  </div>
</header>

<div class="app-wrapper">
  <nav class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-section-label">Main</div>
      <a href="dashboard.php" class="nav-item">
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
      <a href="settings.php" class="nav-item active">
        <span style="width:20px; text-align:center;">⚙️</span>
        Firm Settings
      </a>
    </div>

    <div class="sidebar-logo-container">
      <img src="elk-design-logo.png" alt="ELK Digital" class="sidebar-logo">
    </div>
  </nav>

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Firm Branding & Identity</h1>
      <p class="page-desc">Customise how your firm appears to your staff and in generated reports.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" class="settings-container">
        <div class="form-group full" style="margin-bottom: 24px;">
            <label>Legal Firm Name</label>
            <input type="text" name="firm_name" value="<?php echo htmlspecialchars($firm['name']); ?>" required>
            <p style="font-size:11px; color: var(--text-faint); margin-top:8px;">This appears in report headers and the portal dashboard.</p>
        </div>

        <div class="form-group full">
            <label>Primary Brand Colour</label>
            <div class="color-picker-wrapper">
                <input type="color" name="primary_color" value="<?php echo htmlspecialchars($primary_color); ?>">
                <input type="text" value="<?php echo htmlspecialchars($primary_color); ?>" readonly style="width: 120px; font-family: 'DM Mono', monospace; text-align: center;">
            </div>
            <p style="font-size:11px; color: var(--text-faint); margin-top:8px;">The main accent color used for buttons, active states, and highlights.</p>
        </div>

        <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid var(--border-subtle);">
            <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Save Firm Settings</button>
        </div>
    </form>
    
    <div style="margin-top: 40px; padding: 24px; border: 1px solid var(--error); border-radius: 4px; background: rgba(229, 115, 115, 0.05); max-width: 640px;">
        <h4 style="color: var(--error); margin-bottom: 8px;">Subscription & License</h4>
        <p style="font-size: 13px; color: var(--text-muted);">Your account is currently managed by ELK Digital Super-Admin. Contact Jamie Elkins for billing or to add user seats.</p>
    </div>
  </main>
</div>

</body>
</html>
