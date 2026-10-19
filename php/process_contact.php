<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Advanced Extension: Validate CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security validation failed. Invalid CSRF token.";
        header("Location: contact.php");
        exit;
    }

    // 2. Sanitization (Prevent XSS and strip unwanted tags)
    $name = htmlspecialchars(strip_tags(trim($_POST['name'] ?? '')));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(strip_tags(trim($_POST['message'] ?? '')));

    // 3. Server-Side Validation
    if (empty($name) || empty($email) || empty($message)) {
        $_SESSION['error'] = "All fields are required. Please fill out the entire form.";
        header("Location: contact.php");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please provide a valid email format.";
        header("Location: contact.php");
        exit;
    }

    if (strlen($message) < 10) {
        $_SESSION['error'] = "Your message must be at least 10 characters long.";
        header("Location: contact.php");
        exit;
    }

    // 4. Safely Store Data in JSON format
    $file_path = '../data/contacts.json';
    $data = [];
    
    // Check if file exists and parse current contents
    if (file_exists($file_path)) {
        $json_content = file_get_contents($file_path);
        $parsed_data = json_decode($json_content, true);
        if (is_array($parsed_data)) {
            $data = $parsed_data;
        }
    }

    // Create new record
    $new_record = [
        'id' => uniqid('msg_'),
        'timestamp' => date('Y-m-d H:i:s'),
        'name' => $name,
        'email' => $email,
        'message' => $message
    ];

    $data[] = $new_record;

    // Write array back to JSON file with LOCK_EX to prevent race conditions
    if (file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX)) {
        $_SESSION['success'] = "Thank you, $name! Your message has been saved successfully.";
    } else {
        $_SESSION['error'] = "Server error: Unable to save your message. Please try again later.";
    }

    // Redirect back to form
    header("Location: contact.php");
    exit;

} else {
    // If not a POST request, redirect to form
    header("Location: contact.php");
    exit;
}
