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
    $secondary_color = $_POST['secondary_color'] ?? '#050505';
    $logo_url = $_POST['logo_url'] ?? '';

    try {
        $stmt = $pdo->prepare("UPDATE firms SET name = ?, primary_color = ?, secondary_color = ?, logo_url = ? WHERE id = ?");
        $stmt->execute([$name, $primary_color, $secondary_color, $logo_url, $firm_id]);
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
$logo_url = $firm['logo_url'] ?? '';
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

.palette-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 32px;
}

.palette-option {
    border: 2px solid var(--border-subtle);
    border-radius: 6px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.palette-option:hover {
    border-color: var(--brand-accent);
    transform: translateY(-2px);
}

.palette-preview {
    height: 32px;
    border-radius: 3px;
    margin-bottom: 8px;
    display: flex;
}

.palette-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

.color-picker-group {
    display: flex;
    gap: 24px;
    margin-bottom: 24px;
}

.color-input-item {
    flex: 1;
}

input[type="color"] {
    -webkit-appearance: none;
    border: none;
    width: 100%;
    height: 40px;
    cursor: pointer;
    background: none;
    padding: 0;
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
      <?php if ($logo_url): ?>
        <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="<?php echo htmlspecialchars($firm['name']); ?>" class="sidebar-logo">
      <?php else: ?>
        <img src="elk-design-logo.png" alt="ELK Digital" class="sidebar-logo">
      <?php endif; ?>
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
        <div class="form-group full" style="margin-bottom: 32px;">
            <label>Legal Firm Name</label>
            <input type="text" name="firm_name" value="<?php echo htmlspecialchars($firm['name']); ?>" required>
            <p style="font-size:11px; color: var(--text-faint); margin-top:8px;">This appears in report headers and the portal dashboard.</p>
        </div>

        <div class="section-title" style="margin-top: 0; border: none; padding: 0; margin-bottom: 12px;">Branding Presets</div>
        <div class="palette-grid">
            <div class="palette-option" onclick="setPalette('#c5a059', '#050505')">
                <div class="palette-preview">
                    <div style="flex:3; background:#c5a059;"></div>
                    <div style="flex:7; background:#050505;"></div>
                </div>
                <div class="palette-label">ELK Gold</div>
            </div>
            <div class="palette-option" onclick="setPalette('#1e40af', '#0f172a')">
                <div class="palette-preview">
                    <div style="flex:3; background:#1e40af;"></div>
                    <div style="flex:7; background:#0f172a;"></div>
                </div>
                <div class="palette-label">GTA Blue</div>
            </div>
            <div class="palette-option" onclick="setPalette('#059669', '#064e3b')">
                <div class="palette-preview">
                    <div style="flex:3; background:#059669;"></div>
                    <div style="flex:7; background:#064e3b;"></div>
                </div>
                <div class="palette-label">Emerald</div>
            </div>
            <div class="palette-option" onclick="setPalette('#ef4444', '#1a1a1a')">
                <div class="palette-preview">
                    <div style="flex:3; background:#ef4444;"></div>
                    <div style="flex:7; background:#1a1a1a;"></div>
                </div>
                <div class="palette-label">ELK Red</div>
            </div>
        </div>

        <div class="color-picker-group">
            <div class="color-input-item">
                <label>Accent Colour</label>
                <input type="color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($primary_color); ?>">
            </div>
            <div class="color-input-item">
                <label>Surface Colour</label>
                <input type="color" id="secondary_color" name="secondary_color" value="<?php echo htmlspecialchars($secondary_color); ?>">
            </div>
        </div>

        <div class="form-group full" style="margin-bottom: 24px;">
            <label>Logo URL (PNG/SVG)</label>
            <input type="text" name="logo_url" value="<?php echo htmlspecialchars($logo_url); ?>" placeholder="https://yourfirm.com/logo.png">
            <p style="font-size:11px; color: var(--text-faint); margin-top:8px;">Provide a public URL to your firm's logo. If empty, the ELK Digital logo will be used.</p>
        </div>

        <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid var(--border-subtle);">
            <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Save Firm Settings</button>
        </div>
    </form>
  </main>
</div>

<script>
function setPalette(primary, secondary) {
    document.getElementById('primary_color').value = primary;
    document.getElementById('secondary_color').value = secondary;
}
</script>

</body>
</html>
