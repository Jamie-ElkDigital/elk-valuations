<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT u.*, f.name as firm_name, f.slug as firm_slug, f.global_2fa_enabled FROM users u JOIN firms f ON u.firm_id = f.id WHERE u.email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Check if Global 2FA is DISABLED for this firm
            if (!$user['global_2fa_enabled']) {
                $_SESSION['authenticated'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['firm_id'] = $user['firm_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['firm_name'] = $user['firm_name'];
                $_SESSION['firm_slug'] = $user['firm_slug'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $redirect = ($user['firm_slug'] === 'elk') ? 'super-admin.php' : 'dashboard.php';
                header("Location: $redirect");
                exit;
            }

            // Step 1: Generate MFA Code
            $mfa_code = sprintf("%06d", mt_rand(100000, 999999));
            $mfa_expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            // Step 2: Save to DB
            $stmt = $pdo->prepare("UPDATE users SET mfa_code = ?, mfa_expires_at = ? WHERE id = ?");
            $stmt->execute([$mfa_code, $mfa_expires, $user['id']]);

            // Step 3: Store "Pending" Session
            $_SESSION['mfa_pending_user_id'] = $user['id'];
            $_SESSION['mfa_pending_firm_id'] = $user['firm_id'];
            $_SESSION['mfa_pending_email'] = $user['email'];
            
            // Step 4: Send Email
            require_once 'email-engine.php';
            sendMfaEmail($user['email'], $mfa_code);

            header('Location: verify.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | ELK Valuations</title>
<link rel="icon" type="image/webp" href="favicon.webp">
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
    body {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #050505;
        height: 100vh;
        margin: 0;
    }
    .login-card {
        background: #0a0a15;
        border: 1px solid rgba(197, 160, 89, 0.2);
        padding: 40px;
        width: 100%;
        max-width: 400px;
        border-radius: 4px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }
    .logo-area {
        text-align: center;
        margin-bottom: 32px;
    }
    .logo-area img {
        height: 60px;
        margin-bottom: 16px;
    }
    .login-title {
        font-size: 20px;
        font-weight: 600;
        color: #fff;
        margin-bottom: 8px;
        text-align: center;
    }
    .login-subtitle {
        font-size: 13px;
        color: #71717a;
        text-align: center;
        margin-bottom: 32px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-size: 12px;
        color: #d1d1d6;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .form-group input {
        width: 100%;
        background: #000;
        border: 1px solid #27272a;
        padding: 12px;
        color: #fff;
        border-radius: 2px;
        outline: none;
        transition: border-color 0.2s;
    }
    .form-group input:focus {
        border-color: #c5a059;
    }
    .btn-login {
        width: 100%;
        background: #c5a059;
        color: #050505;
        border: none;
        padding: 14px;
        font-weight: 600;
        border-radius: 2px;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.2s;
        margin-top: 10px;
    }
    .btn-login:hover {
        background: #dfbc82;
    }
    .error-msg {
        background: rgba(229, 115, 115, 0.1);
        color: #e57373;
        padding: 12px;
        border-radius: 2px;
        font-size: 13px;
        margin-bottom: 20px;
        border: 1px solid rgba(229, 115, 115, 0.2);
    }
</style>
</head>
<body>

<div class="login-card">
    <div class="logo-area">
        <img src="elk-design-logo.png" alt="ELK Digital">
    </div>
    
    <h1 class="login-title">Cloud Valuations</h1>
    <p class="login-subtitle">Sign in to your firm's intelligence portal</p>

    <?php if ($error): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="name@firm.com" autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn-login">Sign In</button>
    </form>
    
    <div style="margin-top: 32px; text-align: center; font-size: 11px; color: #71717a;">
        Proprietary Platform by <span style="color: #d1d1d6;">ELK Digital Limited</span>
    </div>
</div>

</body>
</html>
