<?php

require_once __DIR__ . '/auth.php';

// Enforce login (admin role preferred, but allows authenticated sessions)
if (!is_logged_in()) {
    header("Location: login.php?error=unauthorized");
    exit();
}

$message = '';
$messageType = 'success'; // 'success' or 'error'


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE STUDENT (INSERT)
    if ($action === 'create') {
        $name          = trim(htmlspecialchars(strip_tags($_POST['name'] ?? '')));
        $enrollment_no = trim(htmlspecialchars(strip_tags($_POST['enrollment_no'] ?? '')));
        $email         = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $mobile        = trim(htmlspecialchars(strip_tags($_POST['mobile'] ?? '')));
        $course        = trim(htmlspecialchars(strip_tags($_POST['course'] ?? '')));
        $year          = (int)($_POST['year'] ?? 1);
        $gender        = trim(htmlspecialchars(strip_tags($_POST['gender'] ?? 'Male')));
        $status        = trim(htmlspecialchars(strip_tags($_POST['status'] ?? 'Active')));

        if (empty($name) || empty($enrollment_no) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please provide valid Name, Enrollment Number, and Email.';
            $messageType = 'error';
        } else {
            try {
                // Check duplicate enrollment or email
                $dupStmt = $pdo->prepare("SELECT student_id FROM students WHERE email = ? OR enrollment_no = ? LIMIT 1");
                $dupStmt->execute([$email, $enrollment_no]);
                if ($dupStmt->fetch()) {
                    $message = 'Error: A student with this Email or Enrollment Number already exists.';
                    $messageType = 'error';
                } else {
                    $defaultHash = password_hash('Student@123', PASSWORD_DEFAULT);
                    $insStmt = $pdo->prepare("
                        INSERT INTO students (name, enrollment_no, email, mobile, course, year, gender, password_hash, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insStmt->execute([$name, $enrollment_no, $email, $mobile, $course, $year, $gender, $defaultHash, $status]);

                    // Also sync to users table
                    $userSync = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'student') ON DUPLICATE KEY UPDATE name=VALUES(name)");
                    $userSync->execute([$name, $email, $defaultHash]);

                    $message = "Student '$name' ($enrollment_no) added successfully!";
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                $message = "Database Error: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    // UPDATE STUDENT
    elseif ($action === 'update') {
        $student_id    = (int)($_POST['student_id'] ?? 0);
        $name          = trim(htmlspecialchars(strip_tags($_POST['name'] ?? '')));
        $enrollment_no = trim(htmlspecialchars(strip_tags($_POST['enrollment_no'] ?? '')));
        $email         = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $mobile        = trim(htmlspecialchars(strip_tags($_POST['mobile'] ?? '')));
        $course        = trim(htmlspecialchars(strip_tags($_POST['course'] ?? '')));
        $year          = (int)($_POST['year'] ?? 1);
        $gender        = trim(htmlspecialchars(strip_tags($_POST['gender'] ?? 'Male')));
        $status        = trim(htmlspecialchars(strip_tags($_POST['status'] ?? 'Active')));

        if ($student_id <= 0 || empty($name) || empty($enrollment_no) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid student record details provided.';
            $messageType = 'error';
        } else {
            try {
                // Check duplicate email/enrollment among OTHER students
                $dupStmt = $pdo->prepare("SELECT student_id FROM students WHERE (email = ? OR enrollment_no = ?) AND student_id != ? LIMIT 1");
                $dupStmt->execute([$email, $enrollment_no, $student_id]);
                if ($dupStmt->fetch()) {
                    $message = 'Error: Another student already uses this Email or Enrollment Number.';
                    $messageType = 'error';
                } else {
                    $upStmt = $pdo->prepare("
                        UPDATE students 
                        SET name = ?, enrollment_no = ?, email = ?, mobile = ?, course = ?, year = ?, gender = ?, status = ?
                        WHERE student_id = ?
                    ");
                    $upStmt->execute([$name, $enrollment_no, $email, $mobile, $course, $year, $gender, $status, $student_id]);

                    $message = "Student #$student_id ($name) updated successfully!";
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                $message = "Database Error: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}


$getAction = $_GET['action'] ?? '';
$targetId = (int)($_GET['id'] ?? 0);

if ($targetId > 0) {
    // Advanced Extension: Soft Delete
    if ($getAction === 'soft_delete') {
        try {
            $delStmt = $pdo->prepare("UPDATE students SET status = 'Deleted' WHERE student_id = ?");
            $delStmt->execute([$targetId]);
            $message = "Student #$targetId has been soft-deleted (status marked as Deleted).";
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = "Error soft-deleting student: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    // Advanced Extension: Restore soft-deleted student
    elseif ($getAction === 'restore') {
        try {
            $resStmt = $pdo->prepare("UPDATE students SET status = 'Active' WHERE student_id = ?");
            $resStmt->execute([$targetId]);
            $message = "Student #$targetId has been restored to Active status.";
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = "Error restoring student: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    // Hard / Permanent Delete
    elseif ($getAction === 'hard_delete') {
        try {
            $hdelStmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
            $hdelStmt->execute([$targetId]);
            $message = "Student #$targetId permanently deleted from the database.";
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = "Error deleting student: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}


$searchKeyword = trim($_GET['search'] ?? '');
$filterCourse  = trim($_GET['course'] ?? '');
$filterStatus  = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM students WHERE 1=1";
$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?)";
    $term = "%$searchKeyword%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($filterCourse)) {
    $sql .= " AND course = ?";
    $params[] = $filterCourse;
}

if (!empty($filterStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
} else {
    // By default, hide deleted records unless explicitly filtered
    if ($filterStatus !== 'Deleted' && empty($filterStatus)) {
        // Show Active & Inactive by default
        $sql .= " AND status != 'Deleted'";
    }
}

$sql .= " ORDER BY student_id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $students = [];
    $message = "Error querying students: " . $e->getMessage();
    $messageType = 'error';
}

// Check edit request to prefill edit form
$editStudent = null;
if ($getAction === 'edit' && $targetId > 0) {
    $eStmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $eStmt->execute([$targetId]);
    $editStudent = $eStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Student Management</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .msg-box { padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; }
        .msg-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .card { background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 25px; }
        .filter-bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
        .filter-bar input, .filter-bar select { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 0.9rem; }
        .btn-primary { background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background: #0056b3; }
        .btn-success { background: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; text-decoration: none; }
        .btn-danger { background: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; text-decoration: none; }
        .btn-restore { background: #17a2b8; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; background: white; margin-top: 10px; }
        th, td { border: 1px solid #e9ecef; padding: 10px 12px; text-align: left; }
        th { background: #2c3e50; color: white; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        tr:hover { background-color: #f1f4f8; }
        .badge-active { background: #28a745; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .badge-inactive { background: #ffc107; color: #212529; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .badge-deleted { background: #dc3545; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 4px; font-size: 0.85rem; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    </style>
</head>
<body>
<div class="page-wrapper">
<br>

<div class="grid-container">
    <!-- Header -->
    <div class="flex-row">
        <div class="flex-col">
            <h1>STUDENT MANAGEMENT</h1>
            <p>Add, View, Edit, Delete, Search & Filter Student Records</p>
        </div>
        <div class="flex-col" style="text-align: right;">
            <a href="admin_dashboard.php"><b>ADMIN DASHBOARD</b></a> &nbsp;|&nbsp;
            <a href="student_manage.php#studentForm" class="btn-primary">+ ADD NEW STUDENT</a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
        <div class="flex-row">
            <div class="flex-col">
                <div class="msg-box msg-<?php echo $messageType; ?>">
                    <?php echo ($messageType === 'success' ? '✓ ' : '⚠️ ') . htmlspecialchars($message); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add / Edit Student Form Card -->
    <div class="flex-row" id="studentForm">
        <div class="flex-col">
            <div class="card" style="border-top: 4px solid <?php echo $editStudent ? '#ffc107' : '#007bff'; ?>;">
                <h2><?php echo $editStudent ? '✏️ Edit Student Record (#' . $editStudent['student_id'] . ')' : '➕ Add New Student Record'; ?></h2>
                <form action="student_manage.php" method="POST">
                    <input type="hidden" name="action" value="<?php echo $editStudent ? 'update' : 'create'; ?>">
                    <?php if ($editStudent): ?>
                        <input type="hidden" name="student_id" value="<?php echo (int)$editStudent['student_id']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($editStudent['name'] ?? ''); ?>" placeholder="e.g. Ramesh Patel">
                        </div>
                        <div class="form-group">
                            <label>Enrollment No *</label>
                            <input type="text" name="enrollment_no" required value="<?php echo htmlspecialchars($editStudent['enrollment_no'] ?? ''); ?>" placeholder="e.g. EN202650">
                        </div>
                        <div class="form-group">
                            <label>Student Email *</label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($editStudent['email'] ?? ''); ?>" placeholder="e.g. ramesh@example.com">
                        </div>
                        <div class="form-group">
                            <label>Mobile Number</label>
                            <input type="tel" name="mobile" value="<?php echo htmlspecialchars($editStudent['mobile'] ?? ''); ?>" placeholder="e.g. 9876543210">
                        </div>
                        <div class="form-group">
                            <label>Course *</label>
                            <select name="course" required>
                                <option value="BTech" <?php echo (($editStudent['course'] ?? '') === 'BTech') ? 'selected' : ''; ?>>B.Tech</option>
                                <option value="MCA" <?php echo (($editStudent['course'] ?? '') === 'MCA') ? 'selected' : ''; ?>>MCA</option>
                                <option value="BCA" <?php echo (($editStudent['course'] ?? '') === 'BCA') ? 'selected' : ''; ?>>BCA</option>
                                <option value="MTech" <?php echo (($editStudent['course'] ?? '') === 'MTech') ? 'selected' : ''; ?>>M.Tech</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Year *</label>
                            <select name="year" required>
                                <option value="1" <?php echo (($editStudent['year'] ?? 1) == 1) ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2" <?php echo (($editStudent['year'] ?? 1) == 2) ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3" <?php echo (($editStudent['year'] ?? 1) == 3) ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4" <?php echo (($editStudent['year'] ?? 1) == 4) ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="Male" <?php echo (($editStudent['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (($editStudent['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (($editStudent['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status (Soft Delete Control)</label>
                            <select name="status">
                                <option value="Active" <?php echo (($editStudent['status'] ?? 'Active') === 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo (($editStudent['status'] ?? '') === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Deleted" <?php echo (($editStudent['status'] ?? '') === 'Deleted') ? 'selected' : ''; ?>>Deleted (Soft-Deleted)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="<?php echo $editStudent ? 'btn-warning' : 'btn-primary'; ?>" style="padding: 10px 20px; font-size: 0.95rem;">
                            <?php echo $editStudent ? '💾 UPDATE STUDENT RECORD' : '➕ SAVE STUDENT RECORD'; ?>
                        </button>
                        <?php if ($editStudent): ?>
                            <a href="student_manage.php" style="margin-left: 15px; color: #666; text-decoration: none;">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Search, Filter & CSV Export Toolbar -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="card" style="padding: 18px;">
                <form action="student_manage.php" method="GET" class="filter-bar">
                    <!-- Search Input -->
                    <input type="text" name="search" value="<?php echo htmlspecialchars($searchKeyword); ?>" placeholder="🔍 Search Name, Email, Enrollment..." style="min-width: 260px;">

                    <!-- Filter by Course -->
                    <select name="course">
                        <option value="">All Courses</option>
                        <option value="BTech" <?php echo ($filterCourse === 'BTech') ? 'selected' : ''; ?>>B.Tech</option>
                        <option value="MCA" <?php echo ($filterCourse === 'MCA') ? 'selected' : ''; ?>>MCA</option>
                        <option value="BCA" <?php echo ($filterCourse === 'BCA') ? 'selected' : ''; ?>>BCA</option>
                        <option value="MTech" <?php echo ($filterCourse === 'MTech') ? 'selected' : ''; ?>>M.Tech</option>
                    </select>

                    <!-- Filter by Status -->
                    <select name="status">
                        <option value="">Status: Active & Inactive</option>
                        <option value="Active" <?php echo ($filterStatus === 'Active') ? 'selected' : ''; ?>>Active Only</option>
                        <option value="Inactive" <?php echo ($filterStatus === 'Inactive') ? 'selected' : ''; ?>>Inactive Only</option>
                        <option value="Deleted" <?php echo ($filterStatus === 'Deleted') ? 'selected' : ''; ?>>Deleted Only (Soft-Deleted)</option>
                    </select>

                    <button type="submit" class="btn-primary">Apply Filter</button>
                    <a href="student_manage.php" class="btn-primary" style="background: #6c757d;">Reset</a>

                    <!-- Intermediate Extension: CSV Export -->
                    <a href="export_students_csv.php?search=<?php echo urlencode($searchKeyword); ?>&course=<?php echo urlencode($filterCourse); ?>&status=<?php echo urlencode($filterStatus); ?>" class="btn-success" style="margin-left: auto;">
                        📥 Export to CSV
                    </a>
                </form>

                <div style="font-size: 0.9rem; color: #555; margin-top: 5px;">
                    Showing <b><?php echo count($students); ?></b> student records from database.
                </div>
            </div>
        </div>
    </div>

    <!-- Student Table (READ) -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="card" style="overflow-x: auto;">
                <?php if (empty($students)): ?>
                    <p style="text-align: center; color: #777; padding: 30px;">No student records found matching your filter criteria.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Enrollment</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Course / Year</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th style="text-align: center; min-width: 170px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $row): ?>
                            <tr>
                                <td><b><?php echo (int)$row['student_id']; ?></b></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><code><?php echo htmlspecialchars($row['enrollment_no']); ?></code></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($row['course'] . ' / Yr ' . $row['year']); ?></td>
                                <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                <td>
                                    <?php 
                                    $st = $row['status'] ?? 'Active';
                                    if ($st === 'Active') {
                                        echo '<span class="badge-active">Active</span>';
                                    } elseif ($st === 'Inactive') {
                                        echo '<span class="badge-inactive">Inactive</span>';
                                    } else {
                                        echo '<span class="badge-deleted">Deleted</span>';
                                    }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <!-- EDIT (UPDATE) -->
                                    <a href="student_manage.php?action=edit&id=<?php echo $row['student_id']; ?>#studentForm" class="btn-warning">✏️ Edit</a>
                                    
                                    <?php if (($row['status'] ?? 'Active') !== 'Deleted'): ?>
                                        <!-- SOFT DELETE WITH CONFIRMATION -->
                                        <a href="student_manage.php?action=soft_delete&id=<?php echo $row['student_id']; ?>" 
                                           class="btn-danger" 
                                           onclick="return confirm('Confirm Delete: Are you sure you want to soft-delete student #<?php echo $row['student_id']; ?> (<?php echo htmlspecialchars($row['name']); ?>)?');">
                                           🗑 Delete
                                        </a>
                                    <?php else: ?>
                                        <!-- RESTORE SOFT-DELETED -->
                                        <a href="student_manage.php?action=restore&id=<?php echo $row['student_id']; ?>" class="btn-restore">
                                           ♻️ Restore
                                        </a>
                                        <!-- HARD DELETE -->
                                        <a href="student_manage.php?action=hard_delete&id=<?php echo $row['student_id']; ?>" 
                                           class="btn-danger" 
                                           style="background: #000;" 
                                           onclick="return confirm('PERMANENT DELETE: Are you sure you want to completely remove this record from MySQL?');">
                                           ✕ Purge
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div>
</body>
</html>
