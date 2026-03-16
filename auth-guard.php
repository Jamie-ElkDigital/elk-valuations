<?php
/**
 * ELK Valuations - Centralized Auth Guard
 * Handles session validation and persistent 'Remember Me' logic (12h).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// 1. If already authenticated, we're good
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    return;
}

// 2. If not authenticated, check for persistent cookie
if (isset($_COOKIE['elk_session'])) {
    $token = $_COOKIE['elk_session'];
    
    try {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT u.*, f.name as firm_name, f.slug as firm_slug 
                               FROM user_sessions s
                               JOIN users u ON s.user_id = u.id
                               JOIN firms f ON u.firm_id = f.id
                               WHERE s.session_token = ? AND s.expires_at > CURRENT_TIMESTAMP");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            // Restore Session
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firm_id'] = $user['firm_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['firm_name'] = $user['firm_name'];
            $_SESSION['firm_slug'] = $user['firm_slug'];
            
            // Regenerate CSRF Token if missing
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return;
        }
    } catch (Exception $e) {
        error_log("Auth Guard Error: " . $e->getMessage());
    }
}

// 3. Not authenticated and no valid cookie? Boot to login
header('Location: login.php');
exit;
