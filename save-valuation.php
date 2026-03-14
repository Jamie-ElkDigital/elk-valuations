<?php
/**
 * ELK Valuations - Save API
 * Saves the valuation payload to Cloud SQL
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
    
    // For now, we assume Firm 1 (GTA) and User 1
    // In Phase 4, we will pull these from the session
    $firm_id = 1; 
    $user_id = 1; 

    $sql = "INSERT INTO valuations (firm_id, user_id, client_name, valuation_mid, payload) 
            VALUES (:firm, :user, :client, :val, :payload)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'firm'    => $firm_id,
        'user'    => $user_id,
        'client'  => $input['companyName'],
        'val'     => $input['valuationMid'] ?? 0,
        'payload' => json_encode($input)
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
