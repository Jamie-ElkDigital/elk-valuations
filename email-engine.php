<?php
/**
 * ELK Valuations - Secure Gmail API Engine
 * Sends MFA codes using Google Service Account
 */

require_once __DIR__ . '/vendor/autoload.php';

function sendMfaEmail($to, $code) {
    // You will need to place your service-account.json in the project root 
    // OR set an environment variable GOOGLE_APPLICATION_CREDENTIALS
    $auth_config = getenv('GOOGLE_APPLICATION_CREDENTIALS_JSON'); 
    
    if (!$auth_config) {
        error_log("MFA Error: Gmail credentials missing.");
        return false;
    }

    try {
        $client = new Google\Client();
        $client->setAuthConfig(json_decode($auth_config, true));
        $client->addScope(Google\Service\Gmail::GMAIL_SEND);
        
        // Use a static sender from your domain
        $client->setSubject('jamie@elkdigital.co.uk');

        $service = new Google\Service\Gmail($client);

        $subject = "Your ELK Valuations Security Code";
        $body = "Your security code is: $code\n\nThis code expires in 5 minutes.";
        
        $strMailContent = "To: $to\r\n";
        $strMailContent .= "Subject: $subject\r\n";
        $strMailContent .= "MIME-Version: 1.0\r\n";
        $strMailContent .= "Content-Type: text/plain; charset=utf-8\r\n";
        $strMailContent .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $strMailContent .= $body;

        $mime = rtrim(strtr(base64_encode($strMailContent), '+/', '-_'), '=');
        $msg = new Google\Service\Gmail\Message();
        $msg->setRaw($mime);

        $service->users_messages->send('me', $msg);
        return true;
    } catch (Exception $e) {
        error_log("Gmail API Error: " . $e->getMessage());
        return false;
    }
}
