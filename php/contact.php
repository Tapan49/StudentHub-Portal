<?php
session_start();
// Advanced Extension: Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Portal - Contact</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { font-weight: bold; display: block; margin-bottom: 5px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; }
        .btn-submit { background: #0056b3; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .btn-submit:hover { background: #003d82; }
        .msg-success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .msg-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="grid-container">
        <div class="flex-row">
            <div class="flex-col">
                <h1>CONTACT US</h1>
            </div>
            <div class="flex-col" style="text-align: right;">
                <a href="../html/home.html"><b>HOME</b></a> &nbsp;|&nbsp;
                <a href="view_contacts.php"><b>VIEW RECORDS</b></a>
            </div>
        </div>
        <div class="flex-row">
            <div class="flex-col" style="background: white; padding: 30px; border-radius: 8px;">
                <h2>Send us a Message</h2>
                
                <?php
                // Display Success or Error Messages
                if (isset($_SESSION['success'])) {
                    echo "<div class='msg-success'>" . $_SESSION['success'] . "</div>";
                    unset($_SESSION['success']);
                }
                if (isset($_SESSION['error'])) {
                    echo "<div class='msg-error'>" . $_SESSION['error'] . "</div>";
                    unset($_SESSION['error']);
                }
                ?>

                <form action="process_contact.php" method="POST">
                    <!-- CSRF Token Hidden Field -->
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" required placeholder="Your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required placeholder="Your email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message:</label>
                        <textarea id="message" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">Submit Message</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
