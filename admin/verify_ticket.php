<?php
require_once('../config.php');

$heading = '';
$details = '';

if (!isset($_GET['code'])) {
    $heading = 'No ticket code provided.';
} else {
    $ticket_code = $_GET['code'];

    $stmt = $pdo->prepare("SELECT t.*, c.name AS category_name FROM tickets t LEFT JOIN event_ticket_categories c ON t.category_id = c.id WHERE ticket_code = ?");
    $stmt->execute([$ticket_code]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ticket) {
        if ($ticket['status'] === 'sold') {
            $sql = "UPDATE tickets SET status = 'used' WHERE id = ?";
            $update_stmt = $pdo->prepare($sql);
            $update_stmt->execute([$ticket['id']]);

            $heading = 'Ticket is valid and has been redeemed.';
            $details = 'Category: ' . ($ticket['category_name'] ?? 'General');
        } elseif ($ticket['status'] === 'used') {
            $heading = 'This ticket has already been used.';
        } else {
            $heading = 'Invalid ticket status.';
        }
    } else {
        $heading = 'Invalid ticket code.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Ticket</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { text-align: center; padding: 50px; }
        h1 { color: #0779e4; }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($heading); ?></h1>
    <?php if ($details): ?>
        <p><?php echo htmlspecialchars($details); ?></p>
    <?php endif; ?>
</body>
</html>