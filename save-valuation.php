<?php
/**
 * ELK Valuations - Save API
 * Saves the valuation data to Cloud SQL using organized columns.
 */

require_once 'db.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['companyName'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data provided']);
    exit;
}

try {
    $pdo = DB::getInstance();
    
    // For now, we assume Firm 1 and User 1
    $firm_id = 1; 
    $user_id = 1; 

    $sql = "INSERT INTO valuations (
                firm_id, user_id, client_name, company_number, sector, 
                year_end, years_trading, employees, purpose, report_date, 
                financials_json, adjustments_json, shareholders_json, 
                methodology_json, valuation_mid, accountant_notes, ai_narrative
            ) VALUES (
                :firm, :user, :client, :co_num, :sector,
                :year_end, :years_trad, :emp, :purpose, :rep_date,
                :fin_json, :adj_json, :sh_json,
                :meth_json, :val_mid, :notes, :ai_nar
            )";
    
    $stmt = $pdo->prepare($sql);
    
    // Prepare JSON blobs
    $meth_json = json_encode([
        'weighting' => $input['weighting'] ?? [],
        'multiples' => $input['multiples'] ?? [],
        'deduction' => $input['deduction'] ?? 0,
        'deductionDesc' => $input['deductionDesc'] ?? ''
    ]);

    $stmt->execute([
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
        'fin_json'   => json_encode($input['financials'] ?? []),
        'adj_json'   => json_encode($input['adjustments'] ?? []),
        'sh_json'    => json_encode($input['shareholders'] ?? []),
        'meth_json'  => $meth_json,
        'val_mid'    => $input['valuationMid'] ?? 0,
        'notes'      => $input['accountantNotes'] ?? '',
        'ai_nar'     => $input['aiNarrative'] ?? ''
    ]);

    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'message' => 'Valuation saved successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
