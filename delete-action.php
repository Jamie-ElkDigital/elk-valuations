<?php
session_start();
require_once 'db.php';

// Ensure the user is logged in
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

        // 1. Fetch valuation to identify the company group
        $stmt = $pdo->prepare("SELECT client_name, company_number FROM valuations WHERE uuid = ? AND firm_id = ?");
        $stmt->execute([$uuid, $firm_id]);
        $val = $stmt->fetch();

        if (!$val) {
            throw new Exception("Valuation not found or access denied.");
        }

        $client_name = $val['client_name'];
        $company_number = $val['company_number'];

        // 2. Identify all valuation IDs for this company group in this firm
        if ($company_number) {
            $stmt = $pdo->prepare("SELECT id FROM valuations WHERE company_number = ? AND firm_id = ?");
            $stmt->execute([$company_number, $firm_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM valuations WHERE client_name = ? AND firm_id = ? AND company_number IS NULL");
            $stmt->execute([$client_name, $firm_id]);
        }
        $val_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($val_ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($val_ids), '?'));
            
            // 3. Delete related versions (PDF records)
            $stmt = $pdo->prepare("DELETE FROM valuation_versions WHERE valuation_id IN ($placeholders)");
            $stmt->execute($val_ids);

            // 4. Delete the main valuations
            $stmt = $pdo->prepare("DELETE FROM valuations WHERE id IN ($placeholders)");
            $stmt->execute($val_ids);
        }

        // 5. Clean up cached company profile if it exists
        if ($company_number) {
            $stmt = $pdo->prepare("DELETE FROM company_profiles WHERE company_number = ? AND firm_id = ?");
            $stmt->execute([$company_number, $firm_id]);
        }

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