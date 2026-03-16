<?php
/**
 * ELK Valuations - SMTP Test Utility
 * Run this via https://[your-app-url]/test-email.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'email-engine.php';

echo "<h1>SMTP Diagnostic Test</h1>";

$test_email = $_GET['to'] ?? 'jamie@elkdigital.co.uk';
$test_code = "123456";

echo "Attempting to send test MFA code to: <strong>$test_email</strong>...<br><br>";

// Check environment variable
$pass = getenv('SMTP_PASS');
if (!$pass) {
    echo "<div style='color:red;'>ERROR: SMTP_PASS environment variable is NOT SET in Cloud Run.</div>";
    echo "Available System Keys: " . implode(', ', array_keys(getenv())) . "<br>";
} else {
    echo "SMTP_PASS found (length: " . strlen($pass) . " chars).<br>";
}

echo "<hr><h3>Connection Log:</h3><pre>";

// We need to modify email-engine.php temporarily or use a wrapper that captures output.
// For now, let's just run it and see the result.
$result = sendMfaEmail($test_email, $test_code);

echo "</pre><hr>";

if ($result) {
    echo "<h2 style='color:green;'>SUCCESS: The email was accepted by ZeptoMail.</h2>";
    echo "Check your inbox (and spam folder) for a message from noreply@elkdigital.co.uk.";
} else {
    echo "<h2 style='color:red;'>FAILED: The email could not be sent.</h2>";
    echo "Check the 'Connection Log' above or the Cloud Run logs for errors.";
}

echo "<br><br><a href='login.php'>Return to Login</a>";
