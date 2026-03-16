<?php
/**
 * ELK Valuations - Save API
 * Saves or Updates the valuation data with secure UUID isolation.
 */

session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Security Guard
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised access. Please log in.']);
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

$firm_id = $_SESSION['firm_id'];
$user_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['companyName'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data provided']);
    exit;
}

try {
    $pdo = DB::getInstance();
    
    $uuid = $input['uuid'] ?? null;

    // Security: If updating, verify the firm owns this valuation via UUID
    if ($uuid) {
        $check = $pdo->prepare("SELECT id FROM valuations WHERE uuid = ? AND firm_id = ?");
        $check->execute([$uuid, $firm_id]);
        if (!$check->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access Denied: Valuation ownership mismatch.']);
            exit;
        }
    }

    // Methodology JSON
    $meth_json = json_encode([
        'weighting' => $input['weighting'] ?? [],
        'multiples' => $input['multiples'] ?? [],
        'deduction' => $input['deduction'] ?? 0,
        'deductionDesc' => $input['deductionDesc'] ?? ''
    ]);

    $data = [
        'firm'       => $firm_id,
        'user'       => $user_id,
        'client'     => $input['companyName'],
        'co_num'     => $input['companyNumber'] ?? '',
        'sector'     => $input['sector'] ?? '',
        'year_end'   => $input['yearEnd'] ?? '',
        'years_trad' => (int)($input['yearsTrading'] ?? 0),
        'emp'        => (int)($input['employees'] ?? 0),
        'purpose'    => $input['purpose'] ?? '',
        'rep_date'   => $input['reportDate'] ?? '',
        'bus_desc'   => $input['businessDesc'] ?? '',
        'fin_json'   => json_encode($input['financials'] ?? []),
        'adj_json'   => json_encode($input['adjustments'] ?? []),
        'sh_json'    => json_encode($input['shareholders'] ?? []),
        'meth_json'  => $meth_json,
        'val_mid'    => $input['valuationMid'] ?? 0,
        'notes'      => $input['accountantNotes'] ?? '',
        'ai_nar'     => $input['aiNarrative'] ?? ''
    ];

    if ($uuid) {
        // UPDATE
        $sql = "UPDATE valuations SET 
                    client_name = :client,
                    company_number = :co_num,
                    sector = :sector,
                    year_end = :year_end,
                    years_trading = :years_trad,
                    employees = :emp,
                    purpose = :purpose,
                    report_date = :rep_date,
                    business_desc = :bus_desc,
                    financials_json = :fin_json,
                    adjustments_json = :adj_json,
                    shareholders_json = :sh_json,
                    methodology_json = :meth_json,
                    valuation_mid = :val_mid,
                    accountant_notes = :notes,
                    ai_narrative = :ai_nar
                WHERE uuid = :uuid AND firm_id = :firm";
        
        $data['uuid'] = $uuid;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        
        echo json_encode([
            'success' => true,
            'uuid' => $uuid,
            'message' => 'Valuation updated successfully'
        ]);
    } else {
        // INSERT
        // Auto-generate UUID for new records
        $new_uuid = bin2hex(random_bytes(4)) . '-' . 
                    bin2hex(random_bytes(2)) . '-' . 
                    '4' . substr(bin2hex(random_bytes(2)), 1) . '-' . 
                    bin2hex(random_bytes(2)) . '-' . 
                    bin2hex(random_bytes(6));

        $sql = "INSERT INTO valuations (
                    uuid, firm_id, user_id, client_name, company_number, sector, 
                    year_end, years_trading, employees, purpose, report_date, 
                    business_desc, financials_json, adjustments_json, shareholders_json, 
                    methodology_json, valuation_mid, accountant_notes, ai_narrative
                ) VALUES (
                    :uuid, :firm, :user, :client, :co_num, :sector,
                    :year_end, :years_trad, :emp, :purpose, :rep_date,
                    :bus_desc, :fin_json, :adj_json, :sh_json,
                    :meth_json, :val_mid, :notes, :ai_nar
                )";
        
        $data['uuid'] = $new_uuid;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        echo json_encode([
            'success' => true,
            'uuid' => $new_uuid,
            'message' => 'Valuation saved successfully'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
