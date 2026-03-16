<?php
session_start();
require_once 'db.php';
require_once 'theme-engine.php';

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

    // Fetch Initial Valuations (Date Desc)
    $stmt = $pdo->prepare("SELECT * FROM valuations WHERE firm_id = ? ORDER BY created_at DESC");
    $stmt->execute([$firm_id]);
    $valuations = $stmt->fetchAll();
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

$primary_color = $firm['primary_color'] ?? '#c5a059';
$secondary_color = $firm['secondary_color'] ?? '#050505';
$logo_url = $firm['logo_url'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | <?php echo htmlspecialchars($firm_name); ?></title>
<link rel="icon" type="image/webp" href="favicon.webp">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=3.2.1">
<?php injectTheme($primary_color, $secondary_color); ?>
<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
    margin-top: 32px;
}

.valuation-card {
    background: var(--brand-surface-mid);
    border: 1px solid var(--border-subtle);
    padding: 24px;
    border-radius: var(--radius-lg);
    transition: transform 0.2s, border-color 0.2s, background 0.2s;
    cursor: pointer;
    text-decoration: none;
    display: block;
    position: relative;
}

.valuation-card:hover {
    transform: translateY(-4px);
    border-color: var(--brand-accent-border);
    background: var(--bg-dim);
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
    margin-bottom: 24px;
    line-height: 1.6;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding-top: 16px;
    border-top: 1px solid var(--border-subtle);
}

.card-value-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--text-faint);
    margin-bottom: 4px;
}

.card-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--brand-accent-light);
    font-family: 'DM Mono', monospace;
}

.search-bar-container {
    margin-top: 24px;
    display: flex;
    gap: 16px;
    align-items: center;
}

.search-input-wrapper {
    position: relative;
    flex: 1;
    max-width: 500px;
}

.search-input {
    width: 100%;
    background: var(--brand-surface-mid);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius);
    padding: 12px 16px 12px 40px;
    color: var(--text-main);
    font-family: inherit;
    font-size: 14px;
    transition: border-color 0.2s;
}

.search-input:focus {
    border-color: var(--brand-accent);
    outline: none;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-faint);
    font-size: 14px;
}

.btn-new {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--brand-accent);
    color: var(--brand-surface);
    padding: 12px 24px;
    border-radius: var(--radius);
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    transition: background 0.2s;
}
.btn-new:hover { background: var(--brand-accent-hover); }

#noResults {
    grid-column: 1/-1;
    text-align: center;
    padding: 80px 40px;
    background: var(--bg-dim);
    border: 1px dashed var(--border-subtle);
    border-radius: 4px;
    display: none;
}
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
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <h1 class="page-title">Valuation Pipeline</h1>
        <p class="page-desc">Centralised intelligence for <?php echo htmlspecialchars($firm_name); ?> client portfolios.</p>
      </div>
      <a href="index.php" class="btn-new">Create New Valuation</a>
    </div>

    <div class="search-bar-container">
        <div class="search-input-wrapper">
            <span class="search-icon">🔍</span>
            <input type="text" id="searchInput" class="search-input" placeholder="Search valuations by company name..." autocomplete="off">
        </div>
    </div>

    <div class="dashboard-grid" id="valuationGrid">
      <?php foreach ($valuations as $v): ?>
        <a href="view-valuation.php?uuid=<?php echo $v['uuid']; ?>" class="valuation-card">
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
      
      <div id="noResults">
          <div style="font-size: 32px; margin-bottom: 16px;">📂</div>
          <h3 style="color: var(--text-main); margin-bottom: 8px;">No matching valuations</h3>
          <p style="color: var(--text-faint); font-size: 13px; margin-bottom: 24px;">Refine your search or create a new valuation to get started.</p>
          <a href="index.php" class="btn btn-outline">Start New Valuation</a>
      </div>
    </div>
  </main>
</div>

<?php include 'footer.php'; ?>

<script>
const searchInput = document.getElementById('searchInput');
const valuationGrid = document.getElementById('valuationGrid');
const noResults = document.getElementById('noResults');
let searchTimeout;

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    // Minimal debounce
    searchTimeout = setTimeout(() => {
        performSearch(query);
    }, 200);
});

async function performSearch(query) {
    try {
        const response = await fetch(`search-valuations.php?q=${encodeURIComponent(query)}`);
        const result = await response.json();

        if (result.success) {
            updateGrid(result.valuations);
        } else {
            console.error('Search failed:', result.error);
        }
    } catch (err) {
        console.error('Search request error:', err);
    }
}

function updateGrid(valuations) {
    // Clear current cards but keep the noResults div
    const currentCards = valuationGrid.querySelectorAll('.valuation-card');
    currentCards.forEach(card => card.remove());

    if (valuations.length === 0) {
        noResults.style.display = 'block';
    } else {
        noResults.style.display = 'none';
        
        valuations.forEach(v => {
            const card = document.createElement('a');
            card.href = `view-valuation.php?uuid=${v.uuid}`;
            card.className = 'valuation-card';
            card.innerHTML = `
                <div class="card-title">${v.client_name}</div>
                <div class="card-meta">
                    Sector: ${v.sector}<br>
                    Year End: ${v.year_end}<br>
                    Analyst: ${v.user_name}
                </div>
                <div class="card-footer">
                    <div>
                        <div class="card-value-label">Mid-Point Valuation</div>
                        <div class="card-value">${v.valuation_mid_fmt}</div>
                    </div>
                    <div style="font-size: 11px; color: var(--text-faint);">${v.date_fmt}</div>
                </div>
            `;
            valuationGrid.appendChild(card);
        });
    }
}
</script>
</body>
</html>
