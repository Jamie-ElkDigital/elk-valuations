<?php
/**
 * GTA Accounting - Vertex AI Proxy
 * Handles OAuth2 token refresh and proxies requests to Vertex AI (Gemini Pro)
 */

session_start();
require_once 'db.php';

// Authentication Guard
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

$firm_id = $_SESSION['firm_id'];
$user_id = $_SESSION['user_id'];

define('GCP_PROJECT_ID',    'gta-valuations');
define('GCP_LOCATION',      'europe-west2');
define('GEMINI_MODEL',      'gemini-3.1-pro-preview'); 

... (get_access_token remains same) ...

... (payload generation remains same) ...

$ch = curl_init($vertex_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 120,
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err  = curl_error($ch);
curl_close($ch);

if ($curl_err) { http_response_code(500); echo json_encode(['error' => 'cURL error: ' . $curl_err]); exit; }
if ($http_code !== 200) { http_response_code($http_code); echo json_encode(['error' => 'Vertex AI error', 'detail' => $response]); exit; }

$data = json_decode($response, true);
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
$finishReason = $data['candidates'][0]['finishReason'] ?? 'UNKNOWN';

// Log Usage Metadata
$usage = $data['usageMetadata'] ?? [];
$promptTokens = (int)($usage['promptTokenCount'] ?? 0);
$compTokens   = (int)($usage['candidatesTokenCount'] ?? 0);
$totalTokens  = (int)($usage['totalTokenCount'] ?? 0);

try {
    $pdo = DB::getInstance();
    $stmt = $pdo->prepare("INSERT INTO usage_log (firm_id, user_id, action, prompt_tokens, completion_tokens, total_tokens) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$firm_id, $user_id, $action, $promptTokens, $compTokens, $totalTokens]);
} catch (Exception $e) {
    // Log error but don't fail the request
    error_log("Usage logging failed: " . $e->getMessage());
}

if ($action === 'extract') {
...

    $clean_text = trim($text);
    if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/', $clean_text, $matches)) {
        $clean_text = $matches[1];
    }
    
    $json = json_decode($clean_text, true);
    if (!$json) {
        if (preg_match('/\{[\s\S]*\}/', $clean_text, $matches)) {
            $json = json_decode($matches[0], true);
        }
    }

    if (!$json) {
        echo json_encode([
            'error' => 'Failed to parse JSON from Gemini', 
            'finishReason' => $finishReason,
            'raw' => $text
        ]);
    } else {
        echo json_encode(['data' => $json]);
    }
} else {
    echo json_encode(['narrative' => $text]);
}
