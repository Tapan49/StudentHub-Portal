<?php

require_once __DIR__ . '/auth.php';

if (!is_logged_in()) {
    die("Unauthorized access. Please log in first.");
}

$searchKeyword = trim($_GET['search'] ?? '');
$filterCourse  = trim($_GET['course'] ?? '');
$filterStatus  = trim($_GET['status'] ?? '');

$sql = "SELECT student_id, name, enrollment_no, email, mobile, course, year, gender, status, created_at FROM students WHERE 1=1";
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
}

$sql .= " ORDER BY student_id ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Generate CSV Download Headers
$filename = "students_export_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Header Row
fputcsv($output, ['Student ID', 'Full Name', 'Enrollment No', 'Email', 'Mobile', 'Course', 'Year', 'Gender', 'Status', 'Registered Date']);

// Data Rows
foreach ($students as $row) {
    fputcsv($output, [
        $row['student_id'],
        $row['name'],
        $row['enrollment_no'],
        $row['email'],
        $row['mobile'],
        $row['course'],
        $row['year'],
        $row['gender'],
        $row['status'] ?? 'Active',
        $row['created_at']
    ]);
}

fclose($output);
exit();
