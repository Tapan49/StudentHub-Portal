<?php

session_start();
require_once 'db.php';

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Advanced Extension: CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Security check failed (Invalid CSRF token).";
    }

    // 2. Retrieve & Sanitize Inputs
    $name          = trim(htmlspecialchars(strip_tags($_POST['name'] ?? '')));
    $enrollment_no = trim(htmlspecialchars(strip_tags($_POST['enrollment_no'] ?? '')));
    $email         = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $mobile        = trim(htmlspecialchars(strip_tags($_POST['mobile'] ?? '')));
    $course        = trim(htmlspecialchars(strip_tags($_POST['course'] ?? '')));
    $year          = filter_var($_POST['year'] ?? '', FILTER_VALIDATE_INT);
    $gender        = trim(htmlspecialchars(strip_tags($_POST['gender'] ?? '')));
    $password      = $_POST['password'] ?? '';
    $confirm_pwd   = $_POST['confirm_password'] ?? '';
    $terms         = isset($_POST['terms']);

    // 3. Backend Validation
    if (empty($name) || strlen($name) < 3) {
        $errors[] = "Name is required and must be at least 3 characters.";
    }

    if (empty($enrollment_no)) {
        // Generate automatic enrollment if left blank for flexibility
        $enrollment_no = 'EN' . date('Y') . rand(1000, 9999);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid student email address is required.";
    }

    if (!preg_match('/^\d{10}$/', $mobile)) {
        $errors[] = "Mobile number must be a valid 10-digit number.";
    }

    if (empty($course)) {
        $errors[] = "Please select a course.";
    }

    if (!$year || $year < 1 || $year > 4) {
        $errors[] = "Please select a valid study year.";
    }

    if (empty($gender)) {
        $errors[] = "Please select a gender.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if ($password !== $confirm_pwd) {
        $errors[] = "Passwords do not match.";
    }

    if (!$terms) {
        $errors[] = "You must accept the terms and conditions.";
    }

    // 4. Duplicate Check & Database Insert (if validation passes)
    if (empty($errors)) {
        try {
            // Intermediate Extension: Prepared Statement for duplicate check
            $checkStmt = $pdo->prepare("SELECT student_id FROM students WHERE email = :email OR enrollment_no = :enrollment_no LIMIT 1");
            $checkStmt->execute([
                ':email' => $email,
                ':enrollment_no' => $enrollment_no
            ]);

            if ($checkStmt->fetch()) {
                $errors[] = "A student with this Email or Enrollment Number already exists.";
            } else {
                // Key Requirement: Secure Password Hashing
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Intermediate Extension: Prepared Statement for Insert
                $insertStmt = $pdo->prepare("
                    INSERT INTO students (name, enrollment_no, email, mobile, course, year, gender, password_hash)
                    VALUES (:name, :enrollment_no, :email, :mobile, :course, :year, :gender, :password_hash)
                ");

                $inserted = $insertStmt->execute([
                    ':name'          => $name,
                    ':enrollment_no' => $enrollment_no,
                    ':email'         => $email,
                    ':mobile'        => $mobile,
                    ':course'        => $course,
                    ':year'          => $year,
                    ':gender'        => $gender,
                    ':password_hash' => $password_hash
                ]);

                if ($inserted) {
                    $newStudentId = $pdo->lastInsertId();

                    // Practical 10 & 14: Also synchronize into users authentication table
                    try {
                        $userStmt = $pdo->prepare("
                            INSERT INTO users (name, email, password_hash, role) 
                            VALUES (:name, :email, :password_hash, 'student')
                            ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)
                        ");
                        $userStmt->execute([
                            ':name'          => $name,
                            ':email'         => $email,
                            ':password_hash' => $password_hash
                        ]);
                    } catch (PDOException $ex) {
                        // Keep going if users table sync fails
                    }

                    $success = "Registration successful! Welcome, " . htmlspecialchars($name) . " (ID: $newStudentId). Your password has been securely hashed and stored. You can now log in!";
                    
                    // Regenerate CSRF token after success
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $errors[] = "Failed to register user. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Portal - Secure Registration</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .msg-box { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: 500; }
        .msg-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .msg-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .strength-meter { height: 6px; background-color: #e0e0e0; border-radius: 3px; margin-top: 5px; width: 100%; }
        .strength-meter div { height: 100%; border-radius: 3px; width: 0; transition: width 0.3s; }
        .strength-weak { background-color: #d9534f; width: 33%; }
        .strength-medium { background-color: #f0ad4e; width: 66%; }
        .strength-strong { background-color: #5cb85c; width: 100%; }
        .error-message { color: #d9534f; font-size: 0.85rem; display: block; margin-top: 5px; }
        label { font-weight: bold; }
    </style>
</head>
<body>
<div class="page-wrapper">
<br><br>

<div class="grid-container">
    <div class="flex-row">
        <div class="flex-col">
            <h1>SECURE REGISTRATION</h1>
        </div>
        <div class="flex-col" style="text-align: right;">
            <a href="../html/home.html"><b>HOME</b></a> &nbsp;|&nbsp;
            <a href="view_registered_students.php"><b>VIEW REGISTERED STUDENTS</b></a>
        </div>
    </div>

    <div class="flex-row">
        <div class="flex-col" style="background: white; padding: 30px; border-radius: 8px;">
            
            <?php if (!empty($errors)): ?>
                <div class="msg-box msg-error">
                    <strong>Validation Error:</strong>
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="msg-box msg-success">
                    <strong>Success:</strong> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form id="secureRegisterForm" action="register.php" method="POST" novalidate>
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="grid-container">
                    <div class="flex-row">
                        <div class="flex-col"><label for="name">FULL NAME:</label></div>
                        <div class="flex-col">
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required placeholder="Enter full name">
                            <span class="error-message" id="nameError"></span>
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col"><label for="enrollment_no">ENROLLMENT NO:</label></div>
                        <div class="flex-col">
                            <input type="text" id="enrollment_no" name="enrollment_no" value="<?php echo htmlspecialchars($_POST['enrollment_no'] ?? ''); ?>" placeholder="e.g. EN202610 (optional, auto-generated if blank)">
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col"><label for="email">STUDENT EMAIL:</label></div>
                        <div class="flex-col">
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="Enter student email">
                            <span class="error-message" id="emailError"></span>
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col"><label for="mobile">MOBILE NUMBER:</label></div>
                        <div class="flex-col">
                            <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>" required placeholder="Enter 10-digit mobile number">
                            <span class="error-message" id="mobileError"></span>
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col"><label for="course">COURSE:</label></div>
                        <div class="flex-col">
                            <select id="course" name="course" required style="width:100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px;">
                                <option value="">Select Course</option>
                                <option value="BTech" <?php echo (($_POST['course'] ?? '') === 'BTech') ? 'selected' : ''; ?>>B.Tech</option>
                                <option value="MTech" <?php echo (($_POST['course'] ?? '') === 'MTech') ? 'selected' : ''; ?>>M.Tech</option>
                                <option value="BCA" <?php echo (($_POST['course'] ?? '') === 'BCA') ? 'selected' : ''; ?>>BCA</option>
                                <option value="MCA" <?php echo (($_POST['course'] ?? '') === 'MCA') ? 'selected' : ''; ?>>MCA</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col"><label for="year">YEAR:</label></div>
                        <div class="flex-col">
                            <select id="year" name="year" required style="width:100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px;">
                                <option value="">Select Year</option>
                                <option value="1" <?php echo (($_POST['year'] ?? '') == '1') ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2" <?php echo (($_POST['year'] ?? '') == '2') ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3" <?php echo (($_POST['year'] ?? '') == '3') ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4" <?php echo (($_POST['year'] ?? '') == '4') ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col"><label>GENDER:</label></div>
                        <div class="flex-col" style="text-align: left;">
                            <?php $currGender = $_POST['gender'] ?? 'Male'; ?>
                            <label style="font-weight: normal; margin-right: 15px;"><input type="radio" name="gender" value="Male" <?php echo ($currGender === 'Male') ? 'checked' : ''; ?>> Male</label>
                            <label style="font-weight: normal; margin-right: 15px;"><input type="radio" name="gender" value="Female" <?php echo ($currGender === 'Female') ? 'checked' : ''; ?>> Female</label>
                            <label style="font-weight: normal;"><input type="radio" name="gender" value="Other" <?php echo ($currGender === 'Other') ? 'checked' : ''; ?>> Other</label>
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col"><label for="password">PASSWORD:</label></div>
                        <div class="flex-col">
                            <input type="password" id="password" name="password" required placeholder="Create password (min 8 chars)">
                            <div class="strength-meter"><div id="strengthBar"></div></div>
                            <span class="error-message" id="passwordError"></span>
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col"><label for="confirm_password">CONFIRM PASSWORD:</label></div>
                        <div class="flex-col">
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm password">
                            <span class="error-message" id="confirmPasswordError"></span>
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col"></div>
                        <div class="flex-col" style="text-align: left;">
                            <label style="font-weight: normal;">
                                <input type="checkbox" id="terms" name="terms" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?> required> I accept terms & conditions
                            </label>
                        </div>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col">
                            <br>
                            <input type="submit" value="  SECURE REGISTER  ">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
// Real-time password strength check
const pwdInput = document.getElementById('password');
const bar = document.getElementById('strengthBar');

pwdInput.addEventListener('input', () => {
    const val = pwdInput.value;
    let strength = 0;
    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength++;
    if (/\d/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    bar.className = '';
    if (val.length === 0) bar.className = '';
    else if (strength <= 1) bar.className = 'strength-weak';
    else if (strength <= 3) bar.className = 'strength-medium';
    else bar.className = 'strength-strong';
});
</script>
</body>
</html>
