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
    
function sendMfaEmail($to, $code) {
    // ZeptoMail SMTP Configuration (Port 465 SSL is more robust)
    $smtp_host = 'ssl://smtp.zeptomail.eu';
    $smtp_port = 465;
    $smtp_user = 'emailapikey';
    $smtp_pass = getenv('SMTP_PASS') ?: ($_ENV['SMTP_PASS'] ?? ($_SERVER['SMTP_PASS'] ?? '')); 
    $from_email = 'noreply@elkdigital.co.uk';
    
    if (!$smtp_pass) {
        error_log("ZeptoMail MFA Error: SMTP_PASS not set.");
        return false;
    }

    $subject = "Your ELK Valuations Security Code";
    $body = "Your security code is: $code\n\nThis code expires in 5 minutes.";
    
    try {
        $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 15);
        if (!$socket) throw new Exception("Socket failed: $errstr");

        function getResponse($socket) {
            $res = "";
            while ($line = fgets($socket, 512)) {
                $res .= $line;
                if (basename($_SERVER['PHP_SELF']) === 'test-email.php') echo "S: $line";
                if (substr($line, 3, 1) === " ") break; // Final line of response
            }
            return $res;
        }

        function sendCommand($socket, $cmd) {
            if (basename($_SERVER['PHP_SELF']) === 'test-email.php') echo "C: $cmd";
            fwrite($socket, $cmd);
            return getResponse($socket);
        }

        getResponse($socket); // Initial 220
        sendCommand($socket, "EHLO " . gethostname() . "\r\n");
        sendCommand($socket, "AUTH LOGIN\r\n");
        sendCommand($socket, base64_encode($smtp_user) . "\r\n");
        sendCommand($socket, base64_encode($smtp_pass) . "\r\n");
        sendCommand($socket, "MAIL FROM:<$from_email>\r\n");
        sendCommand($socket, "RCPT TO:<$to>\r\n");
        sendCommand($socket, "DATA\r\n");
        
        $data = "To: $to\r\n";
        $data .= "From: ELK Valuations <$from_email>\r\n";
        $data .= "Subject: $subject\r\n";
        $data .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $data .= "\r\n$body\r\n.\r\n";
        
        sendCommand($socket, $data);
        sendCommand($socket, "QUIT\r\n");
        
        fclose($socket);
        return true;
    } catch (Exception $e) {
        error_log("ZeptoMail SMTP Socket Error: " . $e->getMessage());
        return false;
    }
}
