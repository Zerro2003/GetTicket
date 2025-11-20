<?php
require_once('config.php');
require_once('partials/header.php');

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$event = null;
$categories = [];

try {
    // Fetch event details along with organizer's name
    $stmt = $pdo->prepare("
        SELECT e.*, u.username AS organizer_name 
        FROM events e 
        JOIN users u ON e.organizer_id = u.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        // Use a message for better UX instead of a silent redirect
        echo "<div class='container'><div class='message error'>Event not found.</div></div>";
        require_once('partials/footer.php');
        exit;
    }

    // Fetch ticket categories
    $categoryStmt = $pdo->prepare("SELECT id, event_id, name, total_tickets, available_tickets FROM event_ticket_categories WHERE event_id = ? ORDER BY FIELD(name, 'VVIP', 'VIP', 'Regular')");
    $categoryStmt->execute([$id]);
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "<div class='container'><div class='message error'>Could not retrieve event details.</div></div>";
    require_once('partials/footer.php');
    exit;
}

$hasAvailableCategory = false;
foreach ($categories as $cat) {
    if ((int)$cat['available_tickets'] > 0) {
        $hasAvailableCategory = true;
        break;
    }
}
?>

<div class="container" style="margin-top: 2rem;">
    <div class="event-details-grid">
        <!-- Left Column: Event Information -->
        <div class="event-info glass-card">
            <h1 class="event-title" style="font-size: 2.5rem; margin-bottom: 1.5rem; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo htmlspecialchars($event['name']); ?></h1>
            
            <div class="event-meta-icons" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <small style="color: var(--text-light);">Organizer</small>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($event['organizer_name']); ?></div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <small style="color: var(--text-light);">Date & Time</small>
                        <div style="font-weight: 600;"><?php echo date('F j, Y, g:i a', strtotime($event['date'])); ?></div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <small style="color: var(--text-light);">Location</small>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($event['location']); ?></div>
                    </div>
                </div>
            </div>

            <div class="event-description">
                <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-info-circle" style="color: var(--primary-color);"></i> About this Event
                </h3>
                <p style="line-height: 1.8; color: var(--text-secondary);"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>

            <?php if (!empty($categories)): ?>
                <h3 style="margin-top: 2rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-ticket-alt" style="color: var(--primary-color);"></i> Ticket Tiers
                </h3>
                <div class="category-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                    <?php foreach ($categories as $category): ?>
                        <?php $soldOut = (int)$category['available_tickets'] === 0; ?>
                        <div class="category-card <?php echo $soldOut ? 'sold-out' : ''; ?>" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); padding: 1.5rem; border-radius: 16px; text-align: center; transition: transform 0.3s ease;">
                            <div class="category-card-header">
                                <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($category['name']); ?></h4>
                            </div>
                            <div class="category-card-body">
                                <p style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $category['available_tickets']; ?></p>
                                <small style="color: var(--text-light);">Available</small>
                            </div>
                            <div class="category-card-footer" style="margin-top: 1rem;">
                                <?php if ($soldOut): ?>
                                    <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Sold Out</span>
                                <?php else: ?>
                                    <span class="badge badge-success"><i class="fas fa-check-circle"></i> Available</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Booking Card -->
        <div class="event-booking-card glass-card" style="position: sticky; top: 2rem; height: fit-content;">
            <h3 style="margin-bottom: 1.5rem;">Book Your Spot</h3>
            <div class="progress-bar" style="height: 10px; background: rgba(255, 255, 255, 0.1); border-radius: 5px; overflow: hidden; margin-bottom: 1rem;">
                <?php
                $percentage = ($event['total_tickets'] > 0) ? (($event['total_tickets'] - $event['available_tickets']) / $event['total_tickets']) * 100 : 0;
                ?>
                <div class="progress" style="width: <?php echo $percentage; ?>%; height: 100%; background: var(--gradient-primary);"></div>
            </div>
            <p class="progress-text" style="margin-bottom: 2rem; text-align: center;">
                <strong style="color: var(--primary-color);"><?php echo $event['available_tickets']; ?></strong> tickets remaining
            </p>
            
            <hr style="border-color: rgba(255, 255, 255, 0.1); margin-bottom: 2rem;">
            
            <div id="reservation">
                <?php if (isset($_SESSION['loggedin'])): ?>
                    <?php if ($_SESSION['role'] === 'regular'): ?>
                        <?php if ($hasAvailableCategory): ?>
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="ticket-category" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Select Category</label>
                                <div style="position: relative;">
                                    <i class="fas fa-layer-group" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                                    <select id="ticket-category" class="form-control" style="width: 100%; padding: 0.8rem 1rem 0.8rem 2.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: var(--text-primary);">
                                        <option value="">-- Choose a category --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo ((int)$category['available_tickets'] === 0) ? 'disabled' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?> - <?php echo $category['available_tickets']; ?> left
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button id="reserve-btn" class="btn btn-primary btn-block" style="width: 100%; justify-content: center;">
                                <i class="fas fa-ticket-alt"></i> Reserve Ticket
                            </button>
                            <div id="countdown" style="display:none; margin-top: 1.5rem; padding: 1rem; background: rgba(16, 185, 129, 0.1); border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.2);">
                                <p style="margin-bottom: 0.5rem; text-align: center;"><i class="fas fa-clock"></i> Reserved for <strong id="timer" style="color: var(--success-color);">5:00</strong></p>
                                <p style="margin-bottom: 1rem; text-align: center;">Category: <strong id="selected-category-name">—</strong></p>
                                <a href="#" id="get-ticket-link" class="btn btn-success btn-block" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-check-circle"></i> Get Your Ticket
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="message info" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                All tickets for this event are sold out.
                            </div>
                        <?php endif; ?>
                    <?php else: // Organizer ?>
                        <div class="message info" style="text-align: center; padding: 2rem;">
                            <i class="fas fa-user-shield" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                            Organizers cannot reserve tickets. Please use a regular user account to book.
                        </div>
                    <?php endif; ?>
                <?php else: // Not logged in ?>
                    <div class="message info" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-lock" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                        <p style="margin-bottom: 1rem;">Please log in to book your ticket.</p>
                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <a href="/getTicket/login.php" class="btn btn-primary">Log in</a>
                            <a href="/getTicket/register.php" class="btn btn-secondary">Register</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once('partials/footer.php'); ?>

