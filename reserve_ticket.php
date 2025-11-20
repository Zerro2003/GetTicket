<?php
require_once('config.php');
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Check if user is logged in and is a regular user
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'regular') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a user to reserve a ticket.']);
    exit;
}

$user_id = $_SESSION['id'];
$event_id = $_POST['event_id'] ?? null;
$category_id = $_POST['category_id'] ?? null;

if (!$event_id || !$category_id) {
    echo json_encode(['success' => false, 'message' => 'Missing event or category ID.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Lock the category row to prevent race conditions
    $categoryStmt = $pdo->prepare("SELECT ec.id, ec.name, ec.available_tickets, e.available_tickets AS event_available
        FROM event_ticket_categories ec
        INNER JOIN events e ON ec.event_id = e.id
        WHERE ec.id = ? AND ec.event_id = ? FOR UPDATE");
    $categoryStmt->execute([$category_id, $event_id]);
    $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Invalid ticket category selected.']);
        exit;
    }

    if ((int)$category['available_tickets'] <= 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Sorry, this ticket category is now sold out.']);
        exit;
    }

    // Generate a unique ticket code and set reservation time
    $ticket_code = 'TICKET-' . strtoupper(uniqid());
    $reservation_time = date('Y-m-d H:i:s');

    // Insert the new ticket record, now linked to the user
    $insertSql = "INSERT INTO tickets (event_id, user_id, category_id, ticket_code, status, reservation_time)
                  VALUES (?, ?, ?, ?, 'reserved', ?)";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([$event_id, $user_id, $category_id, $ticket_code, $reservation_time]);
    $ticket_id = $pdo->lastInsertId();

    $updateCategory = $pdo->prepare("UPDATE event_ticket_categories SET available_tickets = available_tickets - 1 WHERE id = ?");
    $updateCategory->execute([$category_id]);

    $updateEvent = $pdo->prepare("UPDATE events SET available_tickets = available_tickets - 1 WHERE id = ?");
    $updateEvent->execute([$event_id]);

    $pdo->commit();

    $_SESSION['reserved_ticket_id'] = $ticket_id;
    $_SESSION['reservation_start_time'] = time();

    echo json_encode([
        'success' => true,
        'ticket_code' => $ticket_code,
        'category_name' => $category['name'],
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Unable to reserve ticket.']);
}
?>