<?php
/**
 * ELK Valuations - Transactional Email Engine (ZeptoMail)
 * Sends MFA codes securely via ZeptoMail SMTP Relay.
 */

function sendMfaEmail($to, $code) {
    // ZeptoMail SMTP Configuration
    $smtp_host = 'smtp.zeptomail.eu';
    $smtp_port = 587;
    $smtp_user = 'emailapikey';
    $smtp_pass = getenv('SMTP_PASS') ?: ($_ENV['SMTP_PASS'] ?? ($_SERVER['SMTP_PASS'] ?? '')); 
    $from_email = 'noreply@elkdigital.co.uk'; // Ensure this matches an authorized sender in ZeptoMail
    
    if (!$smtp_pass) {
        error_log("ZeptoMail MFA Error: SMTP_PASS not set in Environment.");
        return false;
    }

    $subject = "Your ELK Valuations Security Code";
    $body = "Your security code is: $code\n\nThis code expires in 5 minutes.";

    // Because Cloud Run doesn't always support the standard mail() function cleanly, 
    // and since we want to avoid external libraries like PHPMailer, 
    // we use a lightweight native SMTP socket implementation for maximum speed.
    
    try {
        $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
        if (!$socket) throw new Exception("Socket connection failed: $errstr");

        $steps = [
            null,
            "EHLO " . gethostname() . "\r\n",
            "STARTTLS\r\n",
            "EHLO " . gethostname() . "\r\n",
            "AUTH LOGIN\r\n",
            base64_encode($smtp_user) . "\r\n",
            base64_encode($smtp_pass) . "\r\n",
            "MAIL FROM:<$from_email>\r\n",
            "RCPT TO:<$to>\r\n",
            "DATA\r\n",
            "To: $to\r\nFrom: ELK Valuations <$from_email>\r\nSubject: $subject\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n$body\r\n.\r\n",
            "QUIT\r\n"
        ];

        foreach ($steps as $i => $step) {
            if ($step) {
                if (basename($_SERVER['PHP_SELF']) === 'test-email.php') echo "C: $step";
                fwrite($socket, $step);
            }
            $res = fgets($socket, 512);
            if (basename($_SERVER['PHP_SELF']) === 'test-email.php') echo "S: $res";
            
            // Re-read after STARTTLS
            if ($i === 2 && strpos($res, '220') !== false) {
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            }
        }
        
        fclose($socket);
        return true;
    } catch (Exception $e) {
        error_log("ZeptoMail SMTP Socket Error: " . $e->getMessage());
        return false;
    }
}
