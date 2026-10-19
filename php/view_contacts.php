<?php
$file_path = '../data/contacts.json';
$contacts = [];

if (file_exists($file_path)) {
    $json_content = file_get_contents($file_path);
    $parsed_data = json_decode($json_content, true);
    if (is_array($parsed_data)) {
        $contacts = array_reverse($parsed_data); // Show newest first
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Portal - View Contacts</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ced4da; padding: 12px; text-align: left; }
        th { background: #2c3e50; color: white; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .empty-msg { text-align: center; padding: 20px; color: #6c757d; font-style: italic; background: white; border-radius: 8px; }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="grid-container">
        <div class="flex-row">
            <div class="flex-col">
                <h1>SUBMITTED MESSAGES</h1>
            </div>
            <div class="flex-col" style="text-align: right;">
                <a href="contact.php"><b>BACK TO FORM</b></a>
            </div>
        </div>
        <div class="flex-row">
            <div class="flex-col">
                <?php if (empty($contacts)): ?>
                    <div class="empty-msg">No messages have been submitted yet.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($contact['timestamp']); ?></td>
                                <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>"><?php echo htmlspecialchars($contact['email']); ?></a></td>
                                <td><?php echo nl2br(htmlspecialchars($contact['message'])); ?></td>
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
