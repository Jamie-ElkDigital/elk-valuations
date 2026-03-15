<?php
/**
 * GTA Accounting - Vertex AI Proxy
 * Handles OAuth2 token refresh and proxies requests to Vertex AI (Gemini Pro)
 */

define('GCP_PROJECT_ID',    'gta-valuations');
define('GCP_LOCATION',      'europe-west2');
define('GEMINI_MODEL',      'gemini-3.1-pro-preview'); 

/**
 * Get a fresh access token using the Service Account (Application Default Credentials)
 */
function get_access_token(): string {
    // On Cloud Run, we get a token directly from the internal Metadata Server
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

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'narrative';

try {
    $access_token = get_access_token();
} catch (RuntimeException $e) {
    http_response_code(500); echo json_encode(['error' => 'Auth failed: ' . $e->getMessage()]); exit;
}

$vertex_url = sprintf(
    'https://aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
    GCP_PROJECT_ID, GCP_LOCATION, GEMINI_MODEL
);

if ($action === 'extract') {
    if (empty($input['files'])) { http_response_code(400); echo json_encode(['error' => 'Missing files']); exit; }
    
    $parts = [
        ['text' => "You are a professional business valuation analyst. Extract data from these Final Accounts. 
        Identify the year for each document. 
        Return ONLY a JSON object with this exact structure:
        {
          'year1': { 'year': 2023, 'turnover': 100000, 'cos': 50000, 'admin': 30000, 'other': 0, 'depreciation': 5000, 'directorsSalaries': 40000 },
          'year2': { ... },
          'year3': { 
            'year': 2025, 'turnover': 120000, 'cos': 60000, 'admin': 35000, 'other': 0, 'depreciation': 6000, 'directorsSalaries': 45000, 
            'netAssets': 150000, 'cash': 20000, 'debtors': 15000, 'loans': 10000, 
            'companyName': '...', 'companyNumber': '...', 'yearEnd': '30 April', 'employees': 8, 'sector': 'HR & Recruitment', 
            'description': 'A detailed 3-4 sentence professional summary of what the company does.', 
            'performanceCommentary': 'A detailed 2-paragraph analysis of the financial trends, growth, and margins seen in these 3 years of accounts.',
            'yearsTrading': 10, 
            'directors': ['Name 1', 'Name 2'], 'shareCapital': 100 
          }
        }
        Ensure 'year1' is oldest and 'year3' is newest. If a figure is missing, use 0. If a string is missing, use ''. 
        Sectors: [Professional Services, HR & Recruitment, IT & Technology, Construction & Trades, Retail, Hospitality & Leisure, Manufacturing, Healthcare, Financial Services, Property, Other].
        IMPORTANT: The 'description' and 'performanceCommentary' MUST be professional and detailed. Infer 'yearsTrading' accurately. Return ONLY the complete JSON object."]
    ];

    foreach ($input['files'] as $file) {
        $parts[] = [
            'inlineData' => [
                'mimeType' => $file['mimeType'],
                'data'     => $file['data']
            ]
        ];
    }

    $payload = [
        'contents' => [['role' => 'user', 'parts' => $parts]],
        'generationConfig' => [
            'temperature' => 0.1, 
            'maxOutputTokens' => 8192, 
        ]
    ];
} else {
    if (empty($input['prompt'])) { http_response_code(400); echo json_encode(['error' => 'Missing prompt']); exit; }
    $prompt = trim($input['prompt']);
    
    $payload = [
        'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 8192, 'topP' => 0.8],
        'systemInstruction' => ['parts' => [['text' => 'You are a professional business valuation analyst writing for a UK chartered accountancy firm (GTA Accounting, Petersfield, Hampshire). Write clear, authoritative commentary suitable for inclusion in a formal valuation report. Use UK English. Write in third person. Be factual, measured and professional. Do not use bullet points or headers. Write in flowing paragraphs only.']]]
    ];
}

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

if ($action === 'extract') {
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
