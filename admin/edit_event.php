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
$error = '';
$success = '';
$event = null;

if (!$event_id) {
    header("location: index.php");
    exit;
}

// Fetch the event to ensure it belongs to the organizer
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
    $stmt->execute([$event_id, $organizer_id]);
    $event = $stmt->fetch();
} catch (Exception $e) {
    $error = "Error fetching event data.";
}

if (!$event) {
    // Event not found or doesn't belong to the organizer
    header("location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update logic
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $date = $_POST['date'];

    if (empty($name) || empty($location) || empty($date)) {
        $error = "Name, location, and date are required.";
    } else {
        try {
            $sql = "UPDATE events SET name = ?, description = ?, location = ?, date = ? WHERE id = ? AND organizer_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $description, $location, $date, $event_id, $organizer_id]);
            
            // Redirect with a success message
            header("location: index.php?status=updated");
            exit;

        } catch (Exception $e) {
            $error = "Failed to update event.";
        }
    }
    
    // If there was an error, repopulate the event array with POST data
    $event['name'] = $name;
    $event['description'] = $description;
    $event['location'] = $location;
    $event['date'] = $date;
}

require_once('../partials/header.php');
?>

<div class="admin-dashboard">
    <header class="dashboard-header" style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem;">Edit Event</h1>
        <p style="color: var(--text-secondary);">Make changes to your event details below. Ticket counts cannot be modified.</p>
    </header>

    <div class="glass-card" style="max-width: 800px; margin: 0 auto; padding: 2rem;">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="message error" style="margin-bottom: 1.5rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="edit_event.php?id=<?php echo $event_id; ?>" method="post" class="modern-form">
                <div class="form-group">
                    <label for="name"><i class="fas fa-heading"></i> Event Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($event['name']); ?>" required placeholder="Enter event name">
                </div>

                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Event Description</label>
                    <textarea id="description" name="description" rows="5" required placeholder="Describe your event..."><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label for="location"><i class="fas fa-map-marker-alt"></i> Event Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required placeholder="Venue or address">
                    </div>

                    <div class="form-group">
                        <label for="date"><i class="fas fa-calendar-alt"></i> Event Date and Time</label>
                        <input type="datetime-local" id="date" name="date" value="<?php echo date('Y-m-d\TH:i', strtotime($event['date'])); ?>" required>
                    </div>
                </div>

                <div class="message info" style="margin: 2rem 0; display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-info-circle" style="font-size: 1.5rem; color: var(--primary-color);"></i>
                    <span>Note: Ticket categories and counts are final after creation to ensure booking integrity.</span>
                </div>

                <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once('../partials/footer.php'); ?>

