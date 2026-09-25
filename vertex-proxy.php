<?php
/**
 * GTA Accounting - Vertex AI Proxy
 * Handles OAuth2 token refresh and proxies requests to Vertex AI (Gemini Pro)
 */

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
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''; // php-fpm normalises the header to X-Csrf-Token, so getallheaders() lookups by exact case missed it (24 Sep 2026)
if (!$csrf_token || $csrf_token !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF validation failed.']);
    exit;
}

$firm_id = $_SESSION['firm_id'];
$user_id = $_SESSION['user_id'];

// LLM endpoint + key come from .env (php-fpm pool env[]). Gemini API shape; Vertex-compatible payloads.
define('LLM_API_BASE', rtrim(getenv('LLM_API_BASE') ?: 'https://generativelanguage.googleapis.com/v1beta', '/'));
define('LLM_API_KEY',  getenv('LLM_API_KEY') ?: '');
define('GEMINI_MODEL', getenv('LLM_MODEL') ?: 'gemini-2.5-flash');

// Set this to true to switch from local prompts to ELK Internal API
define('USE_EXTERNAL_LOGIC', false);
define('ELK_LOGIC_API_URL',  'https://api.elkdigital.co.uk/v1/valuation-logic');

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
        
        // 1. Process local file uploads (if any)
        if (!empty($input['files'])) {
            foreach ($input['files'] as $file) {
                if (isset($file['mimeType']) && isset($file['data'])) {
                    // Came from handleFileUpload (base64)
                    $parts[] = ['inlineData' => ['mimeType' => $file['mimeType'], 'data' => $file['data']]];
                } elseif (isset($file['url'])) {
                    // Came from extract_from_urls (local path fallback)
                    $url = $file['url'];
                    if (preg_match('#^https://document-api\.(companieshouse\.gov\.uk|company-information\.service\.gov\.uk)/#', $url)) { // CH document hosts only: the CH key rides on this request (Codex SSRF finding, 25 Sep 2026)
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
                    } // local paths are never accepted: the browser could name any readable server file (removed 24 Sep 2026)
                }
            }
        }

        // 2. Process Companies House URLs (if hybrid)
        if (!empty($input['ch_urls'])) {
            foreach ($input['ch_urls'] as $file) {
                $url = $file['url'];
                if (preg_match('#^https://document-api\.(companieshouse\.gov\.uk|company-information\.service\.gov\.uk)/#', $url)) { // CH document hosts only: the CH key rides on this request (Codex SSRF finding, 25 Sep 2026)
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
                }
            }
        }

        return [
            'contents' => [['role' => 'user', 'parts' => $parts]],
            'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 65536, 'responseMimeType' => 'application/json', 'thinkingConfig' => ['thinkingLevel' => 'low']] // Gemini 3 counts thinking against maxOutputTokens: 8192 truncated the JSON mid-object (MAX_TOKENS after ~7.8k thought tokens, 25 Sep 2026)
        ];
    } else {
        return [
            'contents' => [['role' => 'user', 'parts' => [['text' => trim($input['prompt'])]]]],
            'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 65536, 'topP' => 0.8, 'thinkingConfig' => ['thinkingLevel' => 'low']],
            'systemInstruction' => ['parts' => [['text' => ElkLogicVault::getNarrativeSystemInstruction($_SESSION['firm_name'] ?? 'the firm')]]]
        ];
    }
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'narrative';

if (LLM_API_KEY === '') {
    http_response_code(500); echo json_encode(['error' => 'LLM_API_KEY not set']); exit;
}

// Usage row for every model call, streamed or not (streamed narratives were never logged before 25 Sep 2026)
function log_usage(string $action, array $usage, ?string $clientName): void {
    global $firm_id, $user_id;
    if (!$usage) return; // no usageMetadata = the call failed; do not write a zero-token row
    try {
        $stmt = DB::getInstance()->prepare("INSERT INTO usage_log (firm_id, user_id, client_name, action, prompt_tokens, completion_tokens, total_tokens) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$firm_id, $user_id, $clientName, $action, (int)($usage['promptTokenCount'] ?? 0), (int)($usage['candidatesTokenCount'] ?? 0), (int)($usage['totalTokenCount'] ?? 0)]);
    } catch (Exception $e) {
        error_log("Usage logging failed: " . $e->getMessage());
    }
}
$clientName = null;
if (!empty($input['context']['name'])) {
    $clientName = $input['context']['name'];
} elseif (!empty($input['prompt']) && preg_match('/valuation commentary for (.*?)\./', $input['prompt'], $m)) {
    $clientName = $m[1];
}

$is_stream = ($action === 'narrative');
$endpoint = $is_stream ? 'streamGenerateContent?alt=sse' : 'generateContent';

$vertex_url = sprintf('%s/models/%s:%s', LLM_API_BASE, GEMINI_MODEL, $endpoint);

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
        CURLOPT_HTTPHEADER     => ['x-goog-api-key: ' . LLM_API_KEY, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_WRITEFUNCTION  => function($curl, $data) use (&$sse) {
            $sse .= $data;
            echo $data;
            ob_flush();
            flush();
            return strlen($data);
        }
    ]);
    $sse = '';
    curl_exec($ch);
    $curl_err = curl_error($ch);
    curl_close($ch);
    // usageMetadata rides on the last SSE chunk; scan every data: line and keep the last one that carries it
    $usage = [];
    if (preg_match_all('/^data: (.*)$/m', $sse, $mm)) {
        foreach ($mm[1] as $line) {
            $j = json_decode(trim($line), true);
            if (!empty($j['usageMetadata'])) $usage = $j['usageMetadata'];
        }
    }
    log_usage($action, $usage, $clientName);
    exit;
}

// Gemini returns transient 503 "high demand" / 429 often enough that one attempt fails a real run
// (seen 24 Sep 2026: first call 503, identical retry 200). Retry with backoff before giving up.
$body = json_encode($payload);
foreach ([0, 2, 5, 10] as $attempt => $delay) {
    if ($delay) sleep($delay);
    $ch = curl_init($vertex_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['x-goog-api-key: ' . LLM_API_KEY, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 120,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);
    if (!$curl_err && !in_array($http_code, [429, 503], true)) break;
    error_log("LLM attempt " . ($attempt + 1) . " failed: HTTP $http_code $curl_err");
}

if ($curl_err) { http_response_code(500); echo json_encode(['error' => 'cURL error: ' . $curl_err]); exit; }
if ($http_code !== 200) { http_response_code($http_code); echo json_encode(['error' => 'Vertex AI error', 'detail' => $response]); exit; }

$data = json_decode($response, true);
// Gemini 3.x splits long answers across several parts; parts[0] alone truncated the JSON (24 Sep 2026).
$text = implode('', array_map(fn($p) => $p['text'] ?? '', $data['candidates'][0]['content']['parts'] ?? []));
$finishReason = $data['candidates'][0]['finishReason'] ?? 'UNKNOWN';

log_usage($action, $data['usageMetadata'] ?? [], $clientName);
if ($action === 'extract' || $action === 'extract_from_urls' || $action === 'hybrid_extract') { // hybrid was missing, so PDF uploads returned raw text as 'narrative' and the UI said no data (24 Sep 2026)
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
