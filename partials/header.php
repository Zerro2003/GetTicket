<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fata Itike - Event Tickets</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/getTicket/css/style.css?v=1.2">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/getTicket/index.php">Fata Itike</a>
            <button class="navbar-toggler" id="navbar-toggler">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="navbar-collapse" id="navbar-collapse">
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        <?php if ($_SESSION['role'] === 'organizer'): ?>
                            <li><a href="/getTicket/admin/index.php" class="nav-link">Dashboard</a></li>
                        <?php else: ?>
                            <li><a href="/getTicket/index.php" class="nav-link">Events</a></li>
                            <li><a href="/getTicket/my_tickets.php" class="nav-link">My Tickets</a></li>
                        <?php endif; ?>
                        <li><a href="/getTicket/logout.php" class="nav-link btn btn-secondary">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/getTicket/login.php" class="nav-link">Login</a></li>
                        <li><a href="/getTicket/register.php" class="nav-link btn btn-primary">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container">
