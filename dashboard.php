<?php
/**
 * ELK Valuations - Relational Dashboard
 * Grouped by Company with nested report history.
 */

session_start();
require_once 'db.php';
require_once 'theme-engine.php';

if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

$firm_id = $_SESSION['firm_id'];
$pdo = DB::getInstance();

// Fetch Firm Branding
$stmt = $pdo->prepare("SELECT * FROM firms WHERE id = ?");
$stmt->execute([$firm_id]);
$firm = $stmt->fetch();

// Initial Grouped Fetch
$sql = "SELECT 
            v.client_name, 
            v.company_number, 
            v.sector, 
            v.uuid as latest_uuid,
            v.valuation_mid as latest_value,
            v.created_at as latest_date,
            (SELECT COUNT(*) FROM valuation_versions vv WHERE vv.valuation_id = v.id) as version_count
        FROM valuations v
        WHERE v.firm_id = ? 
        AND v.id IN (SELECT MAX(id) FROM valuations WHERE firm_id = ? GROUP BY client_name, company_number)
        ORDER BY v.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$firm_id, $firm_id]);
$companies = $stmt->fetchAll();

$primary_color = $firm['primary_color'] ?? '#c5a059';
$secondary_color = $firm['secondary_color'] ?? '#050505';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | ELK Valuations</title>
<link rel="icon" type="image/webp" href="favicon.webp">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Barlow:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=3.2.1">
<?php injectTheme($primary_color, $secondary_color); ?>
<style>
.company-list { margin-top: 32px; background: var(--brand-surface-mid); border: 1px solid var(--border-subtle); border-radius: 8px; overflow: hidden; }
.company-row { display: grid; grid-template-columns: 1fr 180px 180px 140px 60px; padding: 20px 24px; border-bottom: 1px solid var(--border-subtle); cursor: pointer; transition: background 0.2s; align-items: center; }
.company-row:hover { background: var(--bg-dim); }
.company-row.header { background: var(--brand-surface-light); cursor: default; font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-faint); font-weight: 700; padding: 12px 24px; }
.company-row.header:hover { background: var(--brand-surface-light); }

.co-name { font-family: 'Barlow', sans-serif; font-size: 16px; font-weight: 600; color: var(--text-main); }
.co-meta { font-size: 11px; color: var(--text-faint); margin-top: 2px; display: block; transition: transform 0.3s ease; }
.co-value { font-family: 'DM Mono', monospace; font-size: 15px; font-weight: 600; color: var(--brand-accent-light); }
.co-date { font-size: 12px; color: var(--text-muted); }
.co-count { font-size: 10px; background: var(--brand-accent-dim); color: var(--brand-accent-light); padding: 2px 8px; border-radius: 10px; text-align: center; }

.history-panel { display: none; background: var(--bg-dim); padding: 0 24px; border-bottom: 1px solid var(--border-subtle); }
.history-panel.active { display: block; animation: slideDown 0.3s ease; }

@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

.version-row { display: grid; grid-template-columns: 1fr 180px 180px 140px 60px; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 12px; color: var(--text-muted); align-items: center; }
.version-row:last-child { border-bottom: none; }
.v-label { font-weight: 600; color: var(--text-faint); }
.v-link { color: var(--brand-accent); text-decoration: none; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.v-link:hover { text-decoration: underline; }

.btn-new { background: var(--brand-accent); color: var(--brand-surface); padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 13px; }

/* Search Bar Styling */
.search-input {
    width: 100%;
    background: var(--brand-surface-mid);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius);
    padding: 12px 16px;
    color: var(--text-main);
    font-family: inherit;
    font-size: 14px;
    transition: border-color 0.2s;
}
.search-input:focus {
    border-color: var(--brand-accent);
    outline: none;
}
.search-bar-container { position: relative; width: 100%; max-width: 400px; }
.search-bar-container::before { content: '🔍'; position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 14px; opacity: 0.5; pointer-events: none; }
.search-bar-container .search-input { padding-left: 40px; }
</style>
</head>
<body>

<header>
  <div class="header-left">
    <span class="header-label"><?php echo htmlspecialchars($firm['name']); ?> Intelligence Portal</span>
  </div>
  <div class="header-right" style="display: flex; align-items: center; gap: 20px;">
    <span style="font-size: 11px; color: var(--text-faint);"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
    <a href="logout.php" class="btn btn-outline" style="font-size: 9px; padding: 4px 8px;">Logout</a>
  </div>
</header>

<div class="app-wrapper" style="grid-template-columns: 240px 1fr;">
  <nav class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-section-label">Main</div>
      <a href="dashboard.php" class="nav-item active">📊 Dashboard</a>
      <a href="index.php" class="nav-item">➕ New Valuation</a>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-label">Admin</div>
      <a href="settings.php" class="nav-item">⚙️ Firm Settings</a>
    </div>
    <div class="sidebar-logo-container">
      <img src="<?php echo $firm['logo_url'] ?: 'elk-design-logo.png'; ?>" class="sidebar-logo">
    </div>
  </nav>

  <main class="main" style="max-width: 1400px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h1 class="page-title">Valuation Vault</h1>
        <p class="page-desc">Relational repository of all client valuations and report history.</p>
      </div>
      <a href="index.php" class="btn-new">+ Start New Valuation</a>
    </div>

    <div class="search-bar-container" style="margin-top: 32px;">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by client name..." style="max-width: 400px;">
    </div>

    <div class="company-list" id="companyGrid">
      <div class="company-row header">
        <div>Client Identity</div>
        <div>Latest Value</div>
        <div>Last Updated</div>
        <div>Versions</div>
        <div></div>
      </div>

      <?php foreach ($companies as $c): ?>
        <div class="company-group" data-name="<?php echo htmlspecialchars($c['client_name']); ?>">
            <div class="company-row" onclick="toggleHistory('<?php echo $c['latest_uuid']; ?>', this)">
                <div>
                    <div class="co-name"><?php echo htmlspecialchars($c['client_name']); ?></div>
                    <div class="co-meta"><?php echo htmlspecialchars($c['company_number'] ?: $c['sector']); ?></div>
                </div>
                <div class="co-value">£<?php echo number_format($c['latest_value'] / 1000, 0); ?>k</div>
                <div class="co-date"><?php echo date('j M Y', strtotime($c['latest_date'])); ?></div>
                <div><span class="co-count"><?php echo $c['version_count']; ?> Files</span></div>
                <div style="text-align: right; color: var(--text-faint);">▼</div>
            </div>
            <div class="history-panel" id="history-<?php echo $c['latest_uuid']; ?>">
                <!-- Versions AJAX loaded here -->
                <div style="padding: 20px; text-align: center; color: var(--text-faint); font-size: 11px;">Loading report history...</div>
            </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</div>

<?php include 'footer.php'; ?>

<script>
async function toggleHistory(uuid, row) {
    const panel = document.getElementById(`history-${uuid}`);
    const arrow = row.lastElementChild;
    
    if (panel.classList.contains('active')) {
        panel.classList.remove('active');
        arrow.style.transform = 'rotate(0deg)';
        return;
    }

    // Close others
    document.querySelectorAll('.history-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.company-row').forEach(r => r.lastElementChild.style.transform = 'rotate(0deg)');

    panel.classList.add('active');
    arrow.style.transform = 'rotate(180deg)';

    // Load data
    try {
        const res = await fetch(`get-history.php?uuid=${uuid}`);
        const data = await res.json();
        
        if (data.success) {
            let html = `<div style="padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 10px; text-transform: uppercase; color: var(--brand-accent);">Archive Timeline</span>
                            <a href="index.php?edit=${uuid}" class="v-link" style="color: #fff; background: var(--brand-accent); padding: 4px 8px; border-radius: 2px;">Open Latest Model</a>
                        </div>`;
            
            data.versions.forEach(v => {
                html += `
                    <div class="version-row">
                        <div class="v-label">Version ${v.version_number}</div>
                        <div class="co-value">£${(v.valuation_mid / 1000).toFixed(0)}k</div>
                        <div class="co-date">${v.date_fmt}</div>
                        <div><a href="export-pdf.php?uuid=${uuid}&v=${v.version_number}" target="_blank" class="v-link">Download PDF</a></div>
                        <div style="text-align: right;"><a href="view-valuation.php?uuid=${uuid}" class="v-link" style="opacity: 0.5;">View</a></div>
                    </div>
                `;
            });
            
            if (data.versions.length === 0) {
                html = '<div style="padding: 20px; text-align: center; color: var(--text-faint); font-size: 11px;">No archived snapshots found. Generate a PDF to create your first version.</div>';
            }
            
            panel.innerHTML = html;
        }
    } catch (err) {
        panel.innerHTML = '<div style="padding: 20px; color: var(--error);">Failed to load history.</div>';
    }
}

// Search Logic
document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.company-group').forEach(group => {
        const name = group.getAttribute('data-name').toLowerCase();
        group.style.display = name.includes(q) ? 'block' : 'none';
    });
});
</script>
</body>
</html>
