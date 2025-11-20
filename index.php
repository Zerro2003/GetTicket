<?php
require_once('config.php');
require_once('partials/header.php');

// Search and filter logic
$search_term = $_GET['search'] ?? '';
$filter_date = $_GET['date'] ?? '';
$filter_location = $_GET['location'] ?? '';

$sql = "SELECT e.*, u.username AS organizer_name 
        FROM events e 
        JOIN users u ON e.organizer_id = u.id 
        WHERE e.status = 'upcoming'";
$params = [];

if (!empty($search_term)) {
    $sql .= " AND (e.name LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

if (!empty($filter_date)) {
    $sql .= " AND DATE(e.date) = ?";
    $params[] = $filter_date;
}

if (!empty($filter_location)) {
    $sql .= " AND e.location = ?";
    $params[] = $filter_location;
}

$sql .= " ORDER BY e.date ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch categories for each event
    $categoryStmt = $pdo->prepare("SELECT id, name, total_tickets, available_tickets FROM event_ticket_categories WHERE event_id = ? ORDER BY FIELD(name, 'VVIP', 'VIP', 'Regular')");
    foreach ($events as &$event) {
        $categoryStmt->execute([$event['id']]);
        $event['categories'] = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($event);

    // Get distinct locations for filter dropdown
    $locations = $pdo->query("SELECT DISTINCT location FROM events WHERE location IS NOT NULL AND location != '' ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {
    echo "<p class='message error'>Could not fetch events.</p>";
    $events = [];
    $locations = [];
}
?>

<div class="container">
    <h2>Upcoming Events</h2>

    <!-- Search and Filter Form -->
    <form method="GET" action="index.php" class="filter-form">
        <div class="form-group">
            <input type="search" name="search" placeholder="Search by name, description..." value="<?php echo htmlspecialchars($search_term); ?>">
        </div>
        <div class="form-group">
            <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
        </div>
        <div class="form-group">
            <select name="location">
                <option value="">All Locations</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($filter_location === $loc) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($loc); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</a>
    </form>

    <div id="event-list">
        <?php if (empty($events)): ?>
            <div class="message info">
                <h4><i class="fas fa-info-circle"></i> No Events Found</h4>
                <p>No events match your current criteria. Try adjusting your filters or <a href="index.php">resetting the search</a>.</p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <div class="event-item">
                    <div class="event-item-content">
                        <h3><?php echo htmlspecialchars($event['name']); ?></h3>
                        <div class="event-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($event['organizer_name']); ?></span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y, g:i a', strtotime($event['date'])); ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars(substr($event['description'], 0, 100) . (strlen($event['description']) > 100 ? '...' : ''))); ?></p>
                        
                        <div class="progress-bar">
                            <?php
                            $percentage = ($event['total_tickets'] > 0) ? (($event['total_tickets'] - $event['available_tickets']) / $event['total_tickets']) * 100 : 0;
                            ?>
                            <div class="progress" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                        <p style="text-align:center; font-size: 0.9rem; margin-bottom: 1rem; color: var(--text-muted);">
                            <strong><?php echo $event['available_tickets']; ?></strong> tickets left
                        </p>

                        <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-block">
                            <i class="fas fa-ticket-alt"></i> View Details & Book
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once('partials/footer.php'); ?>
