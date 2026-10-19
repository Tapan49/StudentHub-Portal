<?php

require_once __DIR__ . '/auth.php';

// If user is already logged in, redirect immediately to role-specific dashboard
if (is_logged_in()) {
    if (strtolower($_SESSION['user_role']) === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        header("Location: student_dashboard.php");
        exit();
    }
}

// Generate CSRF token for login form
if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$successMessage = '';

// Handle URL query parameters for feedback messages
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $errorMessage = 'Unauthorized Access! Please log in first to view protected pages.';
    } elseif ($_GET['error'] === 'timeout') {
        $errorMessage = 'Your session has timed out due to inactivity. Please log in again.';
    } elseif ($_GET['error'] === 'forbidden') {
        $errorMessage = 'Access Denied! You do not have permissions to access that page.';
    }
} elseif (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'logged_out') {
        $successMessage = 'You have been successfully and securely logged out.';
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['login_csrf'] ?? '', $csrfToken)) {
        $errorMessage = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember_me']);

        if (empty($email) || empty($password)) {
            $errorMessage = 'Please enter both your email address and password.';
        } else {
            try {
                // Fetch user from users table
                $stmt = $pdo->prepare("SELECT user_id, name, email, password_hash, role, status, last_login FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                // Fallback: If not in users table, check students table (from Practical 9)
                if (!$user) {
                    $stmtStudent = $pdo->prepare("SELECT student_id, name, email, password_hash, status FROM students WHERE email = :email LIMIT 1");
                    $stmtStudent->execute([':email' => $email]);
                    $student = $stmtStudent->fetch();

                    if ($student && password_verify($password, $student['password_hash'])) {
                        // Sync student into users table as 'student' role
                        $insertUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, 'student', ?)");
                        $insertUser->execute([$student['name'], $student['email'], $student['password_hash'], $student['status'] ?? 'Active']);
                        $newUserId = $pdo->lastInsertId();

                        $user = [
                            'user_id'       => $newUserId,
                            'name'          => $student['name'],
                            'email'         => $student['email'],
                            'password_hash' => $student['password_hash'],
                            'role'          => 'student',
                            'status'        => $student['status'] ?? 'Active',
                            'last_login'    => null
                        ];
                    }
                }

                // Verify Password using password_verify()
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Check if account is active (Intermediate Extension)
                    if (($user['status'] ?? 'Active') === 'Inactive') {
                        $errorMessage = 'Access Denied: Your account has been deactivated by an administrator.';
                    } else {
                        // Successful Authentication!
                        login_user($pdo, $user, $remember);

                        // Refresh CSRF token
                        $_SESSION['login_csrf'] = bin2hex(random_bytes(32));

                        // Role-Based Redirection (RBAC)
                        if (strtolower($user['role']) === 'admin') {
                            header("Location: admin_dashboard.php");
                        } else {
                            header("Location: student_dashboard.php");
                        }
                        exit();
                    }
                } else {
                    // Authentication Failure (generic message to avoid email enumeration)
                    $errorMessage = 'Invalid email or password. Please check your credentials and try again.';
                }
            } catch (PDOException $e) {
                $errorMessage = 'Database Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Portal - Secure Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .msg-box { padding: 14px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; }
        .msg-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .msg-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .demo-credentials {
            background-color: #e8f4fd;
            border-left: 4px solid #1976d2;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            color: #0d47a1;
            border-radius: 4px;
        }
        .demo-credentials strong { display: block; margin-bottom: 4px; }
        .login-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .checkbox-container input { width: auto; cursor: pointer; }
    </style>
</head>
<body>
<div class="page-wrapper">
<br><br>

<div class="grid-container">
    <div class="flex-row">
        <div class="flex-col">
            <h1>SECURE PORTAL LOGIN</h1>
        </div>
        <div class="flex-col" style="text-align: right;">
            <a href="../html/home.html"><b>HOME</b></a> &nbsp;|&nbsp;
            <a href="register.php"><b>REGISTER</b></a>
        </div>
    </div>

    <div class="flex-row">
        <div class="flex-col login-card">

            <!-- Demo helper card for evaluator testing -->
            <div class="demo-credentials">
                <strong>🔑 Demo Login Credentials for Evaluation:</strong>
                <div>• <b>Admin Account:</b> <code>admin@studenthub.com</code> / Password: <code>Admin@123</code> (Role: <i>admin</i>)</div>
                <div>• <b>Student Account:</b> <code>john@example.com</code> / Password: <code>Student@123</code> (Role: <i>student</i>)</div>
            </div>

            <!-- Error Alerts (Failed Login, Timeout, Unauthorized Access) -->
            <?php if (!empty($errorMessage)): ?>
                <div class="msg-box msg-error" id="errorAlert">
                    <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <!-- Success Alerts (Logout Success) -->
            <?php if (!empty($successMessage)): ?>
                <div class="msg-box msg-success" id="successAlert">
                    <strong>✓ Success:</strong> <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['login_csrf']); ?>">

                <div class="grid-container">
                    <div class="flex-row">
                        <div class="flex-col"><b>EMAIL ADDRESS:</b></div>
                        <div class="flex-col">
                            <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="Enter your registered email">
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col"><b>PASSWORD:</b></div>
                        <div class="flex-col">
                            <input type="password" name="password" required placeholder="Enter your password">
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col"></div>
                        <div class="flex-col">
                            <label class="checkbox-container">
                                <input type="checkbox" name="remember_me" id="remember_me">
                                <span>Remember me on this browser (Token-based)</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col">
                            <br>
                            <input type="submit" value="  LOG IN TO PORTAL  ">
                        </div>
                    </div>
                </div>
            </form>

            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #eee;">

            <div style="text-align: center;">
                <p>Don't have an account yet? <a href="register.php"><b>Create Student Account</b></a></p>
                <p><small style="color: #666;">StudentHub Portal • Secure Authentication System</small></p>
            </div>
        </div>
    </div>
</div>

</div>
</body>
</html>
