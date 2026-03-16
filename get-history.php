<?php
/**
 * ELK Valuations - Version History API
 * Returns nested report snapshots for a specific valuation.
 */

session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

$uuid = $_GET['uuid'] ?? '';
$firm_id = $_SESSION['firm_id'];

if (!$uuid) {
    echo json_encode(['error' => 'Missing UUID']);
    exit;
}

try {
    $pdo = DB::getInstance();
    
    // Security: Ensure valuation belongs to firm
    $stmt = $pdo->prepare("SELECT id FROM valuations WHERE uuid = ? AND firm_id = ?");
    $stmt->execute([$uuid, $firm_id]);
    $valuation = $stmt->fetch();

    if (!$valuation) {
        echo json_encode(['error' => 'Not found']);
        exit;
    }

    $vid = $valuation['id'];

    // Fetch Versions
    $stmt = $pdo->prepare("SELECT id, version_number, valuation_mid, created_at 
                           FROM valuation_versions 
                           WHERE valuation_id = ? 
                           ORDER BY version_number DESC");
    $stmt->execute([$vid]);
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format
    foreach ($versions as &$v) {
        $v['date_fmt'] = date('H:i d M Y', strtotime($v['created_at']));
    }

    echo json_encode([
        'success' => true, 
        'versions' => $versions
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
