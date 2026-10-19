<?php


$host = '127.0.0.1';
$port = '8889'; // Default MySQL port (in MAMP default is often 8889 or 3306)
$db   = 'studenthub_db';
$user = 'root'; 
// Note: In MAMP default password is 'root'; in XAMPP default password is ''
$pass = 'root'; 
$charset = 'utf8mb4';

// Check if connecting with standard password or blank password
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

// PDO connection options for secure and robust database connection
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // True prepared statements (security against SQL injection)
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Auto-provision tables for Practical 10 if not already present
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
        status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
        last_login DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active'");
    } catch (\PDOException $ex) {}

    $pdo->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
        token_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        selector VARCHAR(32) NOT NULL UNIQUE,
        hashed_validator VARCHAR(64) NOT NULL,
        expiry DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    // Seed default admin (admin@studenthub.com / Admin@123)
    $adminEmail = 'admin@studenthub.com';
    $checkAdmin = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkAdmin->execute([$adminEmail]);
    if (!$checkAdmin->fetch()) {
        $adminHash = password_hash('Admin@123', PASSWORD_DEFAULT);
        $insertAdmin = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
        $insertAdmin->execute(['System Administrator', $adminEmail, $adminHash]);
    }

    // Seed default student (john@example.com / Student@123)
    $studentEmail = 'john@example.com';
    $checkStudent = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkStudent->execute([$studentEmail]);
    if (!$checkStudent->fetch()) {
        $studentHash = password_hash('Student@123', PASSWORD_DEFAULT);
        $insertStudent = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'student')");
        $insertStudent->execute(['John Doe', $studentEmail, $studentHash]);
    }

    // Practical 11 Migration: Add status column to students table if not exists (Soft Delete)
    try {
        $pdo->exec("ALTER TABLE students ADD COLUMN status ENUM('Active', 'Inactive', 'Deleted') DEFAULT 'Active'");
    } catch (\PDOException $ex) {
        // Column already exists, ignore
    }

    // Practical 11 Requirement: Ensure at least 20 seeded student records exist
    $studentCount = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    if ($studentCount < 20) {
        $seedStudents = [
            ['John Doe', 'EN202601', 'john@example.com', '9876543210', 'BTech', 3, 'Male', 'Active'],
            ['Jane Smith', 'EN202602', 'jane@example.com', '9876543211', 'MCA', 2, 'Female', 'Active'],
            ['Alex Johnson', 'EN202603', 'alex@example.com', '9876543212', 'BTech', 1, 'Other', 'Active'],
            ['Rohan Patel', 'EN202604', 'rohan.patel@example.com', '9876543213', 'BTech', 2, 'Male', 'Active'],
            ['Priya Sharma', 'EN202605', 'priya.sharma@example.com', '9876543214', 'MCA', 1, 'Female', 'Active'],
            ['Aarav Mehta', 'EN202606', 'aarav.mehta@example.com', '9876543215', 'BCA', 3, 'Male', 'Active'],
            ['Ananya Desai', 'EN202607', 'ananya.desai@example.com', '9876543216', 'BTech', 4, 'Female', 'Active'],
            ['Dev Joshi', 'EN202608', 'dev.joshi@example.com', '9876543217', 'MTech', 1, 'Male', 'Active'],
            ['Diya Shah', 'EN202609', 'diya.shah@example.com', '9876543218', 'BCA', 2, 'Female', 'Active'],
            ['Karan Verma', 'EN202610', 'karan.verma@example.com', '9876543219', 'BTech', 2, 'Male', 'Active'],
            ['Sneha Nair', 'EN202611', 'sneha.nair@example.com', '9876543220', 'MCA', 2, 'Female', 'Active'],
            ['Vikram Rathore', 'EN202612', 'vikram.rathore@example.com', '9876543221', 'BTech', 3, 'Male', 'Active'],
            ['Tanvi Trivedi', 'EN202613', 'tanvi.trivedi@example.com', '9876543222', 'BCA', 1, 'Female', 'Active'],
            ['Manish Kumar', 'EN202614', 'manish.kumar@example.com', '9876543223', 'MTech', 2, 'Male', 'Inactive'],
            ['Kavita Rao', 'EN202615', 'kavita.rao@example.com', '9876543224', 'BTech', 1, 'Female', 'Active'],
            ['Siddharth Bhatt', 'EN202616', 'siddharth.bhatt@example.com', '9876543225', 'MCA', 1, 'Male', 'Active'],
            ['Meera Iyer', 'EN202617', 'meera.iyer@example.com', '9876543226', 'BTech', 4, 'Female', 'Active'],
            ['Aditya Dave', 'EN202618', 'aditya.dave@example.com', '9876543227', 'BCA', 3, 'Male', 'Active'],
            ['Ritu Chauhan', 'EN202619', 'ritu.chauhan@example.com', '9876543228', 'BTech', 2, 'Female', 'Active'],
            ['Yash Solanki', 'EN202620', 'yash.solanki@example.com', '9876543229', 'MCA', 2, 'Male', 'Active'],
            ['Pooja Kulkarni', 'EN202621', 'pooja.kulkarni@example.com', '9876543230', 'BTech', 3, 'Female', 'Active']
        ];
        $defaultHash = password_hash('Student@123', PASSWORD_DEFAULT);
        $insertSeed = $pdo->prepare("
            INSERT INTO students (name, enrollment_no, email, mobile, course, year, gender, password_hash, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE name=VALUES(name)
        ");
        foreach ($seedStudents as $st) {
            $insertSeed->execute([$st[0], $st[1], $st[2], $st[3], $st[4], $st[5], $st[6], $defaultHash, $st[7]]);
        }
    }

    // Practical 12 Migrations: Add description, category, poster_image, status to events table if missing
    $eventColsToAdd = [
        "description TEXT NULL",
        "category VARCHAR(50) NOT NULL DEFAULT 'Technical'",
        "poster_image VARCHAR(255) NULL",
        "status ENUM('Open', 'Closed') DEFAULT 'Open'"
    ];
    foreach ($eventColsToAdd as $colDef) {
        try {
            $pdo->exec("ALTER TABLE events ADD COLUMN $colDef");
        } catch (\PDOException $ex) {
            // Already exists, ignore
        }
    }

    // Audit Logs Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        user_name VARCHAR(100) NULL,
        action VARCHAR(50) NOT NULL,
        entity VARCHAR(50) NOT NULL,
        entity_id INT NULL,
        details TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $logCount = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    if ($logCount === 0) {
        $sampleLogs = [
            [1, 'System Administrator', 'SYSTEM_INIT', 'system', 1, 'Portal initialized and database seeded successfully.', date('Y-m-d H:i:s', strtotime('-2 days'))],
            [1, 'System Administrator', 'LOGIN', 'auth', 1, 'Administrator logged in from 127.0.0.1', date('Y-m-d H:i:s', strtotime('-1 day'))],
            [1, 'System Administrator', 'CREATE', 'events', 1, 'Created event "Coder\'s Arcade Hackathon"', date('Y-m-d H:i:s', strtotime('-18 hours'))],
            [1, 'System Administrator', 'STATUS_TOGGLE', 'users', 2, 'Toggled user status to Active for John Doe', date('Y-m-d H:i:s', strtotime('-5 hours'))]
        ];
        $logInsert = $pdo->prepare("INSERT INTO audit_logs (user_id, user_name, action, entity, entity_id, details, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($sampleLogs as $l) {
            $logInsert->execute($l);
        }
    }

    // Practical 12 Advanced Extension: Create uploads directory with .htaccess protection
    $uploadDir = __DIR__ . '/../uploads/posters';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    $htaccessFile = $uploadDir . '/.htaccess';
    if (!file_exists($htaccessFile)) {
        $htaccessContent = "# Upload Directory Security Protection\nOptions -Indexes -ExecCGI\n<FilesMatch \"\\.(php|php5|php7|phtml|sh|py|pl|cgi)$\">\n    Deny from all\n</FilesMatch>\n";
        @file_put_contents($htaccessFile, $htaccessContent);
    }

} catch (\PDOException $e) {
    // Handling connection errors securely (not exposing exact credentials)
    die("Database Connection Failed: " . $e->getMessage());
}

/**
 * Practical 12 Advanced Extension: Audit Logging Helper
 */
function record_audit_log($pdo, $action, $entity, $entity_id, $details) {
    try {
        $userId   = $_SESSION['user_id'] ?? null;
        $userName = $_SESSION['user_name'] ?? 'System';
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, user_name, action, entity, entity_id, details)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $userName, $action, $entity, $entity_id, $details]);
    } catch (\PDOException $e) {
        // Silently skip if logging fails
    }
}
?>
