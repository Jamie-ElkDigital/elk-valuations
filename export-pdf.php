<?php
/**
 * ELK Valuations - High-Fidelity PDF Export
 * Re-uses branding and data logic to generate a pixel-perfect PDF via Puppeteer.
 */

// Increase memory and timeout for Puppeteer
ini_set('memory_limit', '1024M');
set_time_limit(120);

session_start();
require_once 'db.php';
require_once 'theme-engine.php';

// Authentication Guard
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    http_response_code(401);
    die("Unauthorised Access.");
}

$uuid = $_GET['uuid'] ?? null;
if (!$uuid) {
    die("Missing Valuation UUID.");
}

$firm_id = $_SESSION['firm_id'];

try {
    $pdo = DB::getInstance();
    
    // Fetch Valuation with strict firm isolation and UUID
    $stmt = $pdo->prepare("SELECT * FROM valuations WHERE uuid = ? AND firm_id = ?");
    $stmt->execute([$uuid, $firm_id]);
    $v = $stmt->fetch();

    if (!$v) {
        die("Valuation not found or access denied.");
    }

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
        
        /* PDF Overrides */
        body { background: var(--brand-surface) !important; -webkit-print-color-adjust: exact; }
        .main { max-width: 100% !important; padding: 0 !important; }
        .report-container { width: 100%; padding: 40px; }
        .page-header { border-bottom: 2px solid var(--brand-accent); padding-bottom: 20px; }
        .results-hero { margin-top: 40px; }
        .section-title { margin-top: 50px; border-bottom: 1px solid var(--border-subtle); }
        .share-table th { background: var(--brand-surface-mid); }
        
        /* Force page breaks */
        .page-break { page-break-before: always; }
        
        /* Ensure background colors render */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    </style>
    <?php injectTheme($primary_color, $secondary_color); ?>
</head>
<body style="background: var(--brand-surface);">
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

        <div class="report-disclaimer" style="margin-top: 60px; padding: 24px; background: var(--brand-surface-mid); border: 1px solid var(--border-subtle); font-size: 12px; color: var(--text-faint); border-radius: 4px;">
            <strong>Report Integrity:</strong> This valuation has been generated via the ELK Valuations AI Extraction Engine for <?php echo htmlspecialchars($firm['name']); ?>. 
            The underlying data was sourced from statutory accounts for <?php echo htmlspecialchars($v['client_name']); ?> (Co. No. <?php echo htmlspecialchars($v['company_number']); ?>).
            This document is for advisory purposes only.
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
        error_log("PDF generated successfully: $tempPdf (" . filesize($tempPdf) . " bytes)");
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
        die("PDF Generation Failed. Please contact support. Details logged.");
    }
} else {
    error_log("Failed to start PDF process resource.");
    http_response_code(500);
    die("Failed to start PDF engine.");
}
