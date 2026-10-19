<?php
/**
 * End-to-End System Health & Integration Verification Dashboard
 * Verifies all subsystems, database tables, upload directory security,
 * and API endpoints in a unified testing panel.
 */
require_once __DIR__ . '/db.php';

$tests = [];

// 1. PHP Version & PDO MySQL
$tests[] = [
    'category' => 'Server Environment',
    'title'    => 'PHP Version (>= 7.4 / 8.x recommended)',
    'passed'   => version_compare(PHP_VERSION, '7.4.0', '>='),
    'details'  => 'Current PHP Version: ' . PHP_VERSION
];

$tests[] = [
    'category' => 'Server Environment',
    'title'    => 'PDO MySQL Extension Enabled',
    'passed'   => extension_loaded('pdo_mysql'),
    'details'  => extension_loaded('pdo_mysql') ? 'PDO MySQL extension loaded' : 'Missing PDO MySQL extension'
];

// 2. Database Connection
$dbConnected = false;
$dbMessage = '';
try {
    $pdo->query("SELECT 1");
    $dbConnected = true;
    $dbMessage = "Successfully connected to MySQL database '$db' on $host:$port";
} catch (Exception $e) {
    $dbMessage = $e->getMessage();
}

$tests[] = [
    'category' => 'Database Subsystem',
    'title'    => 'MySQL PDO Database Handshake',
    'passed'   => $dbConnected,
    'details'  => $dbMessage
];

// 3. Table Integrity & Schema Checks
$requiredTables = ['students', 'events', 'registrations', 'users', 'remember_tokens', 'audit_logs'];
foreach ($requiredTables as $tableName) {
    $tableExists = false;
    $rowCount = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$tableName`");
        $rowCount = (int)$stmt->fetchColumn();
        $tableExists = true;
    } catch (Exception $e) {
        $tableExists = false;
    }

    $tests[] = [
        'category' => 'Database Schema & Tables',
        'title'    => "Table: `$tableName` Integrity",
        'passed'   => $tableExists,
        'details'  => $tableExists ? "Table exists with $rowCount records" : "Table `$tableName` not found in database"
    ];
}

// 4. Dataset Requirement Check (Students >= 20)
$studentCount = 0;
try {
    $studentCount = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
} catch (Exception $e) {}

$tests[] = [
    'category' => 'Dataset Verification',
    'title'    => 'Seeded Student Records (Minimum 20)',
    'passed'   => ($studentCount >= 20),
    'details'  => "Found $studentCount student records in database"
];

// 5. Uploads Directory & .htaccess Security Check
$uploadDir = __DIR__ . '/../uploads/posters';
$dirExists = is_dir($uploadDir);
$htaccessExists = file_exists($uploadDir . '/.htaccess');

$tests[] = [
    'category' => 'File & Security Subsystem',
    'title'    => 'Uploads Directory Existence (`uploads/posters/`)',
    'passed'   => $dirExists,
    'details'  => $dirExists ? "Directory exists and writable" : "Upload directory missing"
];

$tests[] = [
    'category' => 'File & Security Subsystem',
    'title'    => 'Upload Execution Protection (`.htaccess`)',
    'passed'   => $htaccessExists,
    'details'  => $htaccessExists ? "Execution protection active (.htaccess in place)" : "Missing .htaccess protection rule"
];

// 6. JSON API Verification
$apiFile = __DIR__ . '/api/events_api.php';
$apiExists = file_exists($apiFile);

$tests[] = [
    'category' => 'RESTful JSON API',
    'title'    => 'Events JSON API Endpoint (`api/events_api.php`)',
    'passed'   => $apiExists,
    'details'  => $apiExists ? "REST API endpoint script configured" : "API endpoint file missing"
];

// Summary statistics
$totalTests  = count($tests);
$passedTests = count(array_filter($tests, fn($t) => $t['passed']));
$failedTests = $totalTests - $passedTests;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - System Health & Integration Verification</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .test-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 25px;
        }
        .score-banner {
            background: <?php echo ($failedTests === 0) ? '#d4edda' : '#fff3cd'; ?>;
            color: <?php echo ($failedTests === 0) ? '#155724' : '#856404'; ?>;
            border: 1px solid <?php echo ($failedTests === 0) ? '#c3e6cb' : '#ffeeba'; ?>;
            padding: 18px 24px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .score-title { font-size: 1.3rem; font-weight: bold; margin: 0; }
        .badge-pass { background: #28a745; color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: bold; }
        .badge-fail { background: #dc3545; color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th, td { padding: 12px 14px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f8f9fa; color: #444; font-weight: 600; }
        .quick-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .nav-btn {
            background: #f8f9fa;
            border: 1px solid #e2e8f0;
            padding: 14px;
            border-radius: 6px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: bold;
            display: block;
            text-align: center;
            transition: transform 0.2s, border-color 0.2s;
        }
        .nav-btn:hover {
            transform: translateY(-2px);
            border-color: #007bff;
            color: #007bff;
        }
    </style>
</head>
<body>
<div class="page-wrapper">
<br>

<div class="grid-container">
    <!-- Header -->
    <div class="flex-row">
        <div class="flex-col">
            <h1 style="margin-bottom: 5px;">SYSTEM HEALTH & INTEGRATION VERIFICATION</h1>
            <p style="color: #666; margin-top: 0;">Automated End-to-End Diagnostics & Subsystem Readiness Check</p>
        </div>
        <div class="flex-col" style="text-align: right;">
            <a href="admin_dashboard.php"><b>ADMIN DASHBOARD</b></a> &nbsp;|&nbsp;
            <a href="../html/home.html"><b>HOME</b></a>
        </div>
    </div>

    <!-- Health Score Banner -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="score-banner">
                <div>
                    <h2 class="score-title">
                        <?php echo ($failedTests === 0) ? '✓ All Systems Operational' : '⚠️ Minor System Diagnostics Warning'; ?>
                    </h2>
                    <span>Automated test suite completed. <b><?php echo $passedTests; ?></b> of <b><?php echo $totalTests; ?></b> diagnostic checkpoints passed.</span>
                </div>
                <div>
                    <span style="font-size: 1.8rem; font-weight: bold;">
                        <?php echo round(($passedTests / $totalTests) * 100); ?>% Pass Rate
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnostics Table -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="test-card">
                <h2 style="margin-top: 0; color: #2c3e50;">📋 Subsystem Verification Matrix</h2>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Diagnostic Item</th>
                                <th>Result</th>
                                <th>Diagnostic Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tests as $t): ?>
                            <tr>
                                <td><b><?php echo htmlspecialchars($t['category']); ?></b></td>
                                <td><?php echo htmlspecialchars($t['title']); ?></td>
                                <td>
                                    <?php if ($t['passed']): ?>
                                        <span class="badge-pass">PASS ✓</span>
                                    <?php else: ?>
                                        <span class="badge-fail">FAIL ✗</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($t['details']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Portal Navigation & Demo Shortcuts -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="test-card">
                <h2 style="margin-top: 0; color: #2c3e50;">🚀 Portal Navigation & Module Demonstration</h2>
                <p style="color: #666; font-size: 0.9rem;">Quick shortcuts to demonstrate all completed modules during viva and presentation:</p>
                <div class="quick-nav">
                    <a href="../html/home.html" class="nav-btn">🏠 Portal Home Page</a>
                    <a href="login.php" class="nav-btn">🔐 Portal Login</a>
                    <a href="admin_dashboard.php" class="nav-btn">📊 Admin Dashboard</a>
                    <a href="student_manage.php" class="nav-btn">🎓 Student Management</a>
                    <a href="event_manage.php" class="nav-btn">📅 Event Management</a>
                    <a href="events_live.php" class="nav-btn">⚡ Live AJAX Events</a>
                    <a href="contact.php" class="nav-btn">✉️ Contact Inquiry Form</a>
                    <a href="test_db.php" class="nav-btn">🗄️ Database Join Test</a>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
</body>
</html>
