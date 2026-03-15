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
    $sql = "SELECT f.name, f.slug, COUNT(u.id) as total_requests, SUM(u.total_tokens) as firm_tokens
            FROM firms f
            LEFT JOIN usage_log u ON f.id = u.firm_id
            GROUP BY f.id
            ORDER BY firm_tokens DESC";
    $stmt = $pdo->query($sql);
    $firmStats = $stmt->fetchAll();

    // 3. Recent Logs
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
<link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=3.2.1">
<style>
:root {
  --brand-accent: #00ffcc;
  --brand-surface: #0a0a0a;
  --brand-surface-mid: #151515;
  --brand-surface-light: #202020;
}
.stat-card {
    background: var(--brand-surface-mid);
    border: 1px solid var(--border-subtle);
    padding: 24px;
    border-radius: 8px;
    text-align: center;
}
.stat-val {
    font-size: 32px;
    font-weight: 700;
    color: var(--brand-accent);
    font-family: 'DM Mono', monospace;
    margin-bottom: 4px;
}
.stat-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-faint);
}
table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 13px; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-subtle); }
th { color: var(--text-faint); font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: 0.1em; }
.firm-row:hover { background: rgba(0,255,204,0.02); }

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
.alert {
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 24px;
    font-size: 13px;
}
.alert-info { background: rgba(0,255,204,0.1); color: var(--brand-accent); border: 1px solid var(--brand-accent); }
</style>
</head>
<body>

<header>
  <div class="header-left">
    <span class="header-label">ELK Super-Admin Dashboard</span>
  </div>
  <div class="header-right">
    <a href="logout.php" class="btn btn-outline" style="font-size: 10px;">Logout</a>
  </div>
</header>

<div class="app-wrapper">
  <main class="main" style="max-width: 1200px; margin: 0 auto; padding: 40px;">
    
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 40px;">
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

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 class="section-title" style="margin: 0;">Firm &amp; User Management</h2>
        <button class="btn btn-primary" onclick="showAddFirm()" style="font-size: 11px;">+ Create New Firm / User</button>
    </div>

    <div id="addFirmModal" class="modal">
        <h3 style="margin-top: 0; color: var(--brand-accent);">Register New Firm</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create_firm">
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Firm Name</label>
                <input type="text" name="name" required placeholder="e.g. Smith &amp; Co">
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Firm Slug (unique)</label>
                <input type="text" name="slug" required placeholder="e.g. smith">
            </div>
            <h4 style="color: var(--text-faint); margin: 24px 0 12px; font-size: 11px; text-transform: uppercase;">Initial Admin User</h4>
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Admin Name</label>
                <input type="text" name="user_name" required placeholder="John Smith">
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Admin Email</label>
                <input type="email" name="user_email" required placeholder="john@smith.com">
            </div>
            <div class="form-group" style="margin-bottom: 24px;">
                <label>Initial Password</label>
                <input type="text" name="user_pass" required value="ELK_<?php echo rand(1000,9999); ?>!">
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Create Firm</button>
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
                <th>Avg. Cost/Val</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($firmStats as $f): ?>
            <tr class="firm-row">
                <td style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($f['name']); ?></td>
                <td><?php echo $f['total_requests']; ?></td>
                <td style="font-family: 'DM Mono', monospace;"><?php echo number_format($f['firm_tokens'] ?: 0); ?></td>
                <td>£<?php echo number_format(($f['firm_tokens'] / 1000000) * 12, 4); ?></td>
                <td><span class="pill" style="background: rgba(0,255,204,0.1); color: var(--brand-accent);">Active</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="section-title" style="margin-top: 60px;">Live Usage Feed</h2>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>Firm</th>
                <th>User</th>
                <th>Action</th>
                <th>Tokens</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $l): ?>
            <tr style="font-size: 11px;">
                <td style="color: var(--text-faint);"><?php echo date('H:i:s d/m', strtotime($l['created_at'])); ?></td>
                <td style="color: var(--brand-accent);"><?php echo htmlspecialchars($l['firm_name']); ?></td>
                <td><?php echo htmlspecialchars($l['user_name']); ?></td>
                <td><span style="text-transform: uppercase; font-size: 9px; padding: 2px 6px; border: 1px solid var(--border-subtle); border-radius: 4px;"><?php echo $l['action']; ?></span></td>
                <td style="font-family: 'DM Mono', monospace;"><?php echo number_format($l['total_tokens']); ?></td>
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
