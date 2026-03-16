<?php
session_start();
require_once 'db.php';

// Guard: Must have a pending login
if (!isset($_SESSION['mfa_pending_user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$email = $_SESSION['mfa_pending_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';

    try {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT u.*, f.name as firm_name, f.slug as firm_slug 
                               FROM users u 
                               JOIN firms f ON u.firm_id = f.id 
                               WHERE u.id = ?");
        $stmt->execute([$_SESSION['mfa_pending_user_id']]);
        $user = $stmt->fetch();

        if ($user && $user['mfa_code'] === $code && strtotime($user['mfa_expires_at']) > time()) {
            // Success: Finalize Authentication
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firm_id'] = $user['firm_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['firm_name'] = $user['firm_name'];
            $_SESSION['firm_slug'] = $user['firm_slug'];
            
            // Generate CSRF Token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Clean up MFA data
            $stmt = $pdo->prepare("UPDATE users SET mfa_code = NULL, mfa_expires_at = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Clear pending MFA session
            unset($_SESSION['mfa_pending_user_id']);
            unset($_SESSION['mfa_pending_firm_id']);
            unset($_SESSION['mfa_pending_email']);

            if ($user['firm_slug'] === 'elk') {
                header('Location: super-admin.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid or expired security code.';
        }
    } catch (Exception $e) {
        $error = 'System error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Identity | ELK Valuations</title>
<link rel="icon" type="image/webp" href="favicon.webp">
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
    body { display: flex; align-items: center; justify-content: center; background: #050505; height: 100vh; margin: 0; }
    .login-card { background: #0a0a15; border: 1px solid rgba(197, 160, 89, 0.2); padding: 40px; width: 100%; max-width: 400px; border-radius: 4px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
    .login-title { font-size: 20px; font-weight: 600; color: #fff; margin-bottom: 8px; text-align: center; }
    .login-subtitle { font-size: 13px; color: #71717a; text-align: center; margin-bottom: 32px; line-height: 1.5; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 12px; color: #d1d1d6; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
    .form-group input { width: 100%; background: #000; border: 1px solid #27272a; padding: 12px; color: #fff; border-radius: 2px; outline: none; transition: border-color 0.2s; text-align: center; font-size: 24px; font-family: 'DM Mono', monospace; letter-spacing: 0.2em; }
    .form-group input:focus { border-color: #c5a059; }
    .btn-login { width: 100%; background: #c5a059; color: #050505; border: none; padding: 14px; font-weight: 600; border-radius: 2px; cursor: pointer; font-size: 14px; transition: background 0.2s; margin-top: 10px; }
    .btn-login:hover { background: #dfbc82; }
    .error-msg { background: rgba(229, 115, 115, 0.1); color: #e57373; padding: 12px; border-radius: 2px; font-size: 13px; margin-bottom: 20px; border: 1px solid rgba(229, 115, 115, 0.2); text-align: center; }
</style>
</head>
<body>

<div class="login-card">
    <h1 class="login-title">Verify Identity</h1>
    <p class="login-subtitle">We've sent a 6-digit security code to<br><strong style="color: #fff;"><?php echo htmlspecialchars($email); ?></strong></p>

    <?php if ($error): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Security Code</label>
            <input type="text" name="code" required placeholder="000000" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autofocus>
        </div>
        <button type="submit" class="btn-login">Verify & Sign In</button>
    </form>
    
    <div style="margin-top: 24px; text-align: center;">
        <a href="login.php" style="color: #71717a; font-size: 12px; text-decoration: none;">← Back to Sign In</a>
    </div>
</div>

</body>
</html>
