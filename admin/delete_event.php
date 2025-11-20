<?php
require_once('../config.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Protect this page
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'organizer') {
    header("location: ../login.php");
    exit;
}

$organizer_id = $_SESSION['id'];
$event_id = $_GET['id'] ?? null;

if ($event_id) {
    try {
        // Ensure the event belongs to the logged-in organizer before deleting
        $sql = "DELETE FROM events WHERE id = ? AND organizer_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$event_id, $organizer_id]);

        if ($stmt->rowCount() > 0) {
            header("location: index.php?status=deleted");
        } else {
            // No rows affected, meaning event didn't exist or didn't belong to user
            header("location: index.php?status=delete_failed");
        }
    } catch (Exception $e) {
        header("location: index.php?status=delete_failed");
    }
} else {
    header("location: index.php");
}
exit;
?>
