<?php
require_once('config.php');

// Find reserved tickets that have expired (e.g., older than 5 minutes)
$five_minutes_ago = date('Y-m-d H:i:s', time() - 300);

$sql = "SELECT id, event_id, category_id FROM tickets WHERE status = 'reserved' AND reservation_time < ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$five_minutes_ago]);
$expired_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($expired_tickets as $ticket) {
    // Increment the available tickets count for the event
    $update_event_sql = "UPDATE events SET available_tickets = available_tickets + 1 WHERE id = ?";
    $update_stmt = $pdo->prepare($update_event_sql);
    $update_stmt->execute([$ticket['event_id']]);

    if (!empty($ticket['category_id'])) {
        $update_category_sql = "UPDATE event_ticket_categories SET available_tickets = available_tickets + 1 WHERE id = ?";
        $category_stmt = $pdo->prepare($update_category_sql);
        $category_stmt->execute([$ticket['category_id']]);
    }

    // Delete the expired ticket reservation
    $delete_ticket_sql = "DELETE FROM tickets WHERE id = ?";
    $delete_stmt = $pdo->prepare($delete_ticket_sql);
    $delete_stmt->execute([$ticket['id']]);

    echo "Released ticket ID: " . $ticket['id'] . " for event ID: " . $ticket['event_id'] . "\n";
}

echo "Expired reservations have been released.";
?>