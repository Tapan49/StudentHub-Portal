<?php


header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../auth.php';


function send_json_response($statusCode, $success, $message, $data = null) {
    http_response_code($statusCode);
    echo json_encode([
        'status'    => $statusCode,
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Global Exception & Error Handler
set_exception_handler(function($e) {
    send_json_response(500, false, "Server Error: " . $e->getMessage());
});

$method = $_SERVER['REQUEST_METHOD'];

// Parse incoming payload for JSON bodies or FormData
$rawInput = file_get_contents('php://input');
$jsonBody = json_decode($rawInput, true);

// Support method overriding via '_method' parameter or 'action' in POST
if ($method === 'POST') {
    if (isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    } elseif (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete') $method = 'DELETE';
        elseif ($_POST['action'] === 'update') $method = 'PUT';
    } elseif (isset($jsonBody['_method'])) {
        $method = strtoupper($jsonBody['_method']);
    }
}

// -------------------------------------------------------------
// GET: Fetch All Events OR Single Event by ID
// -------------------------------------------------------------
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id > 0) {
        // Fetch Single Event
        $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $event = $stmt->fetch();

        if ($event) {
            send_json_response(200, true, "Event retrieved successfully.", $event);
        } else {
            send_json_response(404, false, "Event not found with ID #$id.");
        }
    } else {
        // Fetch All with optional Search & Filter
        $search   = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $status   = trim($_GET['status'] ?? '');

        $sql = "SELECT * FROM events WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR location LIKE ? OR event_type LIKE ? OR description LIKE ?)";
            $term = "%$search%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($category)) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY event_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        send_json_response(200, true, "Retrieved " . count($events) . " events.", [
            'count'  => count($events),
            'events' => $events
        ]);
    }
}

// -------------------------------------------------------------
// POST: Create a New Event
// -------------------------------------------------------------
elseif ($method === 'POST') {
    $input = !empty($_POST) ? $_POST : ($jsonBody ?? []);

    $title       = trim(htmlspecialchars(strip_tags($input['title'] ?? '')));
    $description = trim(htmlspecialchars(strip_tags($input['description'] ?? '')));
    $event_date  = trim($input['event_date'] ?? '');
    $format      = trim(htmlspecialchars(strip_tags($input['format'] ?? 'Offline')));
    $category    = trim(htmlspecialchars(strip_tags($input['category'] ?? 'Technical')));
    $event_type  = trim(htmlspecialchars(strip_tags($input['event_type'] ?? 'Workshop')));
    $location    = trim(htmlspecialchars(strip_tags($input['location'] ?? '')));
    $status      = trim(htmlspecialchars(strip_tags($input['status'] ?? 'Open')));

    // Input Validation
    $errors = [];
    if (empty($title)) {
        $errors[] = "Event title is required.";
    } elseif (strlen($title) < 3) {
        $errors[] = "Event title must be at least 3 characters.";
    }

    if (empty($event_date)) {
        $errors[] = "Event date and time is required.";
    }

    if (!empty($errors)) {
        send_json_response(400, false, implode(' ', $errors));
    }

    // Insert Event
    $stmt = $pdo->prepare("
        INSERT INTO events (title, description, event_date, format, category, event_type, location, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $description, $event_date, $format, $category, $event_type, $location, $status]);
    $newEventId = (int)$pdo->lastInsertId();

    // Fetch created record
    $fetchStmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
    $fetchStmt->execute([$newEventId]);
    $createdEvent = $fetchStmt->fetch();

    // Log audit action
    if (function_exists('record_audit_log')) {
        record_audit_log($pdo, 'CREATE_API', 'events', $newEventId, "Created via AJAX API: $title");
    }

    send_json_response(201, true, "Event '$title' created successfully.", $createdEvent);
}

// -------------------------------------------------------------
// PUT: Update an Existing Event
// -------------------------------------------------------------
elseif ($method === 'PUT') {
    $input = !empty($_POST) ? $_POST : ($jsonBody ?? []);

    $eventId     = (int)($input['event_id'] ?? 0);
    $title       = trim(htmlspecialchars(strip_tags($input['title'] ?? '')));
    $description = trim(htmlspecialchars(strip_tags($input['description'] ?? '')));
    $event_date  = trim($input['event_date'] ?? '');
    $format      = trim(htmlspecialchars(strip_tags($input['format'] ?? 'Offline')));
    $category    = trim(htmlspecialchars(strip_tags($input['category'] ?? 'Technical')));
    $event_type  = trim(htmlspecialchars(strip_tags($input['event_type'] ?? 'Workshop')));
    $location    = trim(htmlspecialchars(strip_tags($input['location'] ?? '')));
    $status      = trim(htmlspecialchars(strip_tags($input['status'] ?? 'Open')));

    if ($eventId <= 0) {
        send_json_response(400, false, "Valid event_id is required for update.");
    }

    if (empty($title) || empty($event_date)) {
        send_json_response(400, false, "Event title and date cannot be empty.");
    }

    // Check if event exists
    $check = $pdo->prepare("SELECT event_id FROM events WHERE event_id = ?");
    $check->execute([$eventId]);
    if (!$check->fetch()) {
        send_json_response(404, false, "Event with ID #$eventId not found.");
    }

    $stmt = $pdo->prepare("
        UPDATE events 
        SET title = ?, description = ?, event_date = ?, format = ?, category = ?, event_type = ?, location = ?, status = ?
        WHERE event_id = ?
    ");
    $stmt->execute([$title, $description, $event_date, $format, $category, $event_type, $location, $status, $eventId]);

    // Fetch updated record
    $fetchStmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
    $fetchStmt->execute([$eventId]);
    $updatedEvent = $fetchStmt->fetch();

    if (function_exists('record_audit_log')) {
        record_audit_log($pdo, 'UPDATE_API', 'events', $eventId, "Updated via AJAX API: $title");
    }

    send_json_response(200, true, "Event #$eventId updated successfully.", $updatedEvent);
}

// -------------------------------------------------------------
// DELETE: Delete an Event
// -------------------------------------------------------------
elseif ($method === 'DELETE') {
    $input = !empty($_POST) ? $_POST : ($jsonBody ?? []);
    $eventId = (int)($input['event_id'] ?? ($_GET['id'] ?? 0));

    if ($eventId <= 0) {
        send_json_response(400, false, "Valid event_id is required for deletion.");
    }

    // Fetch event before delete
    $fetchStmt = $pdo->prepare("SELECT title, poster_image FROM events WHERE event_id = ?");
    $fetchStmt->execute([$eventId]);
    $ev = $fetchStmt->fetch();

    if (!$ev) {
        send_json_response(404, false, "Event #$eventId not found.");
    }

    // Delete record
    $delStmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
    $delStmt->execute([$eventId]);

    // Remove poster file if in uploads
    if (!empty($ev['poster_image']) && strpos($ev['poster_image'], 'uploads/posters/') !== false) {
        $fullPath = __DIR__ . '/../../' . $ev['poster_image'];
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    if (function_exists('record_audit_log')) {
        record_audit_log($pdo, 'DELETE_API', 'events', $eventId, "Deleted via AJAX API: " . $ev['title']);
    }

    send_json_response(200, true, "Event #$eventId ('" . $ev['title'] . "') deleted successfully.", [
        'deleted_id' => $eventId
    ]);
}

// -------------------------------------------------------------
// METHOD NOT ALLOWED (405)
// -------------------------------------------------------------
else {
    send_json_response(405, false, "HTTP Method '$method' not allowed. Use GET, POST, PUT, or DELETE.");
}
