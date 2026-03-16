<?php
/**
 * ELK Valuations - Search API
 * Returns filtered valuations for the dashboard.
 */

session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Security Guard
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised access.']);
    exit;
}

$firm_id = $_SESSION['firm_id'];
$query = $_GET['q'] ?? '';

try {
    $pdo = DB::getInstance();
    
    $sql = "SELECT * FROM valuations WHERE firm_id = ? ";
    $params = [$firm_id];

    if ($query !== '') {
        $sql .= " AND client_name LIKE ? ";
        $params[] = "%$query%";
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $valuations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for JSON
    $results = [];
    foreach ($valuations as $v) {
        $val = (float)$v['valuation_mid'];
        $formatted_val = ($val >= 1000000) ? '£' . number_format($val / 1000000, 2) . 'm' : '£' . number_format($val / 1000, 0) . 'k';
        
        $results[] = [
            'uuid' => $v['uuid'],
            'client_name' => $v['client_name'],
            'sector' => $v['sector'] ?: 'General',
            'year_end' => $v['year_end'],
            'valuation_mid_fmt' => $formatted_val,
            'date_fmt' => date('j M Y', strtotime($v['created_at'])),
            'user_name' => $_SESSION['user_name'] // Simplification for now
        ];
    }

    echo json_encode(['success' => true, 'valuations' => $results]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
