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
    
    $sql = "SELECT 
                v.client_name, 
                v.company_number, 
                v.sector, 
                v.uuid as latest_uuid,
                v.valuation_mid as latest_value,
                v.created_at as latest_date,
                (SELECT COUNT(*) FROM valuation_versions vv WHERE vv.valuation_id = v.id) as version_count
            FROM valuations v
            WHERE v.firm_id = ? ";
    $params = [$firm_id];

    if ($query !== '') {
        $sql .= " AND v.client_name LIKE ? ";
        $params[] = "%$query%";
    }

    // Grouping by company to show only the latest entry per client
    $sql .= " AND v.id IN (SELECT MAX(id) FROM valuations WHERE firm_id = ? GROUP BY client_name, company_number)
              ORDER BY v.created_at DESC";
    $params[] = $firm_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $valuations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for JSON
    $results = [];
    foreach ($valuations as $v) {
        $val = (float)$v['latest_value'];
        $formatted_val = ($val >= 1000000) ? '£' . number_format($val / 1000000, 2) . 'm' : '£' . number_format($val / 1000, 0) . 'k';
        
        $results[] = [
            'uuid' => $v['latest_uuid'],
            'client_name' => $v['client_name'],
            'company_number' => $v['company_number'],
            'sector' => $v['sector'] ?: 'General',
            'valuation_mid_fmt' => $formatted_val,
            'date_fmt' => date('j M Y', strtotime($v['latest_date'])),
            'version_count' => (int)$v['version_count']
        ];
    }

    echo json_encode(['success' => true, 'valuations' => $results]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
