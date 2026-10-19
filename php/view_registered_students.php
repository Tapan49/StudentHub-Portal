<?php

session_start();
require_once 'db.php';

try {
    $stmt = $pdo->query("SELECT student_id, name, enrollment_no, email, mobile, course, year, gender, password_hash, created_at FROM students ORDER BY student_id DESC");
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>StudentHub - Registered Students (DB Verification)</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; font-size: 0.9rem; }
        th, td { border: 1px solid #ced4da; padding: 10px; text-align: left; }
        th { background: #2c3e50; color: white; }
        .hash-code { font-family: monospace; font-size: 0.75rem; color: #555; word-break: break-all; }
        .badge { background: #28a745; color: white; padding: 3px 6px; border-radius: 3px; font-size: 0.75rem; }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="grid-container">
        <div class="flex-row">
            <div class="flex-col"><h1>REGISTERED STUDENTS (DATABASE)</h1></div>
            <div class="flex-col" style="text-align: right;">
                <a href="register.php"><b>NEW REGISTRATION</b></a> &nbsp;|&nbsp;
                <a href="../html/home.html"><b>HOME</b></a>
            </div>
        </div>
        <div class="flex-row">
            <div class="flex-col" style="background: white; padding: 25px; border-radius: 8px;">
                <p>Total Registered Students: <strong><?php echo count($students); ?></strong></p>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Enrollment</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Course / Year</th>
                                <th>Password Stored (Hashed)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($s['name']); ?></td>
                                <td><?php echo htmlspecialchars($s['enrollment_no']); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><?php echo htmlspecialchars($s['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($s['course'] . ' / Yr ' . $s['year']); ?></td>
                                <td class="hash-code">
                                    <span class="badge">bcrypt</span><br>
                                    <?php echo htmlspecialchars($s['password_hash']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
