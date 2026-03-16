<?php
/**
 * ELK Valuations - Logout
 * Clears persistent session tokens and destroys session.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// 1. Clear persistent session from DB
if (isset($_COOKIE['elk_session'])) {
    try {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
        $stmt->execute([$_COOKIE['elk_session']]);
    } catch (Exception $e) {
        // Silently ignore
    }
}

// 2. Clear Cookie
setcookie('elk_session', '', time() - 3600, '/');

// 3. Clear Session
$_SESSION = array();
session_destroy();

header('Location: login.php');
exit;
