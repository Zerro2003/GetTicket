<?php
require_once('../config.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Protect this page - only organizers are allowed
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'organizer') {
    header("location: ../login.php");
    exit;
}

$organizer_id = $_SESSION['id'];
$error = null;
$success = null;

// Default input values
$input = [
    'name' => '',
    'description' => '',
    'date' => '',
    'location' => '',
    'vvip_tickets' => 0,
    'vip_tickets' => 0,
    'regular_tickets' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve input
    $input['name'] = trim($_POST['name'] ?? '');
    $input['description'] = trim($_POST['description'] ?? '');
    $input['date'] = $_POST['date'] ?? '';
    $input['location'] = trim($_POST['location'] ?? '');
    
    $input['vvip_tickets'] = max(0, (int)($_POST['vvip_tickets'] ?? 0));
    $input['vip_tickets'] = max(0, (int)($_POST['vip_tickets'] ?? 0));
    $input['regular_tickets'] = max(0, (int)($_POST['regular_tickets'] ?? 0));

    $categories = [
        ['name' => 'VVIP', 'count' => $input['vvip_tickets']],
        ['name' => 'VIP', 'count' => $input['vip_tickets']],
        ['name' => 'Regular', 'count' => $input['regular_tickets']],
    ];

    $total_tickets = array_sum(array_column($categories, 'count'));

    if (empty($input['name']) || empty($input['date']) || empty($input['location'])) {
        $error = 'Event name, date, and location are required.';
    } elseif ($total_tickets <= 0) {
        $error = 'Please allocate tickets to at least one category.';
    } else {
        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO events (organizer_id, name, description, date, location, total_tickets, available_tickets) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$organizer_id, $input['name'], $input['description'], $input['date'], $input['location'], $total_tickets, $total_tickets]);
            $event_id = $pdo->lastInsertId();

            $categoryStmt = $pdo->prepare("INSERT INTO event_ticket_categories (event_id, name, total_tickets, available_tickets) VALUES (?, ?, ?, ?)");
            foreach ($categories as $category) {
                if ($category['count'] > 0) {
                    $categoryStmt->execute([$event_id, $category['name'], $category['count'], $category['count']]);
                }
            }

            $pdo->commit();
            
            // Set success message and clear form
            $success = "Event '<strong>" . htmlspecialchars($input['name']) . "</strong>' created successfully!";
            // Clear input array to reset the form
            foreach ($input as $key => $value) {
                $input[$key] = is_numeric($value) ? 0 : '';
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($e->getMessage());
            $error = 'Unable to create event. Please try again.';
        }
    }
}

// Fetch events for this organizer
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE organizer_id = ? ORDER BY date ASC");
    $stmt->execute([$organizer_id]);
    $all_events = $stmt->fetchAll();
} catch (Exception $e) {
    $all_events = [];
    $error = 'Could not fetch your events.';
}

// Separate next upcoming event from others
$next_event = null;
$other_events = [];
$current_time = time();

if (!empty($all_events)) {
    // First event in the sorted list (by date ASC) is the featured one
    $next_event = $all_events[0];
    // Rest go to other events
    $other_events = array_slice($all_events, 1);
}

require_once('../partials/header.php');
?>

<div class="admin-dashboard">
    <header class="dashboard-header">
        <h1>Organizer Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! Manage your events from here.</p>
    </header>

    <div class="dashboard-grid">
        <!-- Create Event Form -->
        <div class="card form-card">
            <div class="card-header">
                <h3>Create a New Event</h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="message success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form action="index.php" method="post" class="modern-form">
                    <div class="form-group">
                        <label for="name">Event Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($input['name']); ?>" placeholder="e.g., Summer Music Festival" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Event Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Tell attendees about your event..." required><?php echo htmlspecialchars($input['description']); ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($input['location']); ?>" placeholder="e.g., Central Park" required>
                        </div>
                        <div class="form-group">
                            <label for="date">Date and Time</label>
                            <input type="datetime-local" id="date" name="date" value="<?php echo htmlspecialchars($input['date']); ?>" required>
                        </div>
                    </div>

                    <fieldset>
                        <legend>Ticket Tiers</legend>
                        <div class="form-group">
                            <label for="vvip_tickets">VVIP Tickets</label>
                            <input type="number" id="vvip_tickets" name="vvip_tickets" min="0" value="<?php echo (int)$input['vvip_tickets']; ?>" placeholder="Number of VVIP tickets">
                        </div>
                        <div class="form-group">
                            <label for="vip_tickets">VIP Tickets</label>
                            <input type="number" id="vip_tickets" name="vip_tickets" min="0" value="<?php echo (int)$input['vip_tickets']; ?>" placeholder="Number of VIP tickets">
                        </div>
                        <div class="form-group">
                            <label for="regular_tickets">Regular Tickets</label>
                            <input type="number" id="regular_tickets" name="regular_tickets" min="0" value="<?php echo (int)$input['regular_tickets']; ?>" placeholder="Number of Regular tickets">
                        </div>
                    </fieldset>

                    <button type="submit" class="btn btn-primary btn-block">Create Event</button>
                </form>
            </div>
        </div>

        <!-- Next Event Featured Card -->
        <div class="featured-event-section">
            <?php if ($next_event): ?>
                <?php 
                $sold = $next_event['total_tickets'] - $next_event['available_tickets'];
                $percentage = ($next_event['total_tickets'] > 0) ? ($sold / $next_event['total_tickets']) * 100 : 0;
                $days_until = ceil((strtotime($next_event['date']) - time()) / (60 * 60 * 24));
                
                // Fetch ticket categories for this event
                $stmt = $pdo->prepare("SELECT name, total_tickets, available_tickets FROM event_ticket_categories WHERE event_id = ?");
                $stmt->execute([$next_event['id']]);
                $categories = $stmt->fetchAll();
                ?>
                <div class="featured-event-card">
                    <div class="featured-badge">NEXT EVENT</div>
                    <h2><?php echo htmlspecialchars($next_event['name']); ?></h2>
                    <div class="featured-event-details">
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-calendar-day"></i> Date</span>
                            <span class="detail-value"><?php echo date('F j, Y', strtotime($next_event['date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-clock"></i> Time</span>
                            <span class="detail-value"><?php echo date('g:i A', strtotime($next_event['date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-map-marker-alt"></i> Location</span>
                            <span class="detail-value"><?php echo htmlspecialchars($next_event['location']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-hourglass-half"></i> Countdown</span>
                            <span class="detail-value countdown"><?php echo $days_until; ?> days left</span>
                        </div>
                    </div>
                    <div class="featured-stats">
                        <div class="featured-stat">
                            <div class="stat-number"><?php echo $sold; ?></div>
                            <div class="stat-text">Sold</div>
                        </div>
                        <div class="featured-stat">
                            <div class="stat-number"><?php echo $next_event['available_tickets']; ?></div>
                            <div class="stat-text">Left</div>
                        </div>
                        <div class="featured-stat">
                            <div class="stat-number"><?php echo round($percentage); ?>%</div>
                            <div class="stat-text">Full</div>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $percentage . '%'; ?>;"></div>
                    </div>
                    
                    <?php if (!empty($categories)): ?>
                    <div class="category-breakdown">
                        <h4><i class="fas fa-chart-pie"></i> Ticket Sales</h4>
                        <div class="category-list">
                            <?php foreach ($categories as $category): ?>
                                <?php 
                                $cat_sold = $category['total_tickets'] - $category['available_tickets'];
                                $cat_percentage = ($category['total_tickets'] > 0) ? ($cat_sold / $category['total_tickets']) * 100 : 0;
                                ?>
                                <div class="category-item">
                                    <div class="category-header">
                                        <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                        <span class="category-count"><?php echo $cat_sold; ?> / <?php echo $category['total_tickets']; ?></span>
                                    </div>
                                    <div class="category-progress-bar">
                                        <div class="category-progress" style="width: <?php echo $cat_percentage . '%'; ?>;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="featured-actions">
                        <a href="../view_event.php?id=<?php echo $next_event['id']; ?>" class="btn btn-secondary"><i class="fas fa-eye"></i> View</a>
                        <a href="edit_event.php?id=<?php echo $next_event['id']; ?>" class="btn btn-secondary"><i class="fas fa-edit"></i> Edit</a>
                        <a href="delete_event.php?id=<?php echo $next_event['id']; ?>" class="btn btn-secondary" onclick="return confirm('Delete this event?');"><i class="fas fa-trash"></i> Delete</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="featured-event-card empty">
                    <i class="fas fa-calendar-plus" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>No Upcoming Events</h3>
                    <p>Create your first event using the form to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Other Events Section -->
    <?php if (!empty($other_events)): ?>
    <div class="other-events-section">
        <h3>Other Events</h3>
        <div class="admin-events-grid">
            <?php foreach ($other_events as $event): ?>
                <?php 
                $sold = $event['total_tickets'] - $event['available_tickets'];
                $percentage = ($event['total_tickets'] > 0) ? ($sold / $event['total_tickets']) * 100 : 0;
                $is_upcoming = strtotime($event['date']) > time();
                ?>
                <div class="admin-event-card">
                    <div class="event-card-header">
                        <h4><?php echo htmlspecialchars($event['name']); ?></h4>
                        <span class="event-status <?php echo $is_upcoming ? 'upcoming' : 'past'; ?>">
                            <i class="fas <?php echo $is_upcoming ? 'fa-clock' : 'fa-history'; ?>"></i> <?php echo $is_upcoming ? 'Upcoming' : 'Past'; ?>
                        </span>
                    </div>
                    <div class="event-card-info">
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></p>
                        <p><i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y, g:i A', strtotime($event['date'])); ?></p>
                    </div>
                    <div class="event-card-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $sold; ?></span>
                            <span class="stat-label">Sold</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $event['available_tickets']; ?></span>
                            <span class="stat-label">Left</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $event['total_tickets']; ?></span>
                            <span class="stat-label">Total</span>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $percentage; ?>%;"></div>
                    </div>
                    <div class="event-card-actions">
                        <a href="../view_event.php?id=<?php echo $event['id']; ?>" class="btn-icon" title="View"><i class="fas fa-eye"></i></a>
                        <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="delete_event.php?id=<?php echo $event['id']; ?>" class="btn-icon delete" title="Delete" onclick="return confirm('Delete this event?');"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once('../partials/footer.php'); ?>

