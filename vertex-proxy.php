<?php
/**
 * GTA Accounting - Vertex AI Proxy
 * Handles OAuth2 token refresh and proxies requests to Vertex AI (Gemini Pro)
 */

ini_set('memory_limit', '1024M');
set_time_limit(300);

session_start();
require_once 'db.php';
require_once 'proprietary-logic.php'; // Local fallback (to be replaced by ELK API call)

// Authentication Guard
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
$user_id = $_SESSION['user_id'];

define('GCP_PROJECT_ID',    'gta-valuations');
define('GCP_LOCATION',      'europe-west2');
define('GEMINI_MODEL',      'gemini-3.1-pro-preview'); 

// Set this to true to switch from local prompts to ELK Internal API
define('USE_EXTERNAL_LOGIC', false);
define('ELK_LOGIC_API_URL',  'https://api.elkdigital.co.uk/v1/valuation-logic');

/**
 * Get a fresh access token using the Service Account (Application Default Credentials)
 */
function get_access_token(): string {
    $ch = curl_init('http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Metadata-Flavor: Google'],
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new RuntimeException('Metadata server token fetch failed. Ensure this is running on Google Cloud.');
    }

    $data = json_decode($response, true);
    return $data['access_token'] ?? '';
}

/**
 * Hydrates the request with proprietary ELK Digital prompts.
 */
function get_proprietary_payload($action, $input) {
    if (USE_EXTERNAL_LOGIC) {
        // This is where you would call your internal ELK server to get the prompt
        // and return the pre-constructed Vertex AI payload.
        // For now, this is the architectural target.
    }

    // Local Implementation (Legacy / Phase 5 Start)
    if ($action === 'extract' || $action === 'extract_from_urls' || $action === 'hybrid_extract') {
        $prompt = ElkLogicVault::getExtractionPrompt();
        
        // Inject Corporate Intelligence context if provided (for cross-referencing)
        if (!empty($input['context'])) {
            $intelText = "\n\nCORPORATE INTELLIGENCE (Filing History & Officer Summary):\n";
            $intelText .= json_encode($input['context'], JSON_PRETTY_PRINT);
            $intelText .= "\n\nUse this intelligence to reconcile and verify the details found in the PDF documents.";
            $prompt .= $intelText;
        }

        $parts = [['text' => $prompt]];
        $apiKey = getenv('CH_API_KEY');
        
        // 1. Process Companies House URLs (Parallel Ingestion via curl_multi)
        if (!empty($input['ch_urls'])) {
            $mh = curl_multi_init();
            $handles = [];
            foreach ($input['ch_urls'] as $file) {
                $url = $file['url'];
                if (strpos($url, 'http') === 0) {
                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_USERPWD        => $apiKey . ":",
                        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTPHEADER     => ['Accept: application/pdf'],
                        CURLOPT_TIMEOUT        => 20
                    ]);
                    curl_multi_add_handle($mh, $ch);
                    $handles[] = $ch;
                }
            }

            // Execute all downloads simultaneously
            $active = null;
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURL_MULTI_CALL_MULTI_PERFORM);

            while ($active && $mrc == CURL_MULTI_OK) {
                if (curl_multi_select($mh) != -1) {
                    do {
                        $mrc = curl_multi_exec($mh, $active);
                    } while ($mrc == CURL_MULTI_CALL_MULTI_PERFORM);
                }
            }

            foreach ($handles as $ch) {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $pdfData = curl_multi_getcontent($ch);
                
                if ($httpCode === 200 && $pdfData) {
                    $parts[] = ['inlineData' => ['mimeType' => 'application/pdf', 'data' => base64_encode($pdfData)]];
                } else {
                    error_log("Failed to download PDF. HTTP Code: $httpCode");
                }
                
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
            curl_multi_close($mh);
        }

        // 2. Process local file uploads (if any - placed last so AI prioritizes them)
        if (!empty($input['files'])) {
            foreach ($input['files'] as $file) {
                if (isset($file['mimeType']) && isset($file['data'])) {
                    // Came from handleFileUpload (base64)
                    $parts[] = ['inlineData' => ['mimeType' => $file['mimeType'], 'data' => $file['data']]];
                } elseif (isset($file['url'])) {
                    // Came from extract_from_urls (local path fallback)
                    $url = $file['url'];
                    if (strpos($url, 'http') === 0) {
                        $ch = curl_init($url);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_USERPWD        => $apiKey . ":",
                            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTPHEADER     => ['Accept: application/pdf'],
                            CURLOPT_TIMEOUT        => 30
                        ]);
                        $pdfData = curl_exec($ch);
                        curl_close($ch);
                        if ($pdfData) $parts[] = ['inlineData' => ['mimeType' => 'application/pdf', 'data' => base64_encode($pdfData)]];
                    } elseif (file_exists($url)) {
                        $pdfData = file_get_contents($url);
                        if ($pdfData) $parts[] = ['inlineData' => ['mimeType' => 'application/pdf', 'data' => base64_encode($pdfData)]];
                    }
                }
            }
        }

        return [
            'contents' => [['role' => 'user', 'parts' => $parts]],
            'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 8192]
        ];
    } else {
        return [
            'contents' => [['role' => 'user', 'parts' => [['text' => trim($input['prompt'])]]]],
            'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 8192, 'topP' => 0.8],
            'systemInstruction' => ['parts' => [['text' => ElkLogicVault::getNarrativeSystemInstruction()]]]
        ];
    }
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'narrative';

try {
    $access_token = get_access_token();
} catch (RuntimeException $e) {
    http_response_code(500); echo json_encode(['error' => 'Auth failed: ' . $e->getMessage()]); exit;
}

$is_stream = ($action === 'narrative');
$endpoint = $is_stream ? 'streamGenerateContent?alt=sse' : 'generateContent';

// 1.5 Flash is now used for BOTH extraction and narrative to ensure maximum speed.
// Using specific stable version for Vertex AI reliability.
$current_model = 'gemini-1.5-flash-002';

$vertex_url = sprintf(
    'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:%s',
    GCP_LOCATION, GCP_PROJECT_ID, GCP_LOCATION, $current_model, $endpoint
);

// Get the payload (Now hydrated by the Logic Vault)
$payload = get_proprietary_payload($action, $input);

if ($is_stream) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    $ch = curl_init($vertex_url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 300,
        CURLOPT_WRITEFUNCTION  => function($curl, $data) {
            echo $data;
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
            return strlen($data);
        }
    ]);
    curl_exec($ch);
    $curl_err = curl_error($ch);
    curl_close($ch);
    exit;
}

$ch = curl_init($vertex_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 300,
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

// Extract client name for logging if available
$clientName = null;
if (!empty($input['context']['name'])) {
    $clientName = $input['context']['name'];
} elseif (!empty($input['prompt']) && preg_match('/valuation commentary for (.*?)\./', $input['prompt'], $m)) {
    $clientName = $m[1];
}

try {
    $pdo = DB::getInstance();
    $stmt = $pdo->prepare("INSERT INTO usage_log (firm_id, user_id, client_name, action, prompt_tokens, completion_tokens, total_tokens)
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$firm_id, $user_id, $clientName, $action, $promptTokens, $compTokens, $totalTokens]);
} catch (Exception $e) {
    error_log("Usage logging failed: " . $e->getMessage());
}
if ($action === 'extract' || $action === 'extract_from_urls' || $action === 'hybrid_extract') {
    $clean_text = trim($text);
    if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/', $clean_text, $matches)) { $clean_text = $matches[1]; }
    $json = json_decode($clean_text, true);
    if (!$json && preg_match('/\{[\s\S]*\}/', $clean_text, $matches)) { $json = json_decode($matches[0], true); }

    if (!$json) {
        echo json_encode(['error' => 'Failed to parse JSON from Gemini', 'finishReason' => $finishReason, 'raw' => $text]);
    } else {
        echo json_encode(['data' => $json]);
    }
} else {
    echo json_encode(['narrative' => $text]);
}
