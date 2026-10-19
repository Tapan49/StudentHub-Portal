<?php


// Configure secure session cookie params before starting session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

// Session timeout duration in seconds (15 minutes = 900 seconds)
// For quick demo testing, user can pass ?test_timeout=1 to instantly trigger a timeout
define('SESSION_TIMEOUT', 900);

/**
 * Check and enforce session timeout
 */
function check_session_timeout() {
    // Demo shortcut for testing timeout
    if (isset($_GET['test_timeout']) && $_GET['test_timeout'] == '1') {
        logout_current_session();
        header("Location: login.php?error=timeout");
        exit();
    }

    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            // Inactivity threshold exceeded
            logout_current_session();
            header("Location: login.php?error=timeout");
            exit();
        }
        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Enforce authentication and Role-Based Access Control (RBAC)
 * @param string|null $required_role 'admin' or 'student' (null means any logged-in user)
 */
function require_login($required_role = null) {
    global $pdo;

    // Check remember-me if not currently in active session
    if (!is_logged_in()) {
        check_remember_me_cookie($pdo);
    }

    // Check session timeout
    check_session_timeout();

    // 1. Check if user is logged in
    if (!is_logged_in()) {
        header("Location: login.php?error=unauthorized");
        exit();
    }

    // 2. Check RBAC role requirement
    if ($required_role !== null) {
        $currentRole = strtolower($_SESSION['user_role'] ?? '');
        $targetRole = strtolower($required_role);

        if ($currentRole !== $targetRole) {
            // Role mismatch - students cannot access admin pages
            if ($targetRole === 'admin') {
                header("Location: student_dashboard.php?error=forbidden");
                exit();
            } else {
                header("Location: admin_dashboard.php?error=forbidden");
                exit();
            }
        }
    }
}

/**
 * Intermediate Extension: Update last-login timestamp
 */
function update_last_login($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :id");
        $stmt->execute([':id' => $userId]);
    } catch (PDOException $e) {
        // Silently continue if update fails
    }
}

/**
 * Advanced Extension: Create and store secure Remember-Me Token
 * Uses random selector and validator, storing SHA-256 hash of validator in DB
 */
function setup_remember_me($pdo, $userId) {
    $selector = bin2hex(random_bytes(16)); // 32 chars
    $validator = bin2hex(random_bytes(32)); // 64 chars
    $hashedValidator = hash('sha256', $validator);
    $expiry = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days

    try {
        // Remove any prior tokens for this user
        $delStmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = :user_id");
        $delStmt->execute([':user_id' => $userId]);

        // Insert new token
        $stmt = $pdo->prepare("
            INSERT INTO remember_tokens (user_id, selector, hashed_validator, expiry)
            VALUES (:user_id, :selector, :hashed_validator, :expiry)
        ");
        $stmt->execute([
            ':user_id'          => $userId,
            ':selector'         => $selector,
            ':hashed_validator' => $hashedValidator,
            ':expiry'           => $expiry
        ]);

        // Set remember_me cookie: "selector:validator"
        $cookieValue = $selector . ':' . $validator;
        setcookie('remember_me', $cookieValue, [
            'expires'  => time() + (86400 * 30),
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } catch (PDOException $e) {
        // Ignore token storage errors
    }
}

/**
 * Advanced Extension: Authenticate via Remember-Me cookie
 */
function check_remember_me_cookie($pdo) {
    if (isset($_COOKIE['remember_me']) && !is_logged_in()) {
        $parts = explode(':', $_COOKIE['remember_me']);
        if (count($parts) === 2) {
            $selector = $parts[0];
            $validator = $parts[1];

            try {
                $stmt = $pdo->prepare("
                    SELECT r.token_id, r.user_id, r.hashed_validator, u.name, u.email, u.role, u.last_login 
                    FROM remember_tokens r
                    JOIN users u ON r.user_id = u.user_id
                    WHERE r.selector = :selector AND r.expiry > NOW()
                    LIMIT 1
                ");
                $stmt->execute([':selector' => $selector]);
                $tokenData = $stmt->fetch();

                if ($tokenData && hash_equals($tokenData['hashed_validator'], hash('sha256', $validator))) {
                    // Valid remember-me token! Log user in
                    session_regenerate_id(true);
                    $_SESSION['user_id']       = $tokenData['user_id'];
                    $_SESSION['user_name']     = $tokenData['name'];
                    $_SESSION['user_email']    = $tokenData['email'];
                    $_SESSION['user_role']     = $tokenData['role'];
                    $_SESSION['last_login']    = $tokenData['last_login'];
                    $_SESSION['last_activity'] = time();

                    // Refresh last-login timestamp
                    update_last_login($pdo, $tokenData['user_id']);
                }
            } catch (PDOException $e) {
                // If token invalid, proceed unauthenticated
            }
        }
    }
}

/**
 * Clear Remember-Me Token and Cookie
 */
function clear_remember_me($pdo) {
    if (isset($_COOKIE['remember_me'])) {
        $parts = explode(':', $_COOKIE['remember_me']);
        if (count($parts) === 2) {
            $selector = $parts[0];
            try {
                $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE selector = :selector");
                $stmt->execute([':selector' => $selector]);
            } catch (PDOException $e) {
                // ignore
            }
        }
        setcookie('remember_me', '', time() - 3600, '/');
    }
}

/**
 * Log in a validated user
 */
function login_user($pdo, $user, $remember = false) {
    // Security Best Practice: Regenerate session ID to prevent Session Fixation
    session_regenerate_id(true);

    $_SESSION['user_id']       = $user['user_id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_role']     = $user['role'];
    $_SESSION['last_login']    = $user['last_login']; // previous login timestamp for display
    $_SESSION['last_activity'] = time();

    // Intermediate Extension: Record current last_login timestamp
    update_last_login($pdo, $user['user_id']);

    // Advanced Extension: Handle Remember Me
    if ($remember) {
        setup_remember_me($pdo, $user['user_id']);
    }
}

/**
 * Destroy current session securely
 */
function logout_current_session() {
    global $pdo;
    clear_remember_me($pdo);

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}
