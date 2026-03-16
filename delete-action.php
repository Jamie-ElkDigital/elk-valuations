<?php
session_start();
require_once 'db.php';

// Ensure the user is logged in
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

$firm_id = $_SESSION['firm_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

try {
    $pdo = DB::getInstance();

    if ($action === 'delete_company') {
        $uuid = $input['uuid'] ?? '';
        if (!$uuid) throw new Exception("UUID required");

        // 1. Fetch valuation to verify ownership
        $stmt = $pdo->prepare("SELECT id FROM valuations WHERE uuid = ? AND firm_id = ?");
        $stmt->execute([$uuid, $firm_id]);
        $val_id = $stmt->fetchColumn();

        if (!$val_id) {
            throw new Exception("Valuation not found or access denied.");
        }

        // 2. Fetch all versions to delete from GCS (Future enhancement: actual GCS deletion via API)
        // For now we just delete the DB records, which cascades via foreign keys if set up, 
        // but let's be explicit.
        $stmt = $pdo->prepare("DELETE FROM valuation_versions WHERE valuation_id = ?");
        $stmt->execute([$val_id]);

        // 3. Delete the main valuation
        $stmt = $pdo->prepare("DELETE FROM valuations WHERE id = ?");
        $stmt->execute([$val_id]);

        echo json_encode(['success' => true]);
        
    } elseif ($action === 'delete_version') {
        $version_id = $input['version_id'] ?? '';
        if (!$version_id) throw new Exception("Version ID required");

        // 1. Verify ownership via a join
        $stmt = $pdo->prepare("
            SELECT vv.id 
            FROM valuation_versions vv
            JOIN valuations v ON vv.valuation_id = v.id
            WHERE vv.id = ? AND v.firm_id = ?
        ");
        $stmt->execute([$version_id, $firm_id]);
        if (!$stmt->fetchColumn()) {
             throw new Exception("Version not found or access denied.");
        }

        // 2. Delete version record
        $stmt = $pdo->prepare("DELETE FROM valuation_versions WHERE id = ?");
        $stmt->execute([$version_id]);

        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Unknown action");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>