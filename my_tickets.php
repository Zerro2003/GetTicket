<?php
require_once('config.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Protect this page - only regular users are allowed
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'regular') {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION['id'];
$tickets = [];
$error = '';

try {
    // Fetch all tickets/bookings for the current user
    $stmt = $pdo->prepare(
        "SELECT t.ticket_code, t.status, t.reservation_time, e.name AS event_name, e.date AS event_date, e.location AS event_location, c.name AS category_name
         FROM tickets t
         JOIN events e ON t.event_id = e.id
         JOIN event_ticket_categories c ON t.category_id = c.id
         WHERE t.user_id = ?
         ORDER BY t.reservation_time DESC"
    );
    $stmt->execute([$user_id]);
    $tickets = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Failed to retrieve your tickets.";
}

require_once('partials/header.php');
?>

<div class="container" style="margin-top: 2rem;">
    <header class="page-header" style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem;">My Tickets</h1>
        <p style="color: var(--text-secondary);">Here are all the events you've booked. Download your tickets before heading to the event!</p>
    </header>

    <?php if ($error): ?>
        <div class="message error" style="margin-bottom: 2rem;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($tickets)): ?>
        <div class="glass-card empty-state" style="text-align: center; padding: 4rem 2rem;">
            <i class="fas fa-ticket-alt" style="font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.5; color: var(--primary-color);"></i>
            <h4 style="font-size: 1.5rem; margin-bottom: 1rem;">No Tickets Yet</h4>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">You haven't booked any tickets yet.</p>
            <a href="index.php" class="btn btn-primary"><i class="fas fa-search"></i> Find an Event</a>
        </div>
    <?php else: ?>
        <div class="ticket-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
            <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-card glass-card" style="display: flex; flex-direction: column; height: 100%;">
                    <div class="ticket-card-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.25rem; color: var(--text-primary); margin: 0;"><?php echo htmlspecialchars($ticket['event_name']); ?></h3>
                        <?php 
                            $statusClass = 'badge-secondary';
                            $statusIcon = 'fa-question-circle';
                            if ($ticket['status'] === 'confirmed' || $ticket['status'] === 'booked') {
                                $statusClass = 'badge-success';
                                $statusIcon = 'fa-check-circle';
                            } elseif ($ticket['status'] === 'reserved') {
                                $statusClass = 'badge-warning';
                                $statusIcon = 'fa-clock';
                            } elseif ($ticket['status'] === 'expired' || $ticket['status'] === 'cancelled') {
                                $statusClass = 'badge-danger';
                                $statusIcon = 'fa-times-circle';
                            }
                        ?>
                        <span class="badge <?php echo $statusClass; ?>" style="font-size: 0.8rem;">
                            <i class="fas <?php echo $statusIcon; ?>"></i> <?php echo ucfirst($ticket['status']); ?>
                        </span>
                    </div>
                    
                    <div class="ticket-card-body" style="flex: 1; display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 32px; height: 32px; background: rgba(255, 255, 255, 0.05); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div>
                                <small style="color: var(--text-light); display: block;">Category</small>
                                <strong><?php echo htmlspecialchars($ticket['category_name']); ?></strong>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 32px; height: 32px; background: rgba(255, 255, 255, 0.05); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <small style="color: var(--text-light); display: block;">Date</small>
                                <strong><?php echo date('F j, Y, g:i a', strtotime($ticket['event_date'])); ?></strong>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 32px; height: 32px; background: rgba(255, 255, 255, 0.05); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <small style="color: var(--text-light); display: block;">Location</small>
                                <strong><?php echo htmlspecialchars($ticket['event_location']); ?></strong>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 32px; height: 32px; background: rgba(255, 255, 255, 0.05); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                                <i class="fas fa-history"></i>
                            </div>
                            <div>
                                <small style="color: var(--text-light); display: block;">Booked On</small>
                                <span><?php echo date('M j, Y, g:i A', strtotime($ticket['reservation_time'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ticket-card-footer" style="border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 1.5rem;">
                        <?php if ($ticket['status'] === 'reserved' || $ticket['status'] === 'booked' || $ticket['status'] === 'confirmed'): ?>
                            <a href="generate_ticket.php?code=<?php echo htmlspecialchars($ticket['ticket_code']); ?>" class="btn btn-primary btn-block" target="_blank" style="width: 100%; justify-content: center;">
                                <i class="fas fa-file-pdf"></i> Download Ticket
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-block" disabled style="width: 100%; justify-content: center; opacity: 0.5; cursor: not-allowed;">
                                <i class="fas fa-ban"></i> Ticket Unavailable
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once('partials/footer.php'); ?>

