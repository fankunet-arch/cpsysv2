<?php
/**
 * Toptea HQ - cpsys
 * Backend Login Handler with CAPTCHA
 * Engineer: Gemini | Date: 2025-10-24
 *
 * [GEMINI SECURITY FIX V1.0 - 2025-11-10]
 * - Replaced insecure hash('sha256') / hash_equals() with password_verify()
 * - This unifies the auth logic with the Bcrypt hashes stored by user management.
 */
session_start();
require_once realpath(__DIR__ . '/../../../core/config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$captcha = strtolower($_POST['captcha'] ?? '');

if (empty($username) || empty($password) || empty($captcha)) {
    header('Location: ../login.php?error=2');
    exit;
}

// --- CORE UPGRADE: CAPTCHA Validation ---
if (!isset($_SESSION['captcha_code']) || $captcha !== $_SESSION['captcha_code']) {
    // Unset the code to prevent reuse
    unset($_SESSION['captcha_code']);
    header('Location: ../login.php?error=5');
    exit;
}

// CAPTCHA is correct, unset it so it can't be used again
unset($_SESSION['captcha_code']);

// --- [H5 FIX] Rate Limiting Check ---
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$max_attempts = 5;
$lockout_minutes = 15;

try {
    // 检查IP是否被锁定
    $stmt_check = $pdo->prepare("
        SELECT COUNT(*) as attempts
        FROM audit_logs
        WHERE action = 'login.failed'
          AND ip = ?
          AND created_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL :minutes MINUTE)
    ");
    $stmt_check->execute([$ip, ':minutes' => $lockout_minutes]);
    $result = $stmt_check->fetch();

    if ($result['attempts'] >= $max_attempts) {
        // 记录封禁审计
        if (function_exists('log_audit_action')) {
            log_audit_action($pdo, 'login.blocked', 'cpsys_users', null, null, [
                'ip' => $ip,
                'username' => $username,
                'reason' => 'Too many failed attempts'
            ]);
        }
        header('Location: ../login.php?error=6');  // 新增错误码6：账户锁定
        exit;
    }
} catch (PDOException $e) {
    error_log("Rate limit check failed: " . $e->getMessage());
    // 继续执行，不因rate limit检查失败而中断登录
}

// --- User Authentication (FIXED) ---
try {
    $stmt = $pdo->prepare("SELECT id, username, password_hash, display_name, role_id FROM cpsys_users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // [GEMINI SECURITY FIX V1.0] Use password_verify() to check against Bcrypt hash
    if ($user && password_verify($password, $user['password_hash'])) {
        
        // 验证通过
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['logged_in'] = true;
        
        $update_stmt = $pdo->prepare("UPDATE cpsys_users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update_stmt->execute([$user['id']]);

        // [H5 FIX] 登录成功，记录审计日志
        if (function_exists('log_audit_action')) {
            log_audit_action($pdo, 'login.success', 'cpsys_users', $user['id'], null, [
                'ip' => $ip,
                'username' => $username
            ]);
        }

        header('Location: ../index.php');
        exit;
    }

    // [GEMINI SECURITY FIX V1.0] 用户名或密码无效
    // [H5 FIX] 记录失败的审计日志
    if (function_exists('log_audit_action')) {
        log_audit_action($pdo, 'login.failed', 'cpsys_users', null, null, [
            'ip' => $ip,
            'username' => $username,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    }

    header('Location: ../login.php?error=1');
    exit;

} catch (PDOException $e) {
    header('Location: ../login.php?error=3');
    exit;
}
