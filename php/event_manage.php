<?php

require_once __DIR__ . '/auth.php';

// Access Control: Restrict event management strictly to admin users
require_login('admin');

$message = '';
$messageType = 'success'; // 'success' or 'error'

// Upload configuration
$uploadDir = __DIR__ . '/../uploads/posters/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$allowedExts  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$maxSizeBytes = 2 * 1024 * 1024; // 2 MB maximum limit

/**
 * Helper function to handle and validate poster image upload
 */
function handle_poster_upload($fileInputName, $allowedMimes, $allowedExts, $maxSizeBytes, $uploadDir, &$errorMsg) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No file uploaded
    }

    $file = $_FILES[$fileInputName];

    // Check for standard upload error codes
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "File upload error code: " . $file['error'];
        return false;
    }

    // 1. Validate File Size (Max 2MB)
    if ($file['size'] > $maxSizeBytes) {
        $errorMsg = "File size exceeds the 2MB limit (Uploaded size: " . round($file['size'] / (1024 * 1024), 2) . " MB).";
        return false;
    }

    // 2. Validate File Extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        $errorMsg = "Invalid file extension '.$ext'. Only JPG, PNG, WEBP, and GIF images are allowed.";
        return false;
    }

    // 3. Validate True MIME Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        $errorMsg = "Security rejection: Invalid MIME type ($mimeType). File is not a valid image.";
        return false;
    }

    // 4. Advanced Extension: Secure Image Renaming (prevents overwriting & execution attacks)
    $newFilename = 'poster_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $targetPath = $uploadDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Return relative path for web access
        return 'uploads/posters/' . $newFilename;
    } else {
        $errorMsg = "Failed to move uploaded file to target directory.";
        return false;
    }
}

// -------------------------------------------------------------
// 1. HANDLE POST (CREATE & UPDATE EVENT)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE EVENT
    if ($action === 'create') {
        $title       = trim(htmlspecialchars(strip_tags($_POST['title'] ?? '')));
        $description = trim(htmlspecialchars(strip_tags($_POST['description'] ?? '')));
        $event_date  = trim($_POST['event_date'] ?? '');
        $format      = trim(htmlspecialchars(strip_tags($_POST['format'] ?? 'Offline')));
        $category    = trim(htmlspecialchars(strip_tags($_POST['category'] ?? 'Technical')));
        $event_type  = trim(htmlspecialchars(strip_tags($_POST['event_type'] ?? 'Workshop')));
        $location    = trim(htmlspecialchars(strip_tags($_POST['location'] ?? '')));
        $status      = trim(htmlspecialchars(strip_tags($_POST['status'] ?? 'Open')));

        if (empty($title) || empty($event_date)) {
            $message = "Please provide both an Event Title and Date/Time.";
            $messageType = 'error';
        } else {
            // Process poster upload
            $uploadError = '';
            $posterPath = handle_poster_upload('poster_image', $allowedMimes, $allowedExts, $maxSizeBytes, $uploadDir, $uploadError);

            if ($posterPath === false) {
                // File validation failed
                $message = "Upload Validation Failed: " . $uploadError;
                $messageType = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO events (title, description, event_date, format, category, event_type, location, poster_image, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$title, $description, $event_date, $format, $category, $event_type, $location, $posterPath, $status]);
                    $newEventId = $pdo->lastInsertId();

                    // Advanced Extension: Audit Logging
                    record_audit_log($pdo, 'CREATE', 'events', $newEventId, "Created event '$title' (Category: $category, Status: $status)");

                    $message = "Event '$title' (ID: #$newEventId) created successfully!";
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = "Database Error: " . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }

    // UPDATE EVENT
    elseif ($action === 'update') {
        $event_id    = (int)($_POST['event_id'] ?? 0);
        $title       = trim(htmlspecialchars(strip_tags($_POST['title'] ?? '')));
        $description = trim(htmlspecialchars(strip_tags($_POST['description'] ?? '')));
        $event_date  = trim($_POST['event_date'] ?? '');
        $format      = trim(htmlspecialchars(strip_tags($_POST['format'] ?? 'Offline')));
        $category    = trim(htmlspecialchars(strip_tags($_POST['category'] ?? 'Technical')));
        $event_type  = trim(htmlspecialchars(strip_tags($_POST['event_type'] ?? 'Workshop')));
        $location    = trim(htmlspecialchars(strip_tags($_POST['location'] ?? '')));
        $status      = trim(htmlspecialchars(strip_tags($_POST['status'] ?? 'Open')));
        $existingImg = trim($_POST['existing_poster'] ?? '');

        if ($event_id <= 0 || empty($title) || empty($event_date)) {
            $message = "Invalid event details provided.";
            $messageType = 'error';
        } else {
            $uploadError = '';
            $posterPath = handle_poster_upload('poster_image', $allowedMimes, $allowedExts, $maxSizeBytes, $uploadDir, $uploadError);

            if ($posterPath === false) {
                $message = "Upload Validation Failed: " . $uploadError;
                $messageType = 'error';
            } else {
                $finalPoster = ($posterPath !== null) ? $posterPath : $existingImg;

                try {
                    $stmt = $pdo->prepare("
                        UPDATE events 
                        SET title = ?, description = ?, event_date = ?, format = ?, category = ?, event_type = ?, location = ?, poster_image = ?, status = ?
                        WHERE event_id = ?
                    ");
                    $stmt->execute([$title, $description, $event_date, $format, $category, $event_type, $location, $finalPoster, $status, $event_id]);

                    // Advanced Extension: Audit Logging
                    record_audit_log($pdo, 'UPDATE', 'events', $event_id, "Updated event '$title' (Status: $status)");

                    $message = "Event #$event_id ('$title') updated successfully!";
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = "Database Error: " . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }
}

// -------------------------------------------------------------
// 2. HANDLE GET ACTIONS (DELETE EVENT)
// -------------------------------------------------------------
$getAction = $_GET['action'] ?? '';
$targetId = (int)($_GET['id'] ?? 0);

if ($getAction === 'delete' && $targetId > 0) {
    try {
        // Fetch event info before deletion for poster cleanup and audit log
        $fetchStmt = $pdo->prepare("SELECT title, poster_image FROM events WHERE event_id = ?");
        $fetchStmt->execute([$targetId]);
        $ev = $fetchStmt->fetch();

        if ($ev) {
            // Delete record
            $delStmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
            $delStmt->execute([$targetId]);

            // Clean up uploaded poster file if stored in uploads/
            if (!empty($ev['poster_image']) && strpos($ev['poster_image'], 'uploads/posters/') !== false) {
                $fullPath = __DIR__ . '/../' . $ev['poster_image'];
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            // Advanced Extension: Audit Logging
            record_audit_log($pdo, 'DELETE', 'events', $targetId, "Deleted event '{$ev['title']}'");

            $message = "Event #$targetId ('" . htmlspecialchars($ev['title']) . "') deleted successfully.";
            $messageType = 'success';
        }
    } catch (PDOException $e) {
        $message = "Error deleting event: " . $e->getMessage();
        $messageType = 'error';
    }
}

// -------------------------------------------------------------
// 3. SEARCH & FILTER (INTERMEDIATE EXTENSION)
// -------------------------------------------------------------
$searchKeyword  = trim($_GET['search'] ?? '');
$filterCategory = trim($_GET['category'] ?? '');
$filterStatus   = trim($_GET['status'] ?? '');
$filterFormat   = trim($_GET['format'] ?? '');

$sql = "SELECT * FROM events WHERE 1=1";
$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (title LIKE ? OR location LIKE ? OR event_type LIKE ?)";
    $term = "%$searchKeyword%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($filterCategory)) {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
}

if (!empty($filterStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}

if (!empty($filterFormat)) {
    $sql .= " AND format = ?";
    $params[] = $filterFormat;
}

$sql .= " ORDER BY event_date ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $eventsList = $stmt->fetchAll();
} catch (PDOException $e) {
    $eventsList = [];
    $message = "Query Error: " . $e->getMessage();
    $messageType = 'error';
}

// Edit prefill
$editEvent = null;
if ($getAction === 'edit' && $targetId > 0) {
    $eStmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
    $eStmt->execute([$targetId]);
    $editEvent = $eStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Event Management</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .msg-box { padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; }
        .msg-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .card { background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 25px; }
        .filter-bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 15px; }
        .filter-bar input, .filter-bar select { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 0.9rem; }
        .btn-primary { background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background: #0056b3; }
        .btn-warning { background: #ffc107; color: #212529; padding: 6px 12px; border-radius: 4px; font-size: 0.82rem; font-weight: bold; text-decoration: none; }
        .btn-danger { background: #dc3545; color: white; padding: 6px 12px; border-radius: 4px; font-size: 0.82rem; font-weight: bold; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; background: white; margin-top: 10px; }
        th, td { border: 1px solid #e9ecef; padding: 10px 12px; text-align: left; vertical-align: middle; }
        th { background: #2c3e50; color: white; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        tr:hover { background-color: #f1f4f8; }
        .badge-open { background: #28a745; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .badge-closed { background: #dc3545; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .badge-cat { background: #6c757d; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 4px; font-size: 0.85rem; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .poster-thumb { width: 65px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .no-poster { width: 65px; height: 50px; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #888; border-radius: 4px; border: 1px dashed #ccc; }
    </style>
</head>
<body>
<div class="page-wrapper">
<br>

<div class="grid-container">
    <!-- Header -->
    <div class="flex-row">
        <div class="flex-col">
            <h1>EVENT MANAGEMENT</h1>
            <p>Add, View, Edit, Delete Events with Poster Upload & Security Validation</p>
        </div>
        <div class="flex-col" style="text-align: right;">
            <a href="admin_dashboard.php"><b>ADMIN DASHBOARD</b></a> &nbsp;|&nbsp;
            <a href="student_manage.php"><b>STUDENT CRUD</b></a> &nbsp;|&nbsp;
            <a href="#eventFormCard" class="btn-primary">+ ADD NEW EVENT</a>
        </div>
    </div>

    <!-- Feedback Message / Validation Error Alert -->
    <?php if (!empty($message)): ?>
        <div class="flex-row">
            <div class="flex-col">
                <div class="msg-box msg-<?php echo $messageType; ?>" id="crudAlert">
                    <?php echo ($messageType === 'success' ? '✓ ' : '⚠️ ') . htmlspecialchars($message); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add / Edit Event Form with Poster Upload -->
    <div class="flex-row" id="eventFormCard">
        <div class="flex-col">
            <div class="card" style="border-top: 4px solid <?php echo $editEvent ? '#ffc107' : '#007bff'; ?>;">
                <h2><?php echo $editEvent ? '✏️ Edit Event Details (#' . $editEvent['event_id'] . ')' : '➕ Add New Event'; ?></h2>
                <p style="font-size: 0.88rem; color: #666; margin-top: -5px;">
                    Upload event poster image (Allowed formats: <b>JPG, PNG, WEBP</b> | Max size: <b>2 MB</b>)
                </p>

                <form action="event_manage.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $editEvent ? 'update' : 'create'; ?>">
                    <?php if ($editEvent): ?>
                        <input type="hidden" name="event_id" value="<?php echo (int)$editEvent['event_id']; ?>">
                        <input type="hidden" name="existing_poster" value="<?php echo htmlspecialchars($editEvent['poster_image'] ?? ''); ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Event Title *</label>
                            <input type="text" name="title" required value="<?php echo htmlspecialchars($editEvent['title'] ?? ''); ?>" placeholder="e.g. Coder's Arcade Hackathon 2026">
                        </div>

                        <div class="form-group">
                            <label>Event Date & Time *</label>
                            <input type="datetime-local" name="event_date" required value="<?php echo !empty($editEvent['event_date']) ? date('Y-m-d\TH:i', strtotime($editEvent['event_date'])) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category" required>
                                <option value="Technical" <?php echo (($editEvent['category'] ?? '') === 'Technical') ? 'selected' : ''; ?>>Technical</option>
                                <option value="Cultural" <?php echo (($editEvent['category'] ?? '') === 'Cultural') ? 'selected' : ''; ?>>Cultural</option>
                                <option value="Sports" <?php echo (($editEvent['category'] ?? '') === 'Sports') ? 'selected' : ''; ?>>Sports</option>
                                <option value="Academic" <?php echo (($editEvent['category'] ?? '') === 'Academic') ? 'selected' : ''; ?>>Academic</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Format *</label>
                            <select name="format" required>
                                <option value="Offline" <?php echo (($editEvent['format'] ?? '') === 'Offline') ? 'selected' : ''; ?>>Offline</option>
                                <option value="Online" <?php echo (($editEvent['format'] ?? '') === 'Online') ? 'selected' : ''; ?>>Online</option>
                                <option value="Hybrid" <?php echo (($editEvent['format'] ?? '') === 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Event Type *</label>
                            <input type="text" name="event_type" required value="<?php echo htmlspecialchars($editEvent['event_type'] ?? 'Workshop'); ?>" placeholder="e.g. Hackathon, Seminar">
                        </div>

                        <div class="form-group">
                            <label>Location / Venue</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($editEvent['location'] ?? ''); ?>" placeholder="e.g. Main Auditorium / Zoom Link">
                        </div>

                        <div class="form-group">
                            <label>Registration Status *</label>
                            <select name="status" required>
                                <option value="Open" <?php echo (($editEvent['status'] ?? 'Open') === 'Open') ? 'selected' : ''; ?>>Open</option>
                                <option value="Closed" <?php echo (($editEvent['status'] ?? '') === 'Closed') ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label>Poster Image (Max 2MB - JPG, PNG, WEBP)</label>
                            <input type="file" name="poster_image" accept="image/jpeg,image/png,image/webp,image/gif">
                            <?php if (!empty($editEvent['poster_image'])): ?>
                                <div style="margin-top: 6px; font-size: 0.85rem; color: #555;">
                                    Current poster: <a href="../<?php echo htmlspecialchars($editEvent['poster_image']); ?>" target="_blank">View Current Image</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label>Event Description</label>
                            <textarea name="description" rows="2" placeholder="Brief summary of event schedule and prizes..."><?php echo htmlspecialchars($editEvent['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="<?php echo $editEvent ? 'btn-warning' : 'btn-primary'; ?>" style="padding: 10px 22px; font-size: 0.95rem;">
                            <?php echo $editEvent ? '💾 UPDATE EVENT' : '➕ CREATE & PUBLISH EVENT'; ?>
                        </button>
                        <?php if ($editEvent): ?>
                            <a href="event_manage.php" style="margin-left: 15px; color: #666; text-decoration: none;">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Search & Filters Toolbar (Intermediate Extension) -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="card" style="padding: 18px;">
                <form action="event_manage.php" method="GET" class="filter-bar">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($searchKeyword); ?>" placeholder="🔍 Search Title, Location, Type..." style="min-width: 250px;">

                    <!-- Category Filter -->
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="Technical" <?php echo ($filterCategory === 'Technical') ? 'selected' : ''; ?>>Technical</option>
                        <option value="Cultural" <?php echo ($filterCategory === 'Cultural') ? 'selected' : ''; ?>>Cultural</option>
                        <option value="Sports" <?php echo ($filterCategory === 'Sports') ? 'selected' : ''; ?>>Sports</option>
                        <option value="Academic" <?php echo ($filterCategory === 'Academic') ? 'selected' : ''; ?>>Academic</option>
                    </select>

                    <!-- Status Filter (Open/Closed) -->
                    <select name="status">
                        <option value="">Status: All</option>
                        <option value="Open" <?php echo ($filterStatus === 'Open') ? 'selected' : ''; ?>>Open Only</option>
                        <option value="Closed" <?php echo ($filterStatus === 'Closed') ? 'selected' : ''; ?>>Closed Only</option>
                    </select>

                    <!-- Format Filter -->
                    <select name="format">
                        <option value="">Format: All</option>
                        <option value="Offline" <?php echo ($filterFormat === 'Offline') ? 'selected' : ''; ?>>Offline</option>
                        <option value="Online" <?php echo ($filterFormat === 'Online') ? 'selected' : ''; ?>>Online</option>
                        <option value="Hybrid" <?php echo ($filterFormat === 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                    </select>

                    <button type="submit" class="btn-primary">Apply Filters</button>
                    <a href="event_manage.php" class="btn-primary" style="background: #6c757d;">Reset</a>
                </form>

                <div style="font-size: 0.9rem; color: #555;">
                    Total Events: <b><?php echo count($eventsList); ?></b>
                </div>
            </div>
        </div>
    </div>

    <!-- Events List Table (READ) -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="card" style="overflow-x: auto;">
                <?php if (empty($eventsList)): ?>
                    <p style="text-align: center; color: #777; padding: 30px;">No events match your search criteria.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Poster</th>
                                <th>ID</th>
                                <th>Event Title</th>
                                <th>Category</th>
                                <th>Date & Time</th>
                                <th>Format</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th style="text-align: center; min-width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventsList as $ev): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($ev['poster_image'])): ?>
                                        <a href="../<?php echo htmlspecialchars($ev['poster_image']); ?>" target="_blank">
                                            <img src="../<?php echo htmlspecialchars($ev['poster_image']); ?>" alt="Poster" class="poster-thumb">
                                        </a>
                                    <?php else: ?>
                                        <div class="no-poster">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td><b>#<?php echo (int)$ev['event_id']; ?></b></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($ev['title']); ?></strong>
                                    <div style="font-size: 0.78rem; color: #666;"><?php echo htmlspecialchars($ev['event_type']); ?></div>
                                </td>
                                <td><span class="badge-cat"><?php echo htmlspecialchars($ev['category'] ?? 'Technical'); ?></span></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($ev['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($ev['format']); ?></td>
                                <td><?php echo htmlspecialchars($ev['location'] ?? 'TBA'); ?></td>
                                <td>
                                    <?php if (($ev['status'] ?? 'Open') === 'Open'): ?>
                                        <span class="badge-open">Open</span>
                                    <?php else: ?>
                                        <span class="badge-closed">Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="event_manage.php?action=edit&id=<?php echo $ev['event_id']; ?>#eventFormCard" class="btn-warning">✏️ Edit</a>
                                    <a href="event_manage.php?action=delete&id=<?php echo $ev['event_id']; ?>" 
                                       class="btn-danger" 
                                       onclick="return confirm('Confirm Delete: Are you sure you want to delete event #<?php echo $ev['event_id']; ?> (<?php echo htmlspecialchars($ev['title']); ?>)? This will also remove the poster file.');">
                                       🗑 Delete
                                    </a>
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
