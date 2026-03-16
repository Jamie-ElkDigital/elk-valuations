<?php
/**
 * ELK Valuations - High-Fidelity PDF Export & Persistence
 * Generates pixel-perfect PDFs via Puppeteer and snapshots them to GCS.
 */

// Increase memory and timeout for Puppeteer
ini_set('memory_limit', '1024M');
set_time_limit(120);

session_start();
require_once 'db.php';
require_once 'theme-engine.php';

// Bucket Configuration (GCP)
define('GCS_BUCKET_NAME', 'gta-valuations-reports');

$uuid = $_GET['uuid'] ?? null;
if (!$uuid) {
    die("Missing Valuation UUID.");
}

$pdo = DB::getInstance();

// Fetch Valuation with strict UUID
$stmt = $pdo->prepare("SELECT * FROM valuations WHERE uuid = ?");
$stmt->execute([$uuid]);
$v = $stmt->fetch();

if (!$v) {
    die("Valuation not found or access denied.");
}

// Security: If a session exists, ensure the user belongs to the same firm
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    if ($_SESSION['firm_id'] != $v['firm_id'] && $_SESSION['firm_slug'] !== 'elk') {
        die("Unauthorised Access. Firm isolation violation.");
    }
}

$firm_id = $v['firm_id'];
$user_id = $_SESSION['user_id'] ?? $v['user_id'];

try {
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

// Helper Functions
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

/**
 * Get OAuth2 Token from Metadata Server
 */
function get_gcs_token(): string {
    $ch = curl_init('http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Metadata-Flavor: Google'],
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code !== 200) return '';
    $data = json_decode($response, true);
    return $data['access_token'] ?? '';
}

/**
 * Upload PDF to Google Cloud Storage
 */
function upload_to_gcs($local_path, $gcs_name) {
    $token = get_gcs_token();
    if (!$token) return false;

    $url = "https://storage.googleapis.com/upload/storage/v1/b/" . GCS_BUCKET_NAME . "/o?uploadType=media&name=" . urlencode($gcs_name);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => file_get_contents($local_path),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/pdf'
        ],
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code === 200);
}

/**
 * Download PDF from Google Cloud Storage
 */
function download_from_gcs($gcs_name) {
    $token = get_gcs_token();
    if (!$token) return false;

    $url = "https://storage.googleapis.com/download/storage/v1/b/" . GCS_BUCKET_NAME . "/o/" . urlencode($gcs_name) . "?alt=media";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
        ],
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code === 200) ? $response : false;
}

// Check for historical version request
if (isset($_GET['v']) && is_numeric($_GET['v'])) {
    $v_num = (int)$_GET['v'];
    $stmt = $pdo->prepare("SELECT gcs_path FROM valuation_versions WHERE valuation_id = ? AND version_number = ?");
    $stmt->execute([$v['id'], $v_num]);
    $version = $stmt->fetch();
    
    if ($version && $version['gcs_path']) {
        $pdf_content = download_from_gcs($version['gcs_path']);
        if ($pdf_content) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Valuation_Report_' . str_replace(' ', '_', $v['client_name']) . '_v' . $v_num . '.pdf"');
            header('Content-Length: ' . strlen($pdf_content));
            echo $pdf_content;
            exit;
        } else {
            die("Error: Could not retrieve the requested PDF version from storage.");
        }
    } else {
        die("Error: Version not found.");
    }
}

// Generate the HTML for Puppeteer
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Valuation Report - <?php echo htmlspecialchars($v['client_name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <style>
        <?php echo file_get_contents('style.css'); ?>
        body { background: var(--brand-surface) !important; -webkit-print-color-adjust: exact; }
        .main { max-width: 100% !important; padding: 0 !important; }
        .report-container { width: 100%; padding: 40px; }
        .page-header { border-bottom: 2px solid var(--brand-accent); padding-bottom: 20px; }
        .results-hero { margin-top: 40px; }
        .section-title { margin-top: 50px; border-bottom: 1px solid var(--border-subtle); }
        .share-table th { background: var(--brand-surface-mid); }
        .page-break { page-break-before: always; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    </style>
    <?php injectTheme($primary_color, $secondary_color, true); ?>
</head>
<body style="background: var(--brand-surface) !important;">
    <div class="report-container">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <div class="page-eyebrow">Business Intelligence Report</div>
                <h1 class="page-title" style="font-size: 32px;"><?php echo htmlspecialchars($v['client_name']); ?></h1>
                <p class="page-desc">Valuation prepared on <?php echo date('j F Y', strtotime($v['created_at'])); ?> for the purpose of <?php echo htmlspecialchars($v['purpose']); ?>.</p>
            </div>
            <?php if ($logo_url): ?>
                <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Firm Logo" style="max-height: 80px;">
            <?php endif; ?>
        </div>

        <div class="results-hero">
            <div class="valuation-range" style="display: flex; justify-content: space-around; align-items: flex-end; gap: 20px; text-align: center;">
                <div class="val-point">
                    <div class="label" style="font-size: 10px; text-transform: uppercase; color: var(--text-faint);">Conservative</div>
                    <div class="amount" style="font-family: 'DM Mono', monospace; font-size: 32px; color: var(--text-main);"><?php 
                        $wAvg = (float)$v['valuation_mid'] / (float)$multiples['mid'];
                        echo fmtShort($wAvg * (float)$multiples['low']); 
                    ?></div>
                    <div class="sublabel" style="font-size: 11px; color: var(--text-muted);"><?php echo $multiples['low']; ?>× EBITDA</div>
                </div>
                <div class="val-point mid">
                    <div class="label" style="font-size: 10px; text-transform: uppercase; color: var(--text-faint);">Mid-point Equity Value</div>
                    <div class="amount" style="font-family: 'DM Mono', monospace; font-size: 48px; color: var(--brand-accent-light);"><?php echo fmtShort($v['valuation_mid']); ?></div>
                    <div class="sublabel" style="font-size: 11px; color: var(--text-muted);"><?php echo $multiples['mid']; ?>× EBITDA (Adjusted)</div>
                </div>
                <div class="val-point">
                    <div class="label" style="font-size: 10px; text-transform: uppercase; color: var(--text-faint);">Optimistic</div>
                    <div class="amount" style="font-family: 'DM Mono', monospace; font-size: 32px; color: var(--text-main);"><?php echo fmtShort($wAvg * (float)$multiples['high']); ?></div>
                    <div class="sublabel" style="font-size: 11px; color: var(--text-muted);"><?php echo $multiples['high']; ?>× EBITDA</div>
                </div>
            </div>
        </div>

        <div class="results-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 40px;">
            <div class="result-card" style="background: var(--brand-surface-mid); padding: 20px; border-radius: 4px; border: 1px solid var(--border-subtle);">
                <div class="card-label" style="font-size: 10px; text-transform: uppercase; color: var(--text-faint);">Weighted Average EBITDA</div>
                <div class="card-value" style="font-size: 24px; color: var(--brand-accent-light); font-family: 'DM Mono', monospace;"><?php echo fmt($wAvg); ?></div>
            </div>
            <div class="result-card" style="background: var(--brand-surface-mid); padding: 20px; border-radius: 4px; border: 1px solid var(--border-subtle);">
                <div class="card-label" style="font-size: 10px; text-transform: uppercase; color: var(--text-faint);">Most Recent Turnover</div>
                <div class="card-value" style="font-size: 24px; color: var(--brand-accent-light); font-family: 'DM Mono', monospace;"><?php echo fmt($financials['turnover'][2] ?? 0); ?></div>
            </div>
            <div class="result-card" style="background: var(--brand-surface-mid); padding: 20px; border-radius: 4px; border: 1px solid var(--border-subtle);">
                <div class="card-label" style="font-size: 10px; text-transform: uppercase; color: var(--text-faint);">Net Assets</div>
                <div class="card-value" style="font-size: 24px; color: var(--brand-accent-light); font-family: 'DM Mono', monospace;"><?php echo fmt($financials['balanceSheet']['netAssets'] ?? 0); ?></div>
            </div>
            <div class="result-card" style="background: var(--brand-surface-mid); padding: 20px; border-radius: 4px; border: 1px solid var(--border-subtle);">
                <div class="card-label" style="font-size: 10px; text-transform: uppercase; color: var(--text-faint);">Cash at Bank</div>
                <div class="card-value" style="font-size: 24px; color: var(--brand-accent-light); font-family: 'DM Mono', monospace;"><?php echo fmt($financials['balanceSheet']['cash'] ?? 0); ?></div>
            </div>
        </div>

        <div class="section-title">Professional Commentary</div>
        <div style="background: var(--brand-surface-mid); border: 1px solid var(--border-subtle); padding: 32px; border-radius: 4px; line-height: 1.8; color: var(--text-muted); font-size: 15px; white-space: pre-wrap; margin-top: 20px;">
<?php echo htmlspecialchars($v['ai_narrative'] ?: $v['accountant_notes']); ?>
        </div>

        <div class="page-break"></div>

        <div class="section-title">Shareholder Allocation</div>
        <table class="share-table" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="text-align: left; font-size: 10px; text-transform: uppercase; color: var(--text-faint); border-bottom: 1px solid var(--border-subtle);">
                    <th style="padding: 12px;">Shareholder</th>
                    <th style="padding: 12px;">Class</th>
                    <th style="padding: 12px;">Shares</th>
                    <th style="padding: 12px;">Estimated Value (Mid)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalShares = 0;
                foreach($shareholders as $sh) $totalShares += (int)$sh['shares'];
                foreach ($shareholders as $sh): 
                    $shareVal = $totalShares > 0 ? ((int)$sh['shares'] / $totalShares) * (float)$v['valuation_mid'] : 0;
                ?>
                    <tr style="border-bottom: 1px solid var(--border-subtle); font-size: 14px; color: var(--text-main);">
                        <td style="padding: 12px;"><?php echo htmlspecialchars($sh['name']); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($sh['class']); ?></td>
                        <td style="padding: 12px;"><?php echo number_format((int)$sh['shares']); ?></td>
                        <td style="padding: 12px; color: var(--brand-accent-light); font-weight: 600;"><?php echo fmt($shareVal); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="report-disclaimer" style="margin-top: 60px; padding: 24px; background: var(--brand-surface-mid); border: 1px solid var(--border-subtle); font-size: 11px; line-height: 1.6; color: var(--text-faint); border-radius: 4px;">
            <strong>Data Verification:</strong> This report was prepared by <?php echo htmlspecialchars($firm['name']); ?> using financial data extracted from the statutory accounts of <?php echo htmlspecialchars($v['client_name']); ?> (Co. No. <?php echo htmlspecialchars($v['company_number']); ?>). 
            While advanced analytical tools are employed to ensure precision, this document is provided for advisory purposes and should be reviewed in conjunction with full professional consultation.
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Run Puppeteer
$tempHtml = tempnam(sys_get_temp_dir(), 'pdf_html_');
$tempPdf  = tempnam(sys_get_temp_dir(), 'pdf_out_') . '.pdf';
file_put_contents($tempHtml, $html);

// Pipe HTML into node script
$nodePath = exec('which node') ?: 'node';
$cmd = "$nodePath generate-pdf.js " . escapeshellarg($tempPdf);

$descriptorspec = [
   0 => ["pipe", "r"], // stdin
   1 => ["pipe", "w"], // stdout
   2 => ["pipe", "w"]  // stderr
];

error_log("Starting PDF generation for valuation $uuid");
$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    fwrite($pipes[0], $html);
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $return_value = proc_close($process);

    if ($return_value === 0 && file_exists($tempPdf) && filesize($tempPdf) > 0) {
        error_log("PDF generated successfully for $uuid");

        // ── SNAPSHOT PERSISTENCE ──
        $timestamp = date('Y-m-d_His');
        $gcs_name = "reports/{$firm_id}/{$uuid}/Valuation_{$timestamp}.pdf";
        
        if (upload_to_gcs($tempPdf, $gcs_name)) {
            // Log Version
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(version_number), 0) FROM valuation_versions WHERE valuation_id = ?");
            $stmt->execute([$v['id']]);
            $next_version = (int)$stmt->fetchColumn() + 1;

            $stmt = $pdo->prepare("INSERT INTO valuation_versions (valuation_id, version_number, gcs_path, valuation_mid, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$v['id'], $next_version, $gcs_name, $v['valuation_mid'], $user_id]);
            error_log("GCS Snapshot created: $gcs_name");
        } else {
            error_log("GCS Upload failed. Serving temporary file only.");
        }

        // Stream PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Valuation_Report_' . str_replace(' ', '_', $v['client_name']) . '.pdf"');
        header('Content-Length: ' . filesize($tempPdf));
        readfile($tempPdf);
        
        // Cleanup
        @unlink($tempHtml);
        @unlink($tempPdf);
        exit;
    } else {
        error_log("PDF Generation Failed. Return: $return_value. Stderr: $stderr");
        http_response_code(500);
        die("PDF Generation Failed. Details logged.");
    }
} else {
    error_log("Failed to start PDF process resource.");
    http_response_code(500);
    die("Failed to start PDF engine.");
}
