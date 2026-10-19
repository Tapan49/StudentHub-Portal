<?php

require_once __DIR__ . '/auth.php';

// Enforce role-based access control for students
require_login('student');

$userName  = $_SESSION['user_name'] ?? 'Student';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole  = $_SESSION['user_role'] ?? 'student';
$lastLogin = $_SESSION['last_login'] ?? 'First login session';
$sessionId = session_id();

$feedbackError = '';
if (isset($_GET['error']) && $_GET['error'] === 'forbidden') {
    $feedbackError = 'Access Denied: You do not have administrator permissions to view that page.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Student Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dashboard-header {
            background: #ffffff;
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .user-badge {
            background-color: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        .meta-row {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
        }
        .meta-row:last-child { border-bottom: none; }
        .meta-label { font-weight: bold; color: #555; }
        .meta-value { color: #222; font-family: monospace; }
        .btn-logout {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
        }
        .btn-logout:hover { background-color: #c82333; }
        .btn-test {
            background-color: #ffc107;
            color: #212529;
            padding: 8px 14px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-block;
        }
        .btn-test:hover { background-color: #e0a800; }
        .msg-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 14px;
            border-radius: 6px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .action-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            transition: transform 0.2s;
        }
        .action-box:hover {
            transform: translateY(-3px);
            border-color: #007bff;
        }
        .action-box a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            font-size: 1.05rem;
        }
    </style>
</head>
<body>
<div class="page-wrapper">
<br>

<div class="grid-container">
    <div class="flex-row">
        <div class="flex-col">
            <div class="dashboard-header">
                <div>
                    <h1 style="margin: 0 0 5px 0;">STUDENT PORTAL</h1>
                    <span>Logged in as: <b><?php echo htmlspecialchars($userName); ?></b> (<?php echo htmlspecialchars($userEmail); ?>)</span>
                    <span class="user-badge"><?php echo htmlspecialchars($userRole); ?></span>
                </div>
                <div>
                    <a href="logout.php" class="btn-logout">LOGOUT ⎋</a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($feedbackError)): ?>
        <div class="flex-row">
            <div class="flex-col">
                <div class="msg-error">
                    <strong>⚠️ Unauthorized Access Attempt Blocked:</strong> <?php echo htmlspecialchars($feedbackError); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex-row">
        <div class="flex-col">
            <div class="info-card">
                <h2>🔐 Session & Account Details</h2>
                <div class="meta-row">
                    <span class="meta-label">User ID:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($_SESSION['user_id']); ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Active Role:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($userRole); ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">PHP Session ID:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($sessionId); ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Last Login:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($lastLogin ? $lastLogin : 'First Active Session'); ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Inactivity Timeout:</span>
                    <span class="meta-value">15 minutes (900 seconds)</span>
                </div>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ddd; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <a href="student_dashboard.php?test_timeout=1" class="btn-test">⏱ Test Session Timeout Simulation</a>
                    <a href="admin_dashboard.php" class="btn-test" style="background-color: #6c757d; color: white;">🛡 Try Accessing Admin Area (Test RBAC Block)</a>
                </div>
            </div>

            <div class="info-card">
                <h2>🎓 Student Portal Quick Navigation</h2>
                <div class="card-grid">
                    <div class="action-box">
                        <a href="../html/home.html">🏠 Portal Home</a>
                    </div>
                    <div class="action-box">
                        <a href="../html/events.html">📅 View Campus Events</a>
                    </div>
                    <div class="action-box">
                        <a href="contact.php">✉️ Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
</body>
</html>
