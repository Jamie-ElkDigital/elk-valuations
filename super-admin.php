<?php
session_start();
require_once 'db.php';

// Super-Admin Guard (Must belong to firm 'elk')
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated'] || $_SESSION['firm_slug'] !== 'elk') {
    die("Unauthorised Access. Super-Admin permissions required.");
}

$message = '';

try {
    $pdo = DB::getInstance();

    // Handle Firm Creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_firm') {
        $firm_name = $_POST['name'] ?? '';
        $firm_slug = $_POST['slug'] ?? '';
        $user_name = $_POST['user_name'] ?? '';
        $user_email = $_POST['user_email'] ?? '';
        $user_pass = $_POST['user_pass'] ?? '';

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO firms (name, slug) VALUES (?, ?)");
            $stmt->execute([$firm_name, $firm_slug]);
            $new_firm_id = $pdo->lastInsertId();

            $hash = password_hash($user_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (firm_id, email, password_hash, name, role) VALUES (?, ?, ?, ?, 'admin')");
            $stmt->execute([$new_firm_id, $user_email, $hash, $user_name]);

            $pdo->commit();
            $message = "Firm and Admin User created successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
        }
    }

    // Handle Password Reset
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_pass') {
        $uid = (int)$_POST['user_id'];
        $new_raw = "ELK_" . rand(1000, 9999) . "!";
        $new_hash = password_hash($new_raw, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_hash, $uid]);
        $message = "Password reset successfully. New password: <strong>$new_raw</strong>";
    }

    // Handle User Deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $uid = (int)$_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $message = "User deleted successfully.";
    }

    // Handle 2FA Toggle
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_2fa') {
        $fid = (int)$_POST['firm_id'];
        $new_val = $_POST['current_status'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE firms SET global_2fa_enabled = ? WHERE id = ?");
        $stmt->execute([$new_val, $fid]);
        $message = "Global 2FA updated successfully.";
    }

    // 1. Overview Stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM firms");
    $totalFirms = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM valuations");
    $totalValuations = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT SUM(total_tokens) FROM usage_log");
    $totalTokens = $stmt->fetchColumn() ?: 0;

    // Estimate cost: £12 per 1M tokens (Rough average for Gemini 1.5 Pro in GBP)
    $estCost = ($totalTokens / 1000000) * 12;

    // 2. Usage by Firm
    $sql = "SELECT f.id, f.name, f.slug, f.global_2fa_enabled, COUNT(u.id) as total_requests, SUM(u.total_tokens) as firm_tokens
            FROM firms f
            LEFT JOIN usage_log u ON f.id = u.firm_id
            GROUP BY f.id
            ORDER BY firm_tokens DESC";
    $stmt = $pdo->query($sql);
    $firmStats = $stmt->fetchAll();

    // 3. Global User List
    $sql = "SELECT u.*, f.name as firm_name 
            FROM users u 
            JOIN firms f ON u.firm_id = f.id 
            ORDER BY f.name ASC, u.name ASC";
    $stmt = $pdo->query($sql);
    $allUsers = $stmt->fetchAll();

    // 4. Recent Logs
    $sql = "SELECT u.*, f.name as firm_name, us.name as user_name
            FROM usage_log u
            JOIN firms f ON u.firm_id = f.id
            LEFT JOIN users us ON u.user_id = us.id
            ORDER BY u.created_at DESC
            LIMIT 50";
    $stmt = $pdo->query($sql);
    $logs = $stmt->fetchAll();

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super-Admin | ELK Valuations</title>
<link rel="icon" type="image/webp" href="favicon.webp">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=3.2.1">
<style>
:root {
  --brand-accent: #c5a059;
  --brand-surface: #f8fafc;
  --brand-surface-mid: #ffffff;
  --brand-surface-light: #f1f5f9;
  --text-main: #1e293b;
  --text-muted: #475569;
  --text-faint: #94a3b8;
  --border-subtle: #e2e8f0;
}

body {
    background: var(--brand-surface);
    color: var(--text-main);
}

.main {
    max-width: 1600px !important;
    margin: 0 auto;
    padding: 40px;
}

.stat-card {
    background: var(--brand-surface-mid);
    border: 1px solid var(--border-subtle);
    padding: 32px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.stat-val {
    font-size: 36px;
    font-weight: 700;
    color: var(--brand-accent);
    font-family: 'DM Mono', monospace;
    margin-bottom: 8px;
}
.stat-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-faint);
    font-weight: 600;
}
table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 13px; background: var(--brand-surface-mid); border-radius: 8px; overflow: hidden; border: 1px solid var(--border-subtle); }
th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-subtle); white-space: nowrap; }
th { background: var(--brand-surface-light); color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: 0.1em; }
.firm-row:hover { background: var(--brand-surface-light); }

.modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--brand-surface-mid);
    border: 1px solid var(--brand-accent);
    padding: 40px;
    border-radius: 12px;
    z-index: 100;
    width: 450px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.1);
}
.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 90;
}
.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 32px;
    font-size: 14px;
    border: 1px solid transparent;
}
.alert-info { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }

.pill {
    font-size: 10px;
    text-transform: uppercase;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
}

header {
    background: #0f172a;
    border-bottom: 1px solid rgba(197, 160, 89, 0.2);
}
</style>
</head>
<body>

<header>
  <div class="header-left">
    <span class="header-label" style="color: #fff;">ELK Valuations <span style="color: var(--brand-accent); margin-left: 8px;">Super-Admin</span></span>
  </div>
  <div class="header-right">
    <a href="logout.php" class="btn btn-outline" style="font-size: 10px; color: #fff; border-color: rgba(255,255,255,0.2);">Logout</a>
  </div>
</header>

<div class="app-wrapper">
  <main class="main">
    
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 32px; margin-bottom: 48px;">
        <div class="stat-card">
            <div class="stat-val"><?php echo $totalFirms; ?></div>
            <div class="stat-label">Active Firms</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?php echo $totalValuations; ?></div>
            <div class="stat-label">Total Valuations</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?php echo number_format($totalTokens / 1000, 1); ?>k</div>
            <div class="stat-label">Total Tokens</div>
        </div>
        <div class="stat-card">
            <div class="stat-val">£<?php echo number_format($estCost, 2); ?></div>
            <div class="stat-label">Est. AI Cost (GBP)</div>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <h2 class="section-title" style="margin: 0; color: var(--text-main);">Firm &amp; Usage Metrics</h2>
        <button class="btn btn-primary" onclick="showAddFirm()" style="font-size: 11px; background: var(--brand-accent); border: none;">+ Register New Firm</button>
    </div>

    <div id="addFirmModal" class="modal">
        <h3 style="margin-top: 0; color: var(--brand-accent);">Register New Firm</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create_firm">
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Firm Name</label>
                <input type="text" name="name" required placeholder="e.g. Smith &amp; Co" style="width: 100%; border: 1px solid var(--border-subtle); padding: 10px; border-radius: 4px;">
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Firm Slug (unique)</label>
                <input type="text" name="slug" required placeholder="e.g. smith" style="width: 100%; border: 1px solid var(--border-subtle); padding: 10px; border-radius: 4px;">
            </div>
            <h4 style="color: var(--text-faint); margin: 24px 0 12px; font-size: 11px; text-transform: uppercase;">Initial Admin User</h4>
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Admin Name</label>
                <input type="text" name="user_name" required placeholder="John Smith" style="width: 100%; border: 1px solid var(--border-subtle); padding: 10px; border-radius: 4px;">
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Admin Email</label>
                <input type="email" name="user_email" required placeholder="john@smith.com" style="width: 100%; border: 1px solid var(--border-subtle); padding: 10px; border-radius: 4px;">
            </div>
            <div class="form-group" style="margin-bottom: 24px;">
                <label>Initial Password</label>
                <input type="text" name="user_pass" required value="ELK_<?php echo rand(1000,9999); ?>!" style="width: 100%; border: 1px solid var(--border-subtle); padding: 10px; border-radius: 4px;">
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="flex: 1; background: var(--brand-accent); border: none;">Create Firm</button>
                <button type="button" class="btn btn-outline" onclick="hideAddFirm()" style="flex: 1;">Cancel</button>
            </div>
        </form>
    </div>
    <div id="modalOverlay" class="overlay" onclick="hideAddFirm()"></div>

    <table>
        <thead>
            <tr>
                <th>Firm Name</th>
                <th>Requests</th>
                <th>Total Tokens</th>
                <th>Estimated Cost</th>
                <th>2FA Status</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($firmStats as $f): ?>
            <tr class="firm-row">
                <td style="font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($f['name']); ?></td>
                <td><?php echo number_format($f['total_requests']); ?></td>
                <td style="font-family: 'DM Mono', monospace;"><?php echo number_format($f['firm_tokens'] ?: 0); ?></td>
                <td style="color: var(--brand-accent); font-weight: 600;">£<?php echo number_format(($f['firm_tokens'] / 1000000) * 12, 2); ?></td>
                <td>
                    <?php if ($f['global_2fa_enabled']): ?>
                        <span class="pill" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;">Enabled</span>
                    <?php else: ?>
                        <span class="pill" style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;">Disabled</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right;">
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="toggle_2fa">
                        <input type="hidden" name="firm_id" value="<?php echo $f['id']; ?>">
                        <input type="hidden" name="current_status" value="<?php echo $f['global_2fa_enabled']; ?>">
                        <button type="submit" class="btn btn-outline" style="font-size: 9px; padding: 6px 12px; min-width: 80px;">
                            <?php echo $f['global_2fa_enabled'] ? 'Turn Off' : 'Turn On'; ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="section-title" style="margin-top: 80px; color: var(--text-main);">Global User Management</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Firm</th>
                <th>Role</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($allUsers as $u): ?>
            <tr class="firm-row">
                <td style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($u['name']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><span class="pill" style="background: var(--brand-surface-light); color: var(--brand-accent); border: 1px solid var(--border-subtle);"><?php echo htmlspecialchars($u['firm_name']); ?></span></td>
                <td><span style="text-transform: uppercase; font-size: 10px; font-weight: 700; color: var(--text-muted);"><?php echo htmlspecialchars($u['role']); ?></span></td>
                <td style="text-align: right;">
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <form method="POST" onsubmit="return confirm('Reset password for this user?');">
                            <input type="hidden" name="action" value="reset_pass">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <button type="submit" class="btn btn-outline" style="font-size: 9px; padding: 6px 12px; color: var(--brand-accent); border-color: var(--brand-accent);">Reset Pass</button>
                        </form>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" onsubmit="return confirm('Permanently delete this user?');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <button type="submit" class="btn btn-outline" style="font-size: 9px; padding: 6px 12px; color: #ef4444; border-color: #ef4444;">Delete</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="section-title" style="margin-top: 80px; color: var(--text-main);">Live System Logs</h2>
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Firm Identity</th>
                <th>Active User</th>
                <th>Company</th>
                <th>System Action</th>
                <th>Token Load</th>
                <th>Cost</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $l): ?>
            <tr style="font-size: 11px;">
                <td style="color: var(--text-faint);"><?php echo date('H:i:s d/m/Y', strtotime($l['created_at'])); ?></td>
                <td style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($l['firm_name']); ?></td>
                <td><?php echo htmlspecialchars($l['user_name']); ?></td>
                <td style="color: var(--brand-accent-light);"><?php echo htmlspecialchars($l['client_name'] ?: '—'); ?></td>
                <td><span style="text-transform: uppercase; font-size: 9px; padding: 2px 8px; border: 1px solid var(--border-subtle); border-radius: 4px; color: var(--text-muted);"><?php echo $l['action']; ?></span></td>
                <td style="font-family: 'DM Mono', monospace; font-weight: 600;"><?php echo number_format($l['total_tokens']); ?></td>
                <td style="color: var(--text-muted);">£<?php echo number_format(($l['total_tokens'] / 1000000) * 12, 4); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

  </main>
</div>

<script>
function showAddFirm() {
    document.getElementById('addFirmModal').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
}
function hideAddFirm() {
    document.getElementById('addFirmModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}
</script>

</body>
</html>
