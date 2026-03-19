<?php
/**
 * ELK Valuations - Companies House Data Proxy (v2)
 * Fetches filing history, accounts PDFs, and Corporate Intelligence.
 */

session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Security Guard
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

// CSRF Verification
$headers = getallheaders();
$csrf_token = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
if (!$csrf_token || $csrf_token !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF validation failed.']);
    exit;
}

$apiKey = getenv('CH_API_KEY');
if (!$apiKey) {
    echo json_encode(['error' => 'Companies House API Key missing.']);
    exit;
}

$companyNumber = $_GET['number'] ?? '';
if (!$companyNumber) {
    echo json_encode(['error' => 'Company number required.']);
    exit;
}

$firm_id = $_SESSION['firm_id'];
$companyNumber = str_pad($companyNumber, 8, '0', STR_PAD_LEFT);

function chRequest($url, $apiKey) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ":");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) return null;
    return json_decode($response, true);
}

try {
    $pdo = DB::getInstance();

    // 1. Get Company Profile (Incorporation date, status, etc.)
    $profileUrl = "https://api.companieshouse.gov.uk/company/{$companyNumber}";
    $profile = chRequest($profileUrl, $apiKey);

    if (!$profile) {
        echo json_encode(['error' => 'Company not found.']);
        exit;
    }

    // 2. Get Persons with Significant Control (PSC) - CRITICAL for share structure
    $pscUrl = "https://api.companieshouse.gov.uk/company/{$companyNumber}/persons-with-significant-control";
    $pscs = chRequest($pscUrl, $apiKey);

    // 3. Get Filing History
    $historyUrl = "https://api.companieshouse.gov.uk/company/{$companyNumber}/filing-history?items_per_page=100";
    $history = chRequest($historyUrl, $apiKey);

    $accounts = [];
    $intelDocs = [];
    $shareChanges = [];
    $directorChanges = [];
    $events = [];

    if ($history && isset($history['items'])) {
        foreach ($history['items'] as $item) {
            $type = $item['type'] ?? '';
            $desc = $item['description'] ?? '';
            $date = $item['date'] ?? '';
            $category = $item['category'] ?? '';

            // Extract Any Document with a PDF
            $metadataUrl = $item['links']['document_metadata'] ?? '';
            if ($metadataUrl) {
                // Ensure we are pointing to the document-api host, not the main api host
                $pdfUrl = str_replace('https://api.companieshouse.gov.uk', 'https://document-api.companieshouse.gov.uk', $metadataUrl);
                $pdfUrl = str_replace('https://frontend-sdk.companieshouse.gov.uk', 'https://document-api.companieshouse.gov.uk', $pdfUrl);
                
                if (substr($pdfUrl, -8) !== '/content') $pdfUrl .= '/content';

                $doc = [
                    'date' => $date,
                    'pdf_url' => $pdfUrl,
                    'category' => $category,
                    'type' => $type
                ];

                if ($category === 'accounts') {
                    // Skip micro-entity accounts entirely to reduce payload and noise
                    if (stripos($desc, 'micro-entity') !== false) {
                        continue;
                    }

                    // Friendly labels for UI
                    $label = 'Accounts';
                    if (stripos($desc, 'total exemption') !== false) $label = 'Total Exemption Accounts';
                    elseif (stripos($desc, 'group') !== false) $label = 'Group Accounts';
                    elseif (stripos($desc, 'full') !== false) $label = 'Full Accounts';
                    elseif (stripos($desc, 'filleted') !== false) $label = 'Filleted Accounts';

                    $doc['label'] = $label;
                    $doc['is_account'] = true;
                    if (count($accounts) < 15) $accounts[] = $doc;
                } elseif (in_array($category, ['confirmation-statement', 'incorporation', 'officers', 'shares', 'capital'])) {
                    // Give friendly labels to intelligence docs and ADD them to the UI list
                    $label = 'Company Document';
                    if ($category === 'confirmation-statement' || $type === 'CS01') $label = 'Confirmation Statement';
                    elseif ($category === 'incorporation' || $type === 'NEWINC') $label = 'Incorporation Document';
                    elseif ($category === 'shares' || $type === 'SH01') $label = 'Share Allotment';
                    elseif ($category === 'capital') $label = 'Statement of Capital';
                    elseif ($category === 'officers') $label = 'Officer Change';

                    $doc['label'] = $label;
                    $doc['is_account'] = false; // Flag to differentiate for auto-checking logic
                    
                    // Add to both arrays so UI sees them, and legacy logic still works if needed
                    if (count($accounts) < 15) $accounts[] = $doc;
                    if (count($intelDocs) < 20) $intelDocs[] = $doc;
                }            }

            // Extract Share Allotments (SH01)
            if ($type === 'SH01') {
                $shareChanges[] = ['date' => $date, 'desc' => 'Return of allotment of shares'];
                $events[] = ['date' => $date, 'type' => 'Share Allotment', 'label' => 'Shares Allotted'];
            }

            // Extract Director Changes
            if (in_array($type, ['AP01', 'TM01', 'CH01'])) {
                $label = ($type === 'AP01') ? 'Director Appointed' : (($type === 'TM01') ? 'Director Terminated' : 'Director Details Changed');
                $directorChanges[] = ['date' => $date, 'type' => $label];
                $events[] = ['date' => $date, 'type' => 'Management', 'label' => $label];
            }
            
            // Extract Name Changes
            if ($type === 'NM01' || stripos($desc, 'name changed') !== false) {
                $events[] = ['date' => $date, 'type' => 'Legal', 'label' => 'Company Name Changed'];
            }
        }
    }

    // 3. Persist to company_profiles
    $intelligence = [
        'events' => array_slice($events, 0, 10), // Store last 10 relevant events
        'status' => $profile['company_status'] ?? 'unknown',
        'type' => $profile['type'] ?? 'unknown'
    ];

    $sql = "INSERT INTO company_profiles (company_number, firm_id, company_name, incorporation_date, sic_codes, share_change_count, director_change_count, intelligence_json)
            VALUES (:cn, :firm, :name, :inc, :sic, :sh, :dir, :json)
            ON DUPLICATE KEY UPDATE 
                company_name = VALUES(company_name),
                share_change_count = VALUES(share_change_count),
                director_change_count = VALUES(director_change_count),
                intelligence_json = VALUES(intelligence_json)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'cn'   => $companyNumber,
        'firm' => $firm_id,
        'name' => $profile['company_name'] ?? 'Unknown',
        'inc'  => $profile['date_of_creation'] ?? null,
        'sic'  => implode(', ', $profile['sic_codes'] ?? []),
        'sh'   => count($shareChanges),
        'dir'  => count($directorChanges),
        'json' => json_encode($intelligence)
    ]);

    // 4. Return Data
    echo json_encode([
        'success' => true,
        'profile' => [
            'name' => $profile['company_name'] ?? 'Unknown',
            'incorporated' => $profile['date_of_creation'] ?? 'Unknown',
            'sic' => $profile['sic_codes'] ?? [],
            'share_changes' => count($shareChanges),
            'director_changes' => count($directorChanges),
            'history_summary' => $intelligence['events']
        ],
        'accounts' => $accounts,
        'intel_docs' => $intelDocs,
        'pscs' => $pscs['items'] ?? []
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
