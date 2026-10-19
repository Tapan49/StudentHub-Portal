<?php

require_once __DIR__ . '/auth.php';

// Securely terminate session and cookies
logout_current_session();

// Redirect back to login with success message
header("Location: login.php?msg=logged_out");
exit();
