<?php

require_once __DIR__ . '/auth.php';

// Strict RBAC: Admin only
require_login('admin');

$adminName  = $_SESSION['user_name'] ?? 'System Administrator';
$adminEmail = $_SESSION['user_email'] ?? '';
$adminRole  = $_SESSION['user_role'] ?? 'admin';
$lastLogin  = $_SESSION['last_login'] ?? 'Active session';
$sessionId  = session_id();

$message = '';
$messageType = 'success';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user_id'])) {
    $targetUserId = (int)$_POST['toggle_user_id'];
    
    // Prevent self-deactivation
    if ($targetUserId === (int)$_SESSION['user_id']) {
        $message = "Security restriction: You cannot deactivate your own administrative account.";
        $messageType = 'error';
    } else {
        try {
            // Fetch current status
            $uStmt = $pdo->prepare("SELECT name, email, status FROM users WHERE user_id = ?");
            $uStmt->execute([$targetUserId]);
            $targetUser = $uStmt->fetch();

            if ($targetUser) {
                $newStatus = ($targetUser['status'] === 'Active') ? 'Inactive' : 'Active';
                $upStmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
                $upStmt->execute([$newStatus, $targetUserId]);

                // Record in audit log
                record_audit_log($pdo, 'STATUS_TOGGLE', 'users', $targetUserId, "Toggled status of '{$targetUser['name']}' ({$targetUser['email']}) to $newStatus");

                $message = "Status for user '{$targetUser['name']}' updated to {$newStatus}.";
                $messageType = 'success';
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$metrics = [
    'total_users'     => 0,
    'active_students' => 0,
    'total_events'    => 0,
    'total_regs'      => 0
];

try {
    $metrics['total_users']     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $metrics['active_students'] = $pdo->query("SELECT COUNT(*) FROM students WHERE status != 'Deleted'")->fetchColumn();
    $metrics['total_events']    = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $metrics['total_regs']      = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
} catch (PDOException $e) {
    // default to 0
}


$categoryStats = [];
try {
    $catStmt = $pdo->query("SELECT category, COUNT(*) as count FROM events GROUP BY category");
    $categoryStats = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categoryStats = [];
}

$chartLabels = [];
$chartCounts = [];
foreach ($categoryStats as $row) {
    $chartLabels[] = $row['category'] ?? 'General';
    $chartCounts[] = (int)$row['count'];
}


$latestRegistrations = [];
try {
    $regQuery = "
        SELECT r.registration_id, r.registration_date, 
               s.name as student_name, s.enrollment_no, s.course,
               e.title as event_title, e.category as event_category
        FROM registrations r
        JOIN students s ON r.student_id = s.student_id
        JOIN events e ON r.event_id = e.event_id
        ORDER BY r.registration_date DESC
        LIMIT 6
    ";
    $latestRegistrations = $pdo->query($regQuery)->fetchAll();
} catch (PDOException $e) {
    $latestRegistrations = [];
}

// -------------------------------------------------------------
// 5. USER ACCOUNTS LIST (FOR STATUS TOGGLE)
// -------------------------------------------------------------
$userAccounts = [];
try {
    $userAccounts = $pdo->query("SELECT user_id, name, email, role, status, last_login, created_at FROM users ORDER BY user_id ASC LIMIT 10")->fetchAll();
} catch (PDOException $e) {
    $userAccounts = [];
}


$auditLogs = [];
try {
    $auditLogs = $pdo->query("SELECT * FROM audit_logs ORDER BY log_id DESC LIMIT 8")->fetchAll();
} catch (PDOException $e) {
    $auditLogs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Admin Dashboard & Analytics</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- Chart.js for Advanced Analytics Chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .admin-nav {
            background: #1a252f;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .admin-nav a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 0.88rem;
            font-weight: 500;
            transition: background 0.2s;
        }
        .admin-nav a:hover, .admin-nav a.active {
            background: #34495e;
            color: #ffffff;
        }
        .admin-nav .nav-logout {
            margin-left: auto;
            background: #e74c3c;
            color: white;
            font-weight: bold;
        }
        .admin-nav .nav-logout:hover { background: #c0392b; }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 5px solid #3498db;
        }
        .stat-card h3 {
            font-size: 2.2rem;
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        .stat-card p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: bold;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        .card {
            background: #ffffff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 25px;
        }
        .card h2 {
            margin-top: 0;
            font-size: 1.25rem;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }
        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f2f6;
            text-align: left;
        }
        th {
            background: #f8f9fa;
            color: #555;
            font-weight: 600;
        }
        tr:hover { background-color: #fafbfc; }

        .badge-active { background: #2ecc71; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .badge-inactive { background: #e74c3c; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .badge-admin { background: #9b59b6; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .badge-student { background: #3498db; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }

        .btn-toggle {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.78rem;
            font-weight: bold;
            cursor: pointer;
            border: none;
            transition: opacity 0.2s;
        }
        .btn-toggle-deactivate { background: #f39c12; color: white; }
        .btn-toggle-activate { background: #27ae60; color: white; }
        .btn-toggle:hover { opacity: 0.9; }

        .msg-box { padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; }
        .msg-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .meta-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 0.88rem; }
        .meta-row:last-child { border-bottom: none; }
        .meta-label { font-weight: bold; color: #666; }
        .meta-val { font-family: monospace; color: #333; }
    </style>
</head>
<body>
<div class="page-wrapper">
<br>

<div class="grid-container">
    <!-- Header -->
    <div class="flex-row">
        <div class="flex-col">
            <h1 style="margin-bottom: 5px;">ADMINISTRATOR CONTROL PANEL</h1>
            <p style="color: #666; margin-top: 0;">Role-Based Administration, Live Analytics & Audit Trail</p>
        </div>
        <div class="flex-col" style="text-align: right; font-size: 0.9rem;">
            Welcome, <b><?php echo htmlspecialchars($adminName); ?></b>
            <span class="badge-admin"><?php echo strtoupper(htmlspecialchars($adminRole)); ?></span>
        </div>
    </div>

    <!-- Dynamic Role-Based Navigation Menu -->
    <div class="flex-row">
        <div class="flex-col">
            <nav class="admin-nav">
                <a href="admin_dashboard.php" class="active">📊 Overview</a>
                <a href="student_manage.php">🎓 Student Management</a>
                <a href="event_manage.php">📅 Event Management</a>
                <a href="events_live.php">⚡ Live AJAX Events</a>
                <a href="test_integration.php">🩺 System Health & Tests</a>
                <a href="view_contacts.php">✉️ Inquiries</a>
                <a href="test_db.php">🗄️ Database Test</a>
                <a href="../html/home.html">🏠 Public Portal</a>
                <a href="logout.php" class="nav-logout">LOGOUT ⎋</a>
            </nav>
        </div>
    </div>

    <!-- Alert Banner -->
    <?php if (!empty($message)): ?>
        <div class="flex-row">
            <div class="flex-col">
                <div class="msg-box msg-<?php echo $messageType; ?>">
                    <?php echo ($messageType === 'success' ? '✓ ' : '⚠️ ') . htmlspecialchars($message); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Real-time Metric Cards (SQL Aggregations) -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="stat-grid">
                <div class="stat-card" style="border-left-color: #3498db;">
                    <div>
                        <h3><?php echo (int)$metrics['total_users']; ?></h3>
                        <p>Total User Accounts</p>
                    </div>
                    <div class="stat-icon">👥</div>
                </div>

                <div class="stat-card" style="border-left-color: #2ecc71;">
                    <div>
                        <h3><?php echo (int)$metrics['active_students']; ?></h3>
                        <p>Active Students</p>
                    </div>
                    <div class="stat-icon">🎓</div>
                </div>

                <div class="stat-card" style="border-left-color: #9b59b6;">
                    <div>
                        <h3><?php echo (int)$metrics['total_events']; ?></h3>
                        <p>Campus Events</p>
                    </div>
                    <div class="stat-icon">📅</div>
                </div>

                <div class="stat-card" style="border-left-color: #e67e22;">
                    <div>
                        <h3><?php echo (int)$metrics['total_regs']; ?></h3>
                        <p>Event Registrations</p>
                    </div>
                    <div class="stat-icon">📝</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid (Analytics Chart & Session Details) -->
    <div class="dashboard-grid">
        <!-- Analytics Chart (Advanced Extension) -->
        <div class="card">
            <h2>📈 Event Distribution by Category</h2>
            <div style="height: 260px; position: relative;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <!-- Session & Security Details -->
        <div class="card">
            <h2>🔐 Security & Active Session</h2>
            <div class="meta-row">
                <span class="meta-label">User ID:</span>
                <span class="meta-val"><?php echo (int)$_SESSION['user_id']; ?></span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Admin Email:</span>
                <span class="meta-val"><?php echo htmlspecialchars($adminEmail); ?></span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Privilege Level:</span>
                <span class="meta-val">Full Administrator (RBAC)</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Session ID:</span>
                <span class="meta-val"><?php echo htmlspecialchars(substr($sessionId, 0, 14)) . '...'; ?></span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Last Login:</span>
                <span class="meta-val"><?php echo htmlspecialchars($lastLogin ? $lastLogin : 'Active'); ?></span>
            </div>
            <div style="margin-top: 15px; padding-top: 12px; border-top: 1px dashed #eee;">
                <a href="admin_dashboard.php?test_timeout=1" style="color: #e67e22; font-size: 0.85rem; text-decoration: none; font-weight: bold;">⏱ Test Session Inactivity Timeout</a>
            </div>
        </div>
    </div>

    <!-- User Management with Active/Inactive Toggle (Intermediate Extension) -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="card">
                <h2>
                    <span>👥 User Access Management & Status Control</span>
                    <span style="font-size: 0.85rem; font-weight: normal; color: #666;">One-Click Active/Inactive Toggle</span>
                </h2>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th style="text-align: center;">Account Toggle Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userAccounts as $u): ?>
                            <tr>
                                <td><b>#<?php echo (int)$u['user_id']; ?></b></td>
                                <td><?php echo htmlspecialchars($u['name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge-admin">Admin</span>
                                    <?php else: ?>
                                        <span class="badge-student">Student</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($u['status'] ?? 'Active') === 'Active'): ?>
                                        <span class="badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo !empty($u['last_login']) ? htmlspecialchars($u['last_login']) : '<span style="color:#999;">Never</span>'; ?></td>
                                <td style="text-align: center;">
                                    <?php if ((int)$u['user_id'] !== (int)$_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Change status for user <?php echo htmlspecialchars(addslashes($u['name'])); ?>?');">
                                            <input type="hidden" name="toggle_user_id" value="<?php echo (int)$u['user_id']; ?>">
                                            <?php if (($u['status'] ?? 'Active') === 'Active'): ?>
                                                <button type="submit" class="btn-toggle btn-toggle-deactivate">Deactivate 🚫</button>
                                            <?php else: ?>
                                                <button type="submit" class="btn-toggle btn-toggle-activate">Activate ✓</button>
                                            <?php endif; ?>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #888; font-size: 0.8rem;">Current Account</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Registrations Feed -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="card">
                <h2>📝 Recent Event Registrations</h2>
                <?php if (empty($latestRegistrations)): ?>
                    <p style="color: #777;">No event registrations recorded yet.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reg ID</th>
                                    <th>Student Name</th>
                                    <th>Enrollment</th>
                                    <th>Course</th>
                                    <th>Event Title</th>
                                    <th>Category</th>
                                    <th>Registration Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latestRegistrations as $r): ?>
                                <tr>
                                    <td><b>#<?php echo (int)$r['registration_id']; ?></b></td>
                                    <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($r['enrollment_no']); ?></code></td>
                                    <td><?php echo htmlspecialchars($r['course']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($r['event_title']); ?></strong></td>
                                    <td><span style="background: #eef2f7; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;"><?php echo htmlspecialchars($r['event_category']); ?></span></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($r['registration_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Audit Logs & Traceability -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="card" style="border-left: 4px solid #34495e;">
                <h2>🛡️ Security Audit Trail (Activity Logs)</h2>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Timestamp</th>
                                <th>Operator</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td><b>#<?php echo (int)$log['log_id']; ?></b></td>
                                <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                                <td><code><?php echo htmlspecialchars($log['action']); ?></code></td>
                                <td><?php echo htmlspecialchars($log['entity'] . ' #' . $log['entity_id']); ?></td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<!-- Chart.js Initialization for Analytics -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    const labels = <?php echo json_encode($chartLabels); ?>;
    const dataCounts = <?php echo json_encode($chartCounts); ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.length ? labels : ['Technical', 'Cultural', 'Sports', 'Academic'],
            datasets: [{
                label: 'Active Events by Category',
                data: dataCounts.length ? dataCounts : [2, 1, 1, 1],
                backgroundColor: [
                    'rgba(52, 152, 219, 0.7)',
                    'rgba(155, 89, 182, 0.7)',
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(241, 196, 15, 0.7)'
                ],
                borderColor: [
                    'rgba(52, 152, 219, 1)',
                    'rgba(155, 89, 182, 1)',
                    'rgba(46, 204, 113, 1)',
                    'rgba(241, 196, 15, 1)'
                ],
                borderWidth: 1.5,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
</body>
</html>
