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
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_settings';

    if ($action === 'save_settings') {
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
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif ($action === 'add_user') {
        $user_name = $_POST['user_name'] ?? '';
        $user_email = $_POST['user_email'] ?? '';
        $user_pass = $_POST['user_pass'] ?? '';
        $user_role = $_POST['user_role'] ?? 'user';

        try {
            $hash = password_hash($user_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (firm_id, email, password_hash, name, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$firm_id, $user_email, $hash, $user_name, $user_role]);
            $message = 'User added successfully.';
        } catch (Exception $e) {
            $error = 'Error adding user: ' . $e->getMessage();
        }
    }
}

// Fetch current firm state
$stmt = $pdo->prepare("SELECT * FROM firms WHERE id = ?");
$stmt->execute([$firm_id]);
$firm = $stmt->fetch();

$primary_color = $firm['primary_color'] ?? '#c5a059';
$secondary_color = $firm['secondary_color'] ?? '#050505';
$logo_url = $firm['logo_url'] ?? '';

// Fetch firm users
$stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE firm_id = ? ORDER BY created_at DESC");
$stmt->execute([$firm_id]);
$users = $stmt->fetchAll();

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
<title>Firm Settings | <?php echo htmlspecialchars($firm['name']); ?></title>
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

.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: start;
}

.settings-container {
    background: var(--brand-surface-mid);
    border: 1px solid var(--border-subtle);
    padding: 32px;
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
    height: 24px;
    border-radius: 3px;
    margin-bottom: 8px;
    display: flex;
}

.palette-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

.color-picker-group {
    display: flex;
    gap: 20px;
    margin-bottom: 24px;
}

.color-input-item {
    flex: 1;
}

input[type="color"] {
    -webkit-appearance: none;
    border: none;
    width: 100%;
    height: 36px;
    cursor: pointer;
    background: none;
    padding: 0;
}
input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
input[type="color"]::-webkit-color-swatch {
    border: 1px solid var(--border-subtle);
    border-radius: 4px;
}

.user-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.user-table th, .user-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--border-subtle);
}
.user-table th { color: var(--text-faint); text-transform: uppercase; font-size: 10px; letter-spacing: 0.1em; }

.alert { padding: 16px; border-radius: 4px; margin-bottom: 32px; font-size: 14px; }
.alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); }
.alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; }

.modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--brand-surface-mid);
    border: 1px solid var(--brand-accent);
    padding: 40px;
    border-radius: 8px;
    z-index: 100;
    width: 400px;
    box-shadow: 0 0 50px rgba(0,0,0,0.8);
}
.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 90;
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
      <h1 class="page-title">Firm Management</h1>
      <p class="page-desc">Manage your firm's brand identity and team access.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- BRANDING SECTION -->
        <div class="settings-container">
            <h2 class="section-title" style="margin-top:0;">Branding & Identity</h2>
            <form method="POST">
                <input type="hidden" name="action" value="save_settings">
                <div class="form-group full" style="margin-bottom: 24px;">
                    <label>Legal Firm Name</label>
                    <input type="text" name="firm_name" value="<?php echo htmlspecialchars($firm['name']); ?>" required>
                </div>

                <div style="font-size:11px; color: var(--text-faint); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Presets</div>
                <div class="palette-grid">
                    <div class="palette-option" onclick="setPalette('#c5a059', '#050505')">
                        <div class="palette-preview"><div style="flex:3; background:#c5a059;"></div><div style="flex:7; background:#050505;"></div></div>
                        <div class="palette-label">ELK Gold</div>
                    </div>
                    <div class="palette-option" onclick="setPalette('#1e40af', '#0f172a')">
                        <div class="palette-preview"><div style="flex:3; background:#1e40af;"></div><div style="flex:7; background:#0f172a;"></div></div>
                        <div class="palette-label">GTA Blue</div>
                    </div>
                    <div class="palette-option" onclick="setPalette('#059669', '#064e3b')">
                        <div class="palette-preview"><div style="flex:3; background:#059669;"></div><div style="flex:7; background:#064e3b;"></div></div>
                        <div class="palette-label">Emerald</div>
                    </div>
                    <div class="palette-option" onclick="setPalette('#ef4444', '#1a1a1a')">
                        <div class="palette-preview"><div style="flex:3; background:#ef4444;"></div><div style="flex:7; background:#1a1a1a;"></div></div>
                        <div class="palette-label">ELK Red</div>
                    </div>
                </div>

                <div class="color-picker-group">
                    <div class="color-input-item">
                        <label>Accent</label>
                        <input type="color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($primary_color); ?>">
                    </div>
                    <div class="color-input-item">
                        <label>Surface</label>
                        <input type="color" id="secondary_color" name="secondary_color" value="<?php echo htmlspecialchars($secondary_color); ?>">
                    </div>
                </div>

                <div class="form-group full" style="margin-bottom: 24px;">
                    <label>Logo URL</label>
                    <input type="text" name="logo_url" value="<?php echo htmlspecialchars($logo_url); ?>" placeholder="https://yourfirm.com/logo.png">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">Save Branding</button>
            </form>
        </div>

        <!-- USER SECTION -->
        <div class="settings-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 class="section-title" style="margin:0;">Team Members</h2>
                <button class="btn btn-outline" onclick="showAddUser()" style="font-size: 10px; padding: 6px 12px;">+ Add User</button>
            </div>
            
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="color: var(--text-main); font-weight: 500;"><?php echo htmlspecialchars($u['name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><span style="text-transform: uppercase; font-size: 9px; padding: 2px 6px; border: 1px solid var(--border-subtle); border-radius: 4px;"><?php echo $u['role']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ADD USER MODAL -->
    <div id="addUserModal" class="modal">
        <h3 style="margin-top: 0; color: var(--brand-accent);">Add Team Member</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Full Name</label>
                <input type="text" name="user_name" required placeholder="e.g. Sarah Jenkins">
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Email Address</label>
                <input type="email" name="user_email" required placeholder="sarah@firm.com">
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Role</label>
                <select name="user_role" style="width:100%; background:var(--brand-surface); border:1px solid var(--border-subtle); color:var(--text); padding:10px; border-radius:4px;">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 24px;">
                <label>Temporary Password</label>
                <input type="text" name="user_pass" required value="VAL_<?php echo rand(1000,9999); ?>!">
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Create User</button>
                <button type="button" class="btn btn-outline" onclick="hideAddUser()" style="flex: 1;">Cancel</button>
            </div>
        </form>
    </div>
    <div id="modalOverlay" class="overlay" onclick="hideAddUser()"></div>

  </main>
</div>

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
      <?php 
        $version = getenv('APP_VERSION') ?: '3.2.x';
        $buildTime = getenv('BUILD_TIME') ?: 'Deployment Pending';
        echo "v{$version} (Built: {$buildTime})"; 
      ?>
    </span></span>
  </footer>

<script>
function setPalette(primary, secondary) {
    document.getElementById('primary_color').value = primary;
    document.getElementById('secondary_color').value = secondary;
}
function showAddUser() {
    document.getElementById('addUserModal').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
}
function hideAddUser() {
    document.getElementById('addUserModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}
</script>

</body>
</html>
