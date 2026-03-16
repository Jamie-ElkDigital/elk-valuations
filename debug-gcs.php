<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('GCS_BUCKET_NAME', 'gta-valuations-reports');

echo "<body style='font-family: sans-serif; background: #050505; color: #fff; padding: 40px;'>";
echo "<h1>GCS Upload Debugger</h1>";

function get_gcs_token(): string {
    $ch = curl_init('http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Metadata-Flavor: Google'],
        CURLOPT_TIMEOUT        => 3,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h3>1. Metadata Token Request</h3>";
    echo "HTTP Code: $http_code<br>";
    if ($http_code !== 200) {
        echo "Response: " . htmlspecialchars($response) . "<br>";
        return '';
    }
    $data = json_decode($response, true);
    return $data['access_token'] ?? '';
}

$token = get_gcs_token();
if (!$token) {
    die("<h2 style='color:#e57373;'>Failed to get token from metadata server.</h2><p>This is expected if testing locally. If on Cloud Run, the metadata server is unreachable.</p></body>");
}
echo "<p style='color:#10b981;'>✓ Successfully retrieved OAuth2 token from Google Metadata Server.</p>";

$gcs_name = "debug/test_" . time() . ".txt";
$content = "This is a test file to verify GCS permissions.";

$url = "https://storage.googleapis.com/upload/storage/v1/b/" . GCS_BUCKET_NAME . "/o?uploadType=media&name=" . urlencode($gcs_name);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $content,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $token,
        'Content-Type: text/plain'
    ],
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<hr style='border-color: #333; margin: 30px 0;'>";
echo "<h3>2. Storage Upload Request</h3>";
echo "Target: <code>gs://" . GCS_BUCKET_NAME . "/" . htmlspecialchars($gcs_name) . "</code><br><br>";
echo "HTTP Code: <strong>$http_code</strong><br>";

if ($error) {
    echo "cURL Error: " . htmlspecialchars($error) . "<br>";
}
echo "Response Body:<br><pre style='background:#111; border:1px solid #333; padding:15px; color:#c5a059; border-radius:4px;'>" . htmlspecialchars($response) . "</pre>";

if ($http_code === 200) {
    echo "<h2 style='color:#10b981;'>✓ Upload Successful!</h2>";
    echo "<p>Your Cloud Run service account has the correct permissions to write to the bucket.</p>";
} else {
    echo "<h2 style='color:#e57373;'>❌ Upload Failed.</h2>";
    echo "<p>Google rejected the upload. Check the following in Google Cloud Console:</p>";
    echo "<ol>";
    echo "<li>Does the bucket <strong>" . GCS_BUCKET_NAME . "</strong> exist?</li>";
    echo "<li>Does the Compute Engine default service account have the <strong>Storage Object Admin</strong> or <strong>Storage Object Creator</strong> role on that bucket?</li>";
    echo "</ol>";
}
echo "</body>";
?>