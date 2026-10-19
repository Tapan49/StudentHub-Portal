<?php
require_once 'db.php'; // Include the database connection

try {
    // Intermediate Extension: Prepared Statement Execution
    $stmt = $pdo->prepare("SELECT e.title, e.event_date, COUNT(r.student_id) as total_registrations 
                           FROM events e 
                           LEFT JOIN registrations r ON e.event_id = r.event_id 
                           GROUP BY e.event_id");
    $stmt->execute();
    $events = $stmt->fetchAll();

    // Advanced Extension: Test Stored Procedure
    // $pdo->exec("CALL RegisterStudentForEvent(2, 3)");

} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Connection Test</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .success-box { background-color: #d4edda; color: #155724; padding: 20px; border-radius: 5px; border: 1px solid #c3e6cb; margin-bottom: 20px;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ced4da; padding: 12px; text-align: left; }
        th { background: #2c3e50; color: white; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="grid-container">
            <div class="flex-row">
                <div class="flex-col"><h1>Database Connection Test</h1></div>
                <div class="flex-col" style="text-align: right;"><a href="../html/home.html"><b>HOME</b></a></div>
            </div>
            <div class="flex-row">
                <div class="flex-col">
                    <div class="success-box">
                        <strong>Success!</strong> Successfully connected to <code>studenthub_db</code> using PHP PDO.
                    </div>
                    
                    <h2>Event Registration Stats (Fetched via Prepared Statement)</h2>
                    <?php if (empty($events)): ?>
                        <p>No events found in the database.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Date</th>
                                    <th>Total Registrations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($events as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['event_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['total_registrations']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
